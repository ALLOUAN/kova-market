<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Widgets\CustomersOverview;
use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    /**
     * The header band (CustomersOverview) carries the title and the figures.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [CustomersOverview::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous'),
            'accounts' => Tab::make('Avec un compte')->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('user_id')),
            'guests' => Tab::make('Invités')->modifyQueryUsing(fn (Builder $query) => $query->whereNull('user_id')),
            'new' => Tab::make('Nouveaux ce mois')->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->startOfMonth())),
            'loyal' => Tab::make('Fidèles')->modifyQueryUsing(fn (Builder $query) => $query->where('orders_count', '>=', 2)),
        ];
    }

    /**
     * CSV export of the customers shown (search and filters applied), recorded in the audit log (F-108).
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Exporter (CSV)')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function (): StreamedResponse {
                    $customers = $this->getFilteredSortedTableQuery()->get();

                    activity('clients')
                        ->causedBy(auth()->user())
                        ->withProperties([
                            'lignes' => $customers->count(),
                            'recherche' => $this->getTableSearch(),
                            'filtres' => array_filter($this->tableFilters ?? [], fn ($filter) => filled($filter['value'] ?? null)),
                        ])
                        ->log('Export CSV des clients');

                    return response()->streamDownload(function () use ($customers): void {
                        $out = fopen('php://output', 'w');
                        // BOM and ";" so that Excel (French settings) opens it with the accents and columns right.
                        fwrite($out, "\u{FEFF}");
                        fputcsv($out, ['Nom', 'Téléphone', 'E-mail', 'Type', 'Commandes', 'Total dépensé (FCFA)', 'Dernière commande', 'Client depuis'], ';');

                        $customers->each(fn (Customer $customer) => fputcsv($out, [
                            $customer->name,
                            $customer->formattedPhone(),
                            $customer->email,
                            $customer->isGuest() ? 'Invité' : 'Compte',
                            $customer->orders_count,
                            $customer->total_spent,
                            $customer->last_order_at?->format('d/m/Y H:i'),
                            $customer->created_at?->format('d/m/Y'),
                        ], ';'));

                        fclose($out);
                    }, 'clients-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
                }),
        ];
    }
}
