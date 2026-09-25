<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'icon' => 'fa-regular fa-tag',
            'image' => 'assets/images/catagory-img/cat-transp-img-07.webp',
        ];
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
