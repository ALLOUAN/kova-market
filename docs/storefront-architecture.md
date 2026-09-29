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
| `add_to_cart` | page following an add to cart (session) | AddToCart |
| `begin_checkout` | checkout page | InitiateCheckout |
| `purchase` | confirmation page, once (session) | Purchase / PlaceAnOrder |

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
