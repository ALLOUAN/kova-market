<?php

namespace App\Filament\Resources\Promotions\Pages;

use App\Filament\Resources\Promotions\PromotionResource;
use App\Filament\Resources\Promotions\Widgets\PromotionsOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListPromotions extends ListRecords
{
    protected static string $resource = PromotionResource::class;

    protected static ?string $title = 'Offres spéciales';

    /**
     * The header band (PromotionsOverview) carries the title, the explanation and the figures.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [PromotionsOverview::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Toutes'),
            'current' => Tab::make('En cours')->modifyQueryUsing(fn (Builder $query) => $query->where('is_visible', true)->where('ends_at', '>=', now())
                ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))),
            'coming' => Tab::make('À venir')->modifyQueryUsing(fn (Builder $query) => $query->where('is_visible', true)->where('starts_at', '>', now())),
            'over' => Tab::make('Terminées')->modifyQueryUsing(fn (Builder $query) => $query->where('ends_at', '<', now())),
            'hidden' => Tab::make('Masquées')->modifyQueryUsing(fn (Builder $query) => $query->where('is_visible', false)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Ajouter une offre')->icon(Heroicon::OutlinedPlus),
        ];
    }
}
