<?php

namespace App\Filament\Resources\NewsletterCampaigns\Pages;

use App\Enums\CampaignStatus;
use App\Filament\Resources\NewsletterCampaigns\CampaignActions;
use App\Filament\Resources\NewsletterCampaigns\NewsletterCampaignResource;
use App\Models\NewsletterCampaign;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * A draft or scheduled campaign: written, previewed, tried on one address, then sent or scheduled. Once it has
 * left, its page is the follow-up (view).
 */
class EditNewsletterCampaign extends EditRecord
{
    protected static string $resource = NewsletterCampaignResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (! $this->getRecord()->isEditable()) {
            $this->redirect(NewsletterCampaignResource::getUrl('view', ['record' => $this->getRecord()]));
        }
    }

    public function getSubheading(): ?string
    {
        /** @var NewsletterCampaign $campaign */
        $campaign = $this->getRecord();

        return $campaign->status === CampaignStatus::Scheduled
            ? 'Programmée : envoi le '.$campaign->scheduled_at->translatedFormat('l j F à H\hi').'. Enregistrez vos modifications avant cette date.'
            : 'Brouillon : enregistrez, regardez l’aperçu, envoyez-vous un test, puis envoyez ou programmez.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CampaignActions::preview(),
            CampaignActions::sendTest(),
            CampaignActions::schedule(),
            CampaignActions::unschedule(),
            CampaignActions::sendNow(),
            ActionGroup::make([
                CampaignActions::duplicate(),
                DeleteAction::make(),
            ]),
        ];
    }
}
