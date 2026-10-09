<?php

namespace App\Filament\Resources\Coupons\Pages;

use App\Filament\Resources\Coupons\CouponResource;
use App\Filament\Resources\Coupons\CouponStatus;
use App\Filament\Resources\Coupons\Widgets\CouponsOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListCoupons extends ListRecords
{
    protected static string $resource = CouponResource::class;

    /**
     * The header band (CouponsOverview) carries the title and the figures; its cards open these tabs.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [CouponsOverview::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous'),
            'usable' => Tab::make('Utilisables')->modifyQueryUsing(fn (Builder $query) => CouponStatus::usable($query)),
            'scheduled' => Tab::make('Programmés')->modifyQueryUsing(fn (Builder $query) => CouponStatus::scheduled($query)),
            'over' => Tab::make('Expirés ou épuisés')->modifyQueryUsing(fn (Builder $query) => CouponStatus::over($query)),
            'off' => Tab::make('Désactivés')->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Ajouter un code promo')->icon(Heroicon::OutlinedPlus),
        ];
    }
}
