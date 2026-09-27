<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Filament\Concerns\AuthorizesWithPermission;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Orders (F-106): list, search, details, status changes and order slip. Orders are never created,
 * edited or deleted by hand.
 */
class OrderResource extends Resource
{
    use AuthorizesWithPermission;

    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|UnitEnum|null $navigationGroup = 'Ventes';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'commande';

    protected static ?string $pluralModelLabel = 'commandes';

    protected static ?string $recordTitleAttribute = 'number';

    protected static function managePermission(): Permission
    {
        return Permission::ManageOrders;
    }

    protected static function viewPermission(): Permission
    {
        return Permission::ViewOrders;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * New orders waiting to be confirmed.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = Order::where('status', OrderStatus::Received)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
