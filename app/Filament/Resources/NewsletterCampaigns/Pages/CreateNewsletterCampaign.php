<?php

namespace App\Filament\Resources\NewsletterCampaigns\Pages;

use App\Filament\Resources\NewsletterCampaigns\NewsletterCampaignResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * A campaign starts as a draft; once saved, its page offers the preview, the test and the sending.
 */
class CreateNewsletterCampaign extends CreateRecord
{
    protected static string $resource = NewsletterCampaignResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'created_by' => auth()->id()];
    }

    protected function getRedirectUrl(): string
    {
        return NewsletterCampaignResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
