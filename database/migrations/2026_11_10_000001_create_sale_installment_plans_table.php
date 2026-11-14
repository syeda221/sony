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
        if (!Schema::hasTable('sale_installment_plans')) {
            Schema::create('sale_installment_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
                $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->decimal('advance_amount', 12, 2)->default(0);
                $table->unsignedBigInteger('advance_account_id')->nullable();
                $table->decimal('financed_amount', 12, 2)->default(0);
                $table->integer('duration_months')->default(1);
                $table->decimal('monthly_installment', 12, 2)->default(0);
                $table->date('start_date')->nullable();
                $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sale_installments')) {
            Schema::create('sale_installments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_id')->constrained('sale_installment_plans')->onDelete('cascade');
                $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
                $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
                $table->integer('installment_no')->default(1);
                $table->date('due_date');
                $table->decimal('amount', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->enum('status', ['pending', 'paid', 'partial', 'overdue'])->default('pending');
                $table->date('paid_date')->nullable();
                $table->string('payment_method')->nullable();
                $table->unsignedBigInteger('receipt_account_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_installments');
        Schema::dropIfExists('sale_installment_plans');
    }
};
