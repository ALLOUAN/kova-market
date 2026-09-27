# Back-office

Filament 5 panel, in French, at `/{ADMIN_PATH}` (`admin` by default; use a non-standard value in production).

## First installation (production)

```sh
php artisan migrate --force
php artisan db:seed --force          # roles, permissions, legal pages and starter FAQ only (no demo data in production)
php artisan app:create-super-admin   # asks for name, e-mail and a 12+ character password
```

On first login, super-admins and managers must set up an authenticator app (Google Authenticator, Microsoft
Authenticator…) and keep their recovery codes. A super-admin can reset a lost two-factor set-up from **Équipe**.

Locally, `php artisan migrate:fresh --seed` creates `test@example.com` / `password` as super-admin (two-factor set-up
is requested at first login) plus the demo catalog and banners.

## Roles

| Permission | Super-admin | Gestionnaire | Préparateur | Livreur |
| --- | --- | --- | --- | --- |
| Catalogue (lecture) | ✓ | ✓ | ✓ | |
| Catalogue (modification) | ✓ | ✓ | | |
| Campagnes promotionnelles | ✓ | ✓ | | |
| Contenus (bannières, pages, FAQ) | ✓ | ✓ | | |
| Paramètres de la boutique | ✓ | | | |
| Équipe (comptes du back-office) | ✓ | | | |
| Journal d'audit | ✓ | | | |
| Accès au back-office | ✓ | ✓ | ✓ | |
| Double authentification obligatoire | ✓ | ✓ | | |

The matrix lives in `App\Enums\Role::permissions()`; `RolesAndPermissionsSeeder` applies it (re-run it after a change).
Super-admins pass every check (`Gate::before`). Resources declare their permissions through
`App\Filament\Concerns\AuthorizesWithPermission`.

An account is locked for 15 minutes after 5 failed logins (`App\Filament\Pages\Auth\Login`).

## Where things live

| Concern | Location |
| --- | --- |
| Panel configuration | `app/Providers/Filament/AdminPanelProvider.php` |
| Screens | `app/Filament/Resources/*` (form in `Schemas`, list in `Tables`), `app/Filament/Pages/Settings.php` |
| Dashboard widgets | `app/Filament/Widgets` |
| Uploaded images | `public/uploads/*` through the `storefront` disk (paths render with `asset()` like the template images) |
| Store settings | `settings` table, read by `App\Services\Storefront\StoreSettings`; empty values fall back to `config/storefront.php` |
| Home banners | `banners` table; a slot without a live banner shows its default from `config/homepage.php` |
| Audit trail | `activity_log` table (Spatie Activitylog), screen **Journal d'audit** |

## Not in the back-office yet

Orders, customers, delivery zones and couriers, coupons, stock movements and CSV import arrive with their modules
(see the specification). Products are switched off rather than deleted until soft deletes land with orders.
