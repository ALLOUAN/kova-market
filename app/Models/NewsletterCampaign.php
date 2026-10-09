<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A newsletter campaign: one e-mail written in the back-office and sent to every active subscriber, at once or at
 * a chosen date (App\Services\Newsletter\CampaignSender). Its recipients are frozen at launch.
 */
#[Fillable(['subject', 'preheader', 'image', 'content', 'button_label', 'button_url', 'scheduled_at', 'created_by'])]
class NewsletterCampaign extends Model
{
    use LogsActivity;

    protected $attributes = [
        'status' => 'brouillon',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('newsletter')->logOnly(['subject', 'status', 'scheduled_at'])->logOnlyDirty()->dontLogEmptyChanges();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CampaignStatus::class,
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'sent_at' => 'datetime',
            'recipients_count' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
            'skipped_count' => 'integer',
        ];
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(NewsletterCampaignRecipient::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Share of the recipients already handled (sent, failed or skipped), 0 to 100.
     */
    public function progress(): int
    {
        if ($this->recipients_count === 0) {
            return $this->status === CampaignStatus::Sent ? 100 : 0;
        }

        return (int) floor(($this->sent_count + $this->failed_count + $this->skipped_count) / $this->recipients_count * 100);
    }
}
