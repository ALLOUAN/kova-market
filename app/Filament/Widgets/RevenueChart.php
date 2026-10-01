<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use App\Services\Orders\SalesFigures;
use App\Support\Money;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/**
 * Revenue per day over 7, 30 or 90 days (F-109), in the logo's green, amounts in FCFA.
 */
class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 0;

    protected ?string $heading = 'Chiffre d’affaires';

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];

    protected ?string $maxHeight = '280px';

    public ?string $filter = '30';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(Permission::ManageOrders->value);
    }

    public function getDescription(): ?string
    {
        $days = app(SalesFigures::class)->daily((int) $this->filter);

        return Money::format($days->sum('revenue')).' · '.$days->sum('orders').' commandes';
    }

    protected function getFilters(): ?array
    {
        return ['7' => '7 derniers jours', '30' => '30 derniers jours', '90' => '90 derniers jours'];
    }

    protected function getData(): array
    {
        $days = app(SalesFigures::class)->daily((int) $this->filter);

        return [
            'datasets' => [[
                'label' => 'Chiffre d’affaires (FCFA)',
                'data' => $days->pluck('revenue')->values()->all(),
                'borderColor' => '#0e7d42',
                'backgroundColor' => 'rgba(14, 125, 66, 0.08)',
                'pointBackgroundColor' => '#0e7d42',
                'pointBorderColor' => '#0e7d42',
                'pointRadius' => 0,
                'pointHoverRadius' => 4,
                'fill' => true,
                'tension' => 0.35,
                'borderWidth' => 2,
            ]],
            'labels' => $days->keys()->map(fn (string $day) => Carbon::parse($day)->translatedFormat('j M'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (context) => ' ' + new Intl.NumberFormat('fr-FR').format(context.parsed.y) + ' FCFA' } },
                },
                scales: {
                    y: { beginAtZero: true, suggestedMax: 10000, ticks: { precision: 0, callback: (value) => new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(value) } },
                    x: { grid: { display: false } },
                },
            }
        JS);
    }
}
