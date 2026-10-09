<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One subscriber of a campaign: waiting, sent, failed (with the error) or skipped (unsubscribed meanwhile).
 */
#[Fillable(['newsletter_campaign_id', 'newsletter_subscriber_id', 'status', 'sent_at', 'error'])]
class NewsletterCampaignRecipient extends Model
{
    public const PENDING = 'en_attente';

    public const SENT = 'envoye';

    public const FAILED = 'echec';

    public const SKIPPED = 'ignore';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(NewsletterCampaign::class, 'newsletter_campaign_id');
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(NewsletterSubscriber::class, 'newsletter_subscriber_id');
    }
}
