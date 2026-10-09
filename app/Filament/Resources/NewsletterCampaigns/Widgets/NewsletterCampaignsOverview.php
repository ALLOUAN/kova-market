<?php

namespace App\Filament\Resources\NewsletterCampaigns\Widgets;

use App\Enums\CampaignStatus;
use App\Filament\Resources\NewsletterCampaigns\NewsletterCampaignResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;

/**
 * Header of the e-mail campaigns: drafts, scheduled, sending, sent and e-mails delivered, each card opening the
 * matching tab.
 */
class NewsletterCampaignsOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $count = fn (CampaignStatus ...$statuses) => NewsletterCampaign::query()->whereIn('status', $statuses)->count();
        $drafts = $count(CampaignStatus::Draft);
        $scheduled = $count(CampaignStatus::Scheduled);
        $sending = $count(CampaignStatus::Sending);
        $sent = $count(CampaignStatus::Sent);
        $emails = (int) NewsletterCampaign::query()->sum('sent_count');
        $failed = (int) NewsletterCampaign::query()->sum('failed_count');
        $audience = NewsletterSubscriber::query()->whereNull('unsubscribed_at')->count();
        $next = NewsletterCampaign::query()->where('status', CampaignStatus::Scheduled)->orderBy('scheduled_at')->first();
        $tab = fn (string $tab) => NewsletterCampaignResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Campagnes e-mail',
            'icon' => 'heroicon-o-megaphone',
            'lead' => 'Un e-mail écrit ici part à vos <strong>'.$audience.'</strong> abonné'.($audience > 1 ? 's' : '').' à la newsletter'
                .($next ? ' · prochaine campagne le <strong>'.$next->scheduled_at->translatedFormat('j F à H:i').'</strong>.' : '.'),
            'kpis' => [
                self::kpi('Brouillons', (string) $drafts, 'À terminer avant l’envoi',
                    'heroicon-o-pencil-square', 'navy', $tab('draft')),
                self::kpi('Programmées', (string) $scheduled, $next ? 'Prochaine : '.e($next->subject) : 'Aucune pour le moment',
                    'heroicon-o-calendar-days', 'gold', $tab('scheduled')),
                self::kpi('En cours d’envoi', (string) $sending, 'Par lots, en arrière-plan',
                    'heroicon-o-paper-airplane', 'orange', $tab('sending'), $sending > 0 ? 'orange' : null),
                self::kpi('Envoyées', (string) $sent, number_format($emails, 0, ',', ' ').' e-mail(s) remis'.($failed > 0 ? ' · '.$failed.' en échec' : ''),
                    'heroicon-o-check-circle', 'green', $tab('sent')),
            ],
        ];
    }
}
