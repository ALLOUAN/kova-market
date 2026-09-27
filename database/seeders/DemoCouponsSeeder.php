<?php

namespace Database\Seeders;

use App\Enums\CouponTarget;
use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Seeder;

/**
 * Local demo only: a few promo codes to try the cart. The real codes are created in the back-office.
 */
class DemoCouponsSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            'BIENVENUE10' => ['description' => '10 % sur votre première commande', 'type' => CouponType::Percentage, 'value' => 10, 'usage_limit_per_customer' => 1],
            'LIVRAISON' => ['description' => 'Livraison offerte dès 20 000 FCFA', 'type' => CouponType::FreeShipping, 'value' => 0, 'minimum_subtotal' => 20000],
            'MOINS5000' => ['description' => '5 000 FCFA de remise dès 50 000 FCFA', 'type' => CouponType::Fixed, 'value' => 5000, 'minimum_subtotal' => 50000, 'usage_limit' => 100],
        ];

        foreach ($codes as $code => $attributes) {
            Coupon::updateOrCreate(['code' => $code], [...$attributes, 'target' => CouponTarget::All, 'is_active' => true, 'is_public' => true]);
        }
    }
}
