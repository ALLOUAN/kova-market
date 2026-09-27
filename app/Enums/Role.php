<?php

namespace App\Enums;

/**
 * Back-office roles (specification F-101). Super-admins bypass every permission check.
 */
enum Role: string
{
    case SuperAdmin = 'super-admin';
    case Manager = 'gestionnaire';
    case Picker = 'preparateur';
    case Courier = 'livreur';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super-admin',
            self::Manager => 'Gestionnaire boutique',
            self::Picker => 'Préparateur de commandes',
            self::Courier => 'Livreur',
        };
    }

    /**
     * Roles allowed into the admin panel (couriers get their own mobile space).
     *
     * @return list<self>
     */
    public static function panelRoles(): array
    {
        return [self::SuperAdmin, self::Manager, self::Picker];
    }

    /**
     * Roles that must protect their account with an authenticator app (F-100).
     *
     * @return list<self>
     */
    public static function requiringTwoFactor(): array
    {
        return [self::SuperAdmin, self::Manager];
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => Permission::cases(),
            self::Manager => [
                Permission::ViewCatalog, Permission::ManageCatalog, Permission::ManagePromotions, Permission::ManageContent,
                Permission::ManageDelivery, Permission::ViewOrders, Permission::ManageOrders,
            ],
            self::Picker => [Permission::ViewCatalog, Permission::ViewOrders, Permission::PrepareOrders],
            self::Courier => [],
        };
    }
}
