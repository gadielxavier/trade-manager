<?php

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\Wallet;

class WalletService
{
    public function getOrCreate(User $user): Wallet
    {
        return $user->wallet()->firstOrCreate(
            [
                'user_id' => $user->id,
            ],
            [
                'name' => 'Minha carteira',
                'balance' => 0,
            ]
        );
    }
}
