<?php

namespace App\Filament\Resources\Trades\Pages;

use App\Filament\Resources\Trades\TradeResource;
use App\Filament\Resources\Trades\Widgets\TradeReturnsOverview;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListTrades extends ListRecords
{

    use ExposesTableToWidgets;
    protected static string $resource = TradeResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TradeReturnsOverview::class,
        ];
    }
}
