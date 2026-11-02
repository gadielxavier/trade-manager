<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletPosition extends Model
{
    protected $fillable = [
        'wallet_id',
        'ticker_id',
        'quantity',
        'average_price',
        'invested_amount',
    ];

    protected function casts(): array
    {
        return [
            'wallet_id' => 'integer',
            'ticker_id' => 'integer',
            'quantity' => 'integer',
            'average_price' => 'decimal:4',
            'invested_amount' => 'decimal:2',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function ticker(): BelongsTo
    {
        return $this->belongsTo(Ticker::class);
    }
}
