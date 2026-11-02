<?php

namespace App\Filament\Resources\Wallets\Actions;

use App\Models\Ticker;
use App\Models\Wallet;
use App\Services\Wallet\PurchaseService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Throwable;

class PurchaseAction
{
    public static function make(): Action
    {
        return Action::make('purchase')
            ->label('Comprar ativos')
            ->icon('heroicon-o-shopping-cart')
            ->color('primary')
            ->modalHeading('Comprar ativos')
            ->modalDescription('Registre uma compra de ativos na carteira.')
            ->modalSubmitActionLabel('Confirmar compra')
            ->schema([
                Select::make('ticker_id')
                    ->label('Ativo')
                    ->options(fn () => Ticker::query()
                        ->orderBy('code')
                        ->pluck('code', 'id'))
                    ->searchable()
                    ->required(),

                TextInput::make('quantity')
                    ->label('Quantidade')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->required(),

                TextInput::make('unit_price')
                    ->label('Preço unitário')
                    ->numeric()
                    ->minValue(0.0001)
                    ->step('0.0001')
                    ->prefix('R$')
                    ->required(),

                TextInput::make('fees')
                    ->label('Taxas')
                    ->numeric()
                    ->minValue(0)
                    ->step('0.01')
                    ->prefix('R$')
                    ->default('0.00')
                    ->required(),

                Select::make('funding_source')
                    ->label('Origem dos recursos')
                    ->options([
                        'deposit' => 'Saldo de depósitos',
                        'cash' => 'Caixa disponível',
                    ])
                    ->default('deposit')
                    ->required(),

                DatePicker::make('date')
                    ->label('Data da compra')
                    ->native(false)
                    ->default(now())
                    ->required(),

                Textarea::make('description')
                    ->label('Descrição')
                    ->placeholder('Ex.: Compra de ações')
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

                    app(PurchaseService::class)->execute(
                        user: $user,
                        tickerId: (int) $data['ticker_id'],
                        quantity: (int) $data['quantity'],
                        unitPrice: (string) $data['unit_price'],
                        fundingSource: $data['funding_source'],
                        fees: (string) $data['fees'],
                        description: $data['description'] ?? null,
                        date: $data['date'] ?? null,
                    );

                    Notification::make()
                        ->title('Compra realizada!')
                        ->body('A posição e o saldo foram atualizados.')
                        ->success()
                        ->send();

                } catch (Throwable $exception) {
                    Notification::make()
                        ->title('Não foi possível realizar a compra')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
