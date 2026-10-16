<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One message of the chat between a courier and the back-office (courier area › Messages, Livraison › Messagerie
 * livreurs). "read_at" is when the other side saw it.
 */
#[Fillable(['courier_id', 'user_id', 'from_staff', 'body', 'read_at'])]
class CourierMessage extends Model
{
    /** Length of one message. */
    public const MAX_LENGTH = 2000;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_staff' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    /**
     * Messages from couriers the team has not seen yet.
     */
    #[Scope]
    protected function unreadByStaff(Builder $query): Builder
    {
        return $query->where('from_staff', false)->whereNull('read_at');
    }

    /**
     * Messages from the team the courier has not seen yet.
     */
    #[Scope]
    protected function unreadByCourier(Builder $query): Builder
    {
        return $query->where('from_staff', true)->whereNull('read_at');
    }

    /**
     * What the chat screens draw for one message.
     *
     * @return array{id: int, mine: bool, body: string, time: string, read: bool, author: ?string}
     */
    public function toBubble(bool $viewerIsStaff): array
    {
        return [
            'id' => $this->id,
            'mine' => $this->from_staff === $viewerIsStaff,
            'body' => $this->body,
            'time' => $this->created_at->format('d/m H:i'),
            'read' => $this->read_at !== null,
            'author' => $this->from_staff ? $this->author?->name : null,
        ];
    }
}
