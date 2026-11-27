<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductSerial;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WarehouseStockSyncService
{
    /**
     * Reconcile warehouse_stocks (total_pieces & boxes quantity) with serials, batches, and movements.
     *
     * @return array<string, int>
     */
    public function syncAll(): array
    {
        return DB::transaction(function () {
            $stats = [
                'fixed_box_to_pieces' => 0,
                'synced_serial_rows' => 0,
                'synced_batch_rows' => 0,
                'synced_simple_rows' => 0,
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

            // Get all warehouses
            $warehouses = Warehouse::all();
            if ($warehouses->isEmpty()) {
                return $stats;
            }

            $firstWarehouseId = $warehouses->first()->id;
            $allWarehouseIds = $warehouses->pluck('id')->toArray();

            // 1. Reconcile Serial Products (Exact count of available serials per warehouse)
            if (Schema::hasTable('product_serials')) {
                // Ensure no available serial has null or invalid warehouse_id
                ProductSerial::where('status', 'available')
                    ->where(function ($q) use ($allWarehouseIds) {
                        $q->whereNull('warehouse_id')
                          ->orWhereNotIn('warehouse_id', $allWarehouseIds);
                    })
                    ->update(['warehouse_id' => $firstWarehouseId]);

                $serialProductIds = Product::where('tracking_type', 'serial')
                    ->orWhereIn('id', ProductSerial::select('product_id'))
                    ->distinct()
                    ->pluck('id');

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

                    foreach ($warehouses as $wh) {
                        $sCnt = (float) ($serialCountsByWh[$wh->id] ?? 0);
                        $ws = WarehouseStock::firstOrNew([
                            'warehouse_id' => $wh->id,
                            'product_id' => $pid,
                        ]);

                        $ws->total_pieces = $sCnt;
                        $ws->quantity = (int) floor($sCnt / $ppb);
                        if (empty($ws->remarks)) {
                            $ws->remarks = 'Synced with available serials';
                        }
                        $ws->save();
                        $stats['synced_serial_rows']++;
                    }
                }
            }

            // 2. Reconcile Batch Products (Exact sum of active batch qty per warehouse)
            if (Schema::hasTable('product_batches')) {
                $batchProductIds = Product::where('tracking_type', 'batch')
                    ->orWhereIn('id', ProductBatch::select('product_id'))
                    ->distinct()
                    ->pluck('id');

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

                    foreach ($warehouses as $wh) {
                        $bQty = (float) ($batchSumsByWh[$wh->id] ?? 0);
                        $ws = WarehouseStock::firstOrNew([
                            'warehouse_id' => $wh->id,
                            'product_id' => $pid,
                        ]);

                        $ws->total_pieces = $bQty;
                        $ws->quantity = (int) floor($bQty / $ppb);
                        if (empty($ws->remarks)) {
                            $ws->remarks = 'Synced with active batches';
                        }
                        $ws->save();
                        $stats['synced_batch_rows']++;
                    }
                }
            }

            // 3. Normalize all existing WarehouseStock rows (fix quantity > 0 with total_pieces == 0)
            $allStocks = WarehouseStock::with('product')->get();
            foreach ($allStocks as $ws) {
                if (! $ws->product) {
                    continue;
                }

                // If serial or batch, already handled above
                if (in_array($ws->product->tracking_type, ['serial', 'batch'])) {
                    continue;
                }

                $ppb = $ws->product->pieces_per_box > 0 ? (float) $ws->product->pieces_per_box : 1.0;
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
                    $stats['synced_simple_rows']++;
                }
            }

            return $stats;
        });
    }

    /**
     * Reconcile warehouse_stocks for a single product across all warehouses.
     */
    public function syncProductStock(int|Product $product): void
    {
        $p = is_numeric($product) ? Product::find($product) : $product;
        if (! $p) {
            return;
        }

        $warehouses = Warehouse::all();
        if ($warehouses->isEmpty()) {
            return;
        }

        $ppb = $p->pieces_per_box > 0 ? (float) $p->pieces_per_box : 1.0;

        if ($p->tracking_type === 'serial') {
            $serialCountsByWh = ProductSerial::where('product_id', $p->id)
                ->where('status', 'available')
                ->selectRaw('warehouse_id, COUNT(*) as cnt')
                ->groupBy('warehouse_id')
                ->pluck('cnt', 'warehouse_id')
                ->toArray();

            foreach ($warehouses as $wh) {
                $sCnt = (float) ($serialCountsByWh[$wh->id] ?? 0);
                $ws = WarehouseStock::firstOrNew([
                    'warehouse_id' => $wh->id,
                    'product_id' => $p->id,
                ]);
                $ws->total_pieces = $sCnt;
                $ws->quantity = (int) floor($sCnt / $ppb);
                $ws->save();
            }
        } elseif ($p->tracking_type === 'batch') {
            $batchSumsByWh = ProductBatch::where('product_id', $p->id)
                ->where('qty', '>', 0)
                ->selectRaw('warehouse_id, SUM(qty) as total_qty')
                ->groupBy('warehouse_id')
                ->pluck('total_qty', 'warehouse_id')
                ->toArray();

            foreach ($warehouses as $wh) {
                $bQty = (float) ($batchSumsByWh[$wh->id] ?? 0);
                $ws = WarehouseStock::firstOrNew([
                    'warehouse_id' => $wh->id,
                    'product_id' => $p->id,
                ]);
                $ws->total_pieces = $bQty;
                $ws->quantity = (int) floor($bQty / $ppb);
                $ws->save();
            }
        } else {
            // Simple product: ensure each WarehouseStock is valid
            foreach ($warehouses as $wh) {
                $ws = WarehouseStock::where('warehouse_id', $wh->id)
                    ->where('product_id', $p->id)
                    ->first();

                if ($ws) {
                    $pieces = (float) $ws->total_pieces;
                    $boxes = (float) $ws->quantity;
                    if ($pieces <= 0 && $boxes > 0) {
                        $ws->total_pieces = round($boxes * $ppb, 4);
                        $ws->quantity = (int) floor($ws->total_pieces / $ppb);
                        $ws->save();
                    } elseif ($pieces > 0) {
                        $ws->quantity = (int) floor($pieces / $ppb);
                        $ws->save();
                    }
                }
            }
        }
    }
}
