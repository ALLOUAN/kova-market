{{-- Best sellers of the last 30 days (App\Filament\Widgets\TopProducts), in the cards of the home (.kd-card):
     podium colours for the first three, the product's picture, its revenue, its share of the total and a bar.
     Styles: .kh-best in public/assets/admin/kova-admin.css. --}}
@php($money = fn (int $amount) => \App\Support\Money::format($amount))
<x-filament-widgets::widget>
    <div class="kd">
        <section class="kd-card kh-best" aria-labelledby="kh-best-title">
            <header class="kd-card__head">
                <div>
                    <h2 id="kh-best-title">Meilleures ventes</h2>
                    <p class="kh-best__sub">30 derniers jours · par chiffre d’affaires</p>
                </div>
                <span class="kh-best__badge">
                    <x-filament::icon icon="heroicon-o-trophy" class="kh-best__badge-icon" /> Top {{ max(1, $products->count()) }}
                </span>
            </header>

            @if ($products->isEmpty())
                <div class="kd-empty">
                    <x-filament::icon icon="heroicon-o-chart-bar" class="kd-empty__icon" />
                    <p><strong>Pas encore de vente</strong><br>Le classement apparaît dès les premières commandes.</p>
                </div>
            @else
                <ol class="kh-best__list">
                    @foreach ($products as $index => $product)
                        <li @class(['kh-best__item', 'is-podium-'.($index + 1) => $index < 3])>
                            <span class="kh-best__rank" aria-label="{{ $index + 1 }}e">{{ $index + 1 }}</span>
                            <span class="kh-best__thumb">
                                @if ($product['image'])
                                    <img src="{{ asset($product['image']) }}" alt="" loading="lazy">
                                @else
                                    <x-filament::icon icon="heroicon-o-cube" class="kh-best__thumb-icon" />
                                @endif
                            </span>
                            <div class="kh-best__body">
                                <p class="kh-best__name" title="{{ $product['name'] }}">{{ $product['name'] }}</p>
                                <div class="kh-best__bar" role="presentation"><span style="width: {{ max(4, round($product['revenue'] / $max * 100)) }}%"></span></div>
                                <p class="kh-best__meta">
                                    {{ $product['orders'] }} commande{{ $product['orders'] > 1 ? 's' : '' }}
                                    · {{ $total > 0 ? round($product['revenue'] / $total * 100) : 0 }} % du top
                                </p>
                            </div>
                            <strong class="kh-best__value">{{ $money($product['revenue']) }}</strong>
                        </li>
                    @endforeach
                </ol>
                @if ($financesUrl)
                    <footer class="kh-best__foot">
                        <a class="kd-link" href="{{ $financesUrl }}">Toutes les finances →</a>
                    </footer>
                @endif
            @endif
        </section>
    </div>
</x-filament-widgets::widget>
