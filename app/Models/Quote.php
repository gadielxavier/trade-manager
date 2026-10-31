<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticker_id',
        'currency',
        'regular_market_price',
        'regular_market_day_high',
        'regular_market_day_low',
        'regular_market_day_range',
        'regular_market_change',
        'regular_market_change_percent',
        'regular_market_time',
        'market_cap',
        'regular_market_volume',
        'regular_market_previous_close',
        'regular_market_open',
        'fifty_two_week_range',
        'fifty_two_week_low',
        'fifty_two_week_high',
    ];

    protected function casts(): array
    {
        return [
            'regular_market_price' => 'decimal:4',
            'regular_market_day_high' => 'decimal:4',
            'regular_market_day_low' => 'decimal:4',
            'regular_market_change' => 'decimal:4',
            'regular_market_change_percent' => 'decimal:4',
            'regular_market_time' => 'datetime',
            'market_cap' => 'decimal:2',
            'regular_market_volume' => 'integer',
            'regular_market_previous_close' => 'decimal:4',
            'regular_market_open' => 'decimal:4',
            'fifty_two_week_low' => 'decimal:4',
            'fifty_two_week_high' => 'decimal:4',
        ];
    }

    public function ticker(): BelongsTo
    {
        return $this->belongsTo(Ticker::class);
    }
}
