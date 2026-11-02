<?php

namespace Tests\Feature;

use App\Filament\Resources\WalletTransactions\Pages\ListWalletTransactions;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletTransactionResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(
            Filament::getPanel('admin')
        );
    }

    public function test_user_can_view_own_wallet_transactions(): void
    {
        $user = User::factory()->create();

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
        ]);

        WalletTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => 'deposit',
            'gross_amount' => '500.00',
            'fees' => '0.00',
            'net_amount' => '500.00',
        ]);

        $this->actingAs($user);

        Livewire::test(ListWalletTransactions::class)
            ->assertCanSeeTableRecords(
                WalletTransaction::where('wallet_id', $wallet->id)->get()
            );
    }

    public function test_user_cannot_view_other_users_transactions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $wallet = Wallet::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $transaction = WalletTransaction::factory()->create([
            'wallet_id' => $wallet->id,
            'type' => 'deposit',
            'gross_amount' => '500.00',
            'fees' => '0.00',
            'net_amount' => '500.00',
        ]);

        $this->actingAs($user);

        Livewire::test(ListWalletTransactions::class)
            ->assertCanNotSeeTableRecords([$transaction]);
    }

    public function test_transaction_history_is_read_only(): void
    {
        $user = User::factory()->create();

        Wallet::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(ListWalletTransactions::class)
            ->assertActionDoesNotExist('create');
    }

    public function test_user_only_sees_transactions_from_own_wallets(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
        ]);

        $otherWallet = Wallet::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $transaction1 = WalletTransaction::factory()->create([
            'wallet_id' => $wallet->id,
        ]);

        $transaction2 = WalletTransaction::factory()->create([
            'wallet_id' => $otherWallet->id,
        ]);

        $this->actingAs($user);

        Livewire::test(ListWalletTransactions::class)
            ->assertCanSeeTableRecords([$transaction1])
            ->assertCanNotSeeTableRecords([$transaction2]);
    }
}
