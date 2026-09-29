<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Enums\Permission;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Services\Payments\CinetPayException;
use App\Services\Payments\OnlinePayments;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Online payment attempts of an order (F-066 journal): what CinetPay answered, a manual status check, and the
 * refund recorded once made in CinetPay's merchant space (F-067).
 */
class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Paiements en ligne';

    protected static ?string $modelLabel = 'paiement';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->payment_method->isOnline() || $ownerRecord->payments()->exists();
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('merchant_transaction_id')
            ->columns([
                TextColumn::make('created_at')->label('Lancé le')->dateTime('d/m/Y H:i'),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('amount')->label('Montant')->formatStateUsing(fn (int $state) => Money::format($state)),
                TextColumn::make('operator')->label('Moyen')->placeholder('—'),
                TextColumn::make('merchant_transaction_id')->label('Référence KOVA')->copyable()->fontFamily('mono'),
                TextColumn::make('gateway_transaction_id')->label('Référence CinetPay')->copyable()->placeholder('—')->fontFamily('mono'),
                TextColumn::make('failure_reason')->label('Motif')->placeholder('—')->wrap(),
                TextColumn::make('paid_at')->label('Payé le')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            ->recordActions([
                Action::make('check')
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
                    }),
                Action::make('refund')
                    ->label('Enregistrer un remboursement')
                    ->icon('heroicon-o-receipt-refund')
                    ->color('danger')
                    ->visible(fn (Payment $record) => $record->status === TransactionStatus::Succeeded && (auth()->user()?->can(Permission::ManageOrders->value) ?? false))
                    ->modalDescription('Faites d’abord le remboursement dans l’espace marchand CinetPay, puis enregistrez-le ici : la commande passe en « Remboursé ».')
                    ->schema([Textarea::make('reason')->label('Motif')->required()->maxLength(255)])
                    ->action(function (Payment $record, array $data): void {
                        app(OnlinePayments::class)->recordRefund($record, auth()->user(), $data['reason']);
                        Notification::make()->title('Remboursement enregistré')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Aucun paiement lancé')
            ->emptyStateDescription('Le client n’a pas encore été redirigé vers CinetPay.');
    }
}
