<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Back-office home (F-109): a greeting band with the day in one sentence and the person's shortcuts, the day's
 * figures, "À faire maintenant", then the sales curve, the orders to handle, the best sellers and the stock to
 * order. Widgets: App\Filament\Widgets, each shown to the roles allowed to see it.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Tableau de bord';

    /**
     * The greeting band of DashboardOverview carries the title, the date and the shortcuts.
     */
    public function getHeading(): string
    {
        return '';
    }

    public function getColumns(): int|array
    {
        return ['md' => 2, 'xl' => 3];
    }
}
