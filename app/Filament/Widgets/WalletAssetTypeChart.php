<?php

namespace App\Filament\Widgets;

use App\Services\Wallet\WalletDashboardService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use BackedEnum;

class WalletAssetTypeChart extends ChartWidget
{
    protected ?string $heading = 'Distribuição por tipo de ativo';

    protected ?string $description =
        'Participação percentual de cada classe de ativo na carteira.';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $dashboard = app(WalletDashboardService::class)
            ->execute(Auth::user());

        $positions = $dashboard['positions'];

        // Group invested amounts by asset type.
        $totals = $positions
            ->groupBy(function ($position) {
                $type = $position->ticker->asset_type;

                return $type instanceof BackedEnum
                    ? $type->value
                    : (string) $type;
            })
            ->map(fn ($group) => (float) $group->sum('invested_amount'));

        $totalInvested = $totals->sum();

        $colors = [
            '#3B82F6',
            '#10B981',
            '#F59E0B',
            '#EF4444',
            '#8B5CF6',
            '#EC4899',
            '#06B6D4',
            '#F97316',
            '#64748B',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Participação (%)',
                    'data' => $totals
                        ->map(fn ($amount) => $totalInvested > 0
                            ? round(($amount / $totalInvested) * 100, 2)
                            : 0
                        )
                        ->values()
                        ->all(),

                    'backgroundColor' => $totals
                        ->keys()
                        ->map(fn ($type, $index) => $colors[$index % count($colors)])
                        ->values()
                        ->all(),

                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],

            'labels' => $totals
                ->keys()
                ->map(fn ($type) => Str::headline($type))
                ->values()
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
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
                            return 'Participação: ' +
                                context.raw.toLocaleString('pt-BR', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                }) + '%';
                        },
                    },
                },
            },
            cutout: '60%',
            hover: {
                mode: 'nearest',
                intersect: true,
            },
        }
        JS);
    }
}
