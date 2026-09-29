<?php

use App\Http\Middleware\ApplyStoreSettings;
use App\Http\Middleware\EnsureCourier;
use App\Http\Middleware\ProtectPreproduction;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\UseApiGuard;
use App\Services\Cart\CartException;
use App\Services\Checkout\CheckoutException;
use App\Services\Promotions\CouponException;
use App\Support\SlugRedirector;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The storefront has no login page: guests land on the home page with the sign-in modal open,
        // then come back to the page they asked for (Fortify redirects to the intended URL).
        // Couriers have their own sign-in page.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('livreur', 'livreur/*')
            ? route('courier.login')
            : route('home', ['connexion' => 1]));

        // HTTPS, HSTS, content security policy and the other security headers on every response (F-140, F-143).
        $middleware->append(SecurityHeaders::class);
        // Texts, images and menus edited in the back-office (F-111).
        $middleware->web(prepend: [ApplyStoreSettings::class]);
        $middleware->prepend(ProtectPreproduction::class);

        $middleware->alias(['courier' => EnsureCourier::class, 'api.guard' => UseApiGuard::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Production errors go to Sentry once SENTRY_LARAVEL_DSN is set (F-172); no personal data is sent.
        Integration::handles($exceptions);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A product, category, brand or page asked with a former slug is redirected to its current address (F-150).
        $exceptions->render(fn (NotFoundHttpException $exception, Request $request) => $exception->getPrevious() instanceof ModelNotFoundException && ! $request->is('api/*')
            ? SlugRedirector::respond($request, $exception->getPrevious())
            : null);

        // Business refusals (stock, commune, promo code...) reach API clients as 422 with their message;
        // the storefront controllers catch them to show the message on the page.
        $exceptions->render(fn (CartException|CheckoutException|CouponException $exception, Request $request) => $request->is('api/*')
            ? response()->json(['message' => $exception->getMessage()], 422)
            : null);
    })->create();
