<?php

namespace App\Models;

class TieredFee extends BaseModel
{
    protected string $table = 'tiered_fees';
    protected array $fillable = [
        'fee_setting_id',
        'min_amount',
        'max_amount', 
        'percentage'
    ];

    protected array $casts = [
        'fee_setting_id' => 'int',
        'min_amount' => 'float',
        'max_amount' => 'float',
        'percentage' => 'float'
    ];
}