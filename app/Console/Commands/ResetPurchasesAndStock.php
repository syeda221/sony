<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetPurchasesAndStock extends Command
{
    protected $signature = 'reset:purchases-stock {--dry-run : Only preview what will be deleted/reset without modifying any data} {--preview : Alias for dry-run} {--force : Force execute without confirmation prompt}';
    protected $description = 'Preview or Reset all purchases, purchase returns, and set stock to 0';

    public function handle()
    {
        $isPreview = $this->option('dry-run') || $this->option('preview');

        $this->info('========================================================');
        $this->info('        PURCHASE & STOCK RESET PREVIEW                   ');
        $this->info('========================================================');

        // Gather counts safely
        $purchasesCount = Schema::hasTable('purchases') ? DB::table('purchases')->count() : 0;
        $purchaseItemsCount = Schema::hasTable('purchase_items') ? DB::table('purchase_items')->count() : 0;
        $returnsCount = Schema::hasTable('purchase_returns') ? DB::table('purchase_returns')->count() : 0;
        $returnItemsCount = Schema::hasTable('purchase_return_items') ? DB::table('purchase_return_items')->count() : 0;
        $stockMovementsCount = Schema::hasTable('stock_movements') ? DB::table('stock_movements')->count() : 0;
        
        $stockRowsCount = 0;
        $totalStockPieces = 0;
        if (Schema::hasTable('warehouse_stocks')) {
            $stockRowsCount = DB::table('warehouse_stocks')->where('total_pieces', '>', 0)->count();
            $totalStockPieces = DB::table('warehouse_stocks')->sum('total_pieces');
        }

        $serialsCount = Schema::hasTable('product_serials') ? DB::table('product_serials')->count() : 0;
        $batchesCount = Schema::hasTable('product_batches') ? DB::table('product_batches')->count() : 0;
        $incentivesCount = Schema::hasTable('vendor_incentive_ledgers') ? DB::table('vendor_incentive_ledgers')->count() : 0;
        $vendorLedgerCount = Schema::hasTable('vendor_ledgers') ? DB::table('vendor_ledgers')->count() : 0;

        $tableData = [
            ['Purchases Invoices', $purchasesCount, 'Will be completely DELETED (Truncated)'],
            ['Purchase Items (Lines)', $purchaseItemsCount, 'Will be completely DELETED (Truncated)'],
            ['Purchase Returns', $returnsCount, 'Will be completely DELETED (Truncated)'],
            ['Purchase Return Items', $returnItemsCount, 'Will be completely DELETED (Truncated)'],
            ['Stock Movement Logs', $stockMovementsCount, 'Will be completely DELETED (Truncated)'],
            ['Product Serials / IMEIs', $serialsCount, 'Will be completely DELETED (Truncated)'],
            ['Product Batches', $batchesCount, 'Will be completely DELETED (Truncated)'],
            ['Backup & Annual Ledger (vendor_incentive_ledgers)', $incentivesCount, 'Will be completely DELETED (Truncated)'],
            ['Vendor Balance Ledgers (vendor_ledgers)', $vendorLedgerCount, 'Will be completely DELETED (Truncated)'],
            ['Warehouse Stock Rows (>0)', $stockRowsCount, 'Total ' . number_format($totalStockPieces, 2) . ' pcs will become 0.00'],
        ];

        $this->table(['Record / Table', 'Current Count', 'Action on Reset'], $tableData);

        // Detailed purchases preview if any exist
        if ($purchasesCount > 0 && Schema::hasTable('purchases')) {
            $recentPurchases = DB::table('purchases')
                ->leftJoin('vendors', 'vendors.id', '=', 'purchases.vendor_id')
                ->select('purchases.id', 'purchases.purchase_invoice_no', 'purchases.invoice_date', 'purchases.status_purchase', 'purchases.total_net', 'vendors.name as vendor_name')
                ->latest('purchases.id')
                ->take(5)
                ->get();

            $this->line('');
            $this->info('--- Recent Purchases that will be deleted (Top 5): ---');
            $pData = $recentPurchases->map(function($p) {
                return [
                    'ID' => $p->id,
                    'Invoice No' => $p->purchase_invoice_no ?? 'N/A',
                    'Date' => $p->invoice_date ?? 'N/A',
                    'Vendor' => $p->vendor_name ?? 'N/A',
                    'Status' => $p->status_purchase ?? 'N/A',
                    'Total Net' => 'Rs ' . number_format((float)($p->total_net ?? 0), 2),
                ];
            })->toArray();
            $this->table(['ID', 'Invoice No', 'Date', 'Vendor', 'Status', 'Total Net'], $pData);
        }

        // Detailed stock preview if any exist
        if (Schema::hasTable('warehouse_stocks')) {
            $activeStocks = DB::table('warehouse_stocks')
                ->join('products', 'products.id', '=', 'warehouse_stocks.product_id')
                ->leftJoin('warehouses', 'warehouses.id', '=', 'warehouse_stocks.warehouse_id')
                ->where('warehouse_stocks.total_pieces', '>', 0)
                ->select('products.item_code', 'products.item_name', 'warehouses.warehouse_name', 'warehouse_stocks.total_pieces')
                ->take(5)
                ->get();

            if ($activeStocks->count() > 0) {
                $this->line('');
                $this->info('--- Current Active Stocks to be reset to 0 (Top 5): ---');
                $sData = $activeStocks->map(function($s) {
                    return [
                        'Item Code' => $s->item_code,
                        'Product' => $s->item_name,
                        'Warehouse' => $s->warehouse_name ?? 'N/A',
                        'Current Pieces' => $s->total_pieces,
                        'After Reset' => '0 Pcs',
                    ];
                })->toArray();
                $this->table(['Item Code', 'Product', 'Warehouse', 'Current Pieces', 'After Reset'], $sData);
            }
        }

        if ($isPreview) {
            $this->line('');
            $this->warn('*** PREVIEW MODE ONLY: No data was modified or deleted. ***');
            $this->comment('To actually perform the reset, run: php artisan reset:purchases-stock');
            return 0;
        }

        // Confirmation prompt
        if (!$this->option('force')) {
            $this->line('');
            if (!$this->confirm('WARNING: Are you sure you want to permanently delete all purchases and reset stock to 0?', false)) {
                $this->warn('Operation cancelled by user. No data was changed.');
                return 0;
            }
        }

        $this->line('');
        $this->warn('Executing Reset...');

        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        if (Schema::hasTable('purchase_items')) {
            DB::table('purchase_items')->truncate();
            $this->info('✔ purchase_items truncated.');
        }

        if (Schema::hasTable('purchases')) {
            DB::table('purchases')->truncate();
            $this->info('✔ purchases truncated.');
        }

        if (Schema::hasTable('purchase_return_items')) {
            DB::table('purchase_return_items')->truncate();
            $this->info('✔ purchase_return_items truncated.');
        }

        if (Schema::hasTable('purchase_returns')) {
            DB::table('purchase_returns')->truncate();
            $this->info('✔ purchase_returns truncated.');
        }

        if (Schema::hasTable('stock_movements')) {
            DB::table('stock_movements')->truncate();
            $this->info('✔ stock_movements truncated.');
        }

        if (Schema::hasTable('warehouse_stocks')) {
            DB::table('warehouse_stocks')->update(['total_pieces' => 0, 'quantity' => 0]);
            $this->info('✔ warehouse_stocks set to 0.');
        }

        if (Schema::hasTable('stocks')) {
            if (Schema::hasColumn('stocks', 'qty')) {
                DB::table('stocks')->update(['qty' => 0]);
            } elseif (Schema::hasColumn('stocks', 'quantity')) {
                DB::table('stocks')->update(['quantity' => 0]);
            }
            $this->info('✔ stocks set to 0.');
        }

        if (Schema::hasTable('product_serials')) {
            DB::table('product_serials')->truncate();
            $this->info('✔ product_serials truncated.');
        }

        if (Schema::hasTable('product_batches')) {
            DB::table('product_batches')->truncate();
            $this->info('✔ product_batches truncated.');
        }

        if (Schema::hasTable('vendor_incentive_ledgers')) {
            DB::table('vendor_incentive_ledgers')->truncate();
            $this->info('✔ vendor_incentive_ledgers (Backup & Annual Ledgers) truncated.');
        }

        if (Schema::hasTable('vendor_ledgers')) {
            DB::table('vendor_ledgers')->truncate();
            $this->info('✔ vendor_ledgers (Vendor Balance Ledgers) truncated.');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        $this->line('');
        $this->info('========================================================');
        $this->info('SUCCESS: All purchases deleted & stock successfully reset to 0!');
        $this->info('========================================================');

        return 0;
    }
}
