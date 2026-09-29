<?php

namespace App\Filament\Resources\Payments;

use App\Enums\Permission;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Services\Payments\CinetPayException;
use App\Services\Payments\OnlinePayments;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

/**
 * The actions on one payment, shared by the payments space and the order's payments tab: ask CinetPay where an open
 * payment stands, and record a refund made in CinetPay's merchant space (F-067).
 */
class PaymentActions
{
    public static function check(): Action
    {
        return Action::make('check')
            ->label('Vérifier')
            ->icon('heroicon-o-arrow-path')
            ->visible(fn (Payment $record) => $record->status->isOpen())
            ->action(function (Payment $record): void {
                try {
                    $record = app(OnlinePayments::class)->synchronize($record, 'back-office');
                    Notification::make()->title("Statut CinetPay : {$record->status->getLabel()}")->success()->send();
                } catch (CinetPayException $exception) {
                    Notification::make()->title('CinetPay ne répond pas')->body($exception->getMessage())->warning()->send();
                }
            });
    }

    public static function refund(): Action
    {
        return Action::make('refund')
            ->label('Enregistrer un remboursement')
            ->icon('heroicon-o-receipt-refund')
            ->color('danger')
            ->visible(fn (Payment $record) => $record->status === TransactionStatus::Succeeded && (auth()->user()?->can(Permission::ManageOrders->value) ?? false))
            ->modalDescription('Faites d’abord le remboursement dans l’espace marchand CinetPay, puis enregistrez-le ici : la commande passe en « Remboursé ».')
            ->schema([Textarea::make('reason')->label('Motif')->required()->maxLength(255)])
            ->action(function (Payment $record, array $data): void {
                app(OnlinePayments::class)->recordRefund($record, auth()->user(), $data['reason']);
                Notification::make()->title('Remboursement enregistré')->success()->send();
            });
    }
}
