<?php

namespace App\Filament\Resources\SupportTickets\Widgets;

use App\Enums\SupportTicketStatus;
use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\SupportTicket;

/**
 * Header of the assistance requests: waiting for the team (the oldest one's age), answered, closed this month.
 */
class SupportTicketsOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $open = SupportTicket::where('status', SupportTicketStatus::Open)->count();
        $oldest = SupportTicket::where('status', SupportTicketStatus::Open)->min('last_message_at');
        $answered = SupportTicket::where('status', SupportTicketStatus::Answered)->count();
        $closed = SupportTicket::where('status', SupportTicketStatus::Closed)->where('closed_at', '>=', now()->startOfMonth())->count();
        $tab = fn (string $tab) => SupportTicketResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Assistance',
            'icon' => 'heroicon-o-lifebuoy',
            'lead' => $open > 0
                ? '<strong>'.$open.'</strong> demande'.($open > 1 ? 's' : '').' attend'.($open > 1 ? 'ent' : '').' une réponse de l’équipe.'
                : 'Aucune demande en attente : tous les clients ont leur réponse.',
            'kpis' => [
                self::kpi('À traiter', (string) $open, $oldest ? 'La plus ancienne : '.now()->parse($oldest)->diffForHumans() : 'Rien en attente',
                    'heroicon-o-inbox', 'orange', $tab('open'), $open > 0 ? 'gold' : null),
                self::kpi('Répondues', (string) $answered, 'En attente du client',
                    'heroicon-o-chat-bubble-left-right', 'green', $tab('answered')),
                self::kpi('Fermées ce mois-ci', (string) $closed, 'Problème résolu',
                    'heroicon-o-check-circle', 'navy', $tab('closed')),
            ],
        ];
    }
}
