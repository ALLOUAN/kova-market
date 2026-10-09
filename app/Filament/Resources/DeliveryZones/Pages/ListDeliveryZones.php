<?php

namespace App\Filament\Resources\DeliveryZones\Pages;

use App\Filament\Resources\DeliveryZones\DeliveryZoneResource;
use App\Filament\Resources\DeliveryZones\Widgets\DeliveryZonesOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListDeliveryZones extends ListRecords
{
    protected static string $resource = DeliveryZoneResource::class;

    /**
     * The header band (DeliveryZonesOverview) carries the title and the figures; its cards open these tabs.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [DeliveryZonesOverview::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Toutes'),
            'active' => Tab::make('Actives')->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),
            'inactive' => Tab::make('Désactivées')->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
            'free_shipping' => Tab::make('Livraison offerte')->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('free_shipping_threshold')),
            'no_courier' => Tab::make('Sans livreur')->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)->doesntHave('couriers')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Ajouter une zone')->icon(Heroicon::OutlinedPlus),
        ];
    }
}
