<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\TransactionStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Payments\PaymentActions;
use App\Models\Payment;
use App\Support\Money;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('order'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Lancé le')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('order.number')
                    ->label('Commande')
                    ->weight('bold')
                    ->searchable()
                    ->url(fn (Payment $record) => $record->order ? OrderResource::getUrl('view', ['record' => $record->order]) : null),
                TextColumn::make('order.customer_name')
                    ->label('Client')
                    ->searchable()
                    ->description(fn (Payment $record) => $record->order?->formattedPhone()),
                TextColumn::make('amount')->label('Montant')->formatStateUsing(fn (int $state) => Money::format($state))->sortable(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('operator')->label('Moyen')->placeholder('—'),
                TextColumn::make('merchant_transaction_id')->label('Référence KOVA')->searchable()->copyable()->fontFamily('mono'),
                TextColumn::make('gateway_transaction_id')
                    ->label('Référence CinetPay')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payer_phone')->label('Téléphone payeur')->searchable()->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('failure_reason')->label('Motif')->placeholder('—')->wrap()->toggleable(),
                TextColumn::make('paid_at')->label('Payé le')->dateTime('d/m/Y H:i')->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(TransactionStatus::class)->multiple(),
                SelectFilter::make('operator')
                    ->label('Moyen de paiement')
                    ->options(fn () => Payment::query()->whereNotNull('operator')->distinct()->orderBy('operator')->pluck('operator', 'operator')->all()),
                Filter::make('to_refund')
                    ->label('À rembourser (commande annulée)')
                    ->query(fn (Builder $query) => $query->toRefund()),
                Filter::make('period')
                    ->schema([
                        DatePicker::make('from')->label('Du'),
                        DatePicker::make('until')->label('Au'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make()->label('Ouvrir'),
                ActionGroup::make([
                    PaymentActions::check(),
                    PaymentActions::refund(),
                ]),
            ])
            ->emptyStateHeading('Aucun paiement en ligne')
            ->emptyStateDescription('Les paiements CinetPay apparaissent ici dès qu’un client choisit le paiement en ligne.');
    }
}
