<?php

namespace Tests\Feature;

use App\Models\Ticker;
use App\Models\User;
use App\Models\WalletPosition;
use App\Services\Wallet\DepositService;
use App\Services\Wallet\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class PurchaseServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithBalance(
        string $balance = '10000.00'
    ): User {
        $user = User::factory()->create();

        app(DepositService::class)->execute(
            user: $user,
            amount: $balance,
        );

        return $user;
    }

    private function createTicker(): Ticker
    {
        return Ticker::factory()->create();
    }

    public function test_it_creates_a_purchase_successfully(): void
    {
        $user = $this->createUserWithBalance();

        $ticker = $this->createTicker();

        $transaction = app(PurchaseService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 100,
            unitPrice: '30.0000',
            fundingSource: 'deposit',
            description: 'Compra de PETR4',
        );

        $wallet = $user->wallet()->first();

        $this->assertSame('7000.00', $wallet->balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'ticker_id' => $ticker->id,
            'type' => 'buy',
            'funding_source' => 'deposit',
            'quantity' => 100,
            'unit_price' => '30.0000',
            'gross_amount' => '3000.00',
            'fees' => '0.00',
            'net_amount' => '3000.00',
            'description' => 'Compra de PETR4',
        ]);

        $this->assertSame('buy', $transaction->type);
    }

    public function test_it_creates_a_purchase_using_cash_source(): void
    {
        $user = $this->createUserWithBalance();

        $ticker = $this->createTicker();

        $transaction = app(PurchaseService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 50,
            unitPrice: '30.0000',
            fundingSource: 'cash',
        );

        $wallet = $user->wallet()->first();

        // Ambas as origens debitam o caixa da carteira.
        $this->assertSame('8500.00', $wallet->balance);

        $this->assertSame('cash', $transaction->funding_source);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'ticker_id' => $ticker->id,
            'type' => 'buy',
            'funding_source' => 'cash',
            'gross_amount' => '1500.00',
            'net_amount' => '1500.00',
        ]);
    }

    public function test_it_updates_position_after_multiple_purchases(): void
    {
        $user = $this->createUserWithBalance();

        $ticker = $this->createTicker();

        $service = app(PurchaseService::class);

        $service->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 10,
            unitPrice: '30.0000',
            fundingSource: 'deposit',
        );

        $service->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 10,
            unitPrice: '40.0000',
            fundingSource: 'deposit',
        );

        $position = WalletPosition::query()
            ->where('wallet_id', $user->wallet()->first()->id)
            ->where('ticker_id', $ticker->id)
            ->firstOrFail();

        $this->assertSame(20, $position->quantity);

        $this->assertSame('35.0000', $position->average_price);

        $this->assertSame('700.00', $position->invested_amount);

        $this->assertSame(
            '9300.00',
            $user->wallet()->first()->balance
        );
    }

    public function test_it_creates_only_one_position_per_ticker(): void
    {
        $user = $this->createUserWithBalance();

        $ticker = $this->createTicker();

        $service = app(PurchaseService::class);

        $service->execute(
            $user,
            $ticker->id,
            10,
            '30.0000',
            'deposit',
        );

        $service->execute(
            $user,
            $ticker->id,
            10,
            '40.0000',
            'deposit',
        );

        $this->assertDatabaseCount('wallet_positions', 1);
    }

    public function test_it_rejects_purchase_with_insufficient_balance(): void
    {
        $user = $this->createUserWithBalance('1000.00');

        $ticker = $this->createTicker();

        try {
            app(PurchaseService::class)->execute(
                user: $user,
                tickerId: $ticker->id,
                quantity: 100,
                unitPrice: '30.0000',
                fundingSource: 'deposit',
            );

            $this->fail('A compra deveria ter sido rejeitada.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Saldo insuficiente para realizar a compra.',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            '1000.00',
            $user->wallet()->first()->balance
        );

        $this->assertDatabaseCount('wallet_positions', 0);

        $this->assertDatabaseCount('wallet_transactions', 1);
    }

    public function test_it_rejects_zero_quantity(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(PurchaseService::class)->execute(
            user: $user,
            tickerId: 1,
            quantity: 0,
            unitPrice: '30.0000',
            fundingSource: 'deposit',
        );
    }

    public function test_it_rejects_negative_quantity(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(PurchaseService::class)->execute(
            user: $user,
            tickerId: 1,
            quantity: -10,
            unitPrice: '30.0000',
            fundingSource: 'deposit',
        );
    }

    public function test_it_rejects_zero_price(): void
    {
        $user = User::factory()->create();

        $ticker = $this->createTicker();

        $this->expectException(InvalidArgumentException::class);

        app(PurchaseService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 10,
            unitPrice: '0.0000',
            fundingSource: 'deposit',
        );
    }

    public function test_it_rejects_invalid_price_precision(): void
    {
        $user = User::factory()->create();

        $ticker = $this->createTicker();

        $this->expectException(InvalidArgumentException::class);

        app(PurchaseService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 10,
            unitPrice: '30.12345',
            fundingSource: 'deposit',
        );
    }

    public function test_it_rejects_invalid_funding_source(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(PurchaseService::class)->execute(
            user: $user,
            tickerId: 1,
            quantity: 10,
            unitPrice: '30.0000',
            fundingSource: 'invalid',
        );
    }

    public function test_it_does_not_create_trade_when_buying(): void
    {
        $user = $this->createUserWithBalance();

        $ticker = $this->createTicker();

        app(PurchaseService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 100,
            unitPrice: '30.0000',
            fundingSource: 'deposit',
        );

        $this->assertDatabaseCount('trades', 0);
    }
}
