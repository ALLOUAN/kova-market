<?php

namespace App\Models;

use App\Enums\ContactSubject;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Message from the contact page (F-080); "handled" once someone of the team has answered.
 */
#[Fillable(['user_id', 'name', 'phone', 'email', 'subject', 'message', 'ip'])]
class ContactMessage extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subject' => ContactSubject::class,
            'handled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by')->withTrashed();
    }

    public function formattedPhone(): string
    {
        return PhoneNumber::format($this->phone);
    }

    /**
     * WhatsApp chat with the customer (wa.me wants the number without "+").
     */
    public function whatsappUrl(): string
    {
        return 'https://wa.me/'.ltrim($this->phone, '+');
    }
}
