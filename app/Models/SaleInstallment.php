<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleInstallment extends Model
{
    use HasFactory;

    protected $table = 'sale_installments';

    protected $fillable = [
        'plan_id',
        'sale_id',
        'customer_id',
        'installment_no',
        'due_date',
        'amount',
        'paid_amount',
        'status',
        'paid_date',
        'payment_method',
        'receipt_account_id',
        'notes',
    ];

    protected $casts = [
        'due_date'           => 'date',
        'paid_date'          => 'date',
        'amount'             => 'decimal:2',
        'paid_amount'        => 'decimal:2',
        'installment_no'     => 'integer',
        'receipt_account_id' => 'integer',
    ];

    public function plan()
    {
        return $this->belongsTo(SaleInstallmentPlan::class, 'plan_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function receiptAccount()
    {
        return $this->belongsTo(Account::class, 'receipt_account_id');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'paid' && $this->due_date && $this->due_date->isPast();
    }
}
