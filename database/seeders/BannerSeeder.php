<?php

namespace Database\Seeders;

use App\Enums\BannerPlacement;
use App\Models\Banner;
use Illuminate\Database\Seeder;

/**
 * Demo banners: copies the default home page content of config/homepage.php into the back-office table.
 */
class BannerSeeder extends Seeder
{
    public function run(): void
    {
        // Once only: banners edited in the back-office are kept by a later "db:seed".
        if (Banner::query()->exists()) {
            $this->command?->info('Bannières déjà présentes : ignorées.');

            return;
        }

        foreach (config('homepage.hero') as $position => $slide) {
            Banner::create([...$slide, 'placement' => BannerPlacement::Hero, 'position' => $position]);
        }

        foreach (config('homepage.banners') as $slot => $banner) {
            Banner::create([...$banner, 'placement' => BannerPlacement::from($slot)]);
        }
    }
}
