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
| Commandes (consultation, bon de commande) | ✓ | ✓ | ✓ | |
| Commandes : préparation et expédition | ✓ | ✓ | ✓ | |
| Commandes : confirmation, livraison, annulation | ✓ | ✓ | | |
| Commandes : retour à l’étape précédente | ✓ | | | |
| Catalogue (lecture) | ✓ | ✓ | ✓ | |
| Catalogue (modification) | ✓ | ✓ | | |
| Campagnes promotionnelles | ✓ | ✓ | | |
| Contenus (bannières, pages, FAQ) | ✓ | ✓ | | |
| Zones et tarifs de livraison | ✓ | ✓ | | |
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

## Variants and stock

Every product sells through at least one variant (the default one, `KM-000123`), each with its SKU, prices in whole
FCFA, stock and optional alert threshold. Prices and stock are edited in the product's **Variantes** tab; the product
row keeps a summary (total stock, cheapest price, highest price, variant count) refreshed by `Product::syncFromVariants()`.

Stock only changes through `App\Services\Catalog\StockManager`, which locks the variant row and records a
`stock_movements` line (reason, signed quantity, stock after, author). In the back-office: **Ajuster le stock** on a
variant (inventory count, return) and the read-only **Mouvements de stock** tab. Attributes and their values (Couleur,
Capacité…) are managed under **Catalogue › Attributs de variantes**.

## Delivery zones

`DeliverySeeder` (run by `db:seed`, production included) installs the zones and communes proposed by the specification,
**closed and without fee**: the specification gives no prices. A zone is offered in the cart once it has a fee and is
switched on (**Livraison › Zones de livraison**). The free-delivery threshold is under **Paramètres de la boutique**.
Locally, `DemoDeliveryFeesSeeder` sets demo fees (1 500 / 2 000 / 3 000 / 5 000 FCFA) and a 100 000 FCFA threshold.

## Notifications

Orders and stock send notifications through queued listeners (`app/Listeners`), so a worker must run:
`php artisan queue:work` (locally too, since `QUEUE_CONNECTION=database`).

| Event | Customer | Staff |
|---|---|---|
| Order placed | SMS + e-mail | e-mail + bell alert (`commandes.gerer`) |
| Confirmed, delivered, cancelled | SMS + e-mail | bell alert on cancellation |
| Preparing | e-mail | — |
| Shipped, out for delivery | SMS | — |
| Stock reaches the alert threshold | — | e-mail (`catalogue.gerer`) |

The customer gets e-mails only when the order carries an address. Steps taken back by a super-admin are not
announced. SMS go through `App\Services\Sms\SmsGateway`, chosen by `SMS_DRIVER`: `log` writes them to
`storage/logs/sms-*.log`, `null` discards them (tests). A real provider is a new class added to the match in
`AppServiceProvider`. E-mails use the `MAIL_*` settings (`MAIL_MAILER=log` writes them to `laravel.log`).

## Promo codes

**Promotions › Codes promo** (`promotions.gerer`): fixed amount, percentage (rounded to the franc) or free delivery,
with dates, a minimum order excluding delivery, a total cap and a per-customer cap (the customer is recognised by
phone number, or by account), on the whole catalog or on chosen categories (sub-categories included) or products.
"Affiché dans la boutique" lists the code in the cart's "Codes promo" window. Each use is listed under
**Utilisations**; a cancelled order gives its use back. A used code cannot be deleted, only switched off. Locally,
`DemoCouponsSeeder` creates BIENVENUE10, LIVRAISON and MOINS5000.

## Not in the back-office yet

Customers, couriers and CSV import arrive with their modules (see the
specification). Products are switched off rather than deleted until soft deletes land with orders. The storefront still
shows the colour swatches typed on the product; they switch to the variants with the product page.
