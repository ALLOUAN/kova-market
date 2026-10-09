<?php

namespace App\Filament\Resources\Couriers\RelationManagers;

use App\Enums\Permission;
use App\Enums\RemittanceMethod;
use App\Filament\Resources\Couriers\CourierActions;
use App\Models\CourierRemittance;
use App\Services\Delivery\CashSettlement;
use App\Services\Delivery\RemittanceException;
use App\Services\Delivery\RemittanceReceipt;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Cash a courier handed over to the store (F-126): each payment with its receipt; a payment recorded by mistake is
 * cancelled with its reason, never deleted.
 */
class RemittancesRelationManager extends RelationManager
{
    protected static string $relationship = 'remittances';

    protected static ?string $title = 'Versements';

    protected static ?string $modelLabel = 'versement';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can(Permission::ViewFinances->value);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['receivedBy', 'cancelledBy']))
            ->defaultSort('received_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('N°')
                    ->formatStateUsing(fn (CourierRemittance $record) => $record->number())
                    ->weight('bold'),
                TextColumn::make('received_at')->label('Reçu le')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('amount')
                    ->label('Montant')
                    ->formatStateUsing(fn (int $state) => Money::format($state))
                    ->description(fn (CourierRemittance $record) => $record->balance_after === null ? null : 'Reste ensuite : '.Money::format($record->balance_after)),
                TextColumn::make('method')->label('Mode')->badge()->color('gray'),
                TextColumn::make('reference')->label('Référence')->placeholder('—')->toggleable(),
                TextColumn::make('receivedBy.name')->label('Reçu par')->placeholder('—'),
                TextColumn::make('cancelled_at')
                    ->label('État')
                    ->badge()
                    ->state(fn (CourierRemittance $record) => $record->isCancelled() ? 'Annulé' : 'Valide')
                    ->color(fn (string $state) => $state === 'Annulé' ? 'danger' : 'success')
                    ->description(fn (CourierRemittance $record) => $record->isCancelled()
                        ? "{$record->cancel_reason} ({$record->cancelledBy?->name}, {$record->cancelled_at->format('d/m/Y')})"
                        : $record->note),
            ])
            ->filters([
                SelectFilter::make('method')->label('Mode')->options(RemittanceMethod::class),
                TernaryFilter::make('cancelled')
                    ->label('État')
                    ->placeholder('Tous')
                    ->trueLabel('Annulés')
                    ->falseLabel('Valides')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('cancelled_at'),
                        false: fn (Builder $query) => $query->whereNull('cancelled_at'),
                    ),
            ])
            ->headerActions([
                CourierActions::settleCash()->record(fn () => $this->getOwnerRecord()),
            ])
            ->recordActions([
                Action::make('receipt')
                    ->label('Reçu')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(function (CourierRemittance $record): StreamedResponse {
                        $receipt = app(RemittanceReceipt::class);
                        $pdf = $receipt->pdf($record);

                        return response()->streamDownload(fn () => print ($pdf), $receipt->filename($record), ['Content-Type' => 'application/pdf']);
                    }),
                Action::make('cancel')
                    ->label('Annuler')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (CourierRemittance $record) => ! $record->isCancelled() && (auth()->user()?->can(Permission::RecordRemittances->value) ?? false))
                    ->modalHeading(fn (CourierRemittance $record) => "Annuler le versement {$record->number()}")
                    ->modalDescription('Le versement reste dans l’historique, marqué annulé. Les commandes qu’il couvrait sont de nouveau dues par le livreur.')
                    ->modalSubmitActionLabel('Annuler le versement')
                    ->schema([Textarea::make('reason')->label('Motif')->required()->maxLength(255)])
                    ->action(function (CourierRemittance $record, array $data, Action $action): void {
                        try {
                            app(CashSettlement::class)->cancel($record, $data['reason'], auth()->user());
                        } catch (RemittanceException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                            $action->halt();

                            return;
                        }

                        Notification::make()->title("Versement {$record->number()} annulé")->success()->send();
                    }),
            ])
            ->emptyStateHeading('Aucun versement pour le moment');
    }
}
