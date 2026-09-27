<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Catalog: these route names turn the menu, card and brand links from "#" into real URLs.
Route::get('/boutique', [CatalogController::class, 'index'])->name('shop.index');
Route::get('/categorie/{category:slug}', [CatalogController::class, 'category'])->name('categories.show');
Route::get('/marque/{brand:slug}', [CatalogController::class, 'brand'])->name('brands.show');
Route::get('/produit/{product:slug}', [ProductController::class, 'show'])->name('products.show');

// Cart (F-040 to F-044). Changes are throttled per visitor.
Route::get('/panier', [CartController::class, 'show'])->name('cart.show');
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/panier/articles', [CartController::class, 'store'])->name('cart.items.store');
    Route::patch('/panier/articles/{item}', [CartController::class, 'update'])->whereNumber('item')->name('cart.items.update');
    Route::delete('/panier/articles/{item}', [CartController::class, 'destroy'])->whereNumber('item')->name('cart.items.destroy');
    Route::post('/panier/commune', [CartController::class, 'commune'])->name('cart.commune');
});

// Checkout (F-050 to F-056): guests allowed, order placement throttled.
Route::get('/commande', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/commande', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
Route::get('/commande/{order:number}/merci', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');

// Public order tracking by number + phone (F-073), throttled against guessing.
Route::get('/suivi', [OrderTrackingController::class, 'show'])->name('tracking.show');
Route::post('/suivi', [OrderTrackingController::class, 'search'])->middleware('throttle:10,1')->name('tracking.search');

// Customer area (F-070 to F-075).
Route::middleware('auth')->prefix('compte')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'show'])->name('show');
    Route::get('/commandes', [AccountController::class, 'orders'])->name('orders');
    Route::get('/commandes/{order:number}', [AccountController::class, 'order'])->name('orders.show');
    Route::post('/preferences', [AccountController::class, 'preferences'])->name('preferences');
    Route::get('/donnees', [AccountController::class, 'export'])->name('export');
    Route::delete('/', [AccountController::class, 'destroy'])->middleware('throttle:5,1')->name('destroy');

    Route::get('/adresses', [AddressController::class, 'index'])->name('addresses.index');
    Route::post('/adresses', [AddressController::class, 'store'])->name('addresses.store');
    Route::get('/adresses/{address}/modifier', [AddressController::class, 'edit'])->name('addresses.edit');
    Route::put('/adresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
    Route::post('/adresses/{address}/defaut', [AddressController::class, 'makeDefault'])->name('addresses.default');
    Route::delete('/adresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
});

Route::get('/page/{page:slug}', PageController::class)->name('pages.show');
Route::get('/faq', FaqController::class)->name('faq');
