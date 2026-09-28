<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\OrderController;
use Illuminate\Support\Facades\Route;

// REST API v1 (F-160) for the mobile app: Sanctum bearer tokens, guest carts through the X-Cart-Token header,
// 60 requests a minute, stricter limits where guessing or abuse is possible (as on the storefront).
// Contract: docs/api/openapi-v1.yaml, also served at /api/v1/openapi.yaml.
Route::prefix('v1')->name('api.v1.')->middleware(['api.guard', 'throttle:api'])->group(function () {
    Route::get('/openapi.yaml', fn () => response()->file(base_path('docs/api/openapi-v1.yaml'), ['Content-Type' => 'application/yaml']))->name('openapi');

    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('auth.register');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('auth.login');

    // Catalog and search.
    Route::get('/categories', [CatalogController::class, 'categories'])->name('categories.index');
    Route::get('/categories/{category:slug}/products', [CatalogController::class, 'category'])->name('categories.products');
    Route::get('/brands', [CatalogController::class, 'brands'])->name('brands.index');
    Route::get('/brands/{brand:slug}/products', [CatalogController::class, 'brand'])->name('brands.products');
    Route::get('/products', [CatalogController::class, 'products'])->name('products.index');
    Route::get('/products/{product:slug}', [CatalogController::class, 'product'])->name('products.show');
    Route::get('/communes', [CatalogController::class, 'communes'])->name('communes.index');

    // Cart: guests and customers.
    Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
    Route::patch('/cart/items/{item}', [CartController::class, 'update'])->whereNumber('item')->name('cart.items.update');
    Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->whereNumber('item')->name('cart.items.destroy');
    Route::put('/cart/commune', [CartController::class, 'commune'])->name('cart.commune');
    Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->middleware('throttle:10,1')->name('cart.coupon.store');
    Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.destroy');

    // Orders: placing one (guests allowed) and public tracking by number + phone.
    Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:10,1')->name('orders.store');
    Route::post('/orders/track', [OrderController::class, 'track'])->middleware('throttle:10,1')->name('orders.track');

    // Customer account.
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('/account', [AccountController::class, 'show'])->name('account.show');
        Route::patch('/account/preferences', [AccountController::class, 'preferences'])->name('account.preferences');
        Route::get('/account/orders', [AccountController::class, 'orders'])->name('account.orders.index');
        Route::get('/account/orders/{order:number}', [AccountController::class, 'order'])->name('account.orders.show');
        Route::get('/account/data', [AccountController::class, 'export'])->name('account.export');
        Route::delete('/account', [AccountController::class, 'destroy'])->middleware('throttle:5,1')->name('account.destroy');

        Route::get('/account/addresses', [AddressController::class, 'index'])->name('account.addresses.index');
        Route::post('/account/addresses', [AddressController::class, 'store'])->name('account.addresses.store');
        Route::put('/account/addresses/{address}', [AddressController::class, 'update'])->name('account.addresses.update');
        Route::post('/account/addresses/{address}/default', [AddressController::class, 'makeDefault'])->name('account.addresses.default');
        Route::delete('/account/addresses/{address}', [AddressController::class, 'destroy'])->name('account.addresses.destroy');
    });
});
