<?php

namespace App\Filament\Resources\NewsletterSubscribers\Widgets;

use App\Filament\Resources\NewsletterCampaigns\NewsletterCampaignResource;
use App\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\NewsletterSubscriber;

/**
 * Header of the newsletter subscribers: active, new this month, where they signed up, unsubscribed.
 */
class NewsletterSubscribersOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $active = NewsletterSubscriber::query()->whereNull('unsubscribed_at')->count();
        $thisMonth = NewsletterSubscriber::query()->whereNull('unsubscribed_at')->where('subscribed_at', '>=', now()->startOfMonth())->count();
        $lastMonth = NewsletterSubscriber::query()->whereBetween('subscribed_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->startOfMonth()->subSecond()])->count();
        $popup = NewsletterSubscriber::query()->whereNull('unsubscribed_at')->where('source', 'popup')->count();
        $unsubscribed = NewsletterSubscriber::query()->whereNotNull('unsubscribed_at')->count();
        $tab = fn (string $tab) => NewsletterSubscriberResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Newsletter',
            'icon' => 'heroicon-o-envelope-open',
            'lead' => '<strong>'.$active.'</strong> abonné'.($active > 1 ? 's' : '').' reçoivent vos campagnes e-mail. Chaque e-mail contient un lien de désinscription.',
            'kpis' => [
                self::kpi('Abonnés', (string) $active, 'Reçoivent les campagnes',
                    'heroicon-o-users', 'navy', $tab('active')),
                self::kpi('Nouveaux ce mois', (string) $thisMonth, 'Mois dernier : '.$lastMonth.self::trend($thisMonth, $lastMonth),
                    'heroicon-o-user-plus', 'orange', $tab('active')),
                self::kpi('Par la fenêtre d’invitation', (string) $popup, ($active - $popup).' par le pied de page',
                    'heroicon-o-window', 'gold'),
                self::kpi('Désinscrits', (string) $unsubscribed, 'Ne reçoivent plus rien',
                    'heroicon-o-user-minus', 'navy', $tab('unsubscribed')),
                self::kpi('Campagnes', 'Écrire', 'Envoyer un e-mail à tous les abonnés',
                    'heroicon-o-megaphone', 'green', NewsletterCampaignResource::getUrl('create')),
            ],
        ];
    }
}
