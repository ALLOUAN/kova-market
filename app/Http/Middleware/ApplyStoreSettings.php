<?php

namespace App\Http\Middleware;

use App\Services\Storefront\ConfigOverrides;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lays the texts, images and menus edited in the back-office over the configuration for this request (F-111).
 */
class ApplyStoreSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        ConfigOverrides::apply();

        return $next($request);
    }
}
