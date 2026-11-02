<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WalletTransaction extends Model
{

    use HasFactory;
    protected $fillable = [
        'wallet_id',
        'ticker_id',
        'type',
        'funding_source',
        'quantity',
        'unit_price',
        'gross_amount',
        'fees',
        'net_amount',
        'date',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'wallet_id' => 'integer',
            'ticker_id' => 'integer',
            'quantity' => 'integer',
            'unit_price' => 'decimal:4',
            'gross_amount' => 'decimal:2',
            'fees' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'date' => 'date',
            'metadata' => 'array',
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

    public function trade(): HasOne
    {
        return $this->hasOne(Trade::class);
    }
}
