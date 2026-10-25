<?php

namespace App\Filament\Resources\Tickers\Pages;

use App\Filament\Resources\Tickers\TickerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTickers extends ListRecords
{
    protected static string $resource = TickerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
