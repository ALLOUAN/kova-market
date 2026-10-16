<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\SupportTicket;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupportTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Conversation')
                    ->columnSpan(2)
                    ->schema([
                        ViewEntry::make('messages')
                            ->hiddenLabel()
                            ->view('filament.support-tickets.thread'),
                    ]),
                Section::make('Demande')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('status')->label('État')->badge(),
                        TextEntry::make('reference')->label('Référence')->state(fn (SupportTicket $record) => $record->reference()),
                        TextEntry::make('subject')->label('Sujet')->badge()->color('gray'),
                        TextEntry::make('order.number')
                            ->label('Commande')
                            ->placeholder('Aucune')
                            ->url(fn (SupportTicket $record) => $record->order ? OrderResource::getUrl('view', ['record' => $record->order]) : null),
                        TextEntry::make('user.name')->label('Client')->weight('bold'),
                        TextEntry::make('user.phone')
                            ->label('Téléphone')
                            ->placeholder('—')
                            ->url(fn (SupportTicket $record) => $record->user?->phone ? 'tel:'.$record->user->phone : null),
                        TextEntry::make('user.email')->label('E-mail')->placeholder('Pas d’e-mail : la réponse n’est visible que dans son espace client')->copyable(),
                        TextEntry::make('created_at')->label('Ouverte le')->dateTime('d/m/Y à H:i'),
                        TextEntry::make('closed_at')->label('Fermée le')->dateTime('d/m/Y à H:i')->placeholder('Pas encore'),
                    ]),
            ]);
    }
}
