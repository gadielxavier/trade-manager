<?php

namespace Tests\Feature;

use App\Models\Trade;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletPosition;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_wallet_dashboard_indicators(): void
    {
        $user = User::factory()->create();

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => '9000.00',
        ]);

        $ticker = \App\Models\Ticker::factory()->create();

        WalletPosition::query()->create([
            'wallet_id' => $wallet->id,
            'ticker_id' => $ticker->id,
            'quantity' => 50,
            'average_price' => '30.0000',
            'invested_amount' => '1500.00',
        ]);

        Trade::factory()->create([
            'user_id' => $user->id,
            'ticker_id' => $ticker->id,
            'buy_price' => '30.0000',
            'sell_price' => '35.0000',
            'quantity' => 100,
            'profit' => '500.00',
            'return_percent' => '16.6667',
        ]);

        WalletTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => 'deposit',
            'gross_amount' => '10000.00',
            'fees' => '0.00',
            'net_amount' => '10000.00',
        ]);

        $dashboard = app(WalletDashboardService::class)
            ->execute($user);

        $this->assertEquals('9000.00', $dashboard['balance']);
        $this->assertEquals(1500, $dashboard['invested_amount']);
        $this->assertEquals(500, $dashboard['realized_profit']);
        $this->assertEquals('10500.00', $dashboard['total_patrimony']);

        $this->assertEquals(1, $dashboard['assets_count']);
        $this->assertEquals(1, $dashboard['trades_count']);

        $this->assertCount(1, $dashboard['positions']);
        $this->assertCount(1, $dashboard['recent_transactions']);
    }

    public function test_it_returns_empty_dashboard_when_user_has_no_wallet(): void
    {
        $user = User::factory()->create();

        $dashboard = app(WalletDashboardService::class)
            ->execute($user);

        $this->assertNull($dashboard['wallet']);
        $this->assertEquals('0.00', $dashboard['balance']);
        $this->assertEquals('0.00', $dashboard['invested_amount']);
        $this->assertEquals('0.00', $dashboard['realized_profit']);
        $this->assertEquals('0.00', $dashboard['total_patrimony']);

        $this->assertEquals(0, $dashboard['assets_count']);
        $this->assertEquals(0, $dashboard['trades_count']);

        $this->assertCount(0, $dashboard['positions']);
        $this->assertCount(0, $dashboard['recent_transactions']);
    }

    public function test_it_does_not_include_other_users_trades(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => '1000.00',
        ]);

        $ticker = \App\Models\Ticker::factory()->create();

        Trade::factory()->create([
            'user_id' => $otherUser->id,
            'ticker_id' => $ticker->id,
            'profit' => '500.00',
        ]);

        $dashboard = app(WalletDashboardService::class)
            ->execute($user);

        $this->assertEquals('0', $dashboard['realized_profit']);
        $this->assertEquals(0, $dashboard['trades_count']);
    }

    public function test_it_sums_multiple_positions(): void
    {
        $user = User::factory()->create();

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => '5000.00',
        ]);

        $ticker1 = \App\Models\Ticker::factory()->create();
        $ticker2 = \App\Models\Ticker::factory()->create();

        WalletPosition::query()->create([
            'wallet_id' => $wallet->id,
            'ticker_id' => $ticker1->id,
            'quantity' => 100,
            'average_price' => '20.0000',
            'invested_amount' => '2000.00',
        ]);

        WalletPosition::query()->create([
            'wallet_id' => $wallet->id,
            'ticker_id' => $ticker2->id,
            'quantity' => 50,
            'average_price' => '30.0000',
            'invested_amount' => '1500.00',
        ]);

        $dashboard = app(WalletDashboardService::class)
            ->execute($user);

        $this->assertEquals(3500, $dashboard['invested_amount']);
        $this->assertEquals('8500.00', $dashboard['total_patrimony']);
        $this->assertEquals(2, $dashboard['assets_count']);
    }
}
