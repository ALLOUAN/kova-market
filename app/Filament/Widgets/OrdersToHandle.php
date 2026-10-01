<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\Orders\SalesFigures;
use App\Support\Money;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Orders the team still has to handle (received, confirmed, in preparation), oldest first: a click opens the order.
 */
class OrdersToHandle extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Commandes à traiter';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(Permission::ViewOrders->value);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Order::query()
                ->whereIn('status', SalesFigures::TO_HANDLE)
                ->where(fn (Builder $query) => $query
                    ->where('payment_method', PaymentMethod::CashOnDelivery)
                    ->orWhere('payment_status', PaymentStatus::Paid))
                ->withCount('items')
                ->oldest())
            ->columns([
                TextColumn::make('number')->label('Commande')->weight('bold')->description(fn (Order $record) => $record->created_at->diffForHumans()),
                TextColumn::make('customer_name')->label('Client')->description(fn (Order $record) => $record->formattedPhone()),
                TextColumn::make('commune_name')->label('Livraison')->icon('heroicon-o-map-pin'),
                TextColumn::make('total')->label('Total')->formatStateUsing(fn (int $state) => Money::format($state))
                    ->description(fn (Order $record) => $record->payment_method->getLabel()),
                TextColumn::make('status')->label('Statut')->badge(),
            ])
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]))
            ->emptyStateIcon('heroicon-o-check-badge')
            ->emptyStateHeading('Aucune commande en attente')
            ->emptyStateDescription('Les nouvelles commandes apparaissent ici dès leur arrivée.')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
