<?php

namespace App\Filament\Resources\Banners\Pages;

use App\Filament\Resources\Banners\BannerResource;
use App\Filament\Resources\Banners\Widgets\BannersOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListBanners extends ListRecords
{
    protected static string $resource = BannerResource::class;

    /**
     * The header band (BannersOverview) carries the title and the figures; its cards open these tabs.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [BannersOverview::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Toutes'),
            'live' => Tab::make('En ligne')->modifyQueryUsing(fn (Builder $query) => $query->live()),
            'scheduled' => Tab::make('Programmées')->modifyQueryUsing(fn (Builder $query) => $query->where('is_visible', true)->where('starts_at', '>', now())),
            'expired' => Tab::make('Expirées')->modifyQueryUsing(fn (Builder $query) => $query->where('ends_at', '<', now())),
            'hidden' => Tab::make('Masquées')->modifyQueryUsing(fn (Builder $query) => $query->where('is_visible', false)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Ajouter une bannière')->icon(Heroicon::OutlinedPlus),
        ];
    }
}
