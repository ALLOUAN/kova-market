@extends('courier.layout')

{{-- Courier home: "what do I do now?" first (the delivery to make, one tap to call or open the route), the day in
     four figures, then my deliveries and the ones to take in my zones. Refreshes itself every minute. --}}
@php
    use App\Enums\OrderStatus;
    use Illuminate\Support\Str;

    $firstName = Str::before($courier->name(), ' ');
    $dayTotal = $deliveredToday + $mine->count();
    $progress = $dayTotal > 0 ? (int) round($deliveredToday / $dayTotal * 100) : 0;
    $tone = fn (OrderStatus $status) => match ($status) {
        OrderStatus::OutForDelivery => 'live',
        OrderStatus::Shipped => 'ready',
        default => 'wait',
    };
    // "à l’instant" rather than "il y a 0 s" for what just happened.
    $ago = fn ($date) => $date->gt(now()->subMinute()) ? 'à l’instant' : $date->diffForHumans(short: true);
    $hint = fn (OrderStatus $status) => match ($status) {
        OrderStatus::OutForDelivery => 'En route',
        OrderStatus::Shipped => 'Prête à partir',
        default => 'En préparation',
    };
@endphp

@section('title', 'Mes livraisons')
@section('tab', 'home')

@section('hero')
    <header class="ch-hero">
        @include('courier.partials.hero-top', ['title' => 'Bonjour '.$firstName, 'subtitle' => ucfirst(now()->translatedFormat('l j F'))])
        <div class="ch-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}" aria-label="Avancement de la journée">
            <div class="ch-progress__text">
                <span>Ma journée</span>
                <strong>{{ $deliveredToday }} livrée{{ $deliveredToday > 1 ? 's' : '' }}{{ $mine->isNotEmpty() ? ' · '.$mine->count().' à faire' : '' }}</strong>
            </div>
            <div class="ch-progress__bar"><span style="width: {{ $progress }}%"></span></div>
        </div>
    </header>
@endsection

@section('content')
    {{-- What to do now. --}}
    @if ($next)
        <section class="ch-next ch-next--{{ $tone($next->status) }}" aria-labelledby="ch-next-title">
            <div class="ch-next__head">
                <span class="ch-pill ch-pill--{{ $tone($next->status) }}">{{ $next->status === OrderStatus::OutForDelivery ? 'En cours de livraison' : 'À faire maintenant' }}</span>
                <span class="ch-next__number">{{ $next->number }}</span>
            </div>
            <h2 id="ch-next-title" class="ch-next__place">{{ $next->district }}</h2>
            <p class="ch-next__sub">
                @include('courier.partials.icon', ['name' => 'pin', 'class' => 'ico--sm'])
                {{ $next->commune_name }}@if ($next->landmark) · {{ $next->landmark }}@endif
            </p>
            <div class="ch-next__meta">
                <span>@include('courier.partials.icon', ['name' => 'user', 'class' => 'ico--sm']) {{ $next->customer_name }}</span>
                <span class="ch-next__cash">
                    @if ($next->amountToCollect() > 0)
                        À encaisser <strong>@money($next->amountToCollect())</strong>
                    @else
                        Déjà payée
                    @endif
                </span>
            </div>
            <div class="ch-actions">
                <a class="ch-action" href="tel:{{ $next->phone }}">@include('courier.partials.icon', ['name' => 'phone'])<span>Appeler</span></a>
                <a class="ch-action" href="{{ $next->whatsappUrl() }}" target="_blank" rel="noopener">@include('courier.partials.icon', ['name' => 'chat'])<span>WhatsApp</span></a>
                <a class="ch-action" href="{{ $next->mapsUrl() }}" target="_blank" rel="noopener">@include('courier.partials.icon', ['name' => 'route'])<span>Itinéraire</span></a>
            </div>
            <a class="btn ok ch-next__open" href="{{ route('courier.orders.show', $next) }}">
                {{ $next->status === OrderStatus::OutForDelivery ? 'Terminer la livraison' : 'Ouvrir la livraison' }}
                @include('courier.partials.icon', ['name' => 'chevron', 'class' => 'ico--sm'])
            </a>
        </section>
    @elseif ($queue->isNotEmpty())
        <section class="ch-next ch-next--ready">
            <span class="ch-pill ch-pill--ready">Des clients attendent</span>
            <h2 class="ch-next__place">{{ $queue->count() }} livraison{{ $queue->count() > 1 ? 's' : '' }} à prendre</h2>
            <p class="ch-next__sub">Dans vos zones. Prenez-en une pour commencer.</p>
            <a class="btn ok ch-next__open" href="#a-prendre" data-tab-link="queue">Voir les livraisons à prendre</a>
        </section>
    @else
        <section class="ch-calm">
            @include('courier.partials.icon', ['name' => 'check', 'class' => 'ico--lg'])
            <div>
                <strong>{{ $mine->isEmpty() && $deliveredToday > 0 ? 'Bravo, tout est livré !' : 'Pas de livraison pour le moment' }}</strong>
                <p>Les nouvelles commandes de vos zones s’afficheront ici. La page se met à jour toute seule.</p>
            </div>
        </section>
    @endif

    {{-- The day in four figures. --}}
    <div class="ch-stats">
        <div class="ch-stat">
            <span class="ch-stat__icon ch-tone-green">@include('courier.partials.icon', ['name' => 'check'])</span>
            <span class="ch-stat__value">{{ $deliveredToday }}</span>
            <span class="ch-stat__label">Livrées aujourd’hui</span>
        </div>
        <div class="ch-stat">
            <span class="ch-stat__icon ch-tone-navy">@include('courier.partials.icon', ['name' => 'truck'])</span>
            <span class="ch-stat__value">{{ $mine->count() }}</span>
            <span class="ch-stat__label">En cours</span>
        </div>
        <div class="ch-stat">
            <span class="ch-stat__icon ch-tone-orange">@include('courier.partials.icon', ['name' => 'cash'])</span>
            <span class="ch-stat__value ch-stat__value--money">@money($toCollect)</span>
            <span class="ch-stat__label">À encaisser</span>
        </div>
        {{-- Opens "Mes encaissements": collected, handed over, payments and their receipts. --}}
        <a class="ch-stat ch-stat--link" href="{{ route('courier.money') }}">
            <span class="ch-stat__icon ch-tone-gold">@include('courier.partials.icon', ['name' => 'wallet'])</span>
            <span class="ch-stat__value ch-stat__value--money">@money($cashToHandOver)</span>
            <span class="ch-stat__label">À reverser · Mes encaissements →</span>
        </a>
    </div>

    {{-- My deliveries / to take in my zones. --}}
    <div class="ch-tabs" role="tablist" aria-label="Livraisons">
        <button type="button" role="tab" class="ch-tab is-active" aria-selected="true" aria-controls="ch-panel-mine" id="ch-tab-mine" data-tab="mine">
            Mes livraisons <span class="ch-count">{{ $mine->count() }}</span>
        </button>
        <button type="button" role="tab" class="ch-tab" aria-selected="false" aria-controls="ch-panel-queue" id="ch-tab-queue" data-tab="queue">
            À prendre <span @class(['ch-count', 'is-hot' => $queue->isNotEmpty()])>{{ $queue->count() }}</span>
        </button>
    </div>

    <section id="ch-panel-mine" role="tabpanel" aria-labelledby="ch-tab-mine" class="ch-panel" data-panel="mine">
        <h2 class="ch-panel__title">Mes livraisons</h2>
        @forelse ($mine as $order)
            <article class="ch-card ch-card--{{ $tone($order->status) }}">
                <a class="ch-card__link" href="{{ route('courier.orders.show', $order) }}" aria-label="Ouvrir la livraison {{ $order->number }}"></a>
                <div class="ch-card__body">
                    <div class="ch-card__row">
                        <strong class="ch-card__place">{{ $order->district }}</strong>
                        <span class="ch-pill ch-pill--{{ $tone($order->status) }}">{{ $hint($order->status) }}</span>
                    </div>
                    <p class="ch-card__sub">{{ $order->commune_name }} · {{ $order->customer_name }}</p>
                    <div class="ch-card__row ch-card__foot">
                        <span class="ch-card__meta">
                            {{ $order->number }}
                            @if ($order->assigned_at) · @include('courier.partials.icon', ['name' => 'clock', 'class' => 'ico--xs']) {{ $ago($order->assigned_at) }}@endif
                        </span>
                        <span class="ch-card__cash">{{ $order->amountToCollect() > 0 ? \App\Support\Money::format($order->amountToCollect()) : 'Payée' }}</span>
                    </div>
                </div>
                <div class="ch-card__quick">
                    <a href="tel:{{ $order->phone }}" aria-label="Appeler {{ $order->customer_name }}">@include('courier.partials.icon', ['name' => 'phone'])</a>
                    <a href="{{ $order->mapsUrl() }}" target="_blank" rel="noopener" aria-label="Itinéraire vers {{ $order->district }}">@include('courier.partials.icon', ['name' => 'route'])</a>
                </div>
            </article>
        @empty
            <div class="ch-empty">
                @include('courier.partials.icon', ['name' => 'truck', 'class' => 'ico--lg'])
                <p><strong>Aucune livraison en cours</strong><br>Prenez une commande dans l’onglet « À prendre ».</p>
            </div>
        @endforelse
    </section>

    <section id="ch-panel-queue" role="tabpanel" aria-labelledby="ch-tab-queue" class="ch-panel" data-panel="queue">
        <h2 class="ch-panel__title" id="a-prendre">À prendre dans mes zones</h2>
        @forelse ($queue as $order)
            <article class="ch-card ch-card--ready">
                <div class="ch-card__body">
                    <div class="ch-card__row">
                        <strong class="ch-card__place">{{ $order->district }}</strong>
                        <span class="ch-pill ch-pill--wait">{{ $order->status->getLabel() }}</span>
                    </div>
                    <p class="ch-card__sub">{{ $order->commune_name }} · {{ $order->itemCount() }} article{{ $order->itemCount() > 1 ? 's' : '' }}</p>
                    <div class="ch-card__row ch-card__foot">
                        <span class="ch-card__meta">{{ $order->number }} · {{ $ago($order->created_at) }}</span>
                        <span class="ch-card__cash">@money($order->total)</span>
                    </div>
                    <form method="POST" action="{{ route('courier.orders.accept', $order) }}" class="ch-card__take">
                        @csrf
                        <button type="submit" class="btn ok">Je prends cette livraison</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="ch-empty">
                @include('courier.partials.icon', ['name' => 'inbox', 'class' => 'ico--lg'])
                <p><strong>Rien à prendre</strong><br>Aucune commande en attente dans vos zones.</p>
            </div>
        @endforelse
    </section>

    <p class="ch-updated">
        @include('courier.partials.icon', ['name' => 'refresh', 'class' => 'ico--xs'])
        Mis à jour à {{ now()->format('H:i') }} · <a href="{{ route('courier.home') }}">Actualiser</a>
    </p>

    <script>
        (() => {
            // Tabs: my deliveries / to take. Without JavaScript both lists simply follow each other.
            const tabs = document.querySelectorAll('[data-tab]');
            const panels = document.querySelectorAll('[data-panel]');
            const show = (name) => {
                tabs.forEach((tab) => {
                    const active = tab.dataset.tab === name;
                    tab.classList.toggle('is-active', active);
                    tab.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                panels.forEach((panel) => { panel.hidden = panel.dataset.panel !== name; });
                try { sessionStorage.setItem('courierTab', name); } catch (e) {}
            };
            document.body.classList.add('has-tabs');
            tabs.forEach((tab) => tab.addEventListener('click', () => show(tab.dataset.tab)));
            document.querySelectorAll('[data-tab-link]').forEach((link) => link.addEventListener('click', (event) => {
                event.preventDefault();
                show(link.dataset.tabLink);
                document.querySelector('.ch-tabs').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }));
            let saved = null;
            try { saved = sessionStorage.getItem('courierTab'); } catch (e) {}
            show(saved === 'queue' ? 'queue' : 'mine');

            // Fresh every minute while the page is in front and nothing is being typed.
            setInterval(() => {
                if (document.visibilityState === 'visible' && !document.querySelector('input:focus, select:focus, textarea:focus')) {
                    location.reload();
                }
            }, 60000);
        })();
    </script>
@endsection
