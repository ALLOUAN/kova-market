@extends('courier.layout')

{{-- "Mes encaissements": what I owe the store now first, then my figures over the chosen period, my payments with
     their receipt, and every movement (+ cash collected at a door, − cash handed over). No earnings: the platform
     pays couriers no commission for now. --}}
@section('title', 'Mes encaissements')
@section('tab', 'money')

@section('hero')
    <header class="ch-hero">
        @include('courier.partials.hero-top', ['title' => 'Mes encaissements', 'subtitle' => 'L’argent des clients et vos versements'])
    </header>
@endsection

@section('content')
    {{-- What the courier owes the store today, whatever the period. --}}
    @if ($due > 0)
        <section class="cm-due">
            <span class="cm-due__icon">@include('courier.partials.icon', ['name' => 'wallet'])</span>
            <div>
                <p class="cm-due__label">À reverser à la boutique</p>
                <p class="cm-due__amount">@money($due)</p>
                <p class="cm-due__hint">Argent encaissé que vous n’avez pas encore remis. À chaque versement, la boutique vous remet un reçu.</p>
            </div>
        </section>
    @else
        <section class="cm-due is-clear">
            <span class="cm-due__icon">@include('courier.partials.icon', ['name' => 'check'])</span>
            <div>
                <p class="cm-due__label">À reverser à la boutique</p>
                <p class="cm-due__amount">@money(0)</p>
                <p class="cm-due__hint">Vous avez tout reversé. Merci !</p>
            </div>
        </section>
    @endif

    <nav class="ch-seg" aria-label="Période">
        @foreach ($periods as $key => $label)
            <a href="{{ route('courier.money', ['periode' => $key]) }}" @class(['ch-seg__item', 'is-active' => $key === $preset]) @if ($key === $preset) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    <div class="ch-stats">
        <div class="ch-stat">
            <span class="ch-stat__icon ch-tone-green">@include('courier.partials.icon', ['name' => 'check'])</span>
            <span class="ch-stat__value">{{ $statement['delivered'] }}</span>
            <span class="ch-stat__label">Livraisons réussies</span>
        </div>
        <div class="ch-stat">
            <span class="ch-stat__icon ch-tone-navy">@include('courier.partials.icon', ['name' => 'box'])</span>
            <span class="ch-stat__value ch-stat__value--money">@money($statement['orders_total'])</span>
            <span class="ch-stat__label">Commandes livrées</span>
        </div>
        <div class="ch-stat">
            <span class="ch-stat__icon ch-tone-orange">@include('courier.partials.icon', ['name' => 'cash'])</span>
            <span class="ch-stat__value ch-stat__value--money">@money($statement['collected'])</span>
            <span class="ch-stat__label">Encaissé</span>
        </div>
        <div class="ch-stat">
            <span class="ch-stat__icon ch-tone-gold">@include('courier.partials.icon', ['name' => 'wallet'])</span>
            <span class="ch-stat__value ch-stat__value--money">@money($statement['remitted'])</span>
            <span class="ch-stat__label">Reversé</span>
        </div>
    </div>
    <p class="cm-note">@include('courier.partials.icon', ['name' => 'truck', 'class' => 'ico--sm']) Frais de livraison de ces commandes : <strong>@money($statement['shipping_fees'])</strong></p>

    <h2 class="cm-title">Mes versements</h2>
    @forelse ($remittances as $remittance)
        <article @class(['cm-pay', 'is-cancelled' => $remittance->isCancelled()])>
            <span class="cm-pay__icon">@include('courier.partials.icon', ['name' => $remittance->isCancelled() ? 'refresh' : 'check'])</span>
            <div class="cm-pay__body">
                <div class="ch-card__row">
                    <strong class="cm-pay__amount">@money($remittance->amount)</strong>
                    <span @class(['ch-pill', 'ch-pill--ready' => ! $remittance->isCancelled(), 'ch-pill--wait' => $remittance->isCancelled()])>{{ $remittance->isCancelled() ? 'Annulé' : $remittance->method->getLabel() }}</span>
                </div>
                <p class="cm-pay__meta">
                    {{ $remittance->number() }} · {{ $remittance->received_at->format('d/m/Y à H:i') }}
                    @if ($remittance->receivedBy)<br>Reçu par {{ $remittance->receivedBy->name }}@endif
                    @if ($remittance->isCancelled())<br>Motif : {{ $remittance->cancel_reason }}@endif
                </p>
                <a class="cm-pay__receipt" href="{{ route('courier.remittances.receipt', $remittance) }}">
                    @include('courier.partials.icon', ['name' => 'inbox', 'class' => 'ico--sm']) Voir le reçu
                </a>
            </div>
        </article>
    @empty
        <div class="ch-empty">
            @include('courier.partials.icon', ['name' => 'wallet', 'class' => 'ico--lg'])
            <p><strong>Aucun versement pour le moment.</strong><br>Vos versements à la boutique apparaîtront ici avec leur reçu.</p>
        </div>
    @endforelse

    <h2 class="cm-title">Historique</h2>
    @if ($history->isEmpty())
        <div class="ch-empty">
            @include('courier.partials.icon', ['name' => 'clock', 'class' => 'ico--lg'])
            <p><strong>Aucune opération pour le moment.</strong><br>Chaque encaissement et chaque versement s’affichera ici.</p>
        </div>
    @else
        <ol class="cm-timeline">
            @foreach ($history as $entry)
                <li @class(['cm-timeline__item', $entry['amount'] > 0 ? 'is-in' : 'is-out', 'is-cancelled' => $entry['cancelled']])>
                    <span class="cm-timeline__dot" aria-hidden="true">{{ $entry['amount'] > 0 ? '+' : '−' }}</span>
                    <div class="cm-timeline__body">
                        <div class="ch-card__row">
                            <span class="cm-timeline__label">{{ $entry['label'] }}</span>
                            <strong class="cm-timeline__amount">{{ $entry['amount'] > 0 ? '+' : '−' }} @money(abs($entry['amount']))</strong>
                        </div>
                        <p class="cm-timeline__meta">{{ $entry['date']->format('d/m/Y à H:i') }}@if ($entry['note']) · {{ $entry['note'] }}@endif</p>
                    </div>
                </li>
            @endforeach
        </ol>
        <p class="cm-legend"><span class="is-in">+</span> argent encaissé chez le client, à reverser · <span class="is-out">−</span> argent remis à la boutique</p>
    @endif
@endsection
