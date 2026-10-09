<?php

namespace App\Filament\Resources\Couriers;

use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Filament\Concerns\AuthorizesWithPermission;
use App\Filament\Resources\Couriers\Pages\CreateCourier;
use App\Filament\Resources\Couriers\Pages\EditCourier;
use App\Filament\Resources\Couriers\Pages\ListCouriers;
use App\Filament\Resources\Couriers\RelationManagers\DeliveriesRelationManager;
use App\Filament\Resources\Couriers\RelationManagers\RemittancesRelationManager;
use App\Filament\Resources\Couriers\Schemas\CourierForm;
use App\Filament\Resources\Couriers\Tables\CouriersTable;
use App\Models\Courier;
use App\Models\Order;
use App\Services\Delivery\CashSettlement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Courier accounts (F-122): created here only (no public sign-up), sign-in details sent by SMS, suspension.
 */
class CourierResource extends Resource
{
    use AuthorizesWithPermission;

    protected static ?string $model = Courier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Livraison';

    protected static ?string $modelLabel = 'livreur';

    protected static ?string $pluralModelLabel = 'livreurs';

    protected static function managePermission(): Permission
    {
        return Permission::ManageDelivery;
    }

    public static function getEloquentQuery(): Builder
    {
        // F-126: deliveries, failures (orders cancelled once given to the courier) and cash to hand over.
        return parent::getEloquentQuery()
            ->with(['user', 'zones'])
            ->withCount([
                'openOrders',
                'orders as delivered_count' => fn (Builder $query) => $query->where('status', OrderStatus::Delivered),
                'orders as failed_count' => fn (Builder $query) => $query->where('status', OrderStatus::Cancelled),
            ])
            // Cash still with the courier: delivered orders minus what was already handed over (partial payments too).
            ->addSelect(['cash_due' => Order::query()
                ->selectRaw(CashSettlement::owedSql())
                ->whereColumn('orders.courier_id', 'couriers.id')
                ->tap(fn (Builder $query) => CashSettlement::pending($query)),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return CourierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CouriersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DeliveriesRelationManager::class,
            RemittancesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCouriers::route('/'),
            'create' => CreateCourier::route('/create'),
            'edit' => EditCourier::route('/{record}/edit'),
        ];
    }
}
