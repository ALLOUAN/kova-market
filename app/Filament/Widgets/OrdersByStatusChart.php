<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Models\Order;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

/**
 * Where the orders of the last 30 days stand (F-109): gold for the ones waiting for the team, greys along the way,
 * green once delivered, red when cancelled.
 */
class OrdersByStatusChart extends ChartWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Commandes par statut';

    public function getDescription(): ?string
    {
        $total = Order::query()->where('created_at', '>=', today()->subDays(29))->count();

        return '30 derniers jours · '.($total > 0 ? $total.' '.($total > 1 ? 'commandes' : 'commande') : 'aucune commande');
    }

    protected ?string $maxHeight = '280px';

    private const COLORS = [
        'recue' => '#c69e3e', 'confirmee' => '#94a3b8', 'en_preparation' => '#64748b', 'expediee' => '#334155',
        'en_livraison' => '#021732', 'livree' => '#0e7d42', 'annulee' => '#c0362c',
    ];

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(Permission::ManageOrders->value);
    }

    protected function getData(): array
    {
        $counts = Order::query()->where('created_at', '>=', today()->subDays(29))
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $statuses = collect(OrderStatus::cases())->filter(fn (OrderStatus $status) => ($counts[$status->value] ?? 0) > 0)->values();

        return [
            'datasets' => [[
                'data' => $statuses->map(fn (OrderStatus $status) => (int) $counts[$status->value])->all(),
                'backgroundColor' => $statuses->map(fn (OrderStatus $status) => self::COLORS[$status->value])->all(),
                'borderWidth' => 0,
            ]],
            'labels' => $statuses->map(fn (OrderStatus $status) => $status->getLabel())->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                cutout: '62%',
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 14 } } },
                scales: { x: { display: false }, y: { display: false } },
            }
        JS);
    }
}
