<?php

namespace App\Models;

class TieredFee extends BaseModel
{
    protected $table = 'tiered_fees';
    protected $fillable = [
        'fee_setting_id',
        'min_amount',
        'max_amount', 
        'percentage'
    ];

    protected $casts = [
        'fee_setting_id' => 'int',
        'min_amount' => 'float',
        'max_amount' => 'float',
        'percentage' => 'float'
    ];
}