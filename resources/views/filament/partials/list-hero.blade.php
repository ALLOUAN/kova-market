{{--
    Header band of a back-office list (orders, payments, customers), in the style of the home and the dashboards
    (.kd-*): title, one-sentence summary, then key figures as cards, each opening the matching part of the list.
    $title, $lead (HTML), $icon, $kpis: list of [label, value, hint, icon, tone (orange|navy|green|gold|red),
    url|null, accent (orange|gold|red|null)]. Refreshed every minute.
--}}
<x-filament-widgets::widget>
    <div class="kd kl" wire:poll.60s>
        <header class="kd-hero kl-hero">
            <div class="kd-hero__text">
                <p class="kd-hero__eyebrow">
                    <span class="kd-live" aria-hidden="true"></span>
                    {{ ucfirst(now()->translatedFormat('l j F')) }} · {{ now()->format('H:i') }}
                </p>
                <h1 class="kd-hero__title">
                    <x-filament::icon :icon="$icon" class="kl-hero__icon" /> {{ $title }}
                </h1>
                <p class="kd-hero__lead">{!! $lead !!}</p>
            </div>
        </header>

        <section class="kl-kpis" aria-label="Chiffres clés">
            @foreach ($kpis as $kpi)
                @php($tag = $kpi['url'] ? 'a' : 'div')
                <{{ $tag }} @if ($kpi['url']) href="{{ $kpi['url'] }}" @endif @class([
                    'kd-kpi',
                    'kd-kpi--accent' => ($kpi['accent'] ?? null) === 'orange',
                    'kd-kpi--warn' => ($kpi['accent'] ?? null) === 'gold',
                    'kl-kpi--alert' => ($kpi['accent'] ?? null) === 'red',
                ])>
                    <span class="kd-kpi__icon kd-tone-{{ $kpi['tone'] === 'red' ? 'orange' : $kpi['tone'] }} @if ($kpi['tone'] === 'red') kl-tone-red @endif"><x-filament::icon :icon="$kpi['icon']" /></span>
                    <span class="kd-kpi__label">{{ $kpi['label'] }}</span>
                    <span @class(['kd-kpi__value', 'kd-kpi__value--money' => $kpi['money'] ?? false])>{{ $kpi['value'] }}</span>
                    <span class="kd-kpi__hint">{!! $kpi['hint'] !!}</span>
                </{{ $tag }}>
            @endforeach
        </section>
    </div>
</x-filament-widgets::widget>
