<?php

namespace App\Filament\Resources\Tickers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TickerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Código')
                    ->required()
                    ->maxLength(12)
                    ->unique(ignoreRecord: true)
                    ->extraInputAttributes([
                        'style' => 'text-transform: uppercase',
                    ])
                    ->dehydrateStateUsing(
                        fn ($state) => strtoupper(trim($state))
                    ),
            ]);
    }
}
