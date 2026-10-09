<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Pages\Widgets\PagesOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    /**
     * The header band (PagesOverview) carries the title and the figures; its cards open these tabs.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [PagesOverview::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Toutes'),
            'published' => Tab::make('Publiées')->modifyQueryUsing(fn (Builder $query) => $query->where('is_published', true)),
            'drafts' => Tab::make('Brouillons')->modifyQueryUsing(fn (Builder $query) => $query->where('is_published', false)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Ajouter une page')->icon(Heroicon::OutlinedPlus),
        ];
    }
}
