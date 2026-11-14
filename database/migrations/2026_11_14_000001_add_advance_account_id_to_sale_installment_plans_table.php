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
        if (Schema::hasTable('sale_installment_plans')) {
            Schema::table('sale_installment_plans', function (Blueprint $table) {
                if (!Schema::hasColumn('sale_installment_plans', 'advance_account_id')) {
                    $table->unsignedBigInteger('advance_account_id')->nullable()->after('advance_amount');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sale_installment_plans')) {
            Schema::table('sale_installment_plans', function (Blueprint $table) {
                if (Schema::hasColumn('sale_installment_plans', 'advance_account_id')) {
                    $table->dropColumn('advance_account_id');
                }
            });
        }
    }
};
