<?php

namespace App\Services\Newsletter;

use App\Enums\CampaignStatus;
use App\Jobs\SendNewsletterCampaignBatch;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterCampaignRecipient;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Sends newsletter campaigns. At launch the active subscribers are frozen as the campaign's recipients, then the
 * e-mails leave through the queue in batches (SendNewsletterCampaignBatch), each recipient once, unsubscribes
 * made meanwhile skipped. A campaign can be tested on one address, scheduled, or stopped while going out.
 */
class CampaignSender
{
    /** E-mails per queued job: small enough to finish within the minute the hosting gives the queue. */
    public const BATCH_SIZE = 50;

    public function sendTest(NewsletterCampaign $campaign, string $email): void
    {
        Mail::to($email)->send(new NewsletterCampaignMail($campaign));
    }

    public function schedule(NewsletterCampaign $campaign, CarbonInterface $at): void
    {
        $this->ensureEditable($campaign);

        $campaign->forceFill(['status' => CampaignStatus::Scheduled, 'scheduled_at' => $at])->save();
    }

    public function unschedule(NewsletterCampaign $campaign): void
    {
        if ($campaign->status === CampaignStatus::Scheduled) {
            $campaign->forceFill(['status' => CampaignStatus::Draft, 'scheduled_at' => null])->save();
        }
    }

    /**
     * Freezes the recipients and queues the e-mails; returns the number of recipients.
     */
    public function launch(NewsletterCampaign $campaign, ?User $by = null): int
    {
        $count = DB::transaction(function () use ($campaign): int {
            $campaign = NewsletterCampaign::whereKey($campaign->getKey())->lockForUpdate()->firstOrFail();
            $this->ensureEditable($campaign);

            $now = now();
            NewsletterSubscriber::query()->active()->select('id')->chunkById(500, function ($subscribers) use ($campaign, $now): void {
                NewsletterCampaignRecipient::insert($subscribers->map(fn (NewsletterSubscriber $subscriber) => [
                    'newsletter_campaign_id' => $campaign->getKey(),
                    'newsletter_subscriber_id' => $subscriber->getKey(),
                    'status' => NewsletterCampaignRecipient::PENDING,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });

            $count = $campaign->recipients()->count();
            $campaign->forceFill([
                'status' => CampaignStatus::Sending,
                'started_at' => $now,
                'recipients_count' => $count,
            ])->save();

            return $count;
        });

        activity('newsletter')->performedOn($campaign)->causedBy($by)
            ->withProperties(['destinataires' => $count])
            ->log("Campagne « {$campaign->subject} » lancée");

        $campaign->recipients()->where('status', NewsletterCampaignRecipient::PENDING)->select('id')
            ->chunkById(self::BATCH_SIZE, fn ($recipients) => SendNewsletterCampaignBatch::dispatch($campaign->getKey(), $recipients->modelKeys()));

        $this->finishIfDone($campaign->fresh());

        return $count;
    }

    /**
     * Stops a campaign going out: the e-mails not sent yet are skipped.
     */
    public function stop(NewsletterCampaign $campaign, ?User $by = null): void
    {
        if ($campaign->status !== CampaignStatus::Sending) {
            return;
        }

        DB::transaction(function () use ($campaign): void {
            $skipped = $campaign->recipients()->where('status', NewsletterCampaignRecipient::PENDING)
                ->update(['status' => NewsletterCampaignRecipient::SKIPPED, 'error' => 'Envoi arrêté', 'updated_at' => now()]);
            $campaign->increment('skipped_count', $skipped);
            $campaign->forceFill(['status' => CampaignStatus::Cancelled, 'sent_at' => now()])->save();
        });

        activity('newsletter')->performedOn($campaign)->causedBy($by)->log("Campagne « {$campaign->subject} » arrêtée");
    }

    /**
     * Sends one batch of a campaign (called by the queued job). A recipient already handled is left alone, so a
     * retried batch never sends twice.
     *
     * @param  list<int>  $recipientIds
     */
    public function sendBatch(NewsletterCampaign $campaign, array $recipientIds): void
    {
        $recipients = $campaign->recipients()->with('subscriber')
            ->whereKey($recipientIds)
            ->where('status', NewsletterCampaignRecipient::PENDING)
            ->get();

        foreach ($recipients as $recipient) {
            // Stopped meanwhile: the rest is already marked skipped.
            if ($campaign->fresh()->status !== CampaignStatus::Sending) {
                return;
            }

            $subscriber = $recipient->subscriber;

            if (! $subscriber?->isActive()) {
                $this->mark($campaign, $recipient, NewsletterCampaignRecipient::SKIPPED, 'Désinscrit avant l’envoi');

                continue;
            }

            try {
                Mail::to($subscriber->email)->send(new NewsletterCampaignMail($campaign, $subscriber));
                $this->mark($campaign, $recipient, NewsletterCampaignRecipient::SENT);
            } catch (\Throwable $exception) {
                report($exception);
                $this->mark($campaign, $recipient, NewsletterCampaignRecipient::FAILED, mb_substr($exception->getMessage(), 0, 255));
            }
        }

        $this->finishIfDone($campaign->fresh());
    }

    /**
     * Campaigns whose date has come; returns how many were launched.
     */
    public function launchDue(): int
    {
        $due = NewsletterCampaign::query()->where('status', CampaignStatus::Scheduled)->where('scheduled_at', '<=', now())->get();
        $due->each(fn (NewsletterCampaign $campaign) => $this->launch($campaign));

        return $due->count();
    }

    private function mark(NewsletterCampaign $campaign, NewsletterCampaignRecipient $recipient, string $status, ?string $error = null): void
    {
        $recipient->forceFill(['status' => $status, 'error' => $error, 'sent_at' => $status === NewsletterCampaignRecipient::SENT ? now() : null])->save();

        $campaign->increment(match ($status) {
            NewsletterCampaignRecipient::SENT => 'sent_count',
            NewsletterCampaignRecipient::FAILED => 'failed_count',
            default => 'skipped_count',
        });
    }

    private function finishIfDone(NewsletterCampaign $campaign): void
    {
        if ($campaign->status !== CampaignStatus::Sending || $campaign->recipients()->where('status', NewsletterCampaignRecipient::PENDING)->exists()) {
            return;
        }

        NewsletterCampaign::whereKey($campaign->getKey())->where('status', CampaignStatus::Sending)
            ->update(['status' => CampaignStatus::Sent, 'sent_at' => now()]);
    }

    private function ensureEditable(NewsletterCampaign $campaign): void
    {
        if (! $campaign->isEditable()) {
            throw new RuntimeException('Cette campagne est déjà partie.');
        }
    }
}
