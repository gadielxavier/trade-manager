<?php

namespace App\Filament\Resources\Wallets\Pages;

use App\Filament\Resources\Wallets\WalletResource;
use App\Models\Ticker;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\DepositService;
use App\Services\Wallet\PurchaseService;
use App\Services\Wallet\SellService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;

class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->depositAction(),
            $this->purchaseAction(),
            $this->sellAction(),
        ];
    }

    private function depositAction(): Action
    {
        return Action::make('deposit')
            ->label('Aportar dinheiro')
            ->icon('heroicon-o-plus-circle')
            ->color('success')
            ->schema([
                TextInput::make('amount')
                    ->label('Valor do aporte')
                    ->required()
                    ->rule('regex:/^\d{1,13}(?:\.\d{1,2})?$/')
                    ->placeholder('1000.00'),

                Textarea::make('description')
                    ->label('Descrição')
                    ->rows(2),

                DatePicker::make('date')
                    ->label('Data do aporte')
                    ->default(now()->toDateString())
                    ->required()
                    ->native(false),
            ])
            ->action(function (array $data): void {
                try {
                    app(DepositService::class)->execute(
                        user: $this->currentUser(),
                        amount: $data['amount'],
                        description: $data['description'] ?? null,
                        date: Carbon::parse($data['date']),
                    );

                    Notification::make()
                        ->title('Aporte realizado com sucesso!')
                        ->success()
                        ->send();

                    $this->redirect(
                        WalletResource::getUrl('index')
                    );
                } catch (InvalidArgumentException | RuntimeException $exception) {
                    Notification::make()
                        ->title('Não foi possível realizar o aporte')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    private function purchaseAction(): Action
    {
        return Action::make('purchase')
            ->label('Comprar ações')
            ->icon('heroicon-o-arrow-trending-up')
            ->color('info')
            ->schema([
                Select::make('ticker_id')
                    ->label('Ativo')
                    ->options(fn (): array => Ticker::query()
                        ->orderBy('code')
                        ->pluck('code', 'id')
                        ->all())
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
                    ->required()
                    ->rule('regex:/^\d{1,11}(?:\.\d{1,4})?$/')
                    ->placeholder('30.0000'),

                TextInput::make('fees')
                    ->label('Taxas')
                    ->default('0.00')
                    ->required()
                    ->rule('regex:/^\d{1,13}(?:\.\d{1,2})?$/'),

                Select::make('funding_source')
                    ->label('Origem dos recursos')
                    ->options([
                        'deposit' => 'Dinheiro de aporte',
                        'cash' => 'Dinheiro disponível no caixa',
                    ])
                    ->default('cash')
                    ->required(),

                Textarea::make('description')
                    ->label('Descrição')
                    ->rows(2),

                DatePicker::make('date')
                    ->label('Data da compra')
                    ->default(now()->toDateString())
                    ->required()
                    ->native(false),
            ])
            ->action(function (array $data): void {
                try {
                    app(PurchaseService::class)->execute(
                        user: $this->currentUser(),
                        tickerId: (int) $data['ticker_id'],
                        quantity: (int) $data['quantity'],
                        unitPrice: $data['unit_price'],
                        fundingSource: $data['funding_source'],
                        fees: $data['fees'],
                        description: $data['description'] ?? null,
                        date: Carbon::parse($data['date']),
                    );

                    Notification::make()
                        ->title('Compra realizada com sucesso!')
                        ->success()
                        ->send();

                    $this->redirect(
                        WalletResource::getUrl('index')
                    );
                } catch (InvalidArgumentException | RuntimeException $exception) {
                    Notification::make()
                        ->title('Não foi possível realizar a compra')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    private function sellAction(): Action
    {
        return Action::make('sell')
            ->label('Vender ações')
            ->icon('heroicon-o-arrow-trending-down')
            ->color('danger')
            ->schema([
                Select::make('ticker_id')
                    ->label('Ativo')
                    ->options(fn (): array => Wallet::query()
                        ->where('user_id', Auth::id())
                        ->first()
                        ?->positions()
                        ->with('ticker')
                        ->get()
                        ->mapWithKeys(fn ($position) => [
                            $position->ticker_id => $position->ticker->code,
                        ])
                        ->all() ?? [])
                    ->searchable()
                    ->required(),

                TextInput::make('quantity')
                    ->label('Quantidade')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->required(),

                TextInput::make('unit_price')
                    ->label('Preço unitário de venda')
                    ->required()
                    ->rule('regex:/^\d{1,11}(?:\.\d{1,4})?$/')
                    ->placeholder('35.0000'),

                TextInput::make('fees')
                    ->label('Taxas')
                    ->default('0.00')
                    ->required()
                    ->rule('regex:/^\d{1,13}(?:\.\d{1,2})?$/'),

                Textarea::make('description')
                    ->label('Descrição')
                    ->rows(2),

                DatePicker::make('date')
                    ->label('Data da venda')
                    ->default(now()->toDateString())
                    ->required()
                    ->native(false),
            ])
            ->action(function (array $data): void {
                try {
                    app(SellService::class)->execute(
                        user: $this->currentUser(),
                        tickerId: (int) $data['ticker_id'],
                        quantity: (int) $data['quantity'],
                        unitPrice: $data['unit_price'],
                        fees: $data['fees'],
                        description: $data['description'] ?? null,
                        date: Carbon::parse($data['date']),
                    );

                    Notification::make()
                        ->title('Venda realizada com sucesso!')
                        ->success()
                        ->send();

                    $this->redirect(
                        WalletResource::getUrl('index')
                    );
                } catch (InvalidArgumentException | RuntimeException $exception) {
                    Notification::make()
                        ->title('Não foi possível realizar a venda')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
