<?php

namespace App\Filament\Resources\Bundles\Pages;

use App\Filament\Resources\Bundles\BundleResource;
use App\Filament\Resources\Bundles\Widgets\BundlesOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListBundles extends ListRecords
{
    protected static string $resource = BundleResource::class;

    /**
     * The header band (BundlesOverview) carries the title and the figures; its cards open these tabs.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [BundlesOverview::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous'),
            'online' => Tab::make('En ligne')->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),
            'offline' => Tab::make('Hors ligne')->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
            'sold_out' => Tab::make('En rupture')->modifyQueryUsing(fn (Builder $query) => $query->where('stock', 0)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Ajouter un pack')->icon(Heroicon::OutlinedPlus),
        ];
    }
}
