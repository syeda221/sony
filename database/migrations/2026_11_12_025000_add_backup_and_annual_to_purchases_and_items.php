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
        // 1. Add backup and annual columns to purchase_items
        Schema::table('purchase_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_items', 'backup_percent')) {
                $table->decimal('backup_percent', 8, 2)->default(0)->after('scheme_discount');
            }
            if (!Schema::hasColumn('purchase_items', 'backup_amount')) {
                $table->decimal('backup_amount', 15, 2)->default(0)->after('backup_percent');
            }
            if (!Schema::hasColumn('purchase_items', 'annual_percent')) {
                $table->decimal('annual_percent', 8, 2)->default(0)->after('backup_amount');
            }
            if (!Schema::hasColumn('purchase_items', 'annual_amount')) {
                $table->decimal('annual_amount', 15, 2)->default(0)->after('annual_percent');
            }
        });

        // 2. Add total_backup_amount and total_annual_amount to purchases
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'total_backup_amount')) {
                $table->decimal('total_backup_amount', 15, 2)->default(0)->after('due_amount');
            }
            if (!Schema::hasColumn('purchases', 'total_annual_amount')) {
                $table->decimal('total_annual_amount', 15, 2)->default(0)->after('total_backup_amount');
            }
        });

        // 3. Create vendor_incentive_ledgers table for multi-ledger tracking
        if (!Schema::hasTable('vendor_incentive_ledgers')) {
            Schema::create('vendor_incentive_ledgers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
                $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
                $table->enum('type', ['backup', 'annual'])->index();
                $table->enum('entry_type', ['claim', 'received', 'adjusted'])->default('claim');
                $table->date('date');
                $table->string('reference_no')->nullable();
                $table->decimal('debit', 15, 2)->default(0);  // Claims accrued (+)
                $table->decimal('credit', 15, 2)->default(0); // Paid / Settled by vendor (-)
                $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->foreignId('admin_or_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'annual_amount')) {
                $table->dropColumn(['backup_percent', 'backup_amount', 'annual_percent', 'annual_amount']);
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'total_annual_amount')) {
                $table->dropColumn(['total_backup_amount', 'total_annual_amount']);
            }
        });

        Schema::dropIfExists('vendor_incentive_ledgers');
    }
};
