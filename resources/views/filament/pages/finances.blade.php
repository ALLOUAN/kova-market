{{-- Finance dashboard (App\Filament\Pages\Finances), in the style of the back-office home and the delivery dashboard
     (.kd-* shared, .kf-* here, public/assets/admin/kova-admin.css): header band with the period and the export, four
     key figures, how the money came in, the revenue curve, then per zone and per courier. --}}
@php
    use App\Enums\OrderStatus;
    use App\Filament\Pages\Finances;
    use App\Filament\Widgets\DashboardOverview;
    $money = fn (int $amount) => Finances::money($amount);
    $trend = fn (string $key) => Finances::trend($summary[$key], $before[$key]);
    $badge = fn (?string $value) => $value ? '<span class="kh-trend '.(str_starts_with($value, '+') ? 'is-up' : (str_starts_with($value, '−') ? 'is-down' : '')).'">'.e($value).'</span>' : '';
    $collected = max(1, $summary['collected_online'] + $summary['collected_on_delivery']);
    $onlineShare = (int) round(max(0, $summary['collected_online']) / $collected * 100);
    $ordersTotal = max(1, $summary['orders']);
    $openStatuses = collect(OrderStatus::cases())->reject(fn ($status) => in_array($status, [OrderStatus::Delivered, OrderStatus::Cancelled], true))->map->value->values()->all();
    $zoneMax = max(1, (int) $zones->max('revenue'));
    $revenueTrend = $trend('revenue');
@endphp

<div class="kd kf">
    {{-- Header band: what period, how it went, change it, export it. --}}
    <header class="kd-hero kf-hero">
        <div class="kd-hero__text">
            <p class="kd-hero__eyebrow">
                <x-filament::icon icon="heroicon-o-calendar-days" class="kf-eyebrow-icon" />
                {{ $period->label }} · du {{ $period->from->format('d/m/Y') }} au {{ $period->to->format('d/m/Y') }}
            </p>
            <h1 class="kd-hero__title">Finances</h1>
            <p class="kd-hero__lead">
                <strong>{{ $money($summary['revenue']) }}</strong> de chiffre d’affaires et {{ $money($summary['collected']) }} encaissés
                @if ($revenueTrend) · {{ $revenueTrend }} par rapport à la période précédente @endif
            </p>
        </div>
        <div class="kf-hero__side">
            <div class="kf-seg" role="group" aria-label="Période">
                @foreach ($presets as $key => $label)
                    <button type="button" wire:click="choosePeriod('{{ $key }}')" @class(['kf-seg__item', 'is-active' => $preset === $key]) aria-pressed="{{ $preset === $key ? 'true' : 'false' }}">
                        {{ $key === 'custom' ? 'Dates…' : $label }}
                    </button>
                @endforeach
            </div>
            @if ($preset === 'custom')
                <div class="kf-dates">
                    <label>Du <input type="date" wire:model.live="filters.from"></label>
                    <label>Au <input type="date" wire:model.live="filters.to"></label>
                </div>
            @endif
            <button type="button" class="kd-btn kd-btn--light" wire:click="mountAction('export')">
                <x-filament::icon icon="heroicon-o-arrow-down-tray" class="kd-btn__icon" /> Exporter (CSV)
            </button>
        </div>
    </header>

    {{-- Four key figures. --}}
    <section class="kd-kpis" aria-label="Chiffres clés">
        <div class="kd-kpi kh-kpi">
            <span class="kd-kpi__icon kd-tone-orange"><x-filament::icon icon="heroicon-o-banknotes" /></span>
            <span class="kd-kpi__label">Chiffre d’affaires</span>
            <span class="kd-kpi__value kd-kpi__value--money">{{ $money($summary['revenue']) }}</span>
            <span class="kd-kpi__hint">dont {{ $money($summary['shipping_fees']) }} de livraison {!! $badge($revenueTrend) !!}</span>
            <svg class="kh-spark" viewBox="0 0 100 28" preserveAspectRatio="none" aria-hidden="true"><polyline points="{{ DashboardOverview::sparkline($series->pluck('revenue')->all()) }}" /></svg>
        </div>
        <div class="kd-kpi">
            <span class="kd-kpi__icon kd-tone-green"><x-filament::icon icon="heroicon-o-check-badge" /></span>
            <span class="kd-kpi__label">Total encaissé</span>
            <span class="kd-kpi__value kd-kpi__value--money">{{ $money($summary['collected']) }}</span>
            <span class="kd-kpi__hint">En ligne + à la livraison {!! $badge($trend('collected')) !!}</span>
        </div>
        <a href="{{ $ordersUrl() }}" class="kd-kpi">
            <span class="kd-kpi__icon kd-tone-navy"><x-filament::icon icon="heroicon-o-shopping-bag" /></span>
            <span class="kd-kpi__label">Commandes passées</span>
            <span class="kd-kpi__value">{{ $summary['orders'] }}</span>
            <span class="kd-kpi__hint">{{ $summary['sales'] }} vente{{ $summary['sales'] > 1 ? 's' : '' }} comptée{{ $summary['sales'] > 1 ? 's' : '' }} {!! $badge($trend('orders')) !!}</span>
        </a>
        <a href="{{ $couriersUrl }}" @class(['kd-kpi', 'kd-kpi--warn' => $summary['cash_with_couriers'] > 0])>
            <span class="kd-kpi__icon kd-tone-gold"><x-filament::icon icon="heroicon-o-wallet" /></span>
            <span class="kd-kpi__label">Argent chez les livreurs</span>
            <span class="kd-kpi__value kd-kpi__value--money">{{ $money($summary['cash_with_couriers']) }}</span>
            <span class="kd-kpi__hint">Aujourd’hui · {{ $money($summary['remitted']) }} versés sur la période</span>
        </a>
    </section>

    <div class="kf-split">
        {{-- How the money came in. --}}
        <section class="kd-card kf-card">
            <header class="kd-card__head"><h2>Encaissements</h2><span class="kd-muted">{{ $money($summary['collected']) }}</span></header>
            <div class="kf-body">
                <div class="kf-stack" role="img" aria-label="En ligne {{ $onlineShare }} %, à la livraison {{ 100 - $onlineShare }} %">
                    <span class="kf-stack__online" style="width: {{ $summary['collected'] > 0 ? $onlineShare : 0 }}%"></span>
                    <span class="kf-stack__cash" style="width: {{ $summary['collected'] > 0 ? 100 - $onlineShare : 0 }}%"></span>
                </div>
                <dl class="kf-legend">
                    <div><dt><span class="kf-dot kf-dot--online"></span> En ligne (CinetPay)</dt><dd>{{ $money($summary['collected_online']) }}</dd></div>
                    <div><dt><span class="kf-dot kf-dot--cash"></span> À la livraison</dt><dd>{{ $money($summary['collected_on_delivery']) }}</dd></div>
                    @if ($summary['refunds'] > 0)
                        <div><dt><span class="kf-dot kf-dot--refund"></span> Remboursements déduits</dt><dd>− {{ $money($summary['refunds']) }}</dd></div>
                    @endif
                    <div><dt><span class="kf-dot kf-dot--fees"></span> Frais de livraison facturés</dt><dd>{{ $money($summary['shipping_fees']) }}</dd></div>
                </dl>
            </div>
        </section>

        {{-- Orders of the period. --}}
        <section class="kd-card kf-card">
            <header class="kd-card__head"><h2>Commandes</h2><span class="kd-muted">{{ $summary['orders'] }} passée{{ $summary['orders'] > 1 ? 's' : '' }}</span></header>
            <div class="kf-body">
                <div class="kf-stack" role="img" aria-label="Livrées {{ $summary['delivered'] }}, en cours {{ $summary['in_progress'] }}, annulées {{ $summary['cancelled'] }}">
                    <span class="kf-stack__done" style="width: {{ round($summary['delivered'] / $ordersTotal * 100, 1) }}%"></span>
                    <span class="kf-stack__open" style="width: {{ round($summary['in_progress'] / $ordersTotal * 100, 1) }}%"></span>
                    <span class="kf-stack__cancel" style="width: {{ round($summary['cancelled'] / $ordersTotal * 100, 1) }}%"></span>
                </div>
                <dl class="kf-legend">
                    <div><dt><span class="kf-dot kf-dot--done"></span> <a href="{{ $ordersUrl(['status' => ['values' => [OrderStatus::Delivered->value]]]) }}">Livrées</a></dt><dd>{{ $summary['delivered'] }}</dd></div>
                    <div><dt><span class="kf-dot kf-dot--open"></span> <a href="{{ $ordersUrl(['status' => ['values' => $openStatuses]]) }}">En cours</a></dt><dd>{{ $summary['in_progress'] }}</dd></div>
                    <div><dt><span class="kf-dot kf-dot--cancel"></span> <a href="{{ $ordersUrl(['status' => ['values' => [OrderStatus::Cancelled->value]]]) }}">Annulées</a></dt><dd>{{ $summary['cancelled'] }}</dd></div>
                </dl>
            </div>
        </section>
    </div>

    {{-- Revenue curve. --}}
    <section class="kd-card kf-card">
        <header class="kd-card__head">
            <h2>Chiffre d’affaires {{ ['day' => 'par jour', 'week' => 'par semaine', 'month' => 'par mois'][$period->bucket()] }}</h2>
            <span class="kd-muted">Le plus haut : {{ $money($seriesMax > 1 ? $seriesMax : 0) }}</span>
        </header>
        <div class="kf-body">
            @if ($series->sum('revenue') === 0)
                <p class="kf-empty">Aucune vente sur la période.</p>
            @else
                <div class="kova-fin__chart" role="img" aria-label="Chiffre d’affaires de la période">
                    @foreach ($series as $point)
                        <div class="kova-fin__bar" tabindex="0">
                            <span class="kova-fin__bar-fill" style="height: {{ max($point['revenue'] > 0 ? 2 : 0, round($point['revenue'] / $seriesMax * 100, 1)) }}%"></span>
                            <span class="kova-fin__tip"><strong>{{ $point['label'] }}</strong><br>{{ $money($point['revenue']) }} · {{ $point['orders'] }} vente(s)</span>
                        </div>
                    @endforeach
                </div>
                <div class="kova-fin__axis"><span>{{ $series->first()['label'] }}</span><span>{{ $series->last()['label'] }}</span></div>
            @endif
        </div>
    </section>

    <div class="kf-split">
        {{-- Per zone: share of the revenue as a bar. --}}
        <section class="kd-card kf-card">
            <header class="kd-card__head"><h2>Par zone de livraison</h2><span class="kd-muted">Ventes de la période</span></header>
            <div class="kf-body">
                @if ($zones->isEmpty())
                    <p class="kf-empty">Aucune vente sur la période.</p>
                @else
                    <ul class="kf-rows">
                        @foreach ($zones as $zone)
                            <li class="kf-row">
                                <div class="kf-row__head">
                                    <a href="{{ $ordersUrl(['zone_name' => ['value' => $zone['zone']]]) }}" class="kf-row__name">{{ $zone['zone'] }}</a>
                                    <strong>{{ $money($zone['revenue']) }}</strong>
                                </div>
                                <div class="kh-top__bar"><span style="width: {{ max(3, round($zone['revenue'] / $zoneMax * 100)) }}%"></span></div>
                                <span class="kf-row__meta">{{ $zone['orders'] }} commande{{ $zone['orders'] > 1 ? 's' : '' }} · {{ $money($zone['shipping_fees']) }} de frais</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        {{-- Per courier. --}}
        <section class="kd-card kf-card">
            <header class="kd-card__head"><h2>Par livreur</h2><span class="kd-muted">Livraisons de la période · solde d’aujourd’hui</span></header>
            <div class="kf-body">
                @if ($couriers->isEmpty())
                    <p class="kf-empty">Aucune livraison sur la période.</p>
                @else
                    <ul class="kf-rows">
                        @foreach ($couriers as $row)
                            <li class="kf-courier">
                                <span class="kd-avatar kf-avatar" aria-hidden="true">{{ collect(preg_split('/\s+/', trim($row['courier']->name())))->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('') }}</span>
                                <div class="kf-courier__body">
                                    <div class="kf-row__head">
                                        <a href="{{ $courierUrl($row['courier']) }}" class="kf-row__name">{{ $row['courier']->name() }}</a>
                                        <span @class(['kf-due', 'is-due' => $row['due'] > 0])>{{ $row['due'] > 0 ? $money($row['due']).' à reverser' : 'À jour' }}</span>
                                    </div>
                                    <span class="kf-row__meta">
                                        {{ $row['delivered'] }} livraison{{ $row['delivered'] > 1 ? 's' : '' }}@if ($row['failed']) · {{ $row['failed'] }} échec(s)@endif
                                        @if ($row['success_rate'] !== null) · {{ $row['success_rate'] }} % de réussite @endif
                                        · encaissé {{ $money($row['collected']) }} · versé {{ $money($row['remitted']) }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </div>
</div>
