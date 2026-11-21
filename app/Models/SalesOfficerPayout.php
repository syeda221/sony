<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOfficerPayout extends Model
{
    use HasFactory;

    protected $table = 'sales_officer_payouts';

    protected $fillable = [
        'sales_officer_id',
        'payment_voucher_id',
        'voucher_no',
        'date',
        'amount',
        'account_id',
        'narration',
        'admin_or_user_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'date'   => 'date',
    ];

    public function salesOfficer()
    {
        return $this->belongsTo(SalesOfficer::class, 'sales_officer_id');
    }

    public function paymentVoucher()
    {
        return $this->belongsTo(PaymentVoucher::class, 'payment_voucher_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
