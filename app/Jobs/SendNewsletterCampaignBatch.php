<?php

namespace App\Jobs;

use App\Models\NewsletterCampaign;
use App\Services\Newsletter\CampaignSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * One batch of a newsletter campaign (CampaignSender::BATCH_SIZE e-mails). Safe to retry: recipients already
 * handled are skipped.
 */
class SendNewsletterCampaignBatch implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    /**
     * @param  list<int>  $recipientIds
     */
    public function __construct(
        public readonly int $campaignId,
        public readonly array $recipientIds,
    ) {}

    public function handle(CampaignSender $sender): void
    {
        $campaign = NewsletterCampaign::find($this->campaignId);

        if ($campaign) {
            $sender->sendBatch($campaign, $this->recipientIds);
        }
    }
}
