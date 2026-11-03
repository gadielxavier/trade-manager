<?php

namespace App\Filament\Widgets;

use App\Services\Wallet\WalletDashboardService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class WalletPositions extends ChartWidget
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

        return [
            'datasets' => [
                [
                    'label' => 'Total investido',
                    'data' => $positions
                        ->pluck('invested_amount')
                        ->map(fn ($value) => (float) $value)
                        ->values()
                        ->all(),
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

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            let value = context.parsed;

                            return context.label + ": " +
                                new Intl.NumberFormat("pt-BR", {
                                    style: "currency",
                                    currency: "BRL"
                                }).format(value);
                        }',
                    ],
                ],
            ],
        ];
    }
}
