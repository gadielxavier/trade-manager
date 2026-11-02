<?php

namespace Tests\Feature;

use App\Models\Ticker;
use App\Models\User;
use App\Models\WalletPosition;
use App\Services\Wallet\DepositService;
use App\Services\Wallet\PurchaseService;
use App\Services\Wallet\SellService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class SellServiceTest extends TestCase
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

    private function createPosition(
        User $user,
        Ticker $ticker,
        int $quantity = 100,
        string $unitPrice = '30.0000',
        string $fees = '0.00',
    ): void {
        app(PurchaseService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: $quantity,
            unitPrice: $unitPrice,
            fundingSource: 'deposit',
            fees: $fees,
        );
    }

    public function test_it_creates_a_sale_successfully(): void
    {
        $user = $this->createUserWithBalance();

        $ticker = $this->createTicker();

        $this->createPosition($user, $ticker);

        $transaction = app(SellService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 100,
            unitPrice: '35.0000',
            description: 'Venda de PETR4',
        );

        $wallet = $user->wallet()->first();

        $this->assertSame('10500.00', $wallet->balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'ticker_id' => $ticker->id,
            'type' => 'sell',
            'quantity' => 100,
            'unit_price' => '35.0000',
            'gross_amount' => '3500.00',
            'fees' => '0.00',
            'net_amount' => '3500.00',
            'description' => 'Venda de PETR4',
        ]);

        $this->assertSame('sell', $transaction->type);

        $this->assertDatabaseCount('wallet_positions', 0);
    }

    public function test_it_creates_a_partial_sale_successfully(): void
    {
        $user = $this->createUserWithBalance();

        $ticker = $this->createTicker();

        $this->createPosition($user, $ticker);

        $transaction = app(SellService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 40,
            unitPrice: '35.0000',
        );

        $wallet = $user->wallet()->first();

        $position = WalletPosition::query()
            ->where('wallet_id', $wallet->id)
            ->where('ticker_id', $ticker->id)
            ->firstOrFail();

        $this->assertSame('8400.00', $wallet->balance);

        $this->assertSame(60, $position->quantity);

        $this->assertSame('30.0000', $position->average_price);

        $this->assertSame('1800.00', $position->invested_amount);

        $this->assertSame(40, $transaction->quantity);

        $this->assertDatabaseCount('wallet_positions', 1);
    }

    public function test_it_preserves_average_price_after_partial_sale(): void
    {
        $user = $this->createUserWithBalance();

        $ticker = $this->createTicker();

        $purchaseService = app(PurchaseService::class);

        $purchaseService->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 10,
            unitPrice: '30.0000',
            fundingSource: 'deposit',
        );

        $purchaseService->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 10,
            unitPrice: '40.0000',
            fundingSource: 'deposit',
        );

        app(SellService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 10,
            unitPrice: '50.0000',
        );

        $position = WalletPosition::query()
            ->where('ticker_id', $ticker->id)
            ->firstOrFail();

        $this->assertSame(10, $position->quantity);

        $this->assertSame('35.0000', $position->average_price);

        $this->assertSame('350.00', $position->invested_amount);
    }

    public function test_it_updates_position_after_multiple_partial_sales(): void
    {
        $user = $this->createUserWithBalance();

        $ticker = $this->createTicker();

        $this->createPosition($user, $ticker);

        $service = app(SellService::class);

        $service->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 30,
            unitPrice: '35.0000',
        );

        $service->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 20,
            unitPrice: '40.0000',
        );

        $position = WalletPosition::query()
            ->where('ticker_id', $ticker->id)
            ->firstOrFail();

        $this->assertSame(50, $position->quantity);

        $this->assertSame('30.0000', $position->average_price);

        $this->assertSame('1500.00', $position->invested_amount);

        $this->assertSame(
            '8850.00',
            $user->wallet()->first()->balance
        );

        $this->assertDatabaseCount('wallet_transactions', 4);
    }

    public function test_it_removes_position_after_selling_all_shares(): void
    {
        $user = $this->createUserWithBalance();

        $ticker = $this->createTicker();

        $this->createPosition($user, $ticker);

        app(SellService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 100,
            unitPrice: '35.0000',
        );

        $this->assertDatabaseCount('wallet_positions', 0);
    }

    public function test_it_deducts_fees_from_sale_amount(): void
    {
        $user = $this->createUserWithBalance();

        $ticker = $this->createTicker();

        $this->createPosition($user, $ticker);

        $transaction = app(SellService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 100,
            unitPrice: '35.0000',
            fees: '10.00',
        );

        $wallet = $user->wallet()->first();

        $this->assertSame('10490.00', $wallet->balance);

        $this->assertSame('3500.00', $transaction->gross_amount);

        $this->assertSame('10.00', $transaction->fees);

        $this->assertSame('3490.00', $transaction->net_amount);
    }

    public function test_it_uses_current_date_when_date_is_not_provided(): void
    {
        $user = $this->createUserWithBalance();

        $ticker = $this->createTicker();

        $this->createPosition($user, $ticker);

        $transaction = app(SellService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 100,
            unitPrice: '35.0000',
        );

        $this->assertSame(
            now()->toDateString(),
            $transaction->date->toDateString()
        );
    }

    public function test_it_uses_provided_historical_date(): void
    {
        $user = $this->createUserWithBalance();

        $ticker = $this->createTicker();

        $this->createPosition($user, $ticker);

        $historicalDate = Carbon::parse('2025-06-15');

        $transaction = app(SellService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 100,
            unitPrice: '35.0000',
            date: $historicalDate,
        );

        $this->assertSame(
            '2025-06-15',
            $transaction->date->toDateString()
        );
    }

    public function test_it_rejects_sale_without_position(): void
    {
        $user = $this->createUserWithBalance();

        $ticker = $this->createTicker();

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'Você não possui ações deste ativo na carteira.'
        );

        app(SellService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 10,
            unitPrice: '35.0000',
        );
    }

    public function test_it_rejects_sale_with_insufficient_quantity(): void
    {
        $user = $this->createUserWithBalance();

        $ticker = $this->createTicker();

        $this->createPosition($user, $ticker);

        try {
            app(SellService::class)->execute(
                user: $user,
                tickerId: $ticker->id,
                quantity: 150,
                unitPrice: '35.0000',
            );

            $this->fail('A venda deveria ter sido rejeitada.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Quantidade insuficiente de ações para realizar a venda.',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            '7000.00',
            $user->wallet()->first()->balance
        );

        $this->assertDatabaseCount('wallet_positions', 1);

        $this->assertDatabaseCount('wallet_transactions', 2);
    }

    public function test_it_rejects_zero_quantity(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(SellService::class)->execute(
            user: $user,
            tickerId: 1,
            quantity: 0,
            unitPrice: '35.0000',
        );
    }

    public function test_it_rejects_negative_quantity(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(SellService::class)->execute(
            user: $user,
            tickerId: 1,
            quantity: -10,
            unitPrice: '35.0000',
        );
    }

    public function test_it_rejects_zero_price(): void
    {
        $user = User::factory()->create();

        $ticker = $this->createTicker();

        $this->expectException(InvalidArgumentException::class);

        app(SellService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 10,
            unitPrice: '0.0000',
        );
    }

    public function test_it_rejects_invalid_price_precision(): void
    {
        $user = User::factory()->create();

        $ticker = $this->createTicker();

        $this->expectException(InvalidArgumentException::class);

        app(SellService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 10,
            unitPrice: '35.12345',
        );
    }

    public function test_it_rejects_negative_fees(): void
    {
        $user = User::factory()->create();

        $ticker = $this->createTicker();

        $this->expectException(InvalidArgumentException::class);

        app(SellService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 10,
            unitPrice: '35.0000',
            fees: '-10.00',
        );
    }

    public function test_it_rejects_fees_greater_than_sale_amount(): void
    {
        $user = $this->createUserWithBalance();

        $ticker = $this->createTicker();

        $this->createPosition($user, $ticker);

        $this->expectException(InvalidArgumentException::class);

        app(SellService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 10,
            unitPrice: '30.0000',
            fees: '400.00',
        );
    }

    public function test_it_creates_trade_when_selling(): void
    {
        $user = $this->createUserWithBalance();

        $ticker = $this->createTicker();

        $this->createPosition($user, $ticker);

        $transaction = app(SellService::class)->execute(
            user: $user,
            tickerId: $ticker->id,
            quantity: 100,
            unitPrice: '35.0000',
        );

        $this->assertDatabaseCount('trades', 1);

        $this->assertDatabaseHas('trades', [
            'user_id' => $user->id,
            'ticker_id' => $ticker->id,
            'wallet_transaction_id' => $transaction->id,
            'buy_price' => '30.0000',
            'sell_price' => '35.0000',
            'quantity' => 100,
            'profit' => '500.00',
            'return_percent' => '16.6667',
        ]);
    }
}
