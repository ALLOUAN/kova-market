<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>@hasSection('title')@yield('title') - @endif{{ config('storefront.name') }}</title>
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <meta name="description" content="@yield('description', config('storefront.description'))">
    {{-- Canonical address and link previews (F-152, F-153); pages override the defaults with sections. --}}
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta property="og:site_name" content="{{ config('storefront.name') }}">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@hasSection('title')@yield('title')@else{{ config('storefront.name') }}@endif">
    <meta property="og:description" content="@yield('description', config('storefront.description'))">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:image" content="@yield('og_image', asset(config('storefront.logo')))">
    <meta name="twitter:card" content="@yield('twitter_card', 'summary')">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @inject('analytics', 'App\Services\Storefront\Analytics')
    @if ($searchConsoleToken = $analytics->searchConsoleToken())
        <meta name="google-site-verification" content="{{ $searchConsoleToken }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Cabin:wght@400;500;600;700&family=Caveat:wght@400;500;600;700&family=Bebas+Neue&family=Caprasimo&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cabin:wght@400;500;600;700&family=Caveat:wght@400;500;600;700&family=Bebas+Neue&family=Caprasimo&display=swap">
    </noscript>
    <link rel="preload" href="{{ asset('assets/fonts/fa-brands-400.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('assets/fonts/fa-regular-400.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('assets/fonts/fa-solid-900.woff2') }}" as="font" type="font/woff2" crossorigin>

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset(config('storefront.favicon')) }}">

    <link rel="stylesheet" href="{{ asset('assets/css/vendor/bootstrap.min.css') }}">
    <link rel="preload" href="{{ asset('assets/css/plugins/fontawesome-all.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="{{ asset('assets/css/plugins/fontawesome-all.min.css') }}">
    </noscript>
    @foreach (['swiper.css', 'fancybox.css', 'mavo.css', 'odometer.css', 'animation.css', 'bootstrap-select.min.css', 'bootstrap-datepicker.min.css'] as $stylesheet)
        <link rel="stylesheet" href="{{ asset('assets/css/plugins/'.$stylesheet) }}">
    @endforeach
    <link rel="stylesheet" href="{{ asset('assets/css/style.min.css') }}">
    @stack('meta')
    @stack('styles')
</head>

<body class="rbt-header-sticky">
    @include('partials.header.index')
    @include('partials.overlays.preloader')
    @include('partials.header.mobile-menu')

    {{-- Side panels --}}
    @include('partials.offcanvas.categories')
    @include('partials.offcanvas.cart')
    @include('partials.offcanvas.special-offers')
    @include('partials.modals.recently-viewed')

    <main>
        {{-- Result of the last cart action or other storefront form (notice). --}}
        @foreach (['cart_status' => 'success', 'notice' => 'success', 'cart_error' => 'danger', 'notice_error' => 'danger'] as $key => $type)
            @if (session($key))
                <div class="container mt--24">
                    <div class="alert alert-{{ $type }} d-flex justify-content-between align-items-center gap-3 mb-0" role="{{ $type === 'danger' ? 'alert' : 'status' }}">
                        <span>{{ session($key) }}</span>
                        @if ($key === 'cart_status' && ! request()->routeIs('cart.show'))
                            <a class="rbt-btn rbt-btn-sm" href="{{ route('cart.show') }}">Voir le panier</a>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach

        @yield('content')
    </main>

    @if (config('storefront.features.welcome_popup'))
        @include('partials.modals.welcome-banner')
    @endif
    @if (config('storefront.features.compare'))
        @include('partials.offcanvas.compare-bar')
    @endif
    @if (config('storefront.product_card.quick_view') === 'sidenav')
        @include('partials.offcanvas.quick-view')
    @endif

    {{-- Shopping modals triggered from product cards, the header and the side panels --}}
    @include('partials.modals.added-comparison')
    @include('partials.modals.quick-view')
    @include('partials.modals.notify')
    @include('partials.modals.added-cart')
    {{-- Only needed when a tracker is set (F-156): the store's own cookies are strictly necessary. --}}
    @if ($analytics->enabled())
        @include('partials.overlays.cookies')
    @endif
    @if (config('storefront.features.wishlist'))
        @include('partials.modals.wishlist')
    @endif
    @if (config('storefront.features.compare'))
        @include('partials.modals.compare')
    @endif
    @include('partials.modals.social-share')

    {{-- Page specific modals (size guide, coupons, ...) --}}
    @stack('modals')

    @include('partials.footer.newsletter')
    @include('partials.footer.footer')
    @guest
        @include('partials.modals.sign-in')
        @include('partials.modals.sign-up')
    @else
        <form id="logout-form" method="POST" action="{{ route('logout') }}" class="d-none">@csrf</form>
    @endguest
    @include('partials.footer.mobile-toolbar')
    @include('partials.overlays.feedback')
    @include('partials.overlays.whatsapp')

    {{-- The theme's own cookie banner logic stays off: assets/js/analytics.js handles consent (F-156). --}}
    <script>try { localStorage.setItem('displayed_cookie_alert', '1'); } catch (e) {}</script>
    @foreach ([
        'vendor/modernizr.min.js', 'vendor/jquery.js', 'vendor/bootstrap.min.js', 'vendor/swiper.js', 'vendor/jquery-appear.js',
        'vendor/fancybox.min.js', 'vendor/animation.js', 'vendor/text-type.js', 'vendor/odometer.js', 'vendor/backtotop.js',
        'vendor/jquery-ui.js', 'vendor/bootstrap-select.min.js', 'vendor/countdown.js', 'vendor/progressbar.min.js',
        'vendor/isotope.pkgd.min.js', 'vendor/imageloaded.js', 'vendor/jquery.waypoints.min.js', 'plugins/color-swatches.js',
        'vendor/bootstrap-datepicker.min.js', 'main.min.js', 'storefront.js',
    ] as $script)
        <script src="{{ asset('assets/js/'.$script) }}"></script>
    @endforeach
    <script>
        // A failed sign-in / sign-up comes back as a full page: reopen the form so its error messages are seen.
        @if (in_array(old('_form'), ['signin', 'signup'], true) && $errors->any())
            document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById(@json(old('_form') === 'signin' ? 'signinModal' : 'signupModal'))).show());
        @elseif (request()->boolean('connexion') && auth()->guest())
            // Sent here by a page that needs an account.
            document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('signinModal')).show());
        @endif
        @if (session('cart_open'))
            // Right after an "add to cart" from a product card: show the mini-cart (same classes as the theme's opener).
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelector('.rbt-cart-side-menu')?.classList.add('side-menu-active');
                document.body.classList.add('cart-sidenav-menu-active');
            });
        @endif
        document.querySelectorAll('[data-logout]').forEach((link) => link.addEventListener('click', (event) => {
            event.preventDefault();
            document.getElementById('logout-form')?.submit();
        }));
    </script>
    @if ($analytics->enabled())
        {{-- Identifiers and e-commerce events only: the trackers load after consent (F-155, F-156). --}}
        <script>window.kovaAnalytics = @json([...$analytics->trackers(), 'events' => $analytics->events()]);</script>
        <script src="{{ asset('assets/js/analytics.js') }}"></script>
    @endif
    @if (app(\App\Services\Security\Turnstile::class)->enabled())
        {{-- Anti-robot widgets of the public forms (x-turnstile), F-141. --}}
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
    @stack('scripts')
</body>
</html>
