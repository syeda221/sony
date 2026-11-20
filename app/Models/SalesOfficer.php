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
}
