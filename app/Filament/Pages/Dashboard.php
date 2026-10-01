<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

/**
 * Back-office home (F-109): sales of the day, orders to handle, then the catalog. Widgets: App\Filament\Widgets,
 * each shown to the roles allowed to see it; the usual actions sit in the page header.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Tableau de bord';

    /**
     * @return int|array<string, ?int>
     */
    /** The day the figures are about. */
    public function getSubheading(): ?string
    {
        return ucfirst(now()->translatedFormat('l j F Y'));
    }

    public function getColumns(): int|array
    {
        return ['md' => 2, 'xl' => 3];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('storefront')
                ->label('Voir la boutique')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(route('home'), shouldOpenInNewTab: true),
            Action::make('newProduct')
                ->label('Ajouter un produit')
                ->icon(Heroicon::OutlinedPlus)
                ->url(fn () => ProductResource::getUrl('create'))
                ->visible(fn () => (bool) auth()->user()?->can(Permission::ManageCatalog->value)),
        ];
    }
}
