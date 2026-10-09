<?php

namespace App\Filament\Pages;

use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Filament\Resources\Couriers\CourierResource;
use App\Filament\Resources\DeliveryZones\DeliveryZoneResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Services\Delivery\DeliveryBoard;
use App\Services\Delivery\DeliveryDispatcher;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Delivery dashboard (Livraison › Tableau de bord): the day at a glance, the queue to dispatch with "Attribuer"
 * (DispatchQueue), what needs someone now, each courier's day and each zone's coverage. Money is on Ventes ›
 * Finances. The whole page refreshes every 30 seconds.
 */
class DeliveryDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = 'Livraison';

    protected static ?int $navigationSort = -10;

    protected static ?string $navigationLabel = 'Tableau de bord';

    protected static ?string $title = 'Tableau de bord des livraisons';

    protected static ?string $slug = 'livraisons';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::ManageDelivery->value);
    }

    public static function getNavigationBadge(): ?string
    {
        $waiting = app(DeliveryDispatcher::class)->unassigned()->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Commandes à attribuer';
    }

    /**
     * The page draws its own header band (title, date, live state, shortcuts).
     */
    public function getHeading(): string
    {
        return '';
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.pages.delivery-dashboard')->viewData(fn () => $this->board()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function board(): array
    {
        $board = app(DeliveryBoard::class);
        $orders = fn (array $filters = []) => OrderResource::getUrl('index', ['filters' => $filters]);

        return [
            'stats' => $board->stats(),
            'alerts' => $board->alerts(),
            'couriers' => $board->couriers(),
            'zones' => $board->zones(),
            'waitingHours' => DeliveryBoard::WAITING_ALERT_HOURS,
            'onTheWayHours' => DeliveryBoard::ON_THE_WAY_ALERT_HOURS,
            'links' => [
                'unassigned' => $orders(['unassigned' => ['isActive' => true]]),
                'on_the_way' => $orders(['status' => ['values' => [OrderStatus::OutForDelivery->value]]]),
                'to_prepare' => $orders(['status' => ['values' => [OrderStatus::Confirmed->value, OrderStatus::Preparing->value]]]),
                'interior' => $orders(['status' => ['values' => [OrderStatus::Shipped->value]], 'delivery_mode' => ['value' => 'interieur']]),
                'couriers' => CourierResource::getUrl(),
                'new_courier' => CourierResource::getUrl('create'),
                'zones' => DeliveryZoneResource::getUrl(),
                'new_zone' => DeliveryZoneResource::getUrl('create'),
                'finances' => Finances::canAccess() ? Finances::getUrl() : null,
            ],
            'orderUrl' => fn ($order) => OrderResource::getUrl('view', ['record' => $order]),
            'courierUrl' => fn ($courier) => CourierResource::getUrl('edit', ['record' => $courier]),
            'zoneUrl' => fn ($zone) => DeliveryZoneResource::getUrl('edit', ['record' => $zone]),
            'ordersOfCourierUrl' => fn ($courier) => $orders(['courier_id' => ['value' => $courier->user_id]]),
        ];
    }
}
