<?php

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\Ticker;
use App\Models\Wallet;
use App\Models\WalletPosition;
use App\Models\WalletTransaction;
use App\Models\Trade;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use OverflowException;

class SellService
{
    private const MAX_AMOUNT_CENTS = 999999999999999;

    public function execute(
        User $user,
        int $tickerId,
        int $quantity,
        string $unitPrice,
        string $fees = '0.00',
        ?string $description = null,
        ?CarbonInterface $date = null,
    ): WalletTransaction {
        if ($quantity <= 0) {
            throw new InvalidArgumentException(
                'A quantidade deve ser maior que zero.'
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

        if ($priceInUnits > intdiv(PHP_INT_MAX - 50, $quantity)) {
            throw new OverflowException(
                'O valor da venda ultrapassa o limite permitido.'
            );
        }

        $grossAmountInCents = intdiv(
            ($priceInUnits * $quantity) + 50,
            100
        );

        $netAmountInCents = $grossAmountInCents - $feesInCents;

        if (
            $grossAmountInCents > self::MAX_AMOUNT_CENTS ||
            $netAmountInCents < 0
        ) {
            throw new InvalidArgumentException(
                'As taxas não podem ser superiores ao valor da venda.'
            );
        }

        return DB::transaction(function () use (
            $user,
            $tickerId,
            $quantity,
            $priceInUnits,
            $feesInCents,
            $grossAmountInCents,
            $netAmountInCents,
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

            // Busca e bloqueia a posição.
            $position = WalletPosition::query()
                ->where('wallet_id', $wallet->id)
                ->where('ticker_id', $ticker->id)
                ->lockForUpdate()
                ->first();

            if (! $position) {
                throw new RuntimeException(
                    'Você não possui ações deste ativo na carteira.'
                );
            }

            $oldQuantity = $position->quantity;

            if ($quantity > $oldQuantity) {
                throw new RuntimeException(
                    'Quantidade insuficiente de ações para realizar a venda.'
                );
            }

            // Captura o preço médio antes de atualizar a posição.
            $buyPrice = $position->average_price;

            $oldInvestedAmount = $this->toCents(
                $position->invested_amount
            );

            // Calcula o custo de aquisição proporcional das ações vendidas.
            if (
                $oldInvestedAmount >
                intdiv(PHP_INT_MAX - intdiv($oldQuantity, 2), $quantity)
            ) {
                throw new OverflowException(
                    'Não foi possível calcular o custo de aquisição.'
                );
            }

            $soldCostInCents = intdiv(
                ($oldInvestedAmount * $quantity)
                + intdiv($oldQuantity, 2),
                $oldQuantity
            );

            // Calcula o saldo após a venda.
            $currentBalance = $this->toCents($wallet->balance);

            if (
                $currentBalance >
                self::MAX_AMOUNT_CENTS - $netAmountInCents
            ) {
                throw new OverflowException(
                    'O saldo ultrapassa o limite permitido.'
                );
            }

            $newBalance = $currentBalance + $netAmountInCents;

            $wallet->update([
                'balance' => $this->formatCents($newBalance),
            ]);

            // Atualiza a posição.
            $newQuantity = $oldQuantity - $quantity;

            $newInvestedAmount = $oldInvestedAmount
                - $soldCostInCents;

            if ($newQuantity === 0) {
                $position->delete();
            } else {
                $averagePriceInUnits = (int) round(
                    ($newInvestedAmount * 100) / $newQuantity,
                    0,
                    PHP_ROUND_HALF_UP
                );

                $position->update([
                    'quantity' => $newQuantity,
                    'invested_amount' => $this->formatCents(
                        $newInvestedAmount
                    ),
                    'average_price' => $this->formatPriceUnits(
                        $averagePriceInUnits
                    ),
                ]);
            }

            // Data da operação.
            $operationDate = ($date ?? now())->toDateString();

            // Registra a movimentação financeira.
            $transaction = $wallet->transactions()->create([
                'ticker_id' => $ticker->id,
                'type' => 'sell',
                'quantity' => $quantity,
                'unit_price' => $this->formatPriceUnits(
                    $priceInUnits
                ),
                'gross_amount' => $this->formatCents(
                    $grossAmountInCents
                ),
                'fees' => $this->formatCents($feesInCents),
                'net_amount' => $this->formatCents(
                    $netAmountInCents
                ),
                'date' => $operationDate,
                'description' => $description
                    ?? "Venda de {$ticker->code}",
            ]);

            // Calcula o lucro ou prejuízo líquido.
            $profitInCents = $netAmountInCents
                - $soldCostInCents;

            // Calcula a rentabilidade.
            $returnPercent = $soldCostInCents > 0
                ? round(
                    ($profitInCents / $soldCostInCents) * 100,
                    4
                )
                : 0;

            // Registra o Trade.
            Trade::query()->create([
                'user_id' => $lockedUser->id,
                'ticker_id' => $ticker->id,
                'wallet_transaction_id' => $transaction->id,
                'buy_price' => $buyPrice,
                'sell_price' => $this->formatPriceUnits(
                    $priceInUnits
                ),
                'quantity' => $quantity,
                'profit' => $this->formatSignedCents(
                    $profitInCents
                ),
                'return_percent' => $returnPercent,
                'date' => $operationDate,
            ]);

            return $transaction;
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

    private function formatSignedCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';

        return $sign . $this->formatCents(abs($cents));
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
