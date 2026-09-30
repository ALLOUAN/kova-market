<?php

namespace App\Http\Middleware;

use App\Services\Storefront\Maintenance;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shows the maintenance page to visitors while the mode is on (Administration › Maintenance). Stay open: the
 * back-office, the courier app, CinetPay's notifications and customers coming back from paying (an order paid during
 * the switch must still be recorded), and the preview of the page itself.
 */
class MaintenanceMode
{
    private const OPEN = ['livreur', 'livreur/*', 'paiement/*', 'maintenance/apercu', 'up'];

    public function __construct(private Maintenance $maintenance) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->maintenance->enabled() || $this->staysOpen($request) || $this->maintenance->lets($request)) {
            return $next($request);
        }

        $backAt = $this->maintenance->expectedBackAt();
        $retryAfter = $backAt?->isFuture() ? (int) now()->diffInSeconds($backAt) : 600;

        return response()->view('maintenance', ['maintenance' => $this->maintenance], 503, ['Retry-After' => $retryAfter]);
    }

    private function staysOpen(Request $request): bool
    {
        $admin = trim((string) config('admin.path'), '/');

        return $request->is(self::OPEN) || $request->is($admin, $admin.'/*') || $request->routeIs('*livewire.*');
    }
}
