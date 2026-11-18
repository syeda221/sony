<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add scheme_percent to purchase_items
        Schema::table('purchase_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_items', 'scheme_percent')) {
                $table->decimal('scheme_percent', 8, 2)->default(0)->after('item_discount');
            }
        });

        // 2. Add total_scheme_amount to purchases
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'total_scheme_amount')) {
                $table->decimal('total_scheme_amount', 15, 2)->default(0)->after('total_annual_amount');
            }
        });

        // 3. Update vendor_incentive_ledgers type column to include 'scheme'
        if (Schema::hasTable('vendor_incentive_ledgers')) {
            try {
                DB::statement("ALTER TABLE vendor_incentive_ledgers MODIFY COLUMN `type` ENUM('backup', 'annual', 'scheme') NOT NULL");
            } catch (\Exception $e) {
                // If already varchar or another type, ignore or fallback
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'scheme_percent')) {
                $table->dropColumn('scheme_percent');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'total_scheme_amount')) {
                $table->dropColumn('total_scheme_amount');
            }
        });

        if (Schema::hasTable('vendor_incentive_ledgers')) {
            try {
                DB::statement("ALTER TABLE vendor_incentive_ledgers MODIFY COLUMN `type` ENUM('backup', 'annual') NOT NULL");
            } catch (\Exception $e) {
            }
        }
    }
};
