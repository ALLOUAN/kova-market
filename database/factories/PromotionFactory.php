<?php

namespace Database\Factories;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'image' => 'assets/images/offer-list/offer-card-image-1.webp',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
        ];
    }

    public function expired(): static
    {
        return $this->state(['starts_at' => now()->subMonth(), 'ends_at' => now()->subDay()]);
    }
}
