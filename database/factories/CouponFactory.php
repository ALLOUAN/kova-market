<?php

namespace Database\Factories;

use App\Enums\CouponTarget;
use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('CODE###??'),
            'type' => CouponType::Percentage,
            'value' => 10,
            'target' => CouponTarget::All,
            'is_active' => true,
        ];
    }
}
