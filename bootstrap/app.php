<?php

use App\Http\Middleware\EnsureCourier;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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

        $middleware->alias(['courier' => EnsureCourier::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
