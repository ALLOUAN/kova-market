# Storefront architecture

The storefront is built from the "Home Electronics" page of the Unimart HTML template
(`html.getunimart.com/home-electronics.html`), split into a Laravel layout, partials and Blade components.

## Request flow

`GET /` → `HomeController` → `HomePageService::data()` → `pages/home.blade.php`

The layout (`layouts/storefront`) receives its data from `StorefrontLayoutComposer`
(menus, category tree, brands, promotions, contact details).
`CatalogService` and `NavigationService` are scoped singletons: their queries run once per request.

## Where things live

| Concern | Location |
| --- | --- |
| Store identity, contact, currencies, product card behaviour | `config/storefront.php` |
| Menus (main, side panel, footer, legal) | `config/navigation.php` |
| Home hero slides and banners | `config/homepage.php` |
| Catalog: `Category` (3-level tree), `Brand`, `Product`, `Collection`, `Promotion` | `app/Models`, `database/migrations` |
| Demo catalog | `database/seeders/CatalogSeeder.php` + `database/seeders/data/catalog.php` |
| Layout | `resources/views/layouts/storefront.blade.php` |
| Header, menus, mega menus | `resources/views/partials/header` |
| Side panels (categories, cart, offers, quick view, compare bar) | `resources/views/partials/offcanvas` |
| Modals | `resources/views/partials/modals` (wrapped by `<x-modal>`) |
| Footer, newsletter, mobile toolbar | `resources/views/partials/footer` |
| Home page sections | `resources/views/pages/home/*` |
| Reusable UI | `resources/views/components` (`product.*`, `category.card`, `brand.card`, `promotion.card`, `section-title`, `countdown`, `modal`) |
| Theme assets (Bootstrap theme, plugins, fonts, images) | `public/assets` |

## Merchandising

Home sections list the products of a `Collection` (ordered by the pivot `position`):
`deals-of-the-day`, `todays-best-deals` (its `ends_at` drives the countdown), `weekly-highlights`,
`featured-products` (first product is spotlighted) and `trending-searches` (search dropdown).

Product cards derive their state from the data: "Sold Out" + "Notify Me" when `stock = 0`,
"Limited Stock" under `storefront.product_card.limited_stock_threshold`, discount badge from
`compare_at_price`, countdown from `sale_ends_at`, perks from `sold_count` / `free_shipping` / `return_days`.

## Adding a page

1. Add a route and a controller; return a view that `@extends('layouts.storefront')`.
2. Push page-only modals with `@push('modals')` (ready partials: `size-guide`, `coupons`,
   `find-store`, `instagram`).
3. Give menu entries a `route` in `config/navigation.php`. Product, category and brand links switch from
   `#` to real URLs automatically once the `products.show`, `categories.show` and `brands.show` routes exist.

## Catalog pages

| Route | Page |
| --- | --- |
| `shop.index` `/boutique` | All products and search results (`?q=`); `?category=` from the header search redirects to the category page |
| `categories.show` `/categorie/{slug}` | Category and all its sub-categories |
| `brands.show` `/marque/{slug}` | Brand |
| `products.show` `/produit/{slug}` | Product page: gallery with zoom, variant selector, description, specifications, sharing, recommendations |

The lists share `App\Services\Storefront\ProductListing`: Scout search (database engine, Meilisearch later), filters
(price, availability, brands, sub-categories, attribute values: alternatives within an attribute, all attributes
together), sorting (`?tri=`) and 24 products per page, all kept in the query string.

## Not wired yet

The cart is wired (`App\Services\Cart\CartManager`, page `/panier`, mini-cart panel, header counters), with one
promo code per cart checked by `App\Services\Promotions\CouponValidator` on every display and once more, under a
lock, when the order is placed; public codes are listed in the `coupons` modal of the cart page. The quick view
of the product cards loads `products.quick-view` (`/produit/{slug}/apercu`) into its modal or side panel through
`public/assets/js/storefront.js`; it shares the `x-product.purchase` block (variants, quantity, cart buttons) with the
product page. The template's "popup cart" and "edit cart" modals were removed: the mini-cart and `/panier` replace
them. "Me prévenir" (back-in-stock alerts, `stock-alerts.store`) is offered by the `notify` modal of sold-out cards and
inside the purchase block when the chosen variant is sold out; `StockManager` raises `BackInStock` when a stock goes
from 0 to more, and each alert is sent once. The template's "restock" modal (a sample "welcome back" product) was
removed. Wishlist,
compare and newsletter forms are UI shells from the template (static content in their partials). Checkout: `/commande` (cash on delivery). "Recently viewed" shows the weekly highlights until
per-visitor history is implemented. Sign-in and sign-up modals are wired (phone or e-mail); pages needing an account send guests to `/?connexion=1`, which
opens the sign-in modal, then back to the page they asked for.

## Customer area and tracking

| Route | Page |
| --- | --- |
| `account.show` `/compte` | Recent orders, profile and password (Fortify endpoints), marketing preference, data export and account deletion (anonymisation, refused while an order is open) |
| `account.orders`, `account.orders.show` | Order history and order detail with its progress |
| `account.addresses.*` `/compte/adresses` | Address book, one default address, used to prefill the checkout |
| `password.request` `/mot-de-passe-oublie` | Forgotten password (F-076, `PasswordRecovery`): a phone number gets a 6-digit SMS code (15 min, 5 tries, single use, one SMS a minute), an e-mail a single-use link (`password.reset`, 60 min). Customer accounts only; the answer never tells whether an account exists; the reset signs the account out everywhere. Fortify's own reset routes are off |
| `tracking.show` `/suivi` | Public tracking by order number + phone (same answer for any mismatch, commune only) |

## REST API v1

`/api/v1` (F-160) exposes the storefront to the mobile app: catalog and search, cart, order placement, public tracking,
account and address book. The contract is `docs/api/openapi-v1.yaml`, also served at `/api/v1/openapi.yaml`.

| Concern | Location |
| --- | --- |
| Routes | `routes/api.php` (names `api.v1.*`) |
| Thin controllers | `app/Http/Controllers/Api/V1` |
| JSON shapes | `app/Http/Resources/Api/V1` |
| Shared validation | `AddToCartRequest`, `AddressRequest`, `PlaceOrderRequest` (storefront and API) |

The API calls the same services as the storefront (`ProductListing`, `CartManager`, `PlaceOrder`, `AccountEraser`),
so no business rule is written twice; orders placed through it have `source = mobile`. Customers sign in with a
Sanctum bearer token (`/auth/login`, same credentials and limiter as the storefront; suspended accounts lose their
tokens' access). `UseApiGuard` switches every API route to the `sanctum` guard, so a token is recognised on public
routes too. Guest carts travel in the `X-Cart-Token` header (the cart's `token`) instead of the cookie; sending it at
sign-in merges it into the account's cart. `CartException`, `CheckoutException` and `CouponException` become `422`
with their message (`bootstrap/app.php`). Limits: 60 requests a minute (`api` limiter), 10 for sign-up, promo codes,
orders and tracking, 5 for sign-in. Online payment (CinetPay) will add its own endpoint.

## Search engines

| Concern | Where |
| --- | --- |
| Title, description | `@section('title')`, `@section('description')`; products, categories, brands and pages have editable `meta_title` / `meta_description` (back-office "Référencement"), generated values otherwise |
| Canonical, Open Graph, Twitter Card | layout head, overridden with `@section('canonical')`, `og_type`, `og_image`, `twitter_card` (the product page uses all four) |
| Indexing | `@section('robots')`: cart, checkout, account, tracking and password pages are `noindex, nofollow`; searches and filtered lists `noindex, follow`; paginated lists are canonical to their own page |
| Structured data | `App\Services\Storefront\StructuredData` through `<x-json-ld>`: Product + Offer (AggregateOffer when variant prices differ) and BreadcrumbList on product pages, BreadcrumbList on catalog pages, Organization + WebSite (search box) on the home page |
| Former slugs | `RedirectsOldSlugs` (products, categories, brands, pages) records every old slug in `slug_redirects`; `App\Support\SlugRedirector` answers the old address with a 301 to the current one, query string kept |
| Sitemap | `seo:sitemap` (scheduled nightly at 03:00) writes `storage/app/private/seo/sitemap.xml`, served at `/sitemap.xml` (generated on first request if missing) |
| robots.txt | `SeoController::robots`: production disallows the private sections and points to the sitemap; any other environment disallows everything, so a preproduction copy stays out of Google. The back-office path is never listed |

Check a product page with Google's Rich Results Test once the site is online (it cannot reach a local copy).

## Audience measurement and consent

Google Analytics 4, the Meta pixel and the TikTok pixel (F-155) are set in **Paramètres de la boutique › Mesure
d'audience**, with the Search Console verification code. `App\Services\Storefront\Analytics` puts the identifiers and
the page's e-commerce events in `window.kovaAnalytics`; `public/assets/js/analytics.js` shows the consent banner and
injects the trackers only after "Accepter" (F-156). The choice is kept six months in the `kova_consent` cookie
(`granted` / `denied`) and changed with the footer link "Gérer les cookies". Without any identifier, neither the
banner nor the script is output. The theme's own banner logic (localStorage only) is switched off.

| Event | Sent from | Meta / TikTok name |
| --- | --- | --- |
| `view_item` | product page | ViewContent |
| `add_to_cart` | answer of the background add to cart (`window.kovaTrack`), else the next page | AddToCart |
| `add_to_wishlist` | answer of the heart button | AddToWishlist |
| `search` | first page of a catalogue search | Search |
| `begin_checkout` | checkout page | InitiateCheckout |
| `add_payment_info` | server only, when leaving for CinetPay | AddPaymentInfo |
| `purchase` | confirmation page, once (session) | Purchase / PlaceAnOrder |
| `generate_lead` | answer of a new newsletter sign-up | Lead / SubmitForm |
| `sign_up` | page following the account creation | CompleteRegistration |
| `contact` | page following the contact form; click on the store's WhatsApp (browser only) | Contact |

Every event has an id. With `META_CONVERSIONS_TOKEN` set, `App\Services\Storefront\MetaConversions` also sends the
Meta events from the server (Conversions API, queued job `SendMetaConversionEvent`) with the same id, so that Meta
keeps one of the two. Only for visitors whose `kova_consent` cookie says `granted` (it is not encrypted, nor are
`_fbp` / `_fbc`, so the server can read them); e-mail, phone and name are SHA-256 hashed. The sale is sent by the
server once, with the id `purchase-{number}`: at the order for cash on delivery, at CinetPay's confirmation for
online payments, using what was kept in `orders.tracking` when the order was placed (consent only). Set
`META_TEST_EVENT_CODE` to see the server events in Events Manager's "Test events" tab.

The trackers' origins are allowed in `config/security.php`; a new tracker needs its origins there too.

## End-to-end tests

`tests/e2e` (Playwright, Chromium) runs real journeys in a browser: a guest purchase with cash on delivery then its
public tracking, the checkout refusal without consent to the terms, sign-up and forgotten password, the consent
banner (no request to an advertising domain before "Accepter", the three trackers after it), and the back-office
sign-in with two-factor authentication.

```sh
npm install --ignore-scripts && npx playwright install chromium
npm run e2e                     # PHP_BIN=/path/to/php8.4 when `php` is another version
```

`playwright.config.js` starts `php artisan serve` on port 8123 with `APP_ENV=e2e`: `tests/e2e/environment.js` writes
`.env.e2e` (a throwaway SQLite file, SMS and e-mails to the log, no cache), so the tests never touch the database of
`.env`. The global setup recreates that database with the demo catalog, gives the super-admin a known authenticator
secret (`tests/e2e/support.js` computes its codes) and sets fake tracker identifiers. Every test but the consent ones
starts with the banner already answered. The CI runs them on each push.

## Online payment

`PaymentMethod::Online` (CinetPay) is offered by `PaymentMethod::available()` once the CinetPay keys are set.
`App\Services\Payments\CinetPayClient` speaks CinetPay API v1 (OAuth token cached 23 h, `POST /v1/payment`,
`GET /v1/payment/{token}`, one retry on an expired token); `OnlinePayments` runs the order's payment: `start` (an
attempt in `payments`, redirection URL), `synchronize` (the only place an outcome is applied, from the status check),
`handleNotification`, `expireUnpaid` (F-056) and `recordRefund` (F-067). `PaymentController` holds the storefront
routes (`payments.pay`, `payments.return`, `payments.notify`); the API returns `payment.url` with the order and has
`POST /api/v1/orders/{number}/payment`. Details and back-office side: `docs/back-office.md` › Online payment. The
end-to-end journeys run against `tests/e2e/fake-cinetpay.js`, a stand-in for the API and its payment page.

## Delivery modes: Abidjan or interior

The zone of the chosen destination sets `App\Enums\DeliveryMode` (`delivery_zones.delivery_mode`), copied on the order
(`orders.delivery_mode`):

| Mode | Destination chosen | Order flow | Who delivers |
| --- | --- | --- | --- |
| `abidjan` | a commune of Abidjan | Reçue → Confirmée → En préparation → En livraison → Livrée (no "Expédiée") | a KOVA courier (dispatched to the couriers of the zone) |
| `interieur` | "Intérieur" (single destination of the "Intérieur du pays" zone) + **required town** (`destination_city`) | Reçue → Confirmée → En préparation → Expédiée → Livrée | a carrier; never offered to the Abidjan couriers |

- `OrderStatus::next()` / `previous()` take the mode; use `$order->nextStatuses()`, `$order->canBecome()`,
  `$order->previousStatus()` and `$order->flow()`, never the status alone.
- For the interior, `commune_name` holds the town, so SMS, slips and receipts name it; `destinationLabel()` gives
  "Bouaké (intérieur)" for the back-office.
- Every customer-facing timeline (thank-you page `CustomerProgress`, `x-order-timeline` on tracking and account, API
  `steps`) shows only the steps of the order's flow.
- The mode of a zone is set in the back-office (Zones de livraison); its fee and delay apply as for any zone.

## Couriers' cash and remittances

Cash collected on delivery (`orders.cash_collected`) is handed over to the store in one or several payments
(`App\Models\CourierRemittance`, back-office: Livreurs › a courier › Versements). Rules, all in
`App\Services\Delivery\CashSettlement`:

- **Owed** by a courier = cash of their delivered orders − what was already handed over (`orders.cash_remitted`).
- A payment (`record()`) may be partial; it covers the **oldest orders first** (`courier_remittance_order` keeps the
  part of each order) and can never exceed what is owed. An order is settled (`cash_settled_at`) once all its cash
  is in. `balance_after` keeps what was still owed right after the payment.
- A payment is **never deleted**: `cancel()` marks it cancelled with its reason and its orders owe their part again.
- Each payment has a PDF receipt (`RemittanceReceipt`, number `VER-000012`) to sign by both sides.
- When a courier types another amount than the one due, a reason is required (`orders.cash_note`) and the staff
  allowed to record payments get a back-office alert.
- `CourierFinances` gives the figures of the courier page (deliveries counted on their delivery date, payments on
  the day received) and the history of movements.
- Permissions: `finances.consulter` (see the figures, payments, history) and `finances.versements` (record or
  cancel a payment); managers and super-admins have both, pickers and couriers neither.

## Finance dashboard

Back-office › Ventes › Finances (`App\Filament\Pages\Finances`, permission `finances.consulter`). Every figure comes
from `App\Services\Finance\FinanceReport`; the CSV export gives the same ones. Periods (`FinancePeriod`): today,
this week, this month, this year or chosen dates, compared with the same length just before.

- **Sales and revenue**: as on the dashboard (`SalesFigures::sales()`): orders placed in the period, not cancelled,
  not an online order still unpaid. Revenue = order totals, split into products and delivery fees.
- **Online takings**: CinetPay payments received in the period, minus the refunds made in the period.
- **Takings on delivery**: cash collected at the deliveries made in the period (date of the "Livrée" step).
- **Couriers**: payments received in the period; "argent encore chez les livreurs" and "reste dû" are today's balance.
- No courier commission: the platform pays none for now, delivery fees are the store's revenue.

The orders list also sums the orders shown (products, fees, total), filters by payment method, offers period
shortcuts and exports the orders shown (CSV).

## Delivery zone conditions

Optional, set per zone in the back-office (Zones de livraison › Conditions particulières), rules in
`App\Models\DeliveryZone`:

- `min_order`: below this amount of goods the cart shows what is missing and disables "Commander", `/commande`
  sends back to the cart, and `PlaceOrder` refuses the order (`missingForMinimum()`).
- `free_shipping_threshold`: free delivery from this amount of goods; empty, the store's general threshold
  (Paramètres) applies (`freeShippingThreshold()`, `feeFor()`), used by the cart, the checkout script and `PlaceOrder`.
- `delivery_days`: ISO weekdays the zone is delivered (none or all seven: every day), shown with the other
  conditions (`conditionsLabel()`) in the cart, at checkout and in the API (`/api/v1/communes`, `/api/v1/cart`).

## Courier app: "Mes encaissements"

`/livreur/encaissements` (`Courier\MoneyController`): what the courier owes today, their deliveries, cash collected
and payments over today / this week / this month (`CourierFinances`), their payments with the PDF receipt
(`/livreur/versements/{id}/recu`, 404 for another courier's) and the history. No earnings: no commission for now.

## Delivery dashboard

Back-office › Livraison › Tableau de bord (`App\Filament\Pages\DeliveryDashboard`, permission `livraison.gerer`),
refreshed every 30 seconds; the menu badge counts the orders to assign. Figures from `DeliveryBoard`:

- a header band (date, live state, shortcuts), four large figures (to assign, on the way, delivered today with the
  7-day success ring, cash with couriers) and chips (to prepare, interior in transit, failures, available couriers);
- `DispatchQueue` (`app/Filament/Delivery/Widgets`, not on the main dashboard): orders without courier, oldest
  first, "Attribuer" with the couriers of the zone first (`DeliveryDispatcher::courierOptions()`);
- "À surveiller": orders without courier for more than `WAITING_ALERT_HOURS` (2 h), on the way for more than
  `ON_THE_WAY_ALERT_HOURS` (3 h), open Abidjan zones without any available courier;
- each courier's day (in progress, delivered, failed, cash owed) and each zone's activity and coverage.

Times are those of the order's last status change (status history).

## Newsletter campaigns

Back-office › Promotions › Campagnes e-mail (`NewsletterCampaignResource`, permission `promotions.gerer`). A campaign
(subject, preview line, optional JPEG/PNG cover, rich content, optional button) is a draft until sent or scheduled;
`App\Services\Newsletter\CampaignSender` does the rest:

- **Test**: `sendTest()` sends it to one address, subject prefixed "[TEST]" and a banner in the message.
- **Launch** (`launch()`, or `newsletter:send-scheduled` every minute for scheduled ones): the active subscribers are
  frozen as `newsletter_campaign_recipients`, then `SendNewsletterCampaignBatch` jobs send 50 e-mails each through
  the queue. A recipient is sent once (retries skip handled rows); someone unsubscribed meanwhile is skipped.
- **Stop** (`stop()`): the e-mails not sent yet are skipped, the campaign is "Annulée".
- **E-mail** (`NewsletterCampaignMail`, `mail.newsletter-campaign`): the store's mail theme, unsubscribe link in the
  text and in the `List-Unsubscribe` header.

The e-mails leave when the queue runs: on the hosting the scheduler empties it every minute. Locally, set
`QUEUE_CONNECTION=sync` so that they leave at once without a worker (the campaign is sent during "Envoyer
maintenant"); `demarrer-local.bat` also runs `schedule:work` for scheduled campaigns. With `MAIL_MAILER=log`
they are written to `storage/logs/laravel.log` instead of being sent.
