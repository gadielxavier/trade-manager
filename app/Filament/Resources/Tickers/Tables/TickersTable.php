<?php

namespace App\Filament\Resources\Tickers\Tables;

use App\Models\Ticker;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TickersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('trades_count', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('long_name')
                    ->label('Nome completo')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),

                TextColumn::make('asset_type')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sub_type')
                    ->label('Subtipo')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('exchange')
                    ->label('Bolsa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('currency')
                    ->label('Moeda')
                    ->sortable(),

                TextColumn::make('sector')
                    ->label('Setor')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('subsector')
                    ->label('Subsetor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),

                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean()
                    ->sortable(),

                ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->disk(null)
                    ->height(30)
                    ->width(30),

                TextColumn::make('trades_count')
                    ->label('Operações')
                    ->counts('trades')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('asset_type')
                    ->label('Tipo')
                    ->options(fn (): array => Ticker::query()
                        ->whereNotNull('asset_type')
                        ->where('asset_type', '!=', '')
                        ->distinct()
                        ->orderBy('asset_type')
                        ->pluck('asset_type', 'asset_type')
                        ->all()
                    ),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
