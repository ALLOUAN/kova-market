{{-- The store's guarantees under the hero (Paramètres de la boutique › Page d'accueil): reassurance before the products. --}}
@php($guarantees = collect(config('storefront.guarantees'))->filter(fn ($item) => filled($item['title'] ?? null))->take(4))
@if ($guarantees->isNotEmpty())
    <div class="rbt-section-gap2Top pt--32 pb--8 rbt-bg-color-white">
        <div class="container">
            <ul class="kova-home-guarantees" aria-label="Nos garanties">
                @foreach ($guarantees as $item)
                    <li>
                        <i class="fa-solid fa-{{ $item['icon'] ?? 'circle-check' }}" aria-hidden="true"></i>
                        <span>
                            <strong>{{ $item['title'] }}</strong>
                            @if (filled($item['text'] ?? null))<span>{{ $item['text'] }}</span>@endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
