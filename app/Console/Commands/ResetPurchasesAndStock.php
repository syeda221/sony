<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetPurchasesAndStock extends Command
{
    protected $signature = 'reset:purchases-stock';
    protected $description = 'Reset all purchases, purchase returns, and set stock to 0';

    public function handle()
    {
        $this->warn('Resetting Purchases, Purchase Returns, and Stock...');

        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        if (Schema::hasTable('purchase_items')) {
            DB::table('purchase_items')->truncate();
            $this->info('- purchase_items truncated.');
        }

        if (Schema::hasTable('purchases')) {
            DB::table('purchases')->truncate();
            $this->info('- purchases truncated.');
        }

        if (Schema::hasTable('purchase_return_items')) {
            DB::table('purchase_return_items')->truncate();
            $this->info('- purchase_return_items truncated.');
        }

        if (Schema::hasTable('purchase_returns')) {
            DB::table('purchase_returns')->truncate();
            $this->info('- purchase_returns truncated.');
        }

        if (Schema::hasTable('stock_movements')) {
            DB::table('stock_movements')->truncate();
            $this->info('- stock_movements truncated.');
        }

        if (Schema::hasTable('warehouse_stocks')) {
            DB::table('warehouse_stocks')->update(['total_pieces' => 0, 'quantity' => 0]);
            $this->info('- warehouse_stocks set to 0.');
        }

        if (Schema::hasTable('stocks')) {
            if (Schema::hasColumn('stocks', 'qty')) {
                DB::table('stocks')->update(['qty' => 0]);
            } elseif (Schema::hasColumn('stocks', 'quantity')) {
                DB::table('stocks')->update(['quantity' => 0]);
            }
            $this->info('- stocks set to 0.');
        }

        if (Schema::hasTable('product_serials')) {
            DB::table('product_serials')->truncate();
            $this->info('- product_serials truncated.');
        }

        if (Schema::hasTable('product_batches')) {
            DB::table('product_batches')->truncate();
            $this->info('- product_batches truncated.');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        $this->info('SUCCESS: All purchases deleted and stock has been successfully reset to 0!');
        return 0;
    }
}
