<?php

namespace App\Services\Account;

use App\Models\Order;
use App\Models\User;
use App\Services\Security\SmsCode;
use Illuminate\Database\Eloquent\Builder;

/**
 * Orders placed as a guest with the account's phone number join the account once the customer proves the number is
 * theirs with a code sent by SMS (F-070): they then appear in "Mes commandes". Nothing is attached without it, since
 * the phone number typed at sign-up is never checked.
 */
class GuestOrderClaim
{
    private const PURPOSE = 'guest-orders';

    public function __construct(private SmsCode $codes) {}

    /**
     * Guest orders placed with the customer's phone number.
     */
    public function pending(User $user): Builder
    {
        return Order::query()->whereNull('user_id')->where('phone', $user->phone ?? '');
    }

    /**
     * Sends the code; false when there is nothing to attach or a code went out less than a minute ago.
     */
    public function sendCode(User $user): bool
    {
        $count = $this->pending($user)->count();

        if ($count === 0) {
            return false;
        }

        return $this->codes->send(self::PURPOSE, $user->phone, fn (string $code) => config('storefront.name')
            ." : votre code pour retrouver {$count} commande(s) dans votre compte est {$code}. Il est valable ".SmsCode::MINUTES.' minutes.');
    }

    /**
     * Attaches the guest orders when the code matches; returns how many joined the account (null for a wrong code).
     */
    public function confirm(User $user, string $code): ?int
    {
        if (! $user->phone || ! $this->codes->verify(self::PURPOSE, $user->phone, $code)) {
            return null;
        }

        $count = $this->pending($user)->update(['user_id' => $user->getKey()]);

        activity()->causedBy($user)->performedOn($user)->withProperties(['orders' => $count])->log('Commandes invité rattachées au compte après vérification par SMS');

        return $count;
    }
}
