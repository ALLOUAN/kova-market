{{-- Customer area: in place of the newsletter band and the footer, one line with the copyright and the legal links. --}}
<footer class="kova-account-line">
    <div class="container">
        <div class="kova-account-line__inner">
            <span>© {{ now()->year }} {{ config('storefront.name') }}</span>
            <nav aria-label="Informations légales">
                @foreach ($legalLinks as $link)
                    <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                @endforeach
            </nav>
        </div>
    </div>
</footer>
