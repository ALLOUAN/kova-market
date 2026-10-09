<?php

namespace App\Filament\Resources\Couriers;

use App\Enums\Permission;
use App\Enums\RemittanceMethod;
use App\Models\Courier;
use App\Services\Delivery\CashSettlement;
use App\Services\Delivery\CourierAccounts;
use App\Services\Delivery\RemittanceException;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Account actions on a courier, from the list and from the courier page (F-122).
 */
class CourierActions
{
    /**
     * F-126: the courier hands over cash collected, all of it or part of it; the payment covers their oldest orders
     * first. The amount proposed is everything they owe.
     */
    public static function settleCash(): Action
    {
        $due = fn (Courier $record): int => app(CashSettlement::class)->due($record);

        return Action::make('settleCash')
            ->label('Enregistrer un versement')
            ->icon('heroicon-o-banknotes')
            ->color('warning')
            ->modalHeading(fn (Courier $record) => "Versement de {$record->name()}")
            ->modalDescription(fn (Courier $record) => 'Il doit encore '.Money::format($due($record)).'. Un versement partiel couvre ses commandes les plus anciennes, le reste reste dû.')
            ->modalSubmitActionLabel('Enregistrer le versement')
            // The list and the courier page load "cash_due" with the record.
            ->visible(fn (Courier $record) => (int) ($record->cash_due ?? $due($record)) > 0
                && (auth()->user()?->can(Permission::RecordRemittances->value) ?? false))
            ->schema(fn (Courier $record) => [
                TextInput::make('amount')
                    ->label('Montant reçu')
                    ->suffix('FCFA')
                    ->integer()
                    ->required()
                    ->minValue(1)
                    ->maxValue($due($record))
                    ->default($due($record)),
                Select::make('method')->label('Mode')->options(RemittanceMethod::class)->default(RemittanceMethod::Cash->value)->required(),
                DateTimePicker::make('received_at')->label('Reçu le')->default(now())->seconds(false)->required(),
                TextInput::make('reference')->label('Référence')->placeholder('N° de transaction Mobile Money ou de virement')->maxLength(100),
                Textarea::make('note')->label('Note')->rows(2)->maxLength(255),
            ])
            ->action(function (Courier $record, array $data, Action $action): void {
                try {
                    $remittance = app(CashSettlement::class)->record(
                        $record,
                        (int) $data['amount'],
                        $data['method'] instanceof RemittanceMethod ? $data['method'] : RemittanceMethod::from($data['method']),
                        auth()->user(),
                        filled($data['received_at'] ?? null) ? Carbon::parse($data['received_at']) : null,
                        $data['reference'] ?? null,
                        $data['note'] ?? null,
                    );
                } catch (RemittanceException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();
                    $action->halt();

                    return;
                }

                Notification::make()
                    ->title(Money::format($remittance->amount).' reçus de '.$record->name())
                    ->body($remittance->balance_after > 0 ? 'Reste à reverser : '.Money::format($remittance->balance_after) : 'Tout est reversé.')
                    ->success()
                    ->send();
            });
    }

    public static function resetPassword(): Action
    {
        return Action::make('resetPassword')
            ->label('Nouveau mot de passe')
            ->icon('heroicon-o-key')
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('Un mot de passe provisoire est envoyé par SMS au livreur, qui devra le changer à sa prochaine connexion.')
            ->visible(fn (Courier $record) => ! $record->isSuspended())
            ->action(function (Courier $record): void {
                app(CourierAccounts::class)->resetPassword($record, auth()->user());
                Notification::make()->title('Mot de passe provisoire envoyé par SMS')->success()->send();
            });
    }

    public static function suspend(): Action
    {
        return Action::make('suspend')
            ->label('Suspendre')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('Le livreur ne pourra plus se connecter. Ses livraisons en cours repassent dans la file de leur zone.')
            ->visible(fn (Courier $record) => ! $record->isSuspended())
            ->action(function (Courier $record): void {
                app(CourierAccounts::class)->suspend($record, auth()->user());
                Notification::make()->title("{$record->name()} est suspendu")->success()->send();
            });
    }

    public static function reactivate(): Action
    {
        return Action::make('reactivate')
            ->label('Réactiver')
            ->icon('heroicon-o-arrow-path')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (Courier $record) => $record->isSuspended())
            ->action(function (Courier $record): void {
                app(CourierAccounts::class)->reactivate($record, auth()->user());
                Notification::make()->title("{$record->name()} peut de nouveau livrer")->success()->send();
            });
    }
}
