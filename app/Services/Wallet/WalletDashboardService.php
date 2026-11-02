<?php

namespace App\Services\Wallet;

use App\Models\Trade;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;

class WalletDashboardService
{
    public function execute(User $user): array
    {
        $wallet = Wallet::query()
            ->where('user_id', $user->id)
            ->first();

        if (! $wallet) {
            return $this->emptyDashboard();
        }

        // Posições abertas.
        $positions = $wallet->positions()
            ->with('ticker')
            ->orderBy('invested_amount', 'desc')
            ->get();

        // Total investido nas posições abertas.
        $investedAmount = $wallet->positions()
            ->sum('invested_amount');

        // Quantidade de ativos diferentes.
        $assetsCount = $wallet->positions()->count();

        // Lucro ou prejuízo realizado.
        $realizedProfit = Trade::query()
            ->where('user_id', $user->id)
            ->sum('profit');

        // Quantidade de operações encerradas.
        $tradesCount = Trade::query()
            ->where('user_id', $user->id)
            ->count();

        // Últimas movimentações.
        $recentTransactions = $wallet->transactions()
            ->with('ticker')
            ->latest('date')
            ->latest('id')
            ->limit(5)
            ->get();

        return [
            'wallet' => $wallet,

            'balance' => $wallet->balance,

            'invested_amount' => $investedAmount,

            'realized_profit' => $realizedProfit,

            'total_patrimony' => $this->sumMoney(
                $wallet->balance,
                $investedAmount
            ),

            'assets_count' => $assetsCount,

            'trades_count' => $tradesCount,

            'positions' => $positions,

            'recent_transactions' => $recentTransactions,
        ];
    }

    private function emptyDashboard(): array
    {
        return [
            'wallet' => null,
            'balance' => '0.00',
            'invested_amount' => '0.00',
            'realized_profit' => '0.00',
            'total_patrimony' => '0.00',
            'assets_count' => 0,
            'trades_count' => 0,
            'positions' => new Collection(),
            'recent_transactions' => new Collection(),
        ];
    }

    /**
     * Soma valores monetários sem utilizar operações com floats.
     */
    private function sumMoney(string|int|float $first, string|int|float $second): string
    {
        $toCents = static function ($value): int {
            $value = (string) $value;

            [$integer, $decimal] = array_pad(
                explode('.', $value, 2),
                2,
                '0'
            );

            return ((int) $integer * 100)
                + (int) str_pad($decimal, 2, '0');
        };

        $total = $toCents($first) + $toCents($second);

        return intdiv($total, 100)
            . '.'
            . str_pad(
                (string) ($total % 100),
                2,
                '0',
                STR_PAD_LEFT
            );
    }
}
