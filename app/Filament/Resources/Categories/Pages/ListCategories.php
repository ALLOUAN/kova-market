<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Toutes')
                ->badge(Category::count()),
            'roots' => Tab::make('Rayons principaux')
                ->badge(Category::whereNull('parent_id')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('parent_id')),
            'children' => Tab::make('Sous-catégories')
                ->badge(Category::whereNotNull('parent_id')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('parent_id')),
        ];
    }
}
