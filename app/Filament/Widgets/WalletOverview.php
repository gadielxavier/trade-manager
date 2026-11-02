<?php

namespace App\Filament\Widgets;

use App\Services\Wallet\WalletDashboardService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class WalletOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $dashboard = app(WalletDashboardService::class)
            ->execute(Auth::user());

        return [
            Stat::make(
                'Saldo disponível',
                'R$ ' . number_format(
                    (float) $dashboard['balance'],
                    2,
                    ',',
                    '.'
                )
            )
                ->description('Dinheiro disponível em caixa')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('success'),

            Stat::make(
                'Total investido',
                'R$ ' . number_format(
                    (float) $dashboard['invested_amount'],
                    2,
                    ',',
                    '.'
                )
            )
                ->description('Valor aplicado nos ativos')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make(
                'Lucro realizado',
                'R$ ' . number_format(
                    (float) $dashboard['realized_profit'],
                    2,
                    ',',
                    '.'
                )
            )
                ->description(
                    $dashboard['trades_count'] . ' operações encerradas'
                )
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color(
                    (float) $dashboard['realized_profit'] >= 0
                        ? 'success'
                        : 'danger'
                ),

            Stat::make(
                'Patrimônio total',
                'R$ ' . number_format(
                    (float) $dashboard['total_patrimony'],
                    2,
                    ',',
                    '.'
                )
            )
                ->description('Caixa + valor investido pelo custo')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('primary'),
        ];
    }
}
