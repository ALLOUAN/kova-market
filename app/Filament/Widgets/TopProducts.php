<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use App\Filament\Pages\Finances;
use App\Services\Admin\DashboardSummary;
use Filament\Widgets\Widget;

/**
 * Best sellers of the last 30 days by revenue, with their share of the first one as a bar.
 */
class TopProducts extends Widget
{
    protected static ?int $sort = 3;

    protected string $view = 'filament.dashboard.top-products';

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 1];

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(Permission::ManageOrders->value);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $products = app(DashboardSummary::class)->topProducts();

        return [
            'products' => $products,
            'max' => max(1, (int) $products->max('revenue')),
            'total' => (int) $products->sum('revenue'),
            'financesUrl' => Finances::canAccess() ? Finances::getUrl() : null,
        ];
    }
}
