<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleInstallmentPlan extends Model
{
    use HasFactory;

    protected $table = 'sale_installment_plans';

    protected $fillable = [
        'sale_id',
        'customer_id',
        'total_amount',
        'advance_amount',
        'advance_account_id',
        'financed_amount',
        'duration_months',
        'monthly_installment',
        'start_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'total_amount'        => 'decimal:2',
        'advance_amount'      => 'decimal:2',
        'financed_amount'     => 'decimal:2',
        'monthly_installment' => 'decimal:2',
        'duration_months'     => 'integer',
        'start_date'          => 'date',
    ];

    public function advanceAccount()
    {
        return $this->belongsTo(Account::class, 'advance_account_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function installments()
    {
        return $this->hasMany(SaleInstallment::class, 'plan_id')->orderBy('installment_no', 'asc');
    }

    public function getPaidInstallmentsCountAttribute(): int
    {
        return $this->installments->where('status', 'paid')->count();
    }

    public function getRemainingInstallmentsCountAttribute(): int
    {
        return $this->installments->where('status', '!=', 'paid')->count();
    }

    public function getTotalPaidAmountAttribute(): float
    {
        return (float) $this->installments->sum('paid_amount') + (float) $this->advance_amount;
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->total_amount - $this->total_paid_amount);
    }

    public function getNextDueInstallmentAttribute()
    {
        return $this->installments
            ->where('status', '!=', 'paid')
            ->sortBy('due_date')
            ->first();
    }
}
