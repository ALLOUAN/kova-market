<?php

namespace App\Filament\Resources\Coupons\Widgets;

use App\Filament\Resources\Coupons\CouponResource;
use App\Filament\Resources\Coupons\CouponStatus;
use App\Filament\Support\ListHeroWidget;
use App\Models\Coupon;

/**
 * Header of the promo codes: usable now, scheduled, expired or used up, switched off, and how often they served.
 */
class CouponsOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $usable = CouponStatus::usable(Coupon::query())->count();
        $public = CouponStatus::usable(Coupon::query())->where('is_public', true)->count();
        $scheduled = CouponStatus::scheduled(Coupon::query())->count();
        $over = CouponStatus::over(Coupon::query())->count();
        $off = Coupon::query()->where('is_active', false)->count();
        $uses = (int) Coupon::query()->sum('times_used');
        $tab = fn (string $tab) => CouponResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Codes promo',
            'icon' => 'heroicon-o-ticket',
            'lead' => '<strong>'.$usable.'</strong> code'.($usable > 1 ? 's' : '').' utilisable'.($usable > 1 ? 's' : '').' aujourd’hui, dont <strong>'.$public.'</strong> affiché'.($public > 1 ? 's' : '').' aux clients dans la fenêtre « Codes promo ».',
            'kpis' => [
                self::kpi('Utilisables', (string) $usable, $public.' public(s) · '.($usable - $public).' privé(s)',
                    'heroicon-o-check-circle', 'green', $tab('usable')),
                self::kpi('Programmés', (string) $scheduled, 'Valables à partir de leur date',
                    'heroicon-o-calendar-days', 'navy', $tab('scheduled')),
                self::kpi('Expirés ou épuisés', (string) $over, 'Date passée ou limite atteinte',
                    'heroicon-o-clock', 'gold', $tab('over')),
                self::kpi('Désactivés', (string) $off, 'Refusés au panier',
                    'heroicon-o-pause-circle', 'navy', $tab('off')),
                self::kpi('Utilisations', (string) $uses, 'Commandes passées avec un code',
                    'heroicon-o-receipt-percent', 'orange'),
            ],
        ];
    }
}
