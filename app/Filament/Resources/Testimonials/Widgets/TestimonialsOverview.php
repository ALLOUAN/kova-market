<?php

namespace App\Filament\Resources\Testimonials\Widgets;

use App\Filament\Resources\Testimonials\TestimonialResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\Testimonial;

/**
 * Header of the customer testimonials of the home page: published, hidden, average rating and verified customers.
 */
class TestimonialsOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $published = Testimonial::query()->where('is_published', true)->count();
        $hidden = Testimonial::query()->where('is_published', false)->count();
        $verified = Testimonial::query()->where('is_published', true)->where('is_verified', true)->count();
        $average = Testimonial::query()->where('is_published', true)->avg('rating');
        $stars = $average ? (int) round($average) : 0;
        $tab = fn (string $tab) => TestimonialResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Témoignages clients',
            'icon' => 'heroicon-o-chat-bubble-left-right',
            'lead' => '<strong>'.$published.'</strong> témoignage'.($published > 1 ? 's' : '').' affiché'.($published > 1 ? 's' : '').' sur la page d’accueil.',
            'kpis' => [
                self::kpi('Publiés', (string) $published, 'Sur la page d’accueil',
                    'heroicon-o-check-circle', 'green', $tab('published')),
                self::kpi('Note moyenne', $average ? number_format((float) $average, 1, ',', ' ').' / 5' : '—', $average ? str_repeat('★', $stars).str_repeat('☆', 5 - $stars) : 'Aucun témoignage publié',
                    'heroicon-o-star', 'gold'),
                self::kpi('Clients vérifiés', (string) $verified, 'Avec la mention « Achat vérifié »',
                    'heroicon-o-check-badge', 'orange', $tab('verified')),
                self::kpi('Masqués', (string) $hidden, 'Pas affichés',
                    'heroicon-o-eye-slash', 'navy', $tab('hidden')),
            ],
        ];
    }
}
