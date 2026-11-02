<?php

namespace App\Filament\Resources\Wallets\Actions;

use App\Models\Wallet;
use App\Services\Wallet\DepositService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Throwable;

class DepositAction
{
    public static function make(): Action
    {
        return Action::make('deposit')
            ->label('Depositar')
            ->icon('heroicon-o-plus-circle')
            ->color('success')
            ->modalHeading('Realizar depósito')
            ->modalDescription('Adicione saldo à sua carteira.')
            ->modalSubmitActionLabel('Confirmar depósito')
            ->schema([
                TextInput::make('amount')
                    ->label('Valor do depósito')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->step('0.01')
                    ->prefix('R$'),

                DatePicker::make('date')
                    ->label('Data do depósito')
                    ->native(false)
                    ->default(now())
                    ->required(),

                Textarea::make('description')
                    ->label('Descrição')
                    ->placeholder('Ex.: Aporte mensal')
                    ->rows(2)
                    ->maxLength(255),
            ])
            ->action(function (array $data, Wallet $record): void {
                try {
                    $user = Auth::user();

                    abort_unless(
                        $user && $record->user_id === $user->id,
                        403
                    );

                    app(DepositService::class)->execute(
                        user: $user,
                        amount: (string) $data['amount'],
                        description: $data['description'] ?? null,
                        date: $data['date'] ?? null,
                    );

                    Notification::make()
                        ->title('Depósito realizado!')
                        ->body('O saldo da carteira foi atualizado.')
                        ->success()
                        ->send();

                } catch (Throwable $exception) {
                    Notification::make()
                        ->title('Não foi possível realizar o depósito')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
