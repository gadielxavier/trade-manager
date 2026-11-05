<?php

namespace App\Filament\Widgets;

use App\Services\Wallet\WalletDashboardService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class WalletPositionsChart extends ChartWidget
{
    protected ?string $heading = 'Distribuição da carteira';

    protected ?string $description =
        'Participação de cada ativo no total investido.';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $dashboard = app(WalletDashboardService::class)
            ->execute(Auth::user());

        $positions = $dashboard['positions'];

        $colors = [
            '#3B82F6', // Blue
            '#10B981', // Green
            '#F59E0B', // Amber
            '#EF4444', // Red
            '#8B5CF6', // Purple
            '#EC4899', // Pink
            '#06B6D4', // Cyan
            '#F97316', // Orange
            '#64748B', // Slate
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Total investido',
                    'data' => $positions
                        ->pluck('invested_amount')
                        ->map(fn ($value) => (float) $value)
                        ->values()
                        ->all(),

                    'backgroundColor' => $positions
                        ->map(fn ($position, $index) => $colors[$index % count($colors)])
                        ->values()
                        ->all(),

                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $positions
                ->map(fn ($position) => $position->ticker->code)
                ->values()
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
    {
        plugins: {
            legend: {
                position: 'bottom',
            },
            tooltip: {
                enabled: true,
                callbacks: {
                    title: function (items) {
                        return items[0].label;
                    },
                    label: function (context) {
                        const value = context.raw;

                        return 'Total investido: ' +
                            new Intl.NumberFormat('pt-BR', {
                                style: 'currency',
                                currency: 'BRL',
                            }).format(value);
                    },
                },
            },
        },
        hover: {
            mode: 'nearest',
            intersect: true,
        },
    }
    JS);
    }
}
