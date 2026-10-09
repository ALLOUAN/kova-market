<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\ReviewInvitation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Invites customers to review their products two days after the delivery (scheduled daily): orders of an account
 * with an e-mail address, delivered between 2 and 30 days ago, not invited yet and not reviewed yet.
 */
#[Signature('reviews:invite')]
#[Description('Invite les clients livrés à donner leur avis sur leurs produits')]
class InviteReviews extends Command
{
    public function handle(): int
    {
        $invited = 0;

        Order::query()
            ->where('status', OrderStatus::Delivered)
            ->whereNull('review_invited_at')
            ->whereHas('user', fn (Builder $user) => $user->whereNotNull('email')->whereNull('suspended_at'))
            ->whereHas('statusHistory', fn (Builder $history) => $history->where('to_status', OrderStatus::Delivered)
                ->whereBetween('created_at', [now()->subDays(30), now()->subDays(2)]))
            ->whereDoesntHave('items.review')
            ->with('user')
            ->lazyById()
            ->each(function (Order $order) use (&$invited): void {
                $order->user->notify(new ReviewInvitation($order));
                $order->forceFill(['review_invited_at' => now()])->saveQuietly();
                $invited++;
            });

        $this->info("{$invited} invitation(s) envoyée(s).");

        return self::SUCCESS;
    }
}
