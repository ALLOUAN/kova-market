<?php

namespace App\Console\Commands;

use App\Services\Newsletter\CampaignSender;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Launches the newsletter campaigns whose scheduled date has come; their e-mails then leave through the queue.
 */
#[Signature('newsletter:send-scheduled')]
#[Description('Launch the newsletter campaigns whose scheduled date has come')]
class SendScheduledCampaigns extends Command
{
    public function handle(CampaignSender $sender): int
    {
        $count = $sender->launchDue();

        $this->info("{$count} campagne(s) lancée(s).");

        return self::SUCCESS;
    }
}
