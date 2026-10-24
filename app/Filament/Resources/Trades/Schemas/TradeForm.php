<?php

namespace App\Filament\Resources\Trades\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TradeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('ticker')
                    ->label('Ticker')
                    ->required()
                    ->maxLength(12)
                    ->extraInputAttributes([
                        'style' => 'text-transform: uppercase',
                    ])
                    ->dehydrateStateUsing(
                        fn ($state) => strtoupper(trim($state))
                    ),

                DatePicker::make('date')
                    ->label('Data da operação')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d'),

                TextInput::make('buy_price')
                    ->label('Preço de compra')
                    ->numeric()
                    ->required()
                    ->minValue(0.0001)
                    ->step('0.0001')
                    ->prefix('R$'),

                TextInput::make('sell_price')
                    ->label('Preço de venda')
                    ->numeric()
                    ->required()
                    ->minValue(0.0001)
                    ->step('0.0001')
                    ->prefix('R$'),

                TextInput::make('quantity')
                    ->label('Quantidade')
                    ->integer()
                    ->required()
                    ->minValue(1),
            ]);
    }
}
