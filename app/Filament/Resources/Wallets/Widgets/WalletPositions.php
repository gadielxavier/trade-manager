<?php

namespace App\Filament\Resources\Wallets\Widgets;

use App\Models\Wallet;
use App\Models\WalletPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class WalletPositions extends TableWidget
{
    protected static ?string $heading = 'Ativos da carteira';

    protected int|string|array $columnSpan = 'full';

    public ?Wallet $record = null;

    protected function getTableQuery(): Builder
    {
        return WalletPosition::query()
            ->where('wallet_id', $this->record->id)
            ->with(['ticker.quotes']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getTableQuery())
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

                TextColumn::make('ticker.quotes.regular_market_price')
                    ->label('Preço atual')
                    ->money(fn ($record) =>
                        $record->ticker->quotes?->currency ?? 'BRL'
                    ),

                TextColumn::make('ticker.quotes.regular_market_change')
                    ->label('Variação')
                    ->money(fn ($record) =>
                        $record->ticker->quotes?->currency ?? 'BRL'
                    )
                    ->color(fn ($state) => match (true) {
                        (float) $state > 0 => 'success',
                        (float) $state < 0 => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('ticker.quotes.regular_market_change_percent')
                    ->label('Rentabilidade')
                    ->formatStateUsing(fn ($state) =>
                        number_format((float) $state, 2, ',', '.') . '%'
                    )
                    ->color(fn ($state) => match (true) {
                        (float) $state > 0 => 'success',
                        (float) $state < 0 => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('invested_amount')
                    ->label('Total investido')
                    ->money('BRL')
                    ->sortable()
                    ->color('info'),
            ])
            ->defaultSort('invested_amount', 'desc')
            ->striped()
            ->emptyStateHeading('Nenhum ativo nesta carteira')
            ->emptyStateDescription(
                'Quando você adicionar ativos a esta carteira, eles aparecerão aqui.'
            )
            ->emptyStateIcon('heroicon-o-chart-bar');
    }
}
