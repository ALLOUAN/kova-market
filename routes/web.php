<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\PasswordResetController;
use App\Http\Controllers\Account\ReviewController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Courier\AppController as CourierAppController;
use App\Http\Controllers\Courier\AuthController as CourierAuthController;
use App\Http\Controllers\Courier\DeliveryController;
use App\Http\Controllers\Courier\PasswordController as CourierPasswordController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\StockAlertController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Search engines (F-154).
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

// Catalog: these route names turn the menu, card and brand links from "#" into real URLs.
Route::get('/boutique', [CatalogController::class, 'index'])->name('shop.index');
Route::get('/categorie/{category:slug}', [CatalogController::class, 'category'])->name('categories.show');
Route::get('/marque/{brand:slug}', [CatalogController::class, 'brand'])->name('brands.show');
Route::get('/selection/{collection:slug}', [CatalogController::class, 'collection'])->name('collections.show');
Route::get('/produit/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/produit/{product:slug}/apercu', [ProductController::class, 'quickView'])->name('products.quick-view');

// Back-in-stock alerts (EX-17), throttled against abuse.
Route::post('/alertes-stock', [StockAlertController::class, 'store'])->middleware('throttle:10,1')->name('stock-alerts.store');

// Cart (F-040 to F-044). Changes are throttled per visitor.
Route::get('/panier', [CartController::class, 'show'])->name('cart.show');
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/panier/articles', [CartController::class, 'store'])->name('cart.items.store');
    Route::patch('/panier/articles/{item}', [CartController::class, 'update'])->whereNumber('item')->name('cart.items.update');
    Route::delete('/panier/articles/{item}', [CartController::class, 'destroy'])->whereNumber('item')->name('cart.items.destroy');
    Route::post('/panier/commune', [CartController::class, 'commune'])->name('cart.commune');
    Route::delete('/panier/code-promo', [CartController::class, 'removeCoupon'])->name('cart.coupon.destroy');
});

// Promo codes (F-042): tries are throttled harder against guessing.
Route::post('/panier/code-promo', [CartController::class, 'applyCoupon'])->middleware('throttle:10,1')->name('cart.coupon.store');

// Checkout (F-050 to F-056): guests allowed, order placement throttled.
Route::get('/commande', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/commande', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
Route::get('/commande/{order:number}/merci', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');

// Online payment through CinetPay (F-060 to F-066): retry, return page, server-to-server notification.
Route::post('/commande/{order:number}/paiement', [PaymentController::class, 'pay'])->middleware('throttle:10,1')->name('payments.pay');
Route::match(['get', 'post'], '/paiement/retour/{payment}', [PaymentController::class, 'back'])->middleware('throttle:30,1')->name('payments.return');
Route::match(['get', 'post'], '/paiement/cinetpay/notification', [PaymentController::class, 'notify'])->middleware('throttle:120,1')->name('payments.notify');

// Public order tracking by number + phone (F-073), throttled against guessing.
Route::get('/suivi', [OrderTrackingController::class, 'show'])->name('tracking.show');
Route::post('/suivi', [OrderTrackingController::class, 'search'])->middleware('throttle:10,1')->name('tracking.search');

// Forgotten password (F-076): code by SMS or single-use link by e-mail, throttled against guessing and SMS abuse.
Route::middleware('guest')->group(function () {
    Route::get('/mot-de-passe-oublie', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [PasswordResetController::class, 'store'])->middleware('throttle:5,1')->name('password.send');
    Route::get('/mot-de-passe-oublie/code', [PasswordResetController::class, 'editWithCode'])->name('password.code');
    Route::post('/mot-de-passe-oublie/code', [PasswordResetController::class, 'updateWithCode'])->middleware('throttle:10,1')->name('password.code.update');
    Route::get('/reinitialiser-mot-de-passe/{token}', [PasswordResetController::class, 'editWithToken'])->name('password.reset');
    Route::post('/reinitialiser-mot-de-passe', [PasswordResetController::class, 'updateWithToken'])->middleware('throttle:10,1')->name('password.reset.update');
});

// Customer area (F-070 to F-075).
Route::middleware('auth')->prefix('compte')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'show'])->name('show');
    Route::get('/commandes', [AccountController::class, 'orders'])->name('orders');
    Route::get('/commandes/{order:number}', [AccountController::class, 'order'])->name('orders.show');
    Route::post('/commandes/{order:number}/avis/{item}', [ReviewController::class, 'store'])->middleware('throttle:10,1')->name('reviews.store');
    Route::post('/commandes/retrouver', [AccountController::class, 'claimGuestOrders'])->middleware('throttle:5,1')->name('guest-orders.claim');
    Route::post('/commandes/retrouver/confirmer', [AccountController::class, 'confirmGuestOrders'])->middleware('throttle:10,1')->name('guest-orders.confirm');
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

// Favourites ("Mes favoris"), for visitors and customers.
Route::get('/favoris', [WishlistController::class, 'index'])->name('wishlist.index');
Route::post('/favoris/{product:id}', [WishlistController::class, 'toggle'])->middleware('throttle:60,1')->name('wishlist.toggle');

// Product comparison (up to 4 products, kept for the visit).
Route::get('/comparer', [CompareController::class, 'index'])->name('compare.index');
Route::post('/comparer/{product:id}', [CompareController::class, 'toggle'])->middleware('throttle:60,1')->name('compare.toggle');
Route::delete('/comparer', [CompareController::class, 'clear'])->name('compare.clear');

// Newsletter: sign-up, then one-click unsubscribe from every e-mail (confirmed on the page).
Route::post('/newsletter', [NewsletterController::class, 'store'])->middleware('throttle:5,1')->name('newsletter.store');
Route::get('/newsletter/desinscription/{subscriber}', [NewsletterController::class, 'confirm'])->name('newsletter.unsubscribe');
Route::post('/newsletter/desinscription/{subscriber}', [NewsletterController::class, 'unsubscribe'])->middleware('throttle:10,1')->name('newsletter.unsubscribe.confirm');

Route::get('/page/{page:slug}', PageController::class)->name('pages.show');
Route::get('/faq', FaqController::class)->name('faq');

// Contact page (F-080), sending throttled against abuse.
Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

// Courier area (F-122 to F-125): its own sign-in, installable on the phone (manifest and service worker).
Route::prefix('livreur')->name('courier.')->group(function () {
    Route::get('/manifest.webmanifest', [CourierAppController::class, 'manifest'])->name('manifest');
    Route::get('/sw.js', [CourierAppController::class, 'serviceWorker'])->name('service-worker');

    Route::get('/connexion', [CourierAuthController::class, 'show'])->name('login');
    Route::post('/connexion', [CourierAuthController::class, 'store'])->middleware('throttle:20,1')->name('authenticate');

    Route::middleware(['auth', 'courier'])->group(function () {
        Route::post('/deconnexion', [CourierAuthController::class, 'destroy'])->name('logout');
        Route::get('/mot-de-passe', [CourierPasswordController::class, 'edit'])->name('password.edit');
        Route::put('/mot-de-passe', [CourierPasswordController::class, 'update'])->name('password.update');

        Route::get('/', [DeliveryController::class, 'index'])->name('home');
        Route::get('/commandes/{order:number}', [DeliveryController::class, 'show'])->name('orders.show');
        Route::post('/commandes/{order:number}/prendre', [DeliveryController::class, 'accept'])->name('orders.accept');
        Route::post('/commandes/{order:number}/en-route', [DeliveryController::class, 'start'])->name('orders.start');
        Route::post('/commandes/{order:number}/livree', [DeliveryController::class, 'deliver'])->name('orders.deliver');
        Route::post('/commandes/{order:number}/echec', [DeliveryController::class, 'fail'])->name('orders.fail');
    });
});
