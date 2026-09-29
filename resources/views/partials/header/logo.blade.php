<div @class(['logo', $class ?? null])>
    <a href="{{ route('home') }}">
        <img src="{{ asset(config('storefront.logo_small')) }}" alt="{{ config('storefront.name') }}">
    </a>
</div>
