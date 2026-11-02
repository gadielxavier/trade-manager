<?php

namespace Database\Factories;

use App\Models\Ticker;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trade>
 */
class TradeFactory extends Factory
{
    protected $model = Trade::class;

    public function definition(): array
    {
        $buyPrice = fake()->randomFloat(4, 5, 100);
        $sellPrice = fake()->randomFloat(4, 5, 120);
        $quantity = fake()->numberBetween(1, 100);

        $profit = ($sellPrice - $buyPrice) * $quantity;

        return [
            'user_id' => User::factory(),
            'ticker_id' => Ticker::factory(),
            'buy_price' => $buyPrice,
            'sell_price' => $sellPrice,
            'quantity' => $quantity,
            'profit' => $profit,
            'return_percent' => $buyPrice > 0
                ? (($sellPrice - $buyPrice) / $buyPrice) * 100
                : 0,
            'date' => fake()->date(),
            'wallet_transaction_id' => null,
        ];
    }
}
