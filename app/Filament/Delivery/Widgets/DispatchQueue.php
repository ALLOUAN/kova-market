<?php

namespace App\Filament\Delivery\Widgets;

use App\Enums\Permission;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Courier;
use App\Models\Order;
use App\Services\Delivery\DeliveryBoard;
use App\Services\Delivery\DeliveryDispatcher;
use App\Services\Delivery\DispatchException;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Carbon;

/**
 * Orders waiting for a courier, oldest first, with their zone and how long they have been waiting; "Attribuer"
 * gives one to a courier (those of its zone first) without leaving the dashboard.
 */
class DispatchQueue extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'À attribuer';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(Permission::ManageDelivery->value);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => app(DeliveryDispatcher::class)->unassigned()
                ->with('commune.zone')
                ->addSelect(['orders.*', 'moved_at' => DeliveryBoard::lastMoveQuery()])
                ->orderBy('moved_at'))
            ->poll('30s')
            ->description('Commandes d’Abidjan sans livreur. Les livreurs de la zone les voient aussi dans leur application.')
            ->columns([
                TextColumn::make('number')
                    ->label('Commande')
                    ->weight('bold')
                    ->description(fn (Order $record) => $record->status->getLabel()),
                TextColumn::make('moved_at')
                    ->label('En attente depuis')
                    ->state(fn (Order $record) => match (true) {
                        ! $record->moved_at => '—',
                        Carbon::parse($record->moved_at)->gt(now()->subMinute()) => 'À l’instant',
                        default => Carbon::parse($record->moved_at)->diffForHumans(short: true, syntax: Carbon::DIFF_ABSOLUTE),
                    })
                    ->color(fn (Order $record) => $record->moved_at && Carbon::parse($record->moved_at)->lte(now()->subHours(DeliveryBoard::WAITING_ALERT_HOURS)) ? 'danger' : null)
                    ->weight(fn (Order $record) => $record->moved_at && Carbon::parse($record->moved_at)->lte(now()->subHours(DeliveryBoard::WAITING_ALERT_HOURS)) ? 'bold' : null),
                TextColumn::make('commune_name')
                    ->label('Destination')
                    ->icon('heroicon-o-map-pin')
                    ->description(fn (Order $record) => $record->zone_name),
                TextColumn::make('customer_name')->label('Client')->description(fn (Order $record) => $record->formattedPhone()),
                TextColumn::make('total')
                    ->label('À encaisser')
                    ->state(fn (Order $record) => $record->amountToCollect())
                    ->formatStateUsing(fn (int $state) => $state > 0 ? Money::format($state) : 'Déjà payée'),
            ])
            ->recordActions([
                Action::make('assign')
                    ->label('Attribuer')
                    ->icon('heroicon-o-truck')
                    ->button()
                    ->size('sm')
                    ->visible(fn () => auth()->user()?->can(Permission::ManageOrders->value) ?? false)
                    ->modalHeading(fn (Order $record) => "Confier {$record->number} à un livreur")
                    ->modalSubmitActionLabel('Confier')
                    ->schema(fn (Order $record) => [
                        Select::make('courier_id')
                            ->label('Livreur')
                            ->helperText('Les livreurs de la zone de la commande sont proposés en premier.')
                            ->options(fn () => app(DeliveryDispatcher::class)->courierOptions($record))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Order $record, array $data): void {
                        try {
                            app(DeliveryDispatcher::class)->assign($record, Courier::findOrFail($data['courier_id']), auth()->user());
                        } catch (DispatchException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();

                            return;
                        }

                        Notification::make()->title("Livraison confiée à {$record->fresh()->courier->name()}")->success()->send();
                    }),
            ])
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]))
            ->emptyStateIcon('heroicon-o-check-badge')
            ->emptyStateHeading('Toutes les commandes ont un livreur')
            ->emptyStateDescription('Les commandes confirmées sans livreur apparaissent ici.')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10);
    }
}
