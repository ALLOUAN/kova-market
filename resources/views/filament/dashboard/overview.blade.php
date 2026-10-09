{{-- Top of the back-office home (App\Filament\Widgets\DashboardOverview). Styles in public/assets/admin/kova-admin.css
     (.kd-* shared with the delivery dashboard, .kh-* here). Refreshed every minute. --}}
@php
    use App\Filament\Widgets\DashboardOverview;
    $money = fn (int $amount) => \App\Support\Money::format($amount);
    $hour = (int) now()->format('G');
    $greeting = $hour < 18 ? 'Bonjour' : 'Bonsoir';
@endphp
<x-filament-widgets::widget>
    <div class="kd kh" wire:poll.60s>
        <header class="kd-hero">
            <div class="kd-hero__text">
                <p class="kd-hero__eyebrow">
                    <span class="kd-live" aria-hidden="true"></span>
                    {{ ucfirst(now()->translatedFormat('l j F Y')) }} · {{ now()->format('H:i') }}
                </p>
                <h1 class="kd-hero__title">{{ $greeting }} {{ $firstName }}</h1>
                <p class="kd-hero__lead">
                    @if ($showSales)
                        Aujourd’hui : <strong>{{ $figures['orders'] }}</strong> vente{{ $figures['orders'] > 1 ? 's' : '' }} pour {{ $money($figures['revenue']) }}@if ($todo->isNotEmpty()), et <strong>{{ $todo->count() }}</strong> point{{ $todo->count() > 1 ? 's' : '' }} à traiter.@else. Tout est à jour.@endif
                    @elseif ($todo->isNotEmpty())
                        <strong>{{ $todo->count() }}</strong> point{{ $todo->count() > 1 ? 's' : '' }} demande{{ $todo->count() > 1 ? 'nt' : '' }} votre attention.
                    @else
                        Tout est à jour. Bonne journée !
                    @endif
                </p>
            </div>
            <nav class="kd-hero__actions" aria-label="Raccourcis">
                @foreach ($shortcuts as $shortcut)
                    <a @class(['kd-btn', 'kd-btn--light' => $shortcut['primary'], 'kd-btn--ghost' => ! $shortcut['primary']]) href="{{ $shortcut['url'] }}" @if ($shortcut['external']) target="_blank" rel="noopener" @endif>
                        <x-filament::icon :icon="$shortcut['icon']" class="kd-btn__icon" /> {{ $shortcut['label'] }}
                    </a>
                @endforeach
            </nav>
        </header>

        @if ($showSales)
            @php
                $revenueTrend = DashboardOverview::trend($figures['revenue'], $figures['revenue_before']);
                $ordersTrend = DashboardOverview::trend($figures['orders'], $figures['orders_before']);
            @endphp
            <section class="kd-kpis" aria-label="Chiffres du jour">
                <div class="kd-kpi kh-kpi">
                    <span class="kd-kpi__icon kd-tone-orange"><x-filament::icon icon="heroicon-o-banknotes" /></span>
                    <span class="kd-kpi__label">Chiffre d’affaires du jour</span>
                    <span class="kd-kpi__value kd-kpi__value--money">{{ $money($figures['revenue']) }}</span>
                    <span class="kd-kpi__hint">
                        Hier : {{ $money($figures['revenue_before']) }}@if ($revenueTrend) · <span @class(['kh-trend', 'is-up' => str_starts_with($revenueTrend, '+'), 'is-down' => str_starts_with($revenueTrend, '−')])>{{ $revenueTrend }}</span>@endif
                    </span>
                    <svg class="kh-spark" viewBox="0 0 100 28" preserveAspectRatio="none" aria-hidden="true"><polyline points="{{ DashboardOverview::sparkline($figures['week_revenue']) }}" /></svg>
                </div>
                <div class="kd-kpi kh-kpi">
                    <span class="kd-kpi__icon kd-tone-navy"><x-filament::icon icon="heroicon-o-shopping-bag" /></span>
                    <span class="kd-kpi__label">Ventes du jour</span>
                    <span class="kd-kpi__value">{{ $figures['orders'] }}</span>
                    <span class="kd-kpi__hint">
                        Hier : {{ $figures['orders_before'] }}@if ($ordersTrend) · <span @class(['kh-trend', 'is-up' => str_starts_with($ordersTrend, '+'), 'is-down' => str_starts_with($ordersTrend, '−')])>{{ $ordersTrend }}</span>@endif
                    </span>
                    <svg class="kh-spark" viewBox="0 0 100 28" preserveAspectRatio="none" aria-hidden="true"><polyline points="{{ DashboardOverview::sparkline($figures['week_orders']) }}" /></svg>
                </div>
                {{-- The orders to handle are in "À faire maintenant": here, the week against the one before. --}}
                @php($weekTrend = DashboardOverview::trend($figures['week_total'], $figures['week_total_before']))
                <div class="kd-kpi">
                    <span class="kd-kpi__icon kd-tone-green"><x-filament::icon icon="heroicon-o-arrow-trending-up" /></span>
                    <span class="kd-kpi__label">7 derniers jours</span>
                    <span class="kd-kpi__value kd-kpi__value--money">{{ $money($figures['week_total']) }}</span>
                    <span class="kd-kpi__hint">
                        Semaine d’avant : {{ $money($figures['week_total_before']) }}@if ($weekTrend) · <span @class(['kh-trend', 'is-up' => str_starts_with($weekTrend, '+'), 'is-down' => str_starts_with($weekTrend, '−')])>{{ $weekTrend }}</span>@endif
                    </span>
                </div>
                <div class="kd-kpi">
                    <span class="kd-kpi__icon kd-tone-gold"><x-filament::icon icon="heroicon-o-shopping-cart" /></span>
                    <span class="kd-kpi__label">Panier moyen</span>
                    <span class="kd-kpi__value kd-kpi__value--money">{{ $money($figures['basket']) }}</span>
                    <span class="kd-kpi__hint">30 derniers jours · {{ $figures['month_orders'] }} vente{{ $figures['month_orders'] > 1 ? 's' : '' }}</span>
                </div>
            </section>
        @endif

        <section @class(['kd-card', 'kd-watch', 'kh-todo', 'is-calm' => $todo->isEmpty()]) aria-labelledby="kh-todo-title">
            <header class="kd-card__head">
                <h2 id="kh-todo-title">À faire maintenant</h2>
                <span @class(['kd-count', 'is-hot' => $todo->isNotEmpty()])>{{ $todo->count() }}</span>
            </header>
            @if ($todo->isEmpty())
                <div class="kd-empty">
                    <x-filament::icon icon="heroicon-o-shield-check" class="kd-empty__icon" />
                    <p><strong>Rien en attente</strong><br>Commandes, livraisons, stock et messages sont à jour.</p>
                </div>
            @else
                <ul class="kh-todo__grid">
                    @foreach ($todo as $item)
                        <li>
                            <a class="kh-task kh-task--{{ $item['tone'] }}" href="{{ $item['url'] }}">
                                <span class="kh-task__icon"><x-filament::icon :icon="$item['icon']" /></span>
                                <span class="kh-task__text">
                                    <strong><span class="kh-task__count">{{ $item['count'] }}</span> {{ $item['label'] }}</strong>
                                    <span>{{ $item['hint'] }}</span>
                                </span>
                                <x-filament::icon icon="heroicon-m-chevron-right" class="kh-task__go" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-filament-widgets::widget>
