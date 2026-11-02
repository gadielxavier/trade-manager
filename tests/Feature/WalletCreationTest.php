<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_only_one_wallet_per_user(): void
    {
        $user = User::factory()->create();

        $service = app(WalletService::class);

        $firstWallet = $service->getOrCreate($user);

        $secondWallet = $service->getOrCreate($user);

        $this->assertEquals(
            $firstWallet->id,
            $secondWallet->id
        );

        $this->assertDatabaseCount('wallets', 1);

        $this->assertEquals(
            '0.00',
            $firstWallet->fresh()->balance
        );
    }
}
