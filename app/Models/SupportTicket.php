<?php

namespace App\Models;

use App\Enums\ContactSubject;
use App\Enums\SupportTicketStatus;
use App\Models\Concerns\HasPublicId;
use Database\Factories\SupportTicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Assistance request sent from the customer area ("Assistance"): a conversation with the team, about an order or
 * anything else. A message from the team sets it "Répondu", one from the customer "À traiter" again.
 */
#[Fillable(['user_id', 'order_id', 'subject', 'title', 'status', 'unread_by_customer', 'last_message_at', 'closed_at'])]
class SupportTicket extends Model
{
    /** @use HasFactory<SupportTicketFactory> */
    use HasFactory, HasPublicId;

    /** Open requests a customer may have at once, against floods. */
    public const MAX_OPEN_PER_CUSTOMER = 10;

    protected static function booted(): void
    {
        // The files of the conversation (SupportTicketMessage::storeAttachments) go with the request.
        static::deleted(fn (SupportTicket $ticket) => Storage::disk(SupportTicketMessage::DISK)->deleteDirectory("support/{$ticket->public_id}"));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subject' => ContactSubject::class,
            'status' => SupportTicketStatus::class,
            'unread_by_customer' => 'boolean',
            'last_message_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(SupportTicketMessage::class)->latestOfMany('id');
    }

    /**
     * Short reference shown to the customer and the team ("#01K7F3A2").
     */
    public function reference(): string
    {
        return '#'.Str::upper(Str::substr((string) $this->public_id, -8));
    }

    public function isClosed(): bool
    {
        return $this->status === SupportTicketStatus::Closed;
    }

    /**
     * Adds a message to the conversation and moves the request to the other side's turn.
     */
    /**
     * @param  list<array{path: string, name: string, size: int}>  $attachments
     */
    public function addMessage(User $author, string $body, bool $fromStaff, array $attachments = []): SupportTicketMessage
    {
        $message = $this->messages()->create([
            'user_id' => $author->getKey(),
            'from_staff' => $fromStaff,
            'body' => trim($body),
            'attachments' => $attachments ?: null,
        ]);

        $this->update([
            'status' => $fromStaff ? SupportTicketStatus::Answered : SupportTicketStatus::Open,
            'unread_by_customer' => $fromStaff,
            'last_message_at' => $message->created_at,
            'closed_at' => null,
        ]);

        return $message;
    }

    public function close(): void
    {
        $this->update(['status' => SupportTicketStatus::Closed, 'closed_at' => now(), 'unread_by_customer' => false]);
    }
}
