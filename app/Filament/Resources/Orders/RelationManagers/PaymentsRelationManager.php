<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Filament\Resources\Payments\PaymentActions;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use App\Support\Money;
use Filament\Actions\Action;
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
                PaymentActions::check(),
                PaymentActions::refund(),
                Action::make('details')
                    ->label('Détails')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Payment $record) => PaymentResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('Aucun paiement lancé')
            ->emptyStateDescription('Le client n’a pas encore été redirigé vers CinetPay.');
    }
}
