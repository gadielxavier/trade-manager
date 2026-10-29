<?php

namespace App\Filament\Resources\Tickers;

use App\Filament\Resources\Tickers\Pages\ListTickers;
use App\Filament\Resources\Tickers\Tables\TickersTable;
use App\Models\Ticker;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class TickerResource extends Resource
{
    protected static ?string $model = Ticker::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Cadastros';

    protected static ?string $modelLabel = 'Ticker';

    protected static ?string $pluralModelLabel = 'Tickers';

    protected static ?string $recordTitleAttribute = 'code';

    public static function table(Table $table): Table
    {
        return TickersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickers::route('/'),
        ];
    }
}
