<?php

namespace App\Filament\Resources\Activities\Pages;

use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Activities\Widgets\ActivitiesOverview;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    /**
     * The header band (ActivitiesOverview) carries the title and the figures; its cards open these tabs.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [ActivitiesOverview::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tout'),
            'today' => Tab::make('Aujourd’hui')->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', today())),
            'week' => Tab::make('7 derniers jours')->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(7))),
        ];
    }
}
