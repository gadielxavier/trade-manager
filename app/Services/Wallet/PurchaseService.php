<?php

namespace App\Services\Wallet;

use App\Models\Ticker;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletPosition;
use App\Models\WalletTransaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use OverflowException;

class PurchaseService
{
    private const MAX_AMOUNT_CENTS = 999999999999999;

    public function execute(
        User $user,
        int $tickerId,
        int $quantity,
        string $unitPrice,
        string $fundingSource,
        string $fees = '0.00',
        ?string $description = null,
        ?CarbonInterface $date = null,
    ): WalletTransaction {
        if ($quantity <= 0) {
            throw new InvalidArgumentException(
                'A quantidade deve ser maior que zero.'
            );
        }

        if (! in_array($fundingSource, ['deposit', 'cash'], true)) {
            throw new InvalidArgumentException(
                'Origem dos recursos inválida.'
            );
        }

        $priceInUnits = $this->toPriceUnits($unitPrice);
        $feesInCents = $this->toCents($fees);

        if ($priceInUnits <= 0) {
            throw new InvalidArgumentException(
                'O preço unitário deve ser maior que zero.'
            );
        }

        if ($feesInCents < 0) {
            throw new InvalidArgumentException(
                'As taxas não podem ser negativas.'
            );
        }

        if ($priceInUnits > intdiv(PHP_INT_MAX, $quantity)) {
            throw new OverflowException(
                'O valor da compra ultrapassa o limite permitido.'
            );
        }

        $grossAmountInCents = intdiv(
            ($priceInUnits * $quantity) + 50,
            100
        );

        $totalInCents = $grossAmountInCents + $feesInCents;

        if (
            $grossAmountInCents > self::MAX_AMOUNT_CENTS ||
            $totalInCents > self::MAX_AMOUNT_CENTS
        ) {
            throw new OverflowException(
                'O valor da compra ultrapassa o limite permitido.'
            );
        }

        return DB::transaction(function () use (
            $user,
            $tickerId,
            $quantity,
            $priceInUnits,
            $feesInCents,
            $grossAmountInCents,
            $totalInCents,
            $fundingSource,
            $description,
            $date,
        ) {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $ticker = Ticker::query()->findOrFail($tickerId);

            $wallet = app(WalletService::class)
                ->getOrCreate($lockedUser);

            $wallet = Wallet::query()
                ->whereKey($wallet->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentBalance = $this->toCents($wallet->balance);

            if ($currentBalance < $totalInCents) {
                throw new RuntimeException(
                    'Saldo insuficiente para realizar a compra.'
                );
            }

            // Atualiza o caixa.
            $newBalance = $currentBalance - $totalInCents;

            $wallet->update([
                'balance' => $this->formatCents($newBalance),
            ]);

            // Busca e bloqueia a posição existente.
            $position = WalletPosition::query()
                ->where('wallet_id', $wallet->id)
                ->where('ticker_id', $ticker->id)
                ->lockForUpdate()
                ->first();

            $oldQuantity = $position?->quantity ?? 0;

            $oldInvestedAmount = $position
                ? $this->toCents($position->invested_amount)
                : 0;

            $newQuantity = $oldQuantity + $quantity;
            $newInvestedAmount = $oldInvestedAmount + $totalInCents;

            // Preço médio incluindo as taxas.
            $averagePriceInUnits = (int) round(
                ($newInvestedAmount * 100) / $newQuantity,
                0,
                PHP_ROUND_HALF_UP
            );

            WalletPosition::query()->updateOrCreate(
                [
                    'wallet_id' => $wallet->id,
                    'ticker_id' => $ticker->id,
                ],
                [
                    'quantity' => $newQuantity,
                    'average_price' => $this->formatPriceUnits(
                        $averagePriceInUnits
                    ),
                    'invested_amount' => $this->formatCents(
                        $newInvestedAmount
                    ),
                ]
            );

            return $wallet->transactions()->create([
                'ticker_id' => $ticker->id,
                'type' => 'buy',
                'funding_source' => $fundingSource,
                'quantity' => $quantity,
                'unit_price' => $this->formatPriceUnits(
                    $priceInUnits
                ),
                'gross_amount' => $this->formatCents(
                    $grossAmountInCents
                ),
                'fees' => $this->formatCents($feesInCents),
                'net_amount' => $this->formatCents($totalInCents),
                'date' => ($date ?? now())->toDateString(),
                'description' => $description ?? "Compra de {$ticker->code}",
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

        $cents = (int) (
            $integer . str_pad($decimal, 2, '0')
        );

        if ($cents > self::MAX_AMOUNT_CENTS) {
            throw new OverflowException(
                'Valor monetário ultrapassa o limite permitido.'
            );
        }

        return $cents;
    }

    private function toPriceUnits(string $price): int
    {
        $price = trim($price);

        if (! preg_match('/^\d{1,11}(?:\.\d{1,4})?$/', $price)) {
            throw new InvalidArgumentException(
                'Informe um preço válido com até quatro casas decimais.'
            );
        }

        [$integer, $decimal] = array_pad(
            explode('.', $price, 2),
            2,
            '0'
        );

        return (int) (
            $integer . str_pad($decimal, 4, '0')
        );
    }

    private function formatCents(int $cents): string
    {
        return intdiv($cents, 100)
            . '.'
            . str_pad(
                (string) ($cents % 100),
                2,
                '0',
                STR_PAD_LEFT
            );
    }

    private function formatPriceUnits(int $units): string
    {
        return intdiv($units, 10000)
            . '.'
            . str_pad(
                (string) ($units % 10000),
                4,
                '0',
                STR_PAD_LEFT
            );
    }
}
