<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Wallet\DepositService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DepositServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_deposit_successfully(): void
    {
        $user = User::factory()->create();

        $transaction = app(DepositService::class)->execute(
            user: $user,
            amount: '1000.00',
            description: 'Aporte inicial',
        );

        $wallet = $user->wallet()->first();

        $this->assertNotNull($wallet);

        $this->assertSame('1000.00', $wallet->balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'deposit',
            'gross_amount' => '1000.00',
            'fees' => '0.00',
            'net_amount' => '1000.00',
            'description' => 'Aporte inicial',
        ]);

        $this->assertSame('deposit', $transaction->type);
    }

    public function test_it_accumulates_multiple_deposits(): void
    {
        $user = User::factory()->create();

        $service = app(DepositService::class);

        $service->execute($user, '1000.00');
        $service->execute($user, '500.00');

        $wallet = $user->wallet()->first();

        $this->assertSame('1500.00', $wallet->balance);

        $this->assertDatabaseCount('wallet_transactions', 2);
    }

    public function test_it_rejects_zero_deposit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $user = User::factory()->create();

        app(DepositService::class)->execute($user, '0.00');
    }

    public function test_it_rejects_negative_deposit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $user = User::factory()->create();

        app(DepositService::class)->execute($user, '-100.00');
    }

    public function test_it_rejects_invalid_monetary_values(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $user = User::factory()->create();

        app(DepositService::class)->execute($user, '10.999');
    }

    public function test_it_does_not_create_duplicate_wallets(): void
    {
        $user = User::factory()->create();

        $service = app(DepositService::class);

        $service->execute($user, '100.00');
        $service->execute($user, '200.00');

        $this->assertDatabaseCount('wallets', 1);

        $this->assertSame(
            '300.00',
            $user->wallet()->first()->balance
        );
    }

    public function test_it_uses_current_date_when_date_is_not_provided(): void
    {
        $user = User::factory()->create();

        $transaction = app(DepositService::class)->execute(
            user: $user,
            amount: '1000.00',
        );

        $this->assertSame(
            now()->toDateString(),
            $transaction->date->toDateString()
        );
    }

    public function test_it_uses_provided_historical_date(): void
    {
        $user = User::factory()->create();

        $historicalDate = Carbon::parse('2025-06-15');

        $transaction = app(DepositService::class)->execute(
            user: $user,
            amount: '1000.00',
            description: 'Aporte retroativo',
            date: $historicalDate,
        );

        $this->assertSame(
            '2025-06-15',
            $transaction->date->toDateString()
        );

        $this->assertDatabaseHas('wallet_transactions', [
            'id' => $transaction->id,
        ]);
    }
}
