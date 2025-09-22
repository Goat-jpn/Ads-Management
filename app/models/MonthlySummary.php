<?php

namespace App\Models;

class MonthlySummary extends BaseModel
{
    protected string $table = 'monthly_summaries';
    protected array $fillable = [
        'client_id',
        'ad_account_id',
        'year_month',
        'total_cost',
        'total_reported_cost',
        'total_impressions',
        'total_clicks',
        'total_conversions',
        'average_ctr',
        'average_cpc',
        'average_cpa',
        'average_conversion_rate',
        'calculated_fee',
        'is_invoiced'
    ];

    protected array $casts = [
        'client_id' => 'int',
        'ad_account_id' => 'int',
        'total_cost' => 'float',
        'total_reported_cost' => 'float',
        'total_impressions' => 'int',
        'total_clicks' => 'int',
        'total_conversions' => 'int',
        'average_ctr' => 'float',
        'average_cpc' => 'float',
        'average_cpa' => 'float',
        'average_conversion_rate' => 'float',
        'calculated_fee' => 'float',
        'is_invoiced' => 'bool'
    ];
}