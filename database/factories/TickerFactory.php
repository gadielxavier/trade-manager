<?php

namespace Database\Factories;

use App\Models\Ticker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticker>
 */
class TickerFactory extends Factory
{
    protected $model = Ticker::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('????'),
            'name' => fake()->company(),
        ];
    }
}
