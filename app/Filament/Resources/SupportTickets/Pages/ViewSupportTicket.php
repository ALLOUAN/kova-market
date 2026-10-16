<?php

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Filament\Resources\SupportTickets\ReplyAction;
use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Models\SupportTicket;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->title;
    }

    protected function getHeaderActions(): array
    {
        /** @var SupportTicket $ticket */
        $ticket = $this->getRecord();
        $phone = $ticket->user?->phone;

        return [
            ReplyAction::make(),
            Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon('heroicon-o-phone')
                ->color('gray')
                ->url($phone ? 'https://wa.me/'.ltrim($phone, '+') : null, shouldOpenInNewTab: true)
                ->visible(filled($phone)),
            ReplyAction::toggleClosed(),
        ];
    }
}
