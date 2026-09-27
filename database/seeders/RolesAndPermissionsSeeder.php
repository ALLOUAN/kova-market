<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Reference data: creates the back-office roles and permissions. Safe to run in production and to re-run.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value);
        }

        foreach (Role::cases() as $role) {
            RoleModel::findOrCreate($role->value)->syncPermissions(
                array_map(fn (Permission $permission) => $permission->value, $role->permissions()),
            );
        }
    }
}
