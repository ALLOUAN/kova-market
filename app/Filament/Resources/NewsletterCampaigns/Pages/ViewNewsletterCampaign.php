<?php

namespace App\Filament\Resources\NewsletterCampaigns\Pages;

use App\Filament\Resources\NewsletterCampaigns\CampaignActions;
use App\Filament\Resources\NewsletterCampaigns\NewsletterCampaignResource;
use Filament\Resources\Pages\ViewRecord;

/**
 * Follow-up of a campaign that has left: progress, sent, failed and skipped, refreshed while it goes out (the progress block polls).
 */
class ViewNewsletterCampaign extends ViewRecord
{
    protected static string $resource = NewsletterCampaignResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->subject;
    }

    protected function getHeaderActions(): array
    {
        return [
            CampaignActions::preview(),
            CampaignActions::stop(),
            CampaignActions::duplicate(),
        ];
    }
}
