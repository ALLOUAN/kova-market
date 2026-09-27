<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Local demo only: invented fees so the cart can be tried. The real grid comes from the client.
 */
class DemoDeliveryFeesSeeder extends Seeder
{
    private const DEMO_FEES = ['Zone 1' => 1500, 'Zone 2' => 2000, 'Zone 3' => 3000, 'Intérieur du pays' => 5000];

    public function run(): void
    {
        foreach (self::DEMO_FEES as $zone => $fee) {
            DeliveryZone::where('name', $zone)->update(['fee' => $fee, 'is_active' => true]);
        }

        Setting::store(['delivery.free_shipping_threshold' => 100000]);
    }
}
