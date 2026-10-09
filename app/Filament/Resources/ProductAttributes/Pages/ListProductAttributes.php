<?php

namespace App\Filament\Resources\ProductAttributes\Pages;

use App\Filament\Resources\ProductAttributes\ProductAttributeResource;
use App\Filament\Resources\ProductAttributes\Widgets\ProductAttributesOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListProductAttributes extends ListRecords
{
    protected static string $resource = ProductAttributeResource::class;

    /**
     * The header band (ProductAttributesOverview) carries the title and the figures.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [ProductAttributesOverview::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous'),
            'used' => Tab::make('Utilisés')->modifyQueryUsing(fn (Builder $query) => $query->whereHas('values.variants')),
            'unused' => Tab::make('Inutilisés')->modifyQueryUsing(fn (Builder $query) => $query->whereDoesntHave('values.variants')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Ajouter un attribut')->icon(Heroicon::OutlinedPlus),
        ];
    }
}
