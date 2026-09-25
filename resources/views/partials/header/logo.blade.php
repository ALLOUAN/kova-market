<div @class(['logo', $class ?? null])>
    <a href="{{ route('home') }}">
        <img src="{{ asset(config('storefront.logo')) }}" alt="{{ config('storefront.name') }}">
    </a>
</div>
