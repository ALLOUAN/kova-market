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

Sale prices (F-090): a variant is on sale when its "Prix de vente" is below its "Prix barré". Optional dates
("Promotion à partir du / jusqu'au") limit the sale; outside them the customer pays the crossed-out price and the
discount badge and countdown disappear. Cart and checkout always compute the current price
(`ProductVariant::currentPrice()`); the price kept on the product for lists and sorting is refreshed every minute by
`catalog:refresh-sale-prices`, so the scheduler must run (`php artisan schedule:work` locally).

## CSV import

**Catalogue › Importer (CSV)** (`catalogue.gerer`, F-104): **Analyser le fichier** runs the whole import in a
rolled-back transaction and shows what it would do and the refused lines; **Importer** then saves every valid line,
each on its own. **Télécharger le modèle** gives the columns:

| Column | Content |
| --- | --- |
| `sku` * | Variant reference, the update key |
| `produit` * | Product name |
| `slug` | Product address; derived from the name when empty. Lines sharing a slug are variants of one product |
| `categorie` | Category name or slug (required for a new product) |
| `marque` | Brand name or slug |
| `prix` * | Price in whole FCFA ("15 000" accepted) |
| `prix_barre`, `promo_debut`, `promo_fin` | Sale: crossed-out price and dates (`31/12/2026` or `31/12/2026 18:00`) |
| `stock` | Counted stock; changed through an "ajustement" movement noted "Import CSV", left as is when empty |
| `seuil_alerte` | Low-stock threshold of the variant |
| `description`, `actif` (`oui`/`non`) | Product text and visibility |
| `image` | Path of a file already on the site (`uploads/products/…`) or a web address, downloaded at import |
| `attribut:<Nom>` | Variant value for an existing attribute (e.g. `attribut:Couleur`); new values are created |

A known SKU updates its variant and product (empty cells clear the sale and threshold); a new SKU creates the
product, or adds a variant to the product of the same slug (attributes required, same product name). Files may be
UTF-8 or Windows-1252, separated by `;` or `,`, 5 000 lines at most. Each import is recorded in the audit log.

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
| Sold-out variant back in stock | SMS or e-mail, once, to visitors who asked ("Me prévenir") | — |

Waiting back-in-stock alerts show as the **Alertes** column and the **Attendus par des clients** filter of the product
list, and in the **Alertes de réassort** tab of a product (count on the tab). Catalog managers can delete an alert when
a customer asks for it.

The customer gets e-mails only when the order carries an address. Steps taken back by a super-admin are not
announced. SMS go through `App\Services\Sms\SmsGateway`, chosen by `SMS_DRIVER`: `log` writes them to
`storage/logs/sms-*.log`, `null` discards them (tests). A real provider is a new class added to the match in
`AppServiceProvider`. E-mails use the `MAIL_*` settings (`MAIL_MAILER=log` writes them to `laravel.log`).

## Customers

**Ventes › Clients** (`commandes.gerer`, personal data): customer accounts (users without a staff role) and guests
grouped by phone number, read from the `customers` SQL view. A guest order placed with the phone of an account counts
for that account. "Total dépensé" sums paid orders. The page of a customer lists their details, addresses and last 50
orders. **Exporter (CSV)** downloads the customers shown (search and filters applied) and records who exported what
in the audit log.

## Contact messages and WhatsApp

Messages from `/contact` (F-080) land in **Ventes › Messages** (`commandes.gerer`, badge = messages to handle), with a
bell alert and a copy e-mailed to the contact address of **Paramètres de la boutique**. Answer by WhatsApp, e-mail or
phone from the message page, then **Marquer comme traité**. Robots are stopped by a hidden field, a minimum filling
time and a throttle; Cloudflare Turnstile is added once `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY` are set.

The floating WhatsApp button (F-081) appears on every page once **Numéro WhatsApp** is filled in the settings. Its
message is prefilled by the page (`@section('whatsapp_message', …)`): product viewed, order summary after checkout.

## Packs

**Promotions › Packs** (`promotions.gerer`, F-093): a pack is a product (`is_bundle`) sold at one price through its
own variant (`PACK-000123`), made of variants of other products with a quantity each. Its stock is not counted: it
is the number of whole packs the components allow (none while a component is off sale), refreshed by
`StockManager` whenever a component's stock changes. Selling a pack takes each component's quantity (movements
noted "pack « … »"), cancelling gives them back. Order lines keep the pack contents as ordered (slip, customer area,
back-office). Packs are left out of **Catalogue › Produits** and of the CSV import.

## Couriers

**Livraison › Livreurs** (`livraison.gerer`, F-122): accounts are created here only. The phone is the login; a
temporary password is sent by SMS (again with **Nouveau mot de passe**) and must be replaced at the first sign-in.
**Suspendre** blocks every sign-in and puts the courier's unfinished deliveries back in their zone's queue.

Dispatch (F-123, F-124), chosen in **Paramètres de la boutique › Livraison**: when an order is confirmed it enters the
queue of its commune's zone, then either goes at once to the zone's courier with the fewest open deliveries
(automatic) or waits for the first courier of the zone who takes it (the couriers of the zone get an SMS). The order
page offers **Confier à un livreur** and **Retirer au livreur** at any time. A courier only sees the queue of their
zones and their own deliveries (404 otherwise); of two couriers accepting at once, one gets it.

Courier area (F-125) at `/livreur` (own sign-in, installable on the phone): my deliveries and the zone queue, then per
delivery the address and landmark, call, WhatsApp, route in Google Maps, amount to collect, and **Je pars livrer**,
**Commande livrée** (cash collected typed at the door) or **Livraison impossible** (reason required: the order is
cancelled and its stock put back).

## Promo codes

**Promotions › Codes promo** (`promotions.gerer`): fixed amount, percentage (rounded to the franc) or free delivery,
with dates, a minimum order excluding delivery, a total cap and a per-customer cap (the customer is recognised by
phone number, or by account), on the whole catalog or on chosen categories (sub-categories included) or products.
"Affiché dans la boutique" lists the code in the cart's "Codes promo" window. Each use is listed under
**Utilisations**; a cancelled order gives its use back. A used code cannot be deleted, only switched off. Locally,
`DemoCouponsSeeder` creates BIENVENUE10, LIVRAISON and MOINS5000.

## Not in the back-office yet

Delivery tracking and cash settlement arrive with the next step (see the
specification). Products are switched off rather than deleted until soft deletes land with orders. The storefront still
shows the colour swatches typed on the product; they switch to the variants with the product page.
