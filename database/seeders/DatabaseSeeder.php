<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([RolesAndPermissionsSeeder::class, ContentSeeder::class, DeliverySeeder::class]);

        // Demo data never reaches production: the real catalog is imported by the client.
        if (app()->isProduction()) {
            return;
        }

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'admin-kovamarket@mail.com',
            // Local demo only (never seeded in production).
            'password' => '123456789',
        ])->assignRole(Role::SuperAdmin->value);

        $this->call([CatalogSeeder::class, BannerSeeder::class, DemoDeliveryFeesSeeder::class, DemoCouponsSeeder::class, DemoStaffSeeder::class]);
    }
}
