<?php

namespace App\Filament\Catalog\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\Product;
use App\Models\ProductVariant;
use Spatie\Activitylog\Models\Activity;

/**
 * Header of the catalogue import: the three steps in one sentence, then the catalogue's size and the last import
 * (from the audit log). Kept out of app/Filament/Widgets so that the dashboard does not pick it up.
 */
class ImportCatalogOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $last = Activity::query()->where('log_name', 'catalogue')->where('description', 'Import CSV du catalogue')->latest()->with('causer')->first();
        $report = $last?->properties ?? collect();

        return [
            'title' => 'Importer le catalogue',
            'icon' => 'heroicon-o-arrow-up-tray',
            'lead' => 'Téléchargez le modèle, remplissez-le (une ligne par SKU), analysez-le. <strong>Rien n’est enregistré</strong> avant votre confirmation.',
            'kpis' => [
                self::kpi('Produits au catalogue', (string) Product::query()->count(), Product::query()->where('is_active', true)->count().' en ligne',
                    'heroicon-o-cube', 'navy', ProductResource::getUrl()),
                self::kpi('Variantes (SKU)', (string) ProductVariant::query()->count(), 'Chaque ligne du fichier en crée ou en met à jour une',
                    'heroicon-o-qr-code', 'orange'),
                self::kpi('Dernier import', $last ? $last->created_at->translatedFormat('j M') : '—',
                    $last ? 'À '.$last->created_at->format('H:i').($last->causer ? ' par '.e($last->causer->name) : '') : 'Aucun import pour le moment',
                    'heroicon-o-clock', 'gold'),
                self::kpi('Lignes importées', $last ? (string) ($report['imported'] ?? 0) : '—',
                    $last ? ($report['products_created'] ?? 0).' produit(s) créé(s) · '.count($report['errors'] ?? []).' ligne(s) refusée(s)' : 'Lors du dernier import',
                    'heroicon-o-check-circle', $last && count($report['errors'] ?? []) > 0 ? 'red' : 'green'),
            ],
        ];
    }
}
