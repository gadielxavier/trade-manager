<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Trade extends Model
{

    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'ticker',
        'buy_price',
        'sell_price',
        'quantity',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'buy_price' => 'decimal:4',
            'sell_price' => 'decimal:4',
            'quantity' => 'integer',
            'profit' => 'decimal:2',
            'date' => 'date',
            'return_percent' => 'decimal:4',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Trade $trade): void {
            $buy = (float) $trade->buy_price;
            $sell = (float) $trade->sell_price;
            $quantity = (int) $trade->quantity;

            $trade->ticker = strtoupper(trim($trade->ticker));

            $trade->profit = round(
                ($sell - $buy) * $quantity,
                2
            );

            $trade->return_percent = $buy > 0
                ? round((($sell - $buy) / $buy) * 100, 4)
                : 0;
        });
    }
}
