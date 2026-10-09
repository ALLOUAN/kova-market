<?php

namespace App\Filament\Resources\Couriers\Pages;

use App\Filament\Resources\Couriers\CourierResource;
use App\Filament\Resources\Couriers\Widgets\CouriersOverview;
use App\Services\Delivery\CashSettlement;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListCouriers extends ListRecords
{
    protected static string $resource = CourierResource::class;

    /**
     * The header band (CouriersOverview) carries the title and the figures; its cards open these tabs.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [CouriersOverview::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous'),
            'available' => Tab::make('Disponibles')->modifyQueryUsing(fn (Builder $query) => $query->available()),
            'busy' => Tab::make('En course')->modifyQueryUsing(fn (Builder $query) => $query->available()->has('openOrders')),
            'owing' => Tab::make('Argent à reverser')->modifyQueryUsing(fn (Builder $query) => $query->whereHas('orders', fn (Builder $orders) => CashSettlement::pending($orders))),
            'suspended' => Tab::make('Suspendus')->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user', fn (Builder $user) => $user->whereNotNull('suspended_at'))),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Ajouter un livreur')->icon(Heroicon::OutlinedPlus),
        ];
    }
}
