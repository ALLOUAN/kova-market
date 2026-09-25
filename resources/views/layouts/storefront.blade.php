<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>@hasSection('title')@yield('title') - @endif{{ config('storefront.name') }}</title>
    <meta name="robots" content="index, follow">
    <meta name="description" content="@yield('description', config('storefront.description'))">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="csrf-token" content="{{ csrf_token() }}">

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
        @yield('content')
    </main>

    @include('partials.modals.welcome-banner')
    @include('partials.offcanvas.compare-bar')
    @if (config('storefront.product_card.quick_view') === 'sidenav')
        @include('partials.offcanvas.quick-view')
    @endif

    {{-- Shopping modals triggered from product cards, the header and the side panels --}}
    @include('partials.modals.added-comparison')
    @include('partials.modals.quick-view')
    @include('partials.modals.popup-cart')
    @include('partials.modals.notify')
    @include('partials.modals.added-cart')
    @include('partials.overlays.cookies')
    @include('partials.modals.wishlist')
    @include('partials.modals.compare')
    @include('partials.modals.social-share')
    @include('partials.modals.edit-cart')

    {{-- Page specific modals (size guide, restock, coupons, ...) --}}
    @stack('modals')

    @include('partials.footer.newsletter')
    @include('partials.footer.footer')
    @include('partials.modals.sign-in')
    @include('partials.modals.sign-up')
    @include('partials.footer.mobile-toolbar')
    @include('partials.overlays.feedback')

    @foreach ([
        'vendor/modernizr.min.js', 'vendor/jquery.js', 'vendor/bootstrap.min.js', 'vendor/swiper.js', 'vendor/jquery-appear.js',
        'vendor/fancybox.min.js', 'vendor/animation.js', 'vendor/text-type.js', 'vendor/odometer.js', 'vendor/backtotop.js',
        'vendor/jquery-ui.js', 'vendor/bootstrap-select.min.js', 'vendor/countdown.js', 'vendor/progressbar.min.js',
        'vendor/isotope.pkgd.min.js', 'vendor/imageloaded.js', 'vendor/jquery.waypoints.min.js', 'plugins/color-swatches.js',
        'vendor/bootstrap-datepicker.min.js', 'main.min.js',
    ] as $script)
        <script src="{{ asset('assets/js/'.$script) }}"></script>
    @endforeach
    @stack('scripts')
</body>
</html>
