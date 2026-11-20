<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_officers', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_officers', 'sale_commission_rate')) {
                $table->decimal('sale_commission_rate', 5, 2)->default(0)->after('commission_rate');
            }
            if (!Schema::hasColumn('sales_officers', 'profit_commission_rate')) {
                $table->decimal('profit_commission_rate', 5, 2)->default(0)->after('sale_commission_rate');
            }
        });

        // Seed existing commission_rate into sale_commission_rate if present
        try {
            \Illuminate\Support\Facades\DB::statement("UPDATE sales_officers SET sale_commission_rate = commission_rate WHERE commission_rate > 0 AND (sale_commission_rate = 0 OR sale_commission_rate IS NULL)");
        } catch (\Throwable $e) {}

        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'sales_officer_sale_comm_rate')) {
                $table->decimal('sales_officer_sale_comm_rate', 5, 2)->nullable()->after('sales_officer_id');
            }
            if (!Schema::hasColumn('sales', 'sales_officer_profit_comm_rate')) {
                $table->decimal('sales_officer_profit_comm_rate', 5, 2)->nullable()->after('sales_officer_sale_comm_rate');
            }
            if (!Schema::hasColumn('sales', 'sales_officer_comm_amount')) {
                $table->decimal('sales_officer_comm_amount', 12, 2)->nullable()->after('sales_officer_profit_comm_rate');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_officers', function (Blueprint $table) {
            if (Schema::hasColumn('sales_officers', 'sale_commission_rate')) {
                $table->dropColumn('sale_commission_rate');
            }
            if (Schema::hasColumn('sales_officers', 'profit_commission_rate')) {
                $table->dropColumn('profit_commission_rate');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            $cols = ['sales_officer_sale_comm_rate', 'sales_officer_profit_comm_rate', 'sales_officer_comm_amount'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('sales', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
