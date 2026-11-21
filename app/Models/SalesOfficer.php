<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOfficer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_urdu',
        'mobile',
        'commission_rate',
        'sale_commission_rate',
        'profit_commission_rate',
    ];

    protected $casts = [
        'commission_rate'        => 'float',
        'sale_commission_rate'   => 'float',
        'profit_commission_rate' => 'float',
    ];

    public function payouts()
    {
        return $this->hasMany(SalesOfficerPayout::class, 'sales_officer_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'sales_officer_id');
    }

    public function getCommissionEarnedAttribute(): float
    {
        return app(\App\Services\BalanceService::class)->getSalesOfficerCommissionEarned($this->id);
    }

    public function getCommissionPaidAttribute(): float
    {
        return app(\App\Services\BalanceService::class)->getSalesOfficerCommissionPaid($this->id);
    }

    public function getPayableBalanceAttribute(): float
    {
        return app(\App\Services\BalanceService::class)->getSalesOfficerBalance($this->id);
    }
}
