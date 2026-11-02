<?php

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use OverflowException;

class DepositService
{
    private const MAX_AMOUNT_CENTS = 999999999999999;

    public function execute(
        User $user,
        string $amount,
        ?string $description = null,
        ?CarbonInterface $date = null,
    ): WalletTransaction {
        $amountInCents = $this->toCents($amount);

        if ($amountInCents <= 0) {
            throw new InvalidArgumentException(
                'O valor do aporte deve ser maior que zero.'
            );
        }

        return DB::transaction(function () use (
            $user,
            $amountInCents,
            $description,
            $date,
        ) {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $wallet = app(WalletService::class)
                ->getOrCreate($lockedUser);

            $wallet = Wallet::query()
                ->whereKey($wallet->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentBalance = $this->toCents($wallet->balance);

            $newBalance = $currentBalance + $amountInCents;

            if ($newBalance > self::MAX_AMOUNT_CENTS) {
                throw new OverflowException(
                    'O saldo ultrapassa o limite permitido.'
                );
            }

            $amountFormatted = $this->formatCents($amountInCents);

            $wallet->update([
                'balance' => $this->formatCents($newBalance),
            ]);

            return $wallet->transactions()->create([
                'type' => 'deposit',
                'gross_amount' => $amountFormatted,
                'fees' => '0.00',
                'net_amount' => $amountFormatted,
                'date' => ($date ?? now())->toDateString(),
                'description' => $description ?? 'Aporte na carteira',
            ]);
        });
    }

    private function toCents(string $amount): int
    {
        $amount = trim($amount);

        if (! preg_match('/^\d{1,13}(?:\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException(
                'Informe um valor monetário válido com até duas casas decimais.'
            );
        }

        [$integer, $decimal] = array_pad(
            explode('.', $amount, 2),
            2,
            '0'
        );

        $cents = (int) ($integer . str_pad($decimal, 2, '0'));

        if ($cents > self::MAX_AMOUNT_CENTS) {
            throw new OverflowException(
                'O valor do aporte ultrapassa o limite permitido.'
            );
        }

        return $cents;
    }

    private function formatCents(int $cents): string
    {
        return intdiv($cents, 100)
            . '.'
            . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
