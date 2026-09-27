<?php

namespace App\Filament\Resources\Couriers;

use App\Models\Courier;
use App\Services\Delivery\CourierAccounts;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Account actions on a courier, from the list and from the courier page (F-122).
 */
class CourierActions
{
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
