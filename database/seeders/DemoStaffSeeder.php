<?php

namespace Database\Seeders;

use App\Enums\CourierTransport;
use App\Enums\Role;
use App\Models\DeliveryZone;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One demo account per back-office role (password "password"), to try each space without going through the
 * courier SMS flow. Safe to run again: accounts are matched on their e-mail or phone.
 */
class DemoStaffSeeder extends Seeder
{
    public function run(): void
    {
        $this->staff(['email' => 'gestionnaire@kovamarket.test'], 'Gestionnaire Démo', Role::Manager);
        $this->staff(['email' => 'preparateur@kovamarket.test'], 'Préparateur Démo', Role::Picker);

        $courier = $this->staff(['phone' => '0700000003'], 'Livreur Démo', Role::Courier);
        $profile = $courier->courier()->firstOrCreate([], ['transport' => CourierTransport::Motorbike]);
        $profile->zones()->syncWithoutDetaching(DeliveryZone::pluck('id'));
    }

    /**
     * @param  array{email?: string, phone?: string}  $login
     */
    private function staff(array $login, string $name, Role $role): User
    {
        $user = User::withTrashed()->firstOrNew(isset($login['phone']) ? ['phone' => '+225'.$login['phone']] : $login);
        $user->fill([...$login, 'name' => $name, 'password' => 'password']);
        $user->forceFill(['email_verified_at' => now(), 'suspended_at' => null, 'must_change_password' => false, 'deleted_at' => null]);
        $user->saveQuietly();
        $user->syncRoles([$role->value]);

        return $user;
    }
}
