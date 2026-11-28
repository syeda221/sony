<?php

namespace App\Console\Commands;

use App\Http\Controllers\PurchaseController;
use App\Models\Purchase;
use App\Models\Vendor;
use App\Models\VoucherMaster;
use App\Services\BalanceService;
use App\Services\TransactionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReconcileAccountingAndInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounting:reconcile {--dry-run : Preview changes without modifying the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill missing purchase journal vouchers and reconcile double-encoded serials & serial/batch cost prices';

    /**
     * Execute the console command.
     */
    public function handle(TransactionService $transactionService, BalanceService $balanceService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? 'Running in DRY-RUN mode...' : 'Reconciling accounting vouchers & inventory costs...');

        $vouchersCreated = 0;
        $serialJsonFixed = 0;
        $serialCostFixed = 0;
        $batchCostFixed = 0;

        $apId = $balanceService->getAccountsPayableId();

        // 1. Backfill missing Purchase Vouchers for approved/Returned/Partial purchases
        $purchases = Purchase::whereIn('status_purchase', ['approved', 'Returned', 'Partial'])
            ->orderBy('id')
            ->get();

        foreach ($purchases as $purchase) {
            $expectedAp = round((float) $purchase->net_amount + (float) ($purchase->total_scheme_amount ?? 0), 2);
            if ($expectedAp <= 0) {
                continue;
            }

            $existingVmIds = VoucherMaster::where('voucher_type', VoucherMaster::TYPE_JOURNAL)
                ->where('party_type', Vendor::class)
                ->where('party_id', $purchase->vendor_id)
                ->where('remarks', "Purchase Voucher #{$purchase->invoice_no}")
                ->pluck('id');

            $hasMatchingJe = false;
            if ($existingVmIds->isNotEmpty()) {
                $apCredit = (float) DB::table('journal_entries')
                    ->where('source_type', VoucherMaster::class)
                    ->whereIn('source_id', $existingVmIds)
                    ->where('account_id', $apId)
                    ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as net')
                    ->value('net');

                if (abs($apCredit - $expectedAp) <= 0.05) {
                    $hasMatchingJe = true;
                }
            }

            if (!$hasMatchingJe) {
                $this->line("Missing voucher for Purchase #{$purchase->id} ({$purchase->invoice_no}, Vendor #{$purchase->vendor_id}, AP={$expectedAp})");
                if (!$dryRun) {
                    $transactionService->createPurchaseVoucher($purchase);
                }
                $vouchersCreated++;
            }
        }

        // 2. Fix double-encoded purchase_items.serials and reconcile product_serials / product_batches cost_price
        $pItems = DB::table('purchase_items as pi')
            ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
            ->whereNull('p.deleted_at')
            ->whereIn('p.status_purchase', ['approved', 'Returned', 'Partial'])
            ->select('pi.*', 'p.invoice_no')
            ->orderBy('pi.id')
            ->get();

        $hasSerialsTable = Schema::hasTable('product_serials');
        $hasBatchesTable = Schema::hasTable('product_batches');

        foreach ($pItems as $pi) {
            $ppb = (float) ($pi->pieces_per_box > 0 ? $pi->pieces_per_box : 1);
            $unit = strtolower(trim($pi->unit ?? ''));
            $isPiece = in_array($unit, ['pcs', 'pc', 'piece']);
            $isCarton = in_array($unit, ['carton', 'ctn', 'box', 'bandal', 'bundal', 'bndl'])
                || (!$isPiece && in_array($pi->size_mode, ['by_cartons', 'by_bandal']));

            if ($isCarton) {
                if (isset($pi->boxes_qty) && ($pi->boxes_qty > 0 || $pi->loose_qty > 0)) {
                    $pieces = (((int) $pi->boxes_qty) * $ppb) + ((int) $pi->loose_qty);
                } else {
                    [$b, $l] = PurchaseController::parseCartonQty($pi->qty);
                    $pieces = ($b * $ppb) + $l;
                }
            } elseif (in_array($unit, ['gm', 'g'])) {
                $pieces = ((float) $pi->qty) / 1000.0;
            } else {
                $pieces = (float) $pi->qty;
            }

            $sList = null;
            $wasDoubleEncoded = false;
            if (!empty($pi->serials)) {
                $decoded = json_decode($pi->serials, true);
                if (is_string($decoded)) {
                    $decoded2 = json_decode($decoded, true);
                    if (is_array($decoded2)) {
                        $sList = $decoded2;
                        $wasDoubleEncoded = true;
                    }
                } elseif (is_array($decoded)) {
                    $sList = $decoded;
                }
            }

            if ($wasDoubleEncoded) {
                $this->line("Double-encoded serials JSON on purchase_item #{$pi->id} (Purchase #{$pi->purchase_id})");
                if (!$dryRun) {
                    DB::table('purchase_items')
                        ->where('id', $pi->id)
                        ->update(['serials' => json_encode(array_values($sList))]);
                }
                $serialJsonFixed++;
            }

            if ($pieces <= 0 && is_array($sList) && count($sList) > 0) {
                $pieces = count($sList);
            }

            $lineTotal = (float) ($pi->line_total ?? 0);
            $unitCost = ($pieces > 0 && $lineTotal > 0)
                ? round($lineTotal / $pieces, 2)
                : (float) $pi->price;

            if ($unitCost <= 0) {
                continue;
            }

            if ($hasSerialsTable && is_array($sList) && count($sList) > 0) {
                foreach ($sList as $sn) {
                    $cleanSn = trim((string) $sn);
                    if ($cleanSn === '') {
                        continue;
                    }
                    $serialRow = DB::table('product_serials')
                        ->where('product_id', $pi->product_id)
                        ->where('serial_number', $cleanSn)
                        ->first();

                    if ($serialRow && abs((float) $serialRow->cost_price - $unitCost) > 0.001) {
                        $this->line("Serial #{$serialRow->id} ({$cleanSn}, prod #{$pi->product_id}): cost_price {$serialRow->cost_price} -> {$unitCost}");
                        if (!$dryRun) {
                            DB::table('product_serials')
                                ->where('id', $serialRow->id)
                                ->update(['cost_price' => $unitCost, 'updated_at' => now()]);
                        }
                        $serialCostFixed++;
                    }
                }
            }

            if ($hasBatchesTable && !empty($pi->batch_id)) {
                $batchRow = DB::table('product_batches')->where('id', $pi->batch_id)->first();
                if ($batchRow && abs((float) $batchRow->cost_price - $unitCost) > 0.001) {
                    $this->line("Batch #{$batchRow->id} ({$batchRow->batch_no}, prod #{$pi->product_id}): cost_price {$batchRow->cost_price} -> {$unitCost}");
                    if (!$dryRun) {
                        DB::table('product_batches')
                            ->where('id', $batchRow->id)
                            ->update(['cost_price' => $unitCost, 'updated_at' => now()]);
                    }
                    $batchCostFixed++;
                }
            }
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Missing purchase vouchers created', $vouchersCreated],
                ['Double-encoded purchase item serials fixed', $serialJsonFixed],
                ['Serial cost_price rows corrected', $serialCostFixed],
                ['Batch cost_price rows corrected', $batchCostFixed],
            ]
        );

        return self::SUCCESS;
    }
}
