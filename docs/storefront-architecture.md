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
2. Push page-only modals with `@push('modals')` (ready partials: `size-guide`, `restock`, `coupons`,
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

Cart, wishlist, compare and newsletter forms are UI shells from the template (static content in their partials); the
product page buttons stay disabled until the cart exists. "Recently viewed" shows the weekly highlights until
per-visitor history is implemented. Sign-in and sign-up modals are wired (phone or e-mail).
