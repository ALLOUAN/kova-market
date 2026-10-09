<?php

namespace App\Filament\Resources\NewsletterCampaigns\Pages;

use App\Enums\CampaignStatus;
use App\Filament\Resources\NewsletterCampaigns\NewsletterCampaignResource;
use App\Filament\Resources\NewsletterCampaigns\Widgets\NewsletterCampaignsOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListNewsletterCampaigns extends ListRecords
{
    protected static string $resource = NewsletterCampaignResource::class;

    /**
     * The header band (NewsletterCampaignsOverview) carries the title and the figures; its cards open these tabs.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [NewsletterCampaignsOverview::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Toutes'),
            'draft' => Tab::make('Brouillons')->modifyQueryUsing(fn (Builder $query) => $query->where('status', CampaignStatus::Draft)),
            'scheduled' => Tab::make('Programmées')->modifyQueryUsing(fn (Builder $query) => $query->where('status', CampaignStatus::Scheduled)),
            'sending' => Tab::make('En cours d’envoi')->modifyQueryUsing(fn (Builder $query) => $query->where('status', CampaignStatus::Sending)),
            'sent' => Tab::make('Envoyées')->modifyQueryUsing(fn (Builder $query) => $query->where('status', CampaignStatus::Sent)),
            'cancelled' => Tab::make('Annulées')->modifyQueryUsing(fn (Builder $query) => $query->where('status', CampaignStatus::Cancelled)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nouvelle campagne')->icon(Heroicon::OutlinedPlus),
        ];
    }
}
