<?php

namespace Database\Factories;

use App\Models\Ticker;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'ticker_id' => null,
            'type' => 'deposit',
            'funding_source' => null,
            'quantity' => null,
            'unit_price' => null,
            'gross_amount' => '100.00',
            'fees' => '0.00',
            'net_amount' => '100.00',
            'date' => now()->toDateString(),
            'description' => 'Aporte na carteira',
        ];
    }
}
