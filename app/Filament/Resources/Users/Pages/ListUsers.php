<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Widgets\UsersOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    /**
     * The header band (UsersOverview) carries the title and the figures; its cards open these tabs.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [UsersOverview::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous'),
            'active' => Tab::make('Actifs')->modifyQueryUsing(fn (Builder $query) => $query->whereNull('suspended_at')),
            'suspended' => Tab::make('Suspendus')->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('suspended_at')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Ajouter un membre')->icon(Heroicon::OutlinedPlus),
        ];
    }
}
