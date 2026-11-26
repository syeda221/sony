<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductSerial;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WarehouseStockSyncService
{
    /**
     * Reconcile warehouse_stocks (total_pieces & boxes quantity) with serials and batches.
     *
     * @return array<string, int>
     */
    public function syncAll(): array
    {
        return DB::transaction(function () {
            $stats = [
                'fixed_box_to_pieces' => 0,
                'synced_serial_rows' => 0,
                'reassigned_serials' => 0,
                'synced_batch_rows' => 0,
                'cleaned_draft_serials' => 0,
            ];

            // 0. Clean up phantom available serials created by unapproved draft purchases
            if (Schema::hasTable('product_serials') && Schema::hasTable('purchases')) {
                $draftItems = DB::table('purchase_items as pi')
                    ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
                    ->where('p.status_purchase', 'draft')
                    ->whereNotNull('pi.serials')
                    ->select('pi.product_id', 'pi.serials')
                    ->get();

                $approvedItems = DB::table('purchase_items as pi')
                    ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
                    ->where('p.status_purchase', '!=', 'draft')
                    ->whereNotNull('pi.serials')
                    ->select('pi.product_id', 'pi.serials')
                    ->get();

                $approvedSerialMap = [];
                foreach ($approvedItems as $ai) {
                    $list = json_decode($ai->serials, true);
                    if (is_array($list)) {
                        foreach ($list as $s) {
                            $approvedSerialMap[$ai->product_id . '|' . trim((string) $s)] = true;
                        }
                    }
                }

                foreach ($draftItems as $di) {
                    $list = json_decode($di->serials, true);
                    if (is_array($list)) {
                        foreach ($list as $s) {
                            $s = trim((string) $s);
                            if ($s !== '' && ! isset($approvedSerialMap[$di->product_id . '|' . $s])) {
                                $deleted = ProductSerial::where('product_id', $di->product_id)
                                    ->where('serial_number', $s)
                                    ->where('status', 'available')
                                    ->delete();
                                $stats['cleaned_draft_serials'] += $deleted;
                            }
                        }
                    }
                }
            }

            // 1. Normalize existing WarehouseStock rows (fix quantity > 0 with total_pieces == 0)
            $allStocks = WarehouseStock::with('product')->get();
            foreach ($allStocks as $ws) {
                $ppb = ($ws->product && $ws->product->pieces_per_box > 0)
                    ? (float) $ws->product->pieces_per_box
                    : 1.0;

                $pieces = (float) $ws->total_pieces;
                $boxes = (float) $ws->quantity;
                $changed = false;

                if ($pieces <= 0 && $boxes > 0) {
                    $pieces = round($boxes * $ppb, 4);
                    $ws->total_pieces = $pieces;
                    $changed = true;
                    $stats['fixed_box_to_pieces']++;
                }

                if ($pieces < 0) {
                    $pieces = 0;
                    $ws->total_pieces = 0;
                    $changed = true;
                }

                $expectedBoxes = (int) floor($pieces / $ppb);
                if ((int) $ws->quantity !== $expectedBoxes) {
                    $ws->quantity = $expectedBoxes;
                    $changed = true;
                }

                if ($changed) {
                    $ws->save();
                }
            }

            // 2. Reconcile Serial Products (ProductSerial available vs WarehouseStock)
            if (Schema::hasTable('product_serials')) {
                $serialProductIds = ProductSerial::where('status', 'available')
                    ->distinct()
                    ->pluck('product_id');

                foreach ($serialProductIds as $pid) {
                    $product = Product::find($pid);
                    if (! $product) {
                        continue;
                    }
                    $ppb = $product->pieces_per_box > 0 ? (float) $product->pieces_per_box : 1.0;

                    $serialCountsByWh = ProductSerial::where('product_id', $pid)
                        ->where('status', 'available')
                        ->selectRaw('warehouse_id, COUNT(*) as cnt')
                        ->groupBy('warehouse_id')
                        ->pluck('cnt', 'warehouse_id')
                        ->toArray();

                    $totalAvailableSerials = array_sum($serialCountsByWh);
                    $whStocks = WarehouseStock::where('product_id', $pid)->get()->keyBy('warehouse_id');
                    $totalWhPieces = (float) $whStocks->sum('total_pieces');

                    if ($totalWhPieces >= $totalAvailableSerials && $totalAvailableSerials > 0) {
                        // Totals match or warehouse stock covers all serials: align any stale warehouse_id on serials
                        $excessWarehouses = [];
                        $deficitWarehouses = [];

                        $allWhIds = array_unique(array_merge(array_keys($serialCountsByWh), $whStocks->keys()->toArray()));
                        foreach ($allWhIds as $whId) {
                            $sCnt = (int) ($serialCountsByWh[$whId] ?? 0);
                            $wPcs = (int) floor((float) ($whStocks[$whId]->total_pieces ?? 0));
                            if ($sCnt > $wPcs) {
                                $excessWarehouses[$whId] = $sCnt - $wPcs;
                            } elseif ($wPcs > $sCnt) {
                                $deficitWarehouses[$whId] = $wPcs - $sCnt;
                            }
                        }

                        foreach ($excessWarehouses as $fromWhId => $excessCount) {
                            foreach ($deficitWarehouses as $toWhId => &$deficitCount) {
                                if ($excessCount <= 0) {
                                    break;
                                }
                                if ($deficitCount <= 0) {
                                    continue;
                                }
                                $moveCount = min($excessCount, $deficitCount);
                                $idsToMove = ProductSerial::where('product_id', $pid)
                                    ->where('warehouse_id', $fromWhId)
                                    ->where('status', 'available')
                                    ->orderBy('id', 'asc')
                                    ->limit($moveCount)
                                    ->pluck('id');

                                if ($idsToMove->isNotEmpty()) {
                                    ProductSerial::whereIn('id', $idsToMove)->update(['warehouse_id' => $toWhId]);
                                    $stats['reassigned_serials'] += $idsToMove->count();
                                    $excessCount -= $idsToMove->count();
                                    $deficitCount -= $idsToMove->count();
                                }
                            }
                            unset($deficitCount);
                        }
                    } else {
                        // WarehouseStock is missing stock for available serials (e.g. initial stock saved as 0)
                        foreach ($serialCountsByWh as $whId => $sCnt) {
                            if (! $whId || $sCnt <= 0) {
                                continue;
                            }
                            $ws = WarehouseStock::firstOrNew([
                                'warehouse_id' => $whId,
                                'product_id' => $pid,
                            ]);
                            $current = (float) ($ws->total_pieces ?? 0);
                            if ($current < $sCnt) {
                                $ws->total_pieces = (float) $sCnt;
                                $ws->quantity = (int) floor($ws->total_pieces / $ppb);
                                if (empty($ws->remarks)) {
                                    $ws->remarks = 'Synced from available serials';
                                }
                                $ws->save();
                                $stats['synced_serial_rows']++;
                            }
                        }
                    }
                }
            }

            // 3. Reconcile Batch Products (ProductBatch qty > 0 vs WarehouseStock)
            if (Schema::hasTable('product_batches')) {
                $batchProductIds = ProductBatch::where('qty', '>', 0)
                    ->distinct()
                    ->pluck('product_id');

                foreach ($batchProductIds as $pid) {
                    $product = Product::find($pid);
                    if (! $product) {
                        continue;
                    }
                    $ppb = $product->pieces_per_box > 0 ? (float) $product->pieces_per_box : 1.0;

                    $batchSumsByWh = ProductBatch::where('product_id', $pid)
                        ->where('qty', '>', 0)
                        ->selectRaw('warehouse_id, SUM(qty) as total_qty')
                        ->groupBy('warehouse_id')
                        ->pluck('total_qty', 'warehouse_id')
                        ->toArray();

                    $totalBatchQty = array_sum(array_map('floatval', $batchSumsByWh));
                    $totalWhPieces = (float) WarehouseStock::where('product_id', $pid)->sum('total_pieces');

                    if ($totalWhPieces < $totalBatchQty) {
                        foreach ($batchSumsByWh as $whId => $bQty) {
                            $bQty = (float) $bQty;
                            if (! $whId || $bQty <= 0) {
                                continue;
                            }
                            $ws = WarehouseStock::firstOrNew([
                                'warehouse_id' => $whId,
                                'product_id' => $pid,
                            ]);
                            $current = (float) ($ws->total_pieces ?? 0);
                            if ($current < $bQty) {
                                $ws->total_pieces = $bQty;
                                $ws->quantity = (int) floor($bQty / $ppb);
                                if (empty($ws->remarks)) {
                                    $ws->remarks = 'Synced from active batches';
                                }
                                $ws->save();
                                $stats['synced_batch_rows']++;
                            }
                        }
                    }
                }
            }

            return $stats;
        });
    }
}
