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
        if (!Schema::hasTable('sales_officer_payouts')) {
            Schema::create('sales_officer_payouts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sales_officer_id')->index();
                $table->unsignedBigInteger('payment_voucher_id')->nullable()->index();
                $table->string('voucher_no')->nullable()->index();
                $table->date('date')->index();
                $table->decimal('amount', 15, 2)->default(0);
                $table->unsignedBigInteger('account_id')->nullable();
                $table->text('narration')->nullable();
                $table->unsignedBigInteger('admin_or_user_id')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_officer_payouts');
    }
};
