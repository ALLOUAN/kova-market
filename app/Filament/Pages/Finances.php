<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Filament\Resources\Couriers\CourierResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Services\Finance\FinancePeriod;
use App\Services\Finance\FinanceReport;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Finance dashboard (lot 3): orders, takings online and on delivery, delivery fees, per zone and per courier, over a
 * chosen period compared with the one before. Every figure comes from FinanceReport; the export gives the same ones.
 */
class Finances extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Ventes';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Finances';

    protected static ?string $title = 'Finances';

    protected static ?string $slug = 'finances';

    /** @var array{period?: string, from?: ?string, to?: ?string} */
    public ?array $filters = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::ViewFinances->value);
    }

    /**
     * The page draws its own header band: title, period chosen, shortcuts and export.
     */
    public function getHeading(): string
    {
        return '';
    }

    public function mount(): void
    {
        $this->filters = ['period' => 'month', 'from' => null, 'to' => null];
    }

    /**
     * A ready-made period from the header band; "custom" keeps the chosen dates (this month by default).
     */
    public function choosePeriod(string $preset): void
    {
        if (! array_key_exists($preset, FinancePeriod::PRESETS)) {
            return;
        }

        $this->filters['period'] = $preset;

        if ($preset === 'custom' && blank($this->filters['from'] ?? null)) {
            $this->filters['from'] = now()->startOfMonth()->toDateString();
            $this->filters['to'] = now()->toDateString();
        }
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.pages.finances')->viewData(fn () => $this->report()),
        ]);
    }

    /**
     * "Exporter (CSV)" of the header band: the figures of the page for a spreadsheet.
     */
    public function exportAction(): Action
    {
        return Action::make('export')
            ->label('Exporter (CSV)')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(fn (): StreamedResponse => $this->export());
    }

    public function period(): FinancePeriod
    {
        return FinancePeriod::make($this->filters['period'] ?? 'month', $this->filters['from'] ?? null, $this->filters['to'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function report(): array
    {
        $report = app(FinanceReport::class);
        $period = $this->period();
        $series = $report->series($period);
        $ordersUrl = fn (array $filters = []) => OrderResource::getUrl('index', ['filters' => [
            'period' => ['from' => $period->from->toDateString(), 'until' => $period->to->toDateString()],
            ...$filters,
        ]]);

        return [
            'presets' => FinancePeriod::PRESETS,
            'preset' => $this->filters['period'] ?? 'month',
            'period' => $period,
            'summary' => $report->summary($period),
            'before' => $report->summary($period->previous()),
            'zones' => $report->byZone($period),
            'couriers' => $report->byCourier($period),
            'series' => $series,
            'seriesMax' => max(1, (int) $series->max('revenue')),
            'ordersUrl' => $ordersUrl,
            'couriersUrl' => CourierResource::getUrl(),
            'courierUrl' => fn ($courier) => CourierResource::getUrl('edit', ['record' => $courier]),
        ];
    }

    /**
     * The figures of the page, for a spreadsheet. Semicolons and a BOM so that Excel opens it as is.
     */
    private function export(): StreamedResponse
    {
        $report = app(FinanceReport::class);
        $period = $this->period();
        $summary = $report->summary($period);

        return response()->streamDownload(function () use ($report, $period, $summary): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}");
            $line = fn (array $cells) => fputcsv($out, $cells, ';');

            $line(['KOVA MARKET · Finances', $period->label, 'du '.$period->from->format('d/m/Y').' au '.$period->to->format('d/m/Y')]);
            $line([]);
            $line(['Indicateur', 'Valeur']);
            foreach ([
                'Commandes passées' => $summary['orders'],
                'Commandes livrées' => $summary['delivered'],
                'Commandes en cours' => $summary['in_progress'],
                'Commandes annulées' => $summary['cancelled'],
                'Chiffre d’affaires (FCFA)' => $summary['revenue'],
                'dont produits (FCFA)' => $summary['products'],
                'dont frais de livraison (FCFA)' => $summary['shipping_fees'],
                'Encaissé en ligne, remboursements déduits (FCFA)' => $summary['collected_online'],
                'Remboursements (FCFA)' => $summary['refunds'],
                'Encaissé à la livraison (FCFA)' => $summary['collected_on_delivery'],
                'Total encaissé (FCFA)' => $summary['collected'],
                'Versé par les livreurs (FCFA)' => $summary['remitted'],
                'Argent encore chez les livreurs, aujourd’hui (FCFA)' => $summary['cash_with_couriers'],
            ] as $label => $value) {
                $line([$label, $value]);
            }

            $line([]);
            $line(['Zone', 'Commandes', 'Chiffre d’affaires (FCFA)', 'Frais de livraison (FCFA)']);
            foreach ($report->byZone($period) as $zone) {
                $line([$zone['zone'], $zone['orders'], $zone['revenue'], $zone['shipping_fees']]);
            }

            $line([]);
            $line(['Livreur', 'Livraisons', 'Échecs', 'Réussite (%)', 'Frais de ses livraisons (FCFA)', 'Encaissé (FCFA)', 'Versé (FCFA)', 'Reste à reverser aujourd’hui (FCFA)']);
            foreach ($report->byCourier($period) as $row) {
                $line([$row['courier']->name(), $row['delivered'], $row['failed'], $row['success_rate'] ?? '', $row['shipping_fees'], $row['collected'], $row['remitted'], $row['due']]);
            }

            fclose($out);
        }, 'finances-'.$period->from->format('Y-m-d').'-'.$period->to->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * "+12 %" against the previous period, or null when there is nothing to compare with.
     */
    public static function trend(int $now, int $before): ?string
    {
        if ($before === 0) {
            return null;
        }

        $change = (int) round(($now - $before) / $before * 100);

        return ($change > 0 ? '+' : ($change < 0 ? '−' : '')).abs($change).' %';
    }

    public static function money(int $amount): string
    {
        return Money::format($amount);
    }
}
