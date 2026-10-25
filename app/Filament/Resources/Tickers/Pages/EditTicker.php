<?php

namespace App\Filament\Resources\Tickers\Pages;

use App\Filament\Resources\Tickers\TickerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTicker extends EditRecord
{
    protected static string $resource = TickerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
