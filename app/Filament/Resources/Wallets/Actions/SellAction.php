<?php

namespace App\Filament\Resources\Wallets\Actions;

use App\Models\Ticker;
use App\Services\Wallet\SellService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Throwable;

class SellAction
{
    public static function make(): Action
    {
        return Action::make('sell')
            ->label('Vender')
            ->icon('heroicon-o-arrow-trending-down')
            ->color('danger')
            ->modalHeading('Vender ativos')
            ->form([
                Select::make('ticker_id')
                    ->label('Ativo')
                    ->options(function ($record): array {
                        return $record->positions()
                            ->with('ticker')
                            ->get()
                            ->filter(fn ($position) => $position->quantity > 0)
                            ->mapWithKeys(fn ($position) => [
                                $position->ticker_id =>
                                    $position->ticker->code
                                    . ' (Disponível: '
                                    . $position->quantity
                                    . ')',
                            ])
                            ->all();
                    })
                    ->searchable()
                    ->required()
                    ->live(),

                TextInput::make('quantity')
                    ->label('Quantidade')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->required(),

                TextInput::make('unit_price')
                    ->label('Preço unitário')
                    ->prefix('R$')
                    ->numeric()
                    ->required()
                    ->minValue(0.0001)
                    ->step(0.0001),

                TextInput::make('fees')
                    ->label('Taxas')
                    ->prefix('R$')
                    ->numeric()
                    ->default('0.00')
                    ->required()
                    ->minValue(0)
                    ->step(0.01),

                Textarea::make('description')
                    ->label('Descrição')
                    ->maxLength(255),

                DatePicker::make('date')
                    ->label('Data da venda')
                    ->default(now())
                    ->native(false),
            ])
            ->action(function (array $data, $record): void {
                try {
                    app(SellService::class)->execute(
                        user: Auth::user(),
                        tickerId: (int) $data['ticker_id'],
                        quantity: (int) $data['quantity'],
                        unitPrice: (string) $data['unit_price'],
                        fees: (string) $data['fees'],
                        description: $data['description'] ?? null,
                        date: isset($data['date'])
                            ? Carbon::parse($data['date'])
                            : null,
                    );

                    Notification::make()
                        ->title('Venda realizada com sucesso!')
                        ->success()
                        ->send();

                    $record->refresh();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->title('Não foi possível realizar a venda.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
