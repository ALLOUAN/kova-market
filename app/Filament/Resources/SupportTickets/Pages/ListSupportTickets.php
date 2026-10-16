<?php

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Enums\SupportTicketStatus;
use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Filament\Resources\SupportTickets\Widgets\SupportTicketsOverview;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;

    /**
     * The header band (SupportTicketsOverview) carries the title and the figures; its cards open these tabs.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [SupportTicketsOverview::class];
    }

    public function getTabs(): array
    {
        return [
            'open' => Tab::make('À traiter')->modifyQueryUsing(fn (Builder $query) => $query->where('status', SupportTicketStatus::Open)),
            'answered' => Tab::make('Répondues')->modifyQueryUsing(fn (Builder $query) => $query->where('status', SupportTicketStatus::Answered)),
            'closed' => Tab::make('Fermées')->modifyQueryUsing(fn (Builder $query) => $query->where('status', SupportTicketStatus::Closed)),
            'all' => Tab::make('Toutes'),
        ];
    }
}
