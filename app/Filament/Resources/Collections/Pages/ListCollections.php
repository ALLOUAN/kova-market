<?php

namespace App\Filament\Resources\Collections\Pages;

use App\Filament\Resources\Collections\CollectionResource;
use App\Filament\Resources\Collections\CollectionStatus;
use App\Filament\Resources\Collections\Widgets\CollectionsOverview;
use App\Models\Collection;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCollections extends ListRecords
{
    protected static string $resource = CollectionResource::class;

    /**
     * The header band (CollectionsOverview) carries the title and the figures.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [CollectionsOverview::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Toutes'),
            'running' => Tab::make('En cours')->modifyQueryUsing(fn (Builder $query) => CollectionStatus::running($query)),
            'scheduled' => Tab::make('Programmées')->modifyQueryUsing(fn (Builder $query) => CollectionStatus::scheduled($query)),
            'over' => Tab::make('Offre terminée')->modifyQueryUsing(fn (Builder $query) => CollectionStatus::over($query))
                ->badge(fn () => CollectionStatus::over(Collection::query())->count() ?: null)->badgeColor('warning'),
            'empty' => Tab::make('Sans produit')->modifyQueryUsing(fn (Builder $query) => $query->doesntHave('products')),
        ];
    }
}
