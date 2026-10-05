<?php

namespace Database\Seeders;

use App\Enums\DeliveryMode;
use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;

/**
 * Reference data: the delivery zones and communes proposed by the specification (section 6, "à valider").
 * The specification gives no prices, so the zones are created switched off and without fee: nothing can be
 * delivered until the client enters the fees in the back-office. Safe to re-run: existing zones are kept.
 */
class DeliverySeeder extends Seeder
{
    public const ZONES = [
        'Zone 1' => ['delay' => 'J+1', 'communes' => ['Cocody', 'Plateau', 'Marcory', 'Treichville', 'Adjamé']],
        'Zone 2' => ['delay' => 'J+1 à J+2', 'communes' => ['Yopougon', 'Abobo', 'Koumassi', 'Port-Bouët', 'Attécoubé']],
        'Zone 3' => ['delay' => 'J+2', 'communes' => ['Bingerville', 'Anyama', 'Songon', 'Grand-Bassam']],
        // A single destination: the customer then types the town the parcel is shipped to.
        'Intérieur du pays' => ['delay' => 'J+2 à J+5', 'communes' => ['Intérieur'], 'mode' => DeliveryMode::Interior],
    ];

    public function run(): void
    {
        foreach (array_keys(self::ZONES) as $position => $name) {
            $zone = DeliveryZone::firstOrCreate(['name' => $name], [
                'delay_label' => self::ZONES[$name]['delay'],
                'delivery_mode' => self::ZONES[$name]['mode'] ?? DeliveryMode::Abidjan,
                'position' => $position,
            ]);

            foreach (self::ZONES[$name]['communes'] as $communePosition => $commune) {
                $zone->communes()->firstOrCreate(['name' => $commune], ['position' => $communePosition]);
            }
        }
    }
}
