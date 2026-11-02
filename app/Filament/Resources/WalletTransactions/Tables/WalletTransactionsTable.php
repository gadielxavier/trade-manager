<?php

namespace App\Filament\Resources\WalletTransactions\Tables;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WalletTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Operação')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'deposit' => 'Depósito',
                        'buy' => 'Compra',
                        'sell' => 'Venda',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'deposit' => 'success',
                        'buy' => 'info',
                        'sell' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('ticker.code')
                    ->label('Ativo')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('quantity')
                    ->label('Quantidade')
                    ->numeric()
                    ->placeholder('—'),

                TextColumn::make('unit_price')
                    ->label('Preço unitário')
                    ->money('BRL')
                    ->placeholder('—'),

                TextColumn::make('gross_amount')
                    ->label('Valor bruto')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('fees')
                    ->label('Taxas')
                    ->money('BRL'),

                TextColumn::make('net_amount')
                    ->label('Valor líquido')
                    ->money('BRL')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(40)
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo de operação')
                    ->options([
                        'deposit' => 'Depósito',
                        'buy' => 'Compra',
                        'sell' => 'Venda',
                    ]),

                SelectFilter::make('ticker_id')
                    ->label('Ativo')
                    ->relationship('ticker', 'code')
                    ->searchable()
                    ->preload(),

                Filter::make('date')
                    ->label('Período')
                    ->form([
                        DatePicker::make('from')
                            ->label('De'),

                        DatePicker::make('until')
                            ->label('Até'),
                    ])
                    ->query(function (
                        Builder $query,
                        array $data
                    ): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date) =>
                                $query->whereDate('date', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date) =>
                                $query->whereDate('date', '<=', $date)
                            );
                    }),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('date', 'desc');
    }
}
