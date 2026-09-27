<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Services\Storefront\CatalogService;
use App\Services\Storefront\NavigationService;
use App\View\Composers\StorefrontLayoutComposer;
use Illuminate\Auth\Events\Login;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        $this->app->scoped(CartManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.storefront', StorefrontLayoutComposer::class);

        // The storefront theme is built on Bootstrap 5.
        Paginator::useBootstrapFive();

        // The guest cart joins the customer's cart at sign-in (also right after sign-up).
        Event::listen(Login::class, fn (Login $event) => $event->user instanceof User
            ? app(CartManager::class)->mergeGuestCartInto($event->user)
            : null);

        // Super-admins hold every back-office permission, including the ones added later.
        Gate::before(fn (User $user) => $user->hasRole(Role::SuperAdmin->value) ? true : null);

        // @money($amount) formats an amount in the store currency, e.g. "15 000 FCFA".
        Blade::directive('money', fn (string $amount) => "<?php echo e(\\App\\Support\\Money::format($amount)); ?>");
    }
}
