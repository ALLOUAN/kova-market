<?php

namespace App\Filament\Concerns;

use App\Enums\Permission;
use BackedEnum;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Authorizes every action of a Filament resource with the back-office permissions:
 * listing and viewing need the "view" permission, everything else the "manage" one.
 */
trait AuthorizesWithPermission
{
    abstract protected static function managePermission(): Permission;

    protected static function viewPermission(): Permission
    {
        return static::managePermission();
    }

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $action = $action instanceof BackedEnum ? $action->value : ($action instanceof UnitEnum ? $action->name : $action);

        $permission = in_array($action, ['viewAny', 'view'], true) ? static::viewPermission() : static::managePermission();

        return auth()->user()?->can($permission->value) ? Response::allow() : Response::deny();
    }
}
