<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Makes the courier area installable on the phone's home screen (F-125): manifest and service worker, both
 * served under /livreur so the app's scope is the courier area only.
 */
class AppController extends Controller
{
    public function manifest(): JsonResponse
    {
        return response()->json([
            'name' => config('storefront.name').' Livreur',
            'short_name' => 'Livreur',
            'lang' => 'fr',
            'start_url' => route('courier.home', absolute: false),
            'scope' => '/livreur/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#ffffff',
            'theme_color' => '#1d3fbf',
            'icons' => [
                ['src' => asset('assets/images/courier/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => asset('assets/images/courier/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ], headers: ['Content-Type' => 'application/manifest+json']);
    }

    public function serviceWorker(): Response
    {
        return response(view('courier.service-worker')->render(), headers: [
            'Content-Type' => 'application/javascript',
            'Service-Worker-Allowed' => '/livreur/',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
