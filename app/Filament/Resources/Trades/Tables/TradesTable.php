<?php

namespace App\Filament\Resources\Trades\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TradesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('ticker.logo_url')
                    ->label('Logo')
                    ->disk(null)
                    ->height(30)
                    ->width(30),

                TextColumn::make('ticker.code')
                    ->label('Ticker')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('buy_price')
                    ->label('Compra')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('sell_price')
                    ->label('Venda')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label('Quantidade')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('profit')
                    ->label('Lucro')
                    ->money('BRL')
                    ->color(fn ($state) => (float) $state >= 0
                        ? 'success'
                        : 'danger')
                    ->sortable(),

                TextColumn::make('return_percent')
                    ->label('Retorno')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2)
                    ->color(fn ($state) => (float) $state >= 0
                        ? 'success'
                        : 'danger')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('ticker_id')
                    ->label('Ticker')
                    ->relationship(
                        name: 'ticker',
                        titleAttribute: 'code',
                    )
                    ->searchable()
                    ->preload(),

                Filter::make('date')
                    ->label('Período')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Data inicial')
                            ->native(false),

                        DatePicker::make('until')
                            ->label('Data final')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
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
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}
