<?php

namespace App\Filament\Resources\Wallets\Pages;

use App\Filament\Resources\Wallets\WalletResource;
use App\Filament\Resources\Wallets\Widgets\WalletPositions;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWallet extends ViewRecord
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            WalletPositions::class,
        ];
    }

    protected function getHeaderWidgetsData(): array
    {
        return [
            'record' => $this->getRecord(),
        ];
    }
}
