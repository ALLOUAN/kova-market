{{-- Delivery dashboard (App\Filament\Pages\DeliveryDashboard). Styles in public/assets/admin/kova-admin.css (.kd-*):
     the panel has no Tailwind build of its own. Refreshed every 30 seconds; the queue (DispatchQueue) polls itself. --}}
@php
    use Illuminate\Support\Carbon;
    $money = fn (int $amount) => \App\Support\Money::format($amount);
    $since = fn ($date) => Carbon::parse($date)->diffForHumans(syntax: Carbon::DIFF_ABSOLUTE);
    $initials = fn (string $name) => collect(preg_split('/\s+/', trim($name)))->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
    $alertCount = $alerts['waiting']->count() + $alerts['on_the_way']->count() + $alerts['uncovered_zones']->count();
    $rate = $stats['success_rate_7d'];
@endphp

<div class="kd" wire:poll.30s>
    {{-- Header band: where we are, that it is live, the shortcuts. --}}
    <header class="kd-hero">
        <div class="kd-hero__text">
            <p class="kd-hero__eyebrow">
                <span class="kd-live" aria-hidden="true"></span>
                En direct · {{ ucfirst(now()->translatedFormat('l j F')) }} · {{ now()->format('H:i') }}
            </p>
            <h1 class="kd-hero__title">Tableau de bord des livraisons</h1>
            <p class="kd-hero__lead">
                @if ($stats['to_assign'] > 0)
                    <strong>{{ $stats['to_assign'] }}</strong> commande{{ $stats['to_assign'] > 1 ? 's attendent' : ' attend' }} un livreur,
                @else
                    Toutes les commandes ont un livreur,
                @endif
                {{ $stats['on_the_way'] }} en route et {{ $stats['delivered_today'] }} livrée{{ $stats['delivered_today'] > 1 ? 's' : '' }} aujourd’hui.
            </p>
        </div>
        <nav class="kd-hero__actions" aria-label="Raccourcis">
            <a class="kd-btn kd-btn--light" href="{{ $links['new_courier'] }}">
                <x-filament::icon icon="heroicon-o-user-plus" class="kd-btn__icon" /> Nouveau livreur
            </a>
            <a class="kd-btn kd-btn--ghost" href="{{ $links['zones'] }}">
                <x-filament::icon icon="heroicon-o-map" class="kd-btn__icon" /> Zones
            </a>
            @if ($links['finances'])
                <a class="kd-btn kd-btn--ghost" href="{{ $links['finances'] }}">
                    <x-filament::icon icon="heroicon-o-chart-bar-square" class="kd-btn__icon" /> Finances
                </a>
            @endif
        </nav>
    </header>

    {{-- The four figures that matter now, then the secondary ones. --}}
    <section class="kd-kpis" aria-label="Chiffres du jour">
        <a href="{{ $links['unassigned'] }}" @class(['kd-kpi', 'kd-kpi--accent' => $stats['to_assign'] > 0])>
            <span class="kd-kpi__icon kd-tone-orange"><x-filament::icon icon="heroicon-o-inbox-arrow-down" /></span>
            <span class="kd-kpi__label">À attribuer</span>
            <span class="kd-kpi__value">{{ $stats['to_assign'] }}</span>
            <span class="kd-kpi__hint">{{ $stats['to_assign'] > 0 ? 'Sans livreur, à Abidjan' : 'Tout est attribué' }}</span>
        </a>
        <a href="{{ $links['on_the_way'] }}" class="kd-kpi">
            <span class="kd-kpi__icon kd-tone-navy"><x-filament::icon icon="heroicon-o-truck" /></span>
            <span class="kd-kpi__label">En livraison</span>
            <span class="kd-kpi__value">{{ $stats['on_the_way'] }}</span>
            <span class="kd-kpi__hint">{{ $stats['couriers_on_the_way'] }} livreur{{ $stats['couriers_on_the_way'] > 1 ? 's' : '' }} sur la route</span>
        </a>
        <div class="kd-kpi kd-kpi--ring">
            <span class="kd-kpi__icon kd-tone-green"><x-filament::icon icon="heroicon-o-check-circle" /></span>
            <span class="kd-kpi__label">Livrées aujourd’hui</span>
            <span class="kd-kpi__value">{{ $stats['delivered_today'] }}</span>
            <span class="kd-kpi__hint">{{ $stats['failed_today'] }} échec{{ $stats['failed_today'] > 1 ? 's' : '' }} aujourd’hui</span>
            {{-- 7-day success rate as a ring; the number is written in it, never the colour alone. --}}
            <span class="kd-ring" role="img" aria-label="Réussite sur 7 jours : {{ $rate === null ? 'pas encore de livraison' : $rate.' %' }}">
                <svg viewBox="0 0 36 36" aria-hidden="true">
                    <circle class="kd-ring__track" cx="18" cy="18" r="15.9" />
                    <circle class="kd-ring__value" cx="18" cy="18" r="15.9" stroke-dasharray="{{ $rate ?? 0 }} 100" />
                </svg>
                <span class="kd-ring__text">{{ $rate === null ? '—' : $rate.'%' }}<small>7 j</small></span>
            </span>
        </div>
        <a href="{{ $links['couriers'] }}" @class(['kd-kpi', 'kd-kpi--wide', 'kd-kpi--warn' => $stats['cash_with_couriers'] > 0])>
            <span class="kd-kpi__icon kd-tone-gold"><x-filament::icon icon="heroicon-o-banknotes" /></span>
            <span class="kd-kpi__label">Argent chez les livreurs</span>
            <span class="kd-kpi__value kd-kpi__value--money">{{ $money($stats['cash_with_couriers']) }}</span>
            <span class="kd-kpi__hint">À reverser à la boutique</span>
        </a>
    </section>

    <section class="kd-chips" aria-label="Autres chiffres">
        <a class="kd-chip" href="{{ $links['to_prepare'] }}">
            <x-filament::icon icon="heroicon-o-archive-box" class="kd-chip__icon" />
            <span>À préparer</span><strong>{{ $stats['to_prepare'] }}</strong>
        </a>
        <a class="kd-chip" href="{{ $links['interior'] }}">
            <x-filament::icon icon="heroicon-o-map" class="kd-chip__icon" />
            <span>Vers l’intérieur</span><strong>{{ $stats['interior_in_transit'] }}</strong>
        </a>
        <span @class(['kd-chip', 'is-bad' => $stats['failed_today'] > 0])>
            <x-filament::icon icon="heroicon-o-x-circle" class="kd-chip__icon" />
            <span>Échecs aujourd’hui</span><strong>{{ $stats['failed_today'] }}</strong>
        </span>
        <a class="kd-chip" href="{{ $links['couriers'] }}">
            <x-filament::icon icon="heroicon-o-user-group" class="kd-chip__icon" />
            <span>Livreurs disponibles</span><strong>{{ $stats['couriers_available'] }}</strong>
        </a>
    </section>

    {{-- The queue to dispatch beside what needs attention. --}}
    <div class="kd-main">
        <div class="kd-main__queue">
            @livewire(\App\Filament\Delivery\Widgets\DispatchQueue::class, key('kd-dispatch-queue'))
        </div>

        <aside @class(['kd-card', 'kd-watch', 'is-calm' => $alertCount === 0]) aria-labelledby="kd-watch-title">
            <header class="kd-card__head">
                <h2 id="kd-watch-title">À surveiller</h2>
                <span @class(['kd-count', 'is-hot' => $alertCount > 0])>{{ $alertCount }}</span>
            </header>

            @if ($alertCount === 0)
                <div class="kd-empty">
                    <x-filament::icon icon="heroicon-o-shield-check" class="kd-empty__icon" />
                    <p><strong>Rien d’anormal</strong><br>Aucune commande bloquée, toutes les zones ouvertes ont un livreur.</p>
                </div>
            @else
                <ul class="kd-alerts">
                    @foreach ($alerts['waiting'] as $order)
                        <li class="kd-alert kd-alert--warn">
                            <x-filament::icon icon="heroicon-o-clock" class="kd-alert__icon" />
                            <div>
                                <p class="kd-alert__title"><a href="{{ $orderUrl($order) }}">{{ $order->number }}</a> attend un livreur</p>
                                <p class="kd-alert__meta">{{ $order->commune_name }} · {{ $order->zone_name }} · depuis {{ $since($order->moved_at) }}</p>
                            </div>
                        </li>
                    @endforeach
                    @foreach ($alerts['on_the_way'] as $order)
                        <li class="kd-alert kd-alert--bad">
                            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="kd-alert__icon" />
                            <div>
                                <p class="kd-alert__title"><a href="{{ $orderUrl($order) }}">{{ $order->number }}</a> en route depuis {{ $since($order->moved_at) }}</p>
                                <p class="kd-alert__meta">
                                    {{ $order->courier?->name() ?? 'Livreur inconnu' }}
                                    @if ($order->courier)
                                        · <a href="tel:{{ $order->courier->user->phone }}">Appeler {{ $order->courier->formattedPhone() }}</a>
                                    @endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                    @foreach ($alerts['uncovered_zones'] as $zone)
                        <li class="kd-alert kd-alert--bad">
                            <x-filament::icon icon="heroicon-o-map-pin" class="kd-alert__icon" />
                            <div>
                                <p class="kd-alert__title"><a href="{{ $zoneUrl($zone) }}">{{ $zone->name }}</a> sans livreur</p>
                                <p class="kd-alert__meta">Ouverte aux clients, mais aucun livreur actif ne la dessert.</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <p class="kd-footnote">Seuils : sans livreur depuis plus de {{ $waitingHours }} h, en route depuis plus de {{ $onTheWayHours }} h.</p>
            @endif
        </aside>
    </div>

    {{-- Couriers: one card each, the ones on the road first. --}}
    <section class="kd-section" aria-labelledby="kd-couriers-title">
        <header class="kd-section__head">
            <h2 id="kd-couriers-title">Livreurs <span class="kd-muted">· aujourd’hui</span></h2>
            <a class="kd-link" href="{{ $links['couriers'] }}">Tous les livreurs →</a>
        </header>
        @if ($couriers->isEmpty())
            <div class="kd-card kd-empty">
                <x-filament::icon icon="heroicon-o-user-group" class="kd-empty__icon" />
                <p><strong>Aucun livreur</strong><br><a class="kd-link" href="{{ $links['new_courier'] }}">Créer le premier compte livreur</a></p>
            </div>
        @else
            <div class="kd-grid">
                @foreach ($couriers as $row)
                    @php
                        $courier = $row['courier'];
                        $state = match (true) {
                            $courier->isSuspended() => ['Suspendu', 'off'],
                            $row['on_the_way'] > 0 => ['Sur la route', 'live'],
                            $row['open'] > 0 => ['Livraisons à faire', 'busy'],
                            default => ['Disponible', 'free'],
                        };
                    @endphp
                    <article @class(['kd-card', 'kd-person', 'is-off' => $courier->isSuspended()])>
                        <header class="kd-person__head">
                            <span class="kd-avatar" aria-hidden="true">{{ $initials($courier->name()) }}</span>
                            <div class="kd-person__who">
                                <a class="kd-person__name" href="{{ $courierUrl($courier) }}">{{ $courier->name() }}</a>
                                <span class="kd-status kd-status--{{ $state[1] }}">{{ $state[0] }}</span>
                            </div>
                            @unless ($courier->isSuspended())
                                <a class="kd-icon-btn" href="tel:{{ $courier->user->phone }}" title="Appeler {{ $courier->formattedPhone() }}" aria-label="Appeler {{ $courier->name() }}">
                                    <x-filament::icon icon="heroicon-o-phone" />
                                </a>
                            @endunless
                        </header>
                        <dl class="kd-mini">
                            <div><dt>En cours</dt><dd><a href="{{ $ordersOfCourierUrl($courier) }}">{{ $row['open'] }}</a></dd></div>
                            <div><dt>Livrées</dt><dd>{{ $row['delivered_today'] }}</dd></div>
                            <div><dt>Échecs</dt><dd @class(['is-bad' => $row['failed_today'] > 0])>{{ $row['failed_today'] }}</dd></div>
                        </dl>
                        <footer class="kd-person__foot">
                            <span class="kd-muted">{{ $courier->zones->pluck('name')->join(', ') ?: 'Aucune zone' }}</span>
                            <span @class(['kd-due', 'is-due' => $row['due'] > 0])>{{ $row['due'] > 0 ? $money($row['due']).' à reverser' : 'Rien à reverser' }}</span>
                        </footer>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Zones: activity and coverage. --}}
    <section class="kd-section" aria-labelledby="kd-zones-title">
        <header class="kd-section__head">
            <h2 id="kd-zones-title">Zones <span class="kd-muted">· activité et couverture</span></h2>
            <a class="kd-link" href="{{ $links['zones'] }}">Gérer les zones →</a>
        </header>
        @if ($zones->isEmpty())
            <div class="kd-card kd-empty">
                <x-filament::icon icon="heroicon-o-map" class="kd-empty__icon" />
                <p><strong>Aucune zone</strong><br><a class="kd-link" href="{{ $links['new_zone'] }}">Créer une zone de livraison</a></p>
            </div>
        @else
            <div class="kd-grid kd-grid--zones">
                @foreach ($zones as $row)
                    @php
                        $zone = $row['zone'];
                        $uncovered = $zone->isDeliverable() && ! $zone->isInterior() && $row['couriers'] === 0;
                    @endphp
                    <article @class(['kd-card', 'kd-zone', 'is-off' => ! $zone->isDeliverable(), 'is-alert' => $uncovered])>
                        <header class="kd-zone__head">
                            <a class="kd-zone__name" href="{{ $zoneUrl($zone) }}">{{ $zone->name }}</a>
                            <span class="kd-zone__fee">{{ $zone->isDeliverable() ? ($zone->fee === 0 ? 'Gratuit' : $money($zone->fee)) : 'Fermée' }}</span>
                        </header>
                        <p class="kd-muted kd-zone__meta">
                            {{ $zone->isInterior() ? 'Intérieur · transporteur' : 'Abidjan · livreurs KOVA' }}@if ($zone->delay_label) · {{ $zone->delay_label }}@endif
                        </p>
                        <dl class="kd-mini">
                            <div><dt>À attribuer</dt><dd @class(['is-warn' => $row['waiting'] > 0])>{{ $row['waiting'] }}</dd></div>
                            <div><dt>En cours</dt><dd>{{ $row['in_progress'] }}</dd></div>
                            <div><dt>Livrées</dt><dd>{{ $row['delivered_today'] }}</dd></div>
                        </dl>
                        <footer class="kd-zone__foot">
                            @if ($zone->isInterior())
                                <span class="kd-muted">Expédiée par transporteur</span>
                            @elseif ($uncovered)
                                <span class="kd-status kd-status--alert">Aucun livreur</span>
                            @else
                                <span class="kd-muted">{{ $row['couriers'] }} livreur{{ $row['couriers'] > 1 ? 's' : '' }} disponible{{ $row['couriers'] > 1 ? 's' : '' }}</span>
                            @endif
                        </footer>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
