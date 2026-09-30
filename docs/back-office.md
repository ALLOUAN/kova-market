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
| Offres spéciales (campagnes) | ✓ | ✓ | | |
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
| Texts, images and menus (F-111) | **Paramètres de la boutique** (identity, logo, favicon, scrolling messages, popular searches, product card texts, footer images and app links) and **Contenus › Menus** (Pages and Aide menus, footer columns, legal links, side panel). `App\Services\Storefront\ConfigOverrides` lays these settings over `config/storefront.php` and `config/navigation.php` at start-up and on every web request (`ApplyStoreSettings`), so every screen keeps reading `config()`; an emptied setting, or **Rétablir les menus d’origine**, goes back to the file |
| Home banners | `banners` table (with the button text, "Acheter maintenant" by default); a slot without a live banner hides its banner and the section around it takes the whole width: the template samples of `config/homepage.php` are only copied by the demo `BannerSeeder`, never shown by themselves |
| Home selections | **Catalogue › Collections de l'accueil**: title, products and their order, start date (a selection prepared in advance shows from then) and end date (countdown). Each selection has its own page `/selection/{identifiant}` ("Tout voir"), with filters and sorting |
| Home page title, guarantees and sections | **Paramètres de la boutique › Page d'accueil**: the Google title of the home page, up to 4 guarantees under the hero (none: no banner), and the order and visibility of the 11 sections (`HomePageService::SECTIONS`; a section missing from the setting shows, last). Defaults in `config/storefront.php` |
| Banner promoting a product | A banner's **Produit mis en avant**: its price, crossed-out price, discount and page come from the product and follow it; the typed prices are then ignored. A product taken off the site: no price, the button leads to the shop |
| Home page measurement | With Google Analytics set: `view_item_list` per selection shown and `view_promotion` for the banners (`HomeController`), `select_item` and `select_promotion` on click (`public/assets/js/analytics.js`, through the `data-analytics-*` marks). Only after the visitor accepts the cookies |
| Product reviews | **Catalogue › Avis clients** and the product's **Avis clients** tab (`product_reviews`): customers review the articles of a delivered order from their account; published after moderation (never edited). The product's stars and count come from the published reviews only |
| "N personnes ont vu ce produit" | Real visits of the product page over 15 minutes, one per visitor (keyed hash of the session, `product_viewers`, forgotten after 15 minutes); `catalog:refresh-viewers` (every minute) keeps `products.watchers_count`. **Paramètres › Fiches produit**: on/off and threshold (3 visitors by default) |
| Favourites ("Mes favoris") | `wishlist_items`: the heart of product cards and pages, the header counts and the `/favoris` page. A visitor's favourites live on the year-long `kova_wishlist` cookie token and join the account at sign-in; a customer's are on the account (all devices). Products taken off the site leave the list. `App\Services\Storefront\Wishlist`, `public/assets/js/wishlist.js` (the heart is `kova-wishlist-btn`, not the theme's `rbt-wishlisted-btn`, whose script redirects to a template page) |
| Customer receipt | `App\Services\Orders\OrderReceipt`, `resources/views/pdf/receipt.blade.php`: PDF "Reçu de commande" (payment due) or "Reçu de paiement" (paid online with the CinetPay references, or on delivery). Given on the confirmation page, in the customer area, attached to the order and delivery e-mails, and linked in the delivery SMS. `/commande/{numéro}/recu` opens for whoever placed the order, or through its signed link |
| Maintenance mode | Administration › Maintenance (`App\Filament\Pages\Maintenance`, `App\Services\Storefront\Maintenance`, `App\Http\Middleware\MaintenanceMode`): one button puts the storefront behind `resources/views/maintenance.blade.php` (503 with Retry-After) with the message, estimated duration, progress and expected return set on the page. Signed-in team members and the allowed IPs (one per line, CIDR accepted) keep the site, with a red bar reminding them; the back-office, `/livreur`, `/paiement/*` (CinetPay) and `/up` stay open. Preview: `/maintenance/apercu`. Switches are in the audit log |
| « Mon Marché » | Market department created by the migration `2026_09_30_120000_create_mon_marche_categories` (every environment, skipped when the slug `mon-marche` exists): first in the menus, 8 categories (Fruits et légumes, Viandes, volailles et poissons, Céréales et légumineuses, Épicerie, Produits frais et laitiers, Boulangerie et pâtisserie, Boissons, Surgelés) and their sub-categories, all editable in the back-office. A category without an image shows its icon on the storefront until one is added |
| Parent categories | Catalogue › Catégories parentes (`ListParentCategories`): the main departments in menu order, dragged to reorder the « Boutique » menu, the mobile menu and the category panel, with « Ajouter une catégorie parente » (the category form without the parent field, placed last, back to this list once saved) |
| Sub-categories | Catalogue › Sous-catégories (`ListSubCategories`): every sub-category grouped by parent, with « Ajouter une sous-catégorie » (the category form with the parent required, back to this list once saved). Catalogue › Catégories: tabs « Toutes / Rayons principaux / Sous-catégories », a « + » button on each row opening the form with the parent chosen, and on each category page a **Sous-catégories** table (`ChildrenRelationManager`) to add, edit, drag into order and delete its sub-categories. Three levels at most: a third-level category has no such table. A category holding products or sub-categories cannot be deleted |
| Product comparison | Up to 4 products kept in the visitor's session (`App\Services\Storefront\Comparison`): the compare button of product cards and pages, the bottom bar of the chosen products, the header counts and `/comparer` (price, availability, reviews, brand, category, options, delivery, returns, then one row per specification label of the products, from their **Caractéristiques**). `public/assets/js/compare.js` (own classes: the theme's compare buttons redirect to a template page) |
| Newsletter | **Promotions › Newsletter** (`newsletter_subscribers`): sign-ups from the footer and the invitation window, welcome e-mail with the one-click unsubscribe link (confirmed on a page, so mail scanners unsubscribe nobody), CSV export of the active subscribers with their unsubscribe link for the e-mailing tool. Unsubscribed rows are kept as proof; **Effacer** removes one on request. **Paramètres › Newsletter**: footer form and window on/off, texts, image and delay (the window opens once per visitor, never during a purchase, a payment or in the customer area) |
| Customer testimonials | **Contenus › Témoignages clients** (`testimonials` table): shown in the sign-in and sign-up windows; "Client vérifié" only for a customer who really ordered. None published: the windows show the store's guarantees (delivery, payment) instead of any review |
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

Tracking (F-126, F-127): the courier list shows deliveries, failures, success rate and the cash to hand over (cash
of delivered orders not received yet). **Encaissements reçus** marks it all as received, with who received it, in the
audit log; the courier page lists every delivery with its cash. On the order page, **Date de livraison prévue**
tells the customer by SMS (and e-mail); the customer's tracking and order pages show that date and, while the order
is on its way, the courier's name, photo and phone.

## Promo codes

**Promotions › Codes promo** (`promotions.gerer`): fixed amount, percentage (rounded to the franc) or free delivery,
with dates, a minimum order excluding delivery, a total cap and a per-customer cap (the customer is recognised by
phone number, or by account), on the whole catalog or on chosen categories (sub-categories included) or products.
"Affiché dans la boutique" lists the code in the cart's "Codes promo" window. Each use is listed under
**Utilisations**; a cancelled order gives its use back. A used code cannot be deleted, only switched off. Locally,
`DemoCouponsSeeder` creates BIENVENUE10, LIVRAISON and MOINS5000.

## Security

- **HTTPS** (F-140): production generates https links only and sends session and cart cookies as `Secure`, `HttpOnly`,
  `SameSite=Lax`. HSTS (one year, sub-domains) is sent on HTTPS responses; the http → https redirect and the
  certificate (Let's Encrypt) are set on the web server.
- **Headers** (F-143): `App\Http\Middleware\SecurityHeaders` adds the content security policy, `nosniff`,
  `X-Frame-Options`, the referrer policy and a restrictive `Permissions-Policy` to every response. Outside origins
  allowed by the policy are listed in `config/security.php`: add a new outside service there (analytics, payment
  page), after trying it with `SECURITY_CSP_REPORT_ONLY=true`.
- **Robots** (F-141): once `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY` are set, the contact, sign-up, checkout,
  tracking and "Me prévenir" forms require the Turnstile check (`x-turnstile` + `App\Rules\PassesTurnstile`). The API
  cannot show the widget and relies on its rate limits. Every public form and the API are throttled.
- **Uploads** (F-142): images only (JPG, PNG, WebP, 5 MB), typed from their content, stored under a random name as
  WebP at most 1600 px wide, with a 480 px copy that product cards load through `srcset` (`ImageOptimizer`).
  They are served from `public/uploads` because the storefront shows them; the CSV import stays in `storage/app`.
- **Passwords** (F-144): 8 characters minimum, hashed (bcrypt).
- **Deletion** (F-145): nothing the specification protects is erased from the back-office. Products are switched off
  (reversible), orders are never deleted, customers are anonymised on their own request, and a removed staff account is
  kept: it can no longer sign in and **Équipe › Comptes supprimés › Restaurer** brings it back.
- **Dependencies** (F-146): `.github/workflows/ci.yml` runs the tests, `composer audit` and `npm audit` on every push
  and every Monday; a known vulnerability fails the run. Run `composer audit` before each deployment.

## Online payment (CinetPay)

F-060 to F-067, adapted from the CinetPay integration of MesRévisions to KOVA MARKET's orders. The method "Paiement en
ligne" (Orange Money, MTN MoMo, Moov Money, Wave, card) is offered at checkout once `CINETPAY_API_KEY` and
`CINETPAY_API_PASSWORD` (KOVA MARKET's own merchant account, CinetPay API v1) are set; **Paramètres de la boutique ›
Paiement** says whether it is active and sets the unpaid timeout.

| Step | What happens |
| --- | --- |
| Order | Placed as usual (stock reserved, payment "En attente"), then the customer is sent to CinetPay's page. A `payments` row records the attempt (our reference `KM…`, CinetPay's ids, the SHA-256 of the notify token) |
| Notification | CinetPay posts to `/paiement/cinetpay/notification` (no CSRF, open on the preproduction). A notification without the notify token of its payment is ignored; otherwise the outcome is **fetched from CinetPay** (`GET /v1/payment/{token}`), never read from the notification. Always answers 200 |
| Return | `/paiement/retour/{référence}` (success and failure) checks the status the same way; the customer who placed the order lands on its confirmation, anyone else sees the outcome only. The confirmation checks again on each visit and refreshes itself while CinetPay has not answered |
| Paid | The order becomes "Payé" and is announced then (customer SMS, staff alert): unpaid online orders are never announced. Applying a success locks the payment row: notification and return page may arrive together |
| Failed / abandoned | The attempt is closed; the order stays payable with **Payer maintenant** (a new attempt) |
| Timeout (F-056) | `payments:expire-unpaid` (every 5 minutes) cancels online orders unpaid after 30 minutes (setting), after a last status check; stock and promo code use are given back, the customer is not notified. CinetPay unreachable: the order waits for the next run |
| Refund (F-067) | Made in CinetPay's merchant space (the API has no refund call), then **Enregistrer un remboursement** on the order's **Paiements en ligne** tab: payment and order "Remboursé", audit log |

**Ventes › Paiements** gathers every attempt of every order (orders' view permission to see, management permission
to record a refund):

- figures: taken today and this month, success rate over 30 days, attempts still open, payments to refund;
- tabs Tous / Réussis / En cours / Échoués ou annulés / Remboursés / **À rembourser** (paid online, order cancelled
  since; also the red badge of the menu entry), filters by status, operator, period, search by order, customer,
  phone or either reference;
- the payment page: amounts, references, operator, payer's phone, the order, and the journal in words (initiation,
  notifications, what CinetPay answered and from where it was asked);
- **Vérifier**, **Enregistrer un remboursement**, and **Exporter (CSV)** of the payments shown (tab, filters and
  search applied; semicolons, opens in Excel) to reconcile with CinetPay's statements.

The order page lists its own attempts (status, operator, both references, reason) with the same actions.
Amounts are sent rounded up to 5 FCFA (a CinetPay rule for XOF), the difference kept in the attempt's journal. A
customer without e-mail is sent to CinetPay with `CINETPAY_FALLBACK_EMAIL` (else the store's contact e-mail), CinetPay
requiring one. A payment received after its order was cancelled is logged "à rembourser" in the audit log. A cancelled
order already paid online stays "Payé" until its refund is recorded.

## Not in the back-office yet

Products are switched off rather than deleted. The storefront still
shows the colour swatches typed on the product; they switch to the variants with the product page.
