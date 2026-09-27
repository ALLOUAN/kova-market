<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Filament\Resources\DeliveryZones\DeliveryZoneResource;
use App\Filament\Resources\DeliveryZones\Pages\CreateDeliveryZone;
use App\Models\DeliveryZone;
use App\Models\User;
use Database\Seeders\DeliverySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeliveryAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolesAndPermissionsSeeder::class, DeliverySeeder::class]);
    }

    public function test_the_specification_zones_are_installed_closed_and_without_fee(): void
    {
        $this->assertSame(['Zone 1', 'Zone 2', 'Zone 3', 'Intérieur du pays'], DeliveryZone::orderBy('position')->pluck('name')->all());
        $this->assertSame(0, DeliveryZone::where('is_active', true)->orWhereNotNull('fee')->count());
        $this->assertSame(['Cocody', 'Plateau', 'Marcory', 'Treichville', 'Adjamé'], DeliveryZone::firstWhere('name', 'Zone 1')->communes->pluck('name')->all());
    }

    public function test_managers_manage_zones_but_pickers_do_not(): void
    {
        $this->actingAs(User::factory()->staff(Role::Manager)->create());
        $this->get(DeliveryZoneResource::getUrl('index'))->assertOk();
        $this->get(DeliveryZoneResource::getUrl('edit', ['record' => DeliveryZone::first()]))->assertOk();

        $this->actingAs(User::factory()->staff(Role::Picker)->create());
        $this->get(DeliveryZoneResource::getUrl('index'))->assertForbidden();
    }

    public function test_an_open_zone_needs_a_fee_and_a_commune_belongs_to_one_zone(): void
    {
        $this->actingAs(User::factory()->staff(Role::Manager)->create());

        Livewire::test(CreateDeliveryZone::class)
            ->fillForm(['name' => 'Zone 4', 'is_active' => true, 'fee' => null, 'position' => 4, 'communes' => [['name' => 'Cocody']]])
            ->call('create')
            ->assertHasFormErrors(['fee']);

        Livewire::test(CreateDeliveryZone::class)
            ->fillForm(['name' => 'Zone 4', 'is_active' => true, 'fee' => 4000, 'position' => 4, 'communes' => [['name' => 'Cocody']]])
            ->call('create')
            ->assertHasFormErrors();

        Livewire::test(CreateDeliveryZone::class)
            ->fillForm(['name' => 'Zone 4', 'is_active' => true, 'fee' => 4000, 'position' => 4, 'communes' => [['name' => 'Dabou']]])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(['Dabou'], DeliveryZone::firstWhere('name', 'Zone 4')->communes->pluck('name')->all());
    }
}
