<?php

namespace App\Filament\Resources\Wallets\Tables;

use App\Filament\Resources\Wallets\Actions\DepositAction;
use App\Filament\Resources\Wallets\Actions\PurchaseAction;
use App\Filament\Resources\Wallets\Actions\SellAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WalletsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Carteira')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('balance')
                    ->label('Saldo disponível')
                    ->money('BRL')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('positions_count')
                    ->label('Ativos')
                    ->counts('positions')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('transactions_count')
                    ->label('Movimentações')
                    ->counts('transactions')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Criada em')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Última atualização')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('has_balance')
                    ->label('Carteiras com saldo')
                    ->query(fn (Builder $query): Builder =>
                    $query->where('balance', '>', 0)
                    ),

                Filter::make('has_positions')
                    ->label('Carteiras com ativos')
                    ->query(fn (Builder $query): Builder =>
                    $query->has('positions')
                    ),
            ])
            ->recordActions([
                DepositAction::make(),
                PurchaseAction::make(),
                SellAction::make(),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
