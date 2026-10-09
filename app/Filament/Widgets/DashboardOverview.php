<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use App\Filament\Pages\DeliveryDashboard;
use App\Filament\Pages\Finances;
use App\Filament\Resources\NewsletterCampaigns\NewsletterCampaignResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Services\Admin\DashboardSummary;
use Filament\Widgets\Widget;
use Illuminate\Support\Str;

/**
 * Top of the back-office home (F-109): a greeting band with the day in one sentence and the person's shortcuts, the
 * day's key figures (for those who see the sales), and "À faire maintenant": only what needs someone, with its link.
 * Rendered with the page (not lazy) and refreshed every minute.
 */
class DashboardOverview extends Widget
{
    protected static ?int $sort = -10;

    protected static bool $isLazy = false;

    protected string $view = 'filament.dashboard.overview';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();
        $summary = app(DashboardSummary::class);
        $sales = $user->can(Permission::ManageOrders->value);

        return [
            'firstName' => Str::before($user->name, ' '),
            'showSales' => $sales,
            'figures' => $sales ? $summary->figures() : null,
            'todo' => $summary->todo($user),
            'shortcuts' => array_values(array_filter([
                ['label' => 'Voir la boutique', 'icon' => 'heroicon-o-arrow-top-right-on-square', 'url' => route('home'), 'external' => true, 'primary' => false],
                $user->can(Permission::ManageCatalog->value) ? ['label' => 'Ajouter un produit', 'icon' => 'heroicon-o-plus', 'url' => ProductResource::getUrl('create'), 'external' => false, 'primary' => true] : null,
                $user->can(Permission::ViewOrders->value) ? ['label' => 'Commandes', 'icon' => 'heroicon-o-shopping-bag', 'url' => OrderResource::getUrl(), 'external' => false, 'primary' => false] : null,
                DeliveryDashboard::canAccess() ? ['label' => 'Livraisons', 'icon' => 'heroicon-o-truck', 'url' => DeliveryDashboard::getUrl(), 'external' => false, 'primary' => false] : null,
                Finances::canAccess() ? ['label' => 'Finances', 'icon' => 'heroicon-o-chart-bar-square', 'url' => Finances::getUrl(), 'external' => false, 'primary' => false] : null,
                $user->can(Permission::ManagePromotions->value) ? ['label' => 'Campagne e-mail', 'icon' => 'heroicon-o-megaphone', 'url' => NewsletterCampaignResource::getUrl('create'), 'external' => false, 'primary' => false] : null,
            ])),
        ];
    }

    /**
     * "+200 %" against yesterday, or null when yesterday had nothing.
     */
    public static function trend(int $now, int $before): ?string
    {
        if ($before === 0) {
            return null;
        }

        $change = (int) round(($now - $before) / $before * 100);

        return ($change > 0 ? '+' : ($change < 0 ? '−' : '')).abs($change).' %';
    }

    /**
     * SVG points of a small 7-day curve, 100 × 28.
     *
     * @param  list<int>  $values
     */
    public static function sparkline(array $values): string
    {
        $max = max(1, ...$values);
        $step = count($values) > 1 ? 100 / (count($values) - 1) : 100;

        return collect($values)->map(fn (int $value, int $i) => round($i * $step, 1).','.round(26 - $value / $max * 24, 1))->join(' ');
    }
}
