<?php

namespace App\Filament\Resources\Testimonials\Pages;

use App\Filament\Resources\Testimonials\TestimonialResource;
use App\Filament\Resources\Testimonials\Widgets\TestimonialsOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListTestimonials extends ListRecords
{
    protected static string $resource = TestimonialResource::class;

    /**
     * The header band (TestimonialsOverview) carries the title and the figures; its cards open these tabs.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [TestimonialsOverview::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous'),
            'published' => Tab::make('Publiés')->modifyQueryUsing(fn (Builder $query) => $query->where('is_published', true)),
            'verified' => Tab::make('Vérifiés')->modifyQueryUsing(fn (Builder $query) => $query->where('is_verified', true)),
            'hidden' => Tab::make('Masqués')->modifyQueryUsing(fn (Builder $query) => $query->where('is_published', false)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Ajouter un témoignage')->icon(Heroicon::OutlinedPlus),
        ];
    }
}
