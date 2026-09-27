<?php

namespace App\Support;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Back-office users holding a permission, i.e. who should hear about an event. An installation whose roles
 * are not seeded yet simply has nobody to notify.
 */
class StaffRecipients
{
    /**
     * @return Collection<int, User>
     */
    public static function with(Permission $permission): Collection
    {
        try {
            return User::permission($permission->value)->get();
        } catch (PermissionDoesNotExist) {
            return new Collection;
        }
    }
}
