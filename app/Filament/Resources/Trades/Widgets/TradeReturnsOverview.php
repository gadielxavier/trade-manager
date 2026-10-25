<?php

namespace App\Filament\Resources\Trades\Widgets;

use App\Filament\Resources\Trades\Pages\ListTrades;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class TradeReturnsOverview extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListTrades::class;
    }

    protected function getStats(): array
    {
        $month = $this->calculateReturns(
            now()->year,
            now()->month,
        );

        $year = $this->calculateReturns(
            now()->year,
        );

        $total = $this->calculateReturns();

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
                'Todas as operações filtradas',
            ),
        ];
    }

    private function calculateReturns(
        ?int $year = null,
        ?int $month = null,
    ): array {
        $query = clone $this->getPageTableQuery();

        if ($year !== null) {
            $query->whereYear('date', $year);
        }

        if ($month !== null) {
            $query->whereMonth('date', $month);
        }

        $result = $query
            ->reorder()
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

        return [
            'profit' => $profit,
            'percentage' => $invested > 0
                ? ($profit / $invested) * 100
                : 0,
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
