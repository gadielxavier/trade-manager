<?php

namespace App\Filament\Resources\Wallets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WalletInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações da carteira')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nome da carteira')
                            ->weight('bold'),

                        TextEntry::make('balance')
                            ->label('Saldo disponível')
                            ->money('BRL')
                            ->color('success'),

                        TextEntry::make('created_at')
                            ->label('Data de criação')
                            ->date('d/m/Y'),

                        TextEntry::make('updated_at')
                            ->label('Última atualização')
                            ->dateTime('d/m/Y H:i'),
                    ]),

                Section::make('Resumo da carteira')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('positions_count')
                            ->label('Ativos')
                            ->state(
                                fn ($record) => $record->positions()->count()
                            ),

                        TextEntry::make('transactions_count')
                            ->label('Movimentações')
                            ->state(
                                fn ($record) => $record->transactions()->count()
                            ),

                        TextEntry::make('invested_amount')
                            ->label('Total investido')
                            ->state(
                                fn ($record) => $record->positions()
                                    ->sum('invested_amount')
                            )
                            ->money('BRL'),
                    ]),
            ]);
    }
}
