<?php

namespace App\Services\Delivery;

use App\Enums\Role;
use App\Models\Courier;
use App\Models\Order;
use App\Models\User;
use App\Notifications\CourierCredentials;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Courier accounts (F-122): created by the back-office only, sign-in details sent by SMS with a temporary
 * password to change at the first sign-in, suspension at any time.
 */
class CourierAccounts
{
    /** Letters and digits that cannot be mistaken for one another on a phone screen. */
    private const PASSWORD_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(private DeliveryDispatcher $dispatcher) {}

    /**
     * @param  array{name: string, phone: string, transport: string, photo?: ?string, zones?: array<int, int|string>}  $data
     */
    public function create(array $data, ?User $by = null): Courier
    {
        $password = self::temporaryPassword();

        $courier = DB::transaction(function () use ($data, $password): Courier {
            $user = User::create(['name' => $data['name'], 'phone' => $data['phone'], 'password' => $password]);
            $user->forceFill(['must_change_password' => true])->save();
            $user->assignRole(Role::Courier->value);

            $courier = $user->courier()->create(['transport' => $data['transport'], 'photo' => $data['photo'] ?? null]);
            $courier->zones()->sync($data['zones'] ?? []);

            return $courier;
        });

        $this->sendCredentials($courier, $password, reset: false);
        activity('livraisons')->performedOn($courier)->causedBy($by)->log("Compte livreur créé : {$courier->name()}");

        return $courier;
    }

    /**
     * @param  array{name: string, phone: string, transport: string, photo?: ?string, zones?: array<int, int|string>}  $data
     */
    public function update(Courier $courier, array $data): Courier
    {
        DB::transaction(function () use ($courier, $data): void {
            $courier->user->update(['name' => $data['name'], 'phone' => $data['phone']]);
            $courier->update(['transport' => $data['transport'], 'photo' => $data['photo'] ?? null]);
            $courier->zones()->sync($data['zones'] ?? []);
        });

        return $courier;
    }

    /**
     * New temporary password by SMS; the courier must choose their own at the next sign-in.
     */
    public function resetPassword(Courier $courier, ?User $by = null): void
    {
        $password = self::temporaryPassword();
        $courier->user->forceFill(['password' => $password, 'must_change_password' => true, 'remember_token' => null])->save();

        $this->sendCredentials($courier, $password, reset: true);
        activity('livraisons')->performedOn($courier)->causedBy($by)->log("Mot de passe réinitialisé : {$courier->name()}");
    }

    /**
     * The courier can no longer sign in, and their unfinished deliveries go back to the queue of their zone.
     */
    public function suspend(Courier $courier, ?User $by = null): void
    {
        DB::transaction(function () use ($courier): void {
            $courier->user->forceFill(['suspended_at' => now(), 'remember_token' => null])->save();
            $courier->openOrders()->get()->each(fn (Order $order) => $this->dispatcher->release($order));
        });

        activity('livraisons')->performedOn($courier)->causedBy($by)->log("Livreur suspendu : {$courier->name()}");
    }

    public function reactivate(Courier $courier, ?User $by = null): void
    {
        $courier->user->forceFill(['suspended_at' => null])->save();
        activity('livraisons')->performedOn($courier)->causedBy($by)->log("Livreur réactivé : {$courier->name()}");
    }

    public static function temporaryPassword(): string
    {
        return collect(range(1, 8))->map(fn () => self::PASSWORD_ALPHABET[random_int(0, strlen(self::PASSWORD_ALPHABET) - 1)])->join('');
    }

    private function sendCredentials(Courier $courier, string $password, bool $reset): void
    {
        Notification::route('sms', $courier->user->phone)
            ->notify(new CourierCredentials($courier->user->phone, $password, $reset));
    }
}
