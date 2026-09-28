<?php

namespace App\Filament\Resources\Couriers;

use App\Models\Courier;
use App\Services\Delivery\CashSettlement;
use App\Services\Delivery\CourierAccounts;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Account actions on a courier, from the list and from the courier page (F-122).
 */
class CourierActions
{
    /**
     * F-126: the courier hands over the cash collected; all their delivered orders not settled yet are marked.
     */
    public static function settleCash(): Action
    {
        return Action::make('settleCash')
            ->label('Encaissements reçus')
            ->icon('heroicon-o-banknotes')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(fn (Courier $record) => "Recevoir l’argent de {$record->name()}")
            ->modalDescription(fn (Courier $record) => 'Confirmez avoir reçu '.Money::format(app(CashSettlement::class)->due($record)).' pour '.CashSettlement::pendingOrders($record)->count().' livraison(s) payée(s) à la livraison.')
            // The list and the courier page load "cash_due" with the record.
            ->visible(fn (Courier $record) => (int) ($record->cash_due ?? app(CashSettlement::class)->due($record)) > 0)
            ->action(function (Courier $record): void {
                $amount = app(CashSettlement::class)->settle($record, auth()->user());
                Notification::make()->title(Money::format($amount).' reçus de '.$record->name())->success()->send();
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
