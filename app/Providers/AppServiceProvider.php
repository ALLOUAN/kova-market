<?php

namespace App\Providers;

use App\Services\Storefront\CatalogService;
use App\Services\Storefront\NavigationService;
use App\View\Composers\StorefrontLayoutComposer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CatalogService::class);
        $this->app->scoped(NavigationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.storefront', StorefrontLayoutComposer::class);

        // @money($amount) formats an amount in the store currency, e.g. "$179.98".
        Blade::directive('money', fn (string $amount) => "<?php echo e(\\Illuminate\\Support\\Number::currency($amount, in: config('storefront.currency'))); ?>");
    }
}
