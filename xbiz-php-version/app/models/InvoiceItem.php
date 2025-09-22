<?php

namespace App\Models;

class InvoiceItem extends BaseModel
{
    protected string $table = 'invoice_items';
    protected array $fillable = [
        'invoice_id',
        'ad_account_id',
        'platform',
        'description',
        'ad_cost',
        'fee_amount'
    ];

    protected array $casts = [
        'invoice_id' => 'int',
        'ad_account_id' => 'int',
        'ad_cost' => 'float',
        'fee_amount' => 'float'
    ];
}