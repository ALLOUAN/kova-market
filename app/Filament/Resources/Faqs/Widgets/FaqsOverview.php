<?php

namespace App\Filament\Resources\Faqs\Widgets;

use App\Filament\Resources\Faqs\FaqResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\Faq;

/**
 * Header of the frequently asked questions: published, hidden, and the topics they are grouped in.
 */
class FaqsOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $published = Faq::query()->where('is_published', true)->count();
        $hidden = Faq::query()->where('is_published', false)->count();
        $topics = Faq::query()->where('is_published', true)->distinct()->count('topic');
        $biggest = Faq::query()->where('is_published', true)->selectRaw('topic, count(*) as total')->groupBy('topic')->orderByDesc('total')->first();
        $tab = fn (string $tab) => FaqResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Questions fréquentes',
            'icon' => 'heroicon-o-question-mark-circle',
            'lead' => '<strong>'.$published.'</strong> question'.($published > 1 ? 's' : '').' en ligne sur la page FAQ, rangée'.($published > 1 ? 's' : '').' en <strong>'.$topics.'</strong> thème'.($topics > 1 ? 's' : '').'.',
            'kpis' => [
                self::kpi('Publiées', (string) $published, 'Sur la page « Questions fréquentes »',
                    'heroicon-o-check-circle', 'green', $tab('published')),
                self::kpi('Masquées', (string) $hidden, 'Pas affichées',
                    'heroicon-o-eye-slash', 'gold', $tab('hidden')),
                self::kpi('Thèmes', (string) $topics, $biggest ? 'Le plus fourni : '.e($biggest->topic).' ('.$biggest->total.')' : 'Aucun thème',
                    'heroicon-o-tag', 'navy'),
            ],
        ];
    }
}
