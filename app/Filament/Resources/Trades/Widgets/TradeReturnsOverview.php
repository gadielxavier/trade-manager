<?php

namespace App\Filament\Resources\Trades\Widgets;

use App\Models\Trade;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TradeReturnsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $userId = auth()->id();

        $month = $this->calculateReturns(
            $userId,
            now()->year,
            now()->month,
        );

        $year = $this->calculateReturns(
            $userId,
            now()->year,
        );

        $total = $this->calculateReturns($userId);

        return [
            $this->makeReturnStat(
                'Retorno do mês',
                $month,
                now()->translatedFormat('F/Y'),
            ),

            $this->makeReturnStat(
                'Retorno do ano',
                $year,
                'Ano ' . now()->year,
            ),

            $this->makeReturnStat(
                'Retorno total',
                $total,
                'Todas as operações',
            ),
        ];
    }

    private function calculateReturns(
        ?int $userId,
        ?int $year = null,
        ?int $month = null,
    ): array {
        $query = Trade::query()
            ->where('user_id', $userId);

        if ($year !== null) {
            $query->whereYear('date', $year);
        }

        if ($month !== null) {
            $query->whereMonth('date', $month);
        }

        $result = (clone $query)
            ->selectRaw('
                COALESCE(
                    SUM((sell_price - buy_price) * quantity),
                    0
                ) AS profit,
                COALESCE(
                    SUM(buy_price * quantity),
                    0
                ) AS invested
            ')
            ->first();

        $profit = (float) $result->profit;
        $invested = (float) $result->invested;

        $percentage = $invested > 0
            ? ($profit / $invested) * 100
            : 0;

        return [
            'profit' => $profit,
            'percentage' => $percentage,
        ];
    }

    private function makeReturnStat(
        string $label,
        array $returns,
        string $description,
    ): Stat {
        $profit = $returns['profit'];
        $percentage = $returns['percentage'];

        return Stat::make(
            $label,
            $this->formatCurrency($profit)
            . ' (' . $this->formatPercentage($percentage) . ')',
        )
            ->description($description)
            ->color($profit >= 0 ? 'success' : 'danger');
    }

    private function formatCurrency(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    private function formatPercentage(float $value): string
    {
        return number_format($value, 2, ',', '.') . '%';
    }
}
