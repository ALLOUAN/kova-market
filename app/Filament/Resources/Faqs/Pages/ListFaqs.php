<?php

namespace App\Filament\Resources\Faqs\Pages;

use App\Filament\Resources\Faqs\FaqResource;
use App\Filament\Resources\Faqs\Widgets\FaqsOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListFaqs extends ListRecords
{
    protected static string $resource = FaqResource::class;

    /**
     * The header band (FaqsOverview) carries the title and the figures; its cards open these tabs.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [FaqsOverview::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Toutes'),
            'published' => Tab::make('Publiées')->modifyQueryUsing(fn (Builder $query) => $query->where('is_published', true)),
            'hidden' => Tab::make('Masquées')->modifyQueryUsing(fn (Builder $query) => $query->where('is_published', false)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Ajouter une question')->icon(Heroicon::OutlinedPlus),
        ];
    }
}
