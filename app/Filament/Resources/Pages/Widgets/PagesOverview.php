<?php

namespace App\Filament\Resources\Pages\Widgets;

use App\Filament\Resources\Pages\PageResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\Page;

/**
 * Header of the content pages (terms of sale, privacy…): published, drafts and the last one changed.
 */
class PagesOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $published = Page::query()->where('is_published', true)->count();
        $drafts = Page::query()->where('is_published', false)->count();
        $last = Page::query()->latest('updated_at')->first(['title', 'updated_at']);
        $tab = fn (string $tab) => PageResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Pages',
            'icon' => 'heroicon-o-document-text',
            'lead' => 'Les pages d’information de la boutique (CGV, confidentialité, livraison…) : <strong>'.$published.'</strong> publiée'.($published > 1 ? 's' : '').', reliées depuis le pied de page.',
            'kpis' => [
                self::kpi('Publiées', (string) $published, 'Visibles sur la boutique',
                    'heroicon-o-check-circle', 'green', $tab('published')),
                self::kpi('Brouillons', (string) $drafts, 'Pas encore visibles',
                    'heroicon-o-pencil-square', 'gold', $tab('drafts')),
                self::kpi('Dernière modification', $last ? $last->updated_at->translatedFormat('j M') : '—', $last ? e($last->title) : 'Aucune page',
                    'heroicon-o-clock', 'navy'),
            ],
        ];
    }
}
