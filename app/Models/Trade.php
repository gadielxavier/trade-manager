<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trade extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'ticker_id',
        'buy_price',
        'sell_price',
        'quantity',
        'date',
        'wallet_transaction_id',
        'profit',
        'return_percent',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'ticker_id' => 'integer',
            'buy_price' => 'decimal:4',
            'sell_price' => 'decimal:4',
            'quantity' => 'integer',
            'profit' => 'decimal:2',
            'date' => 'date',
            'return_percent' => 'decimal:4',
            'deleted_at' => 'datetime',
        ];
    }

    public function ticker(): BelongsTo
    {
        return $this->belongsTo(Ticker::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
