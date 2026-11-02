<?php

namespace App\Filament\Widgets;

use App\Models\WalletPosition;
use App\Services\Wallet\WalletDashboardService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class WalletPositions extends TableWidget
{
    protected static ?string $heading = 'Minha carteira de ativos';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $dashboard = app(WalletDashboardService::class)
            ->execute(Auth::user());

        $positionIds = $dashboard['positions']->modelKeys();

        return $table
            ->query(
                fn (): Builder => WalletPosition::query()
                    ->whereIn('id', $positionIds)
                    ->with('ticker')
            )
            ->columns([
                TextColumn::make('ticker.code')
                    ->label('Ativo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('quantity')
                    ->label('Quantidade')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('average_price')
                    ->label('Preço médio')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('invested_amount')
                    ->label('Total investido')
                    ->money('BRL')
                    ->sortable()
                    ->color('info'),

                TextColumn::make('participation')
                    ->label('Participação')
                    ->state(function (WalletPosition $record) use ($dashboard): string {
                        $total = (float) $dashboard['invested_amount'];

                        if ($total <= 0) {
                            return '0,00%';
                        }

                        $percentage = (
                                (float) $record->invested_amount / $total
                            ) * 100;

                        return number_format(
                                $percentage,
                                2,
                                ',',
                                '.'
                            ) . '%';
                    }),
            ])
            ->defaultSort('invested_amount', 'desc')
            ->striped()
            ->emptyStateHeading('Nenhum ativo na carteira')
            ->emptyStateDescription(
                'Quando você comprar seus primeiros ativos, eles aparecerão aqui.'
            )
            ->emptyStateIcon('heroicon-o-chart-bar');
    }
}
