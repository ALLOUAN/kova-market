<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A newsletter sign-up: kept with its source and consent dates; unsubscribing keeps the row (proof of the
 * withdrawal) and stops every mailing. Addressed in the unsubscribe link by its random token, never its id.
 */
#[Fillable(['email', 'source', 'token', 'subscribed_at', 'unsubscribed_at'])]
class NewsletterSubscriber extends Model
{
    /** Where the address was given => name in the back-office. */
    public const SOURCES = [
        'footer' => 'Pied de page',
        'popup' => 'Fenêtre d’invitation',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('unsubscribed_at');
    }

    public function isActive(): bool
    {
        return $this->unsubscribed_at === null;
    }

    /**
     * Signs the address up, or back up after an unsubscribe (a new consent, dated).
     */
    public static function subscribe(string $email, string $source): self
    {
        $subscriber = static::firstOrNew(['email' => Str::lower(trim($email))]);

        if ($subscriber->exists && $subscriber->isActive()) {
            return $subscriber;
        }

        $subscriber->fill([
            'source' => $subscriber->source ?? $source,
            'token' => $subscriber->token ?? Str::random(40),
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
        ])->save();

        return $subscriber;
    }

    public function unsubscribe(): void
    {
        if ($this->isActive()) {
            $this->update(['unsubscribed_at' => now()]);
        }
    }
}
