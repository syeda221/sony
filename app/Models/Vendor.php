<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'cnic',
        'ntn_number',
        'strn_number',
        'address',
        'party_type',
        'linked_customer_id',
        'opening_balance',
        'province',
        'city',
        'area'
    ];

    public function linkedCustomer()
    {
        return $this->belongsTo(Customer::class, 'linked_customer_id');
    }

    public function ledger()
    {
        return $this->hasOne(VendorLedger::class);
    }

    public function incentiveLedgers()
    {
        return $this->hasMany(VendorIncentiveLedger::class, 'vendor_id');
    }

    public function backupLedgers()
    {
        return $this->hasMany(VendorIncentiveLedger::class, 'vendor_id')->where('type', 'backup');
    }

    public function annualLedgers()
    {
        return $this->hasMany(VendorIncentiveLedger::class, 'vendor_id')->where('type', 'annual');
    }
}
