@extends('layouts.storefront')

@section('title', 'Assistance')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-account-layout title="Assistance">
        <section class="kova-ticket-intro">
            <span class="kova-ticket-intro__icon" aria-hidden="true"><i class="fa-regular fa-headset"></i></span>
            {{-- One request at a time: while one waits for the team, the button leads back to it. --}}
            @if ($pending)
                <div class="kova-ticket-intro__text">
                    <h2 class="kova-dash-card__title mb--4">Votre demande est entre nos mains</h2>
                    <p class="mb-0">« {{ $pending->title }} » attend notre réponse. Vous pourrez ouvrir une nouvelle demande dès que nous y aurons répondu ; en attendant, vous pouvez la compléter.</p>
                </div>
                <a class="rbt-btn rbt-btn-sm kova-ticket-intro__action" href="{{ route('account.support.show', $pending) }}"><i class="fa-regular fa-message-lines mr--8"></i>Voir ma demande</a>
            @else
                <div class="kova-ticket-intro__text">
                    <h2 class="kova-dash-card__title mb--4">Une question, un problème ?</h2>
                    <p class="mb-0">Écrivez à notre équipe : votre demande et nos réponses restent ici. Réponse en général dans la journée.</p>
                </div>
                <a class="rbt-btn rbt-btn-sm kova-ticket-intro__action" href="{{ route('account.support.create') }}"><i class="fa-regular fa-plus mr--8"></i>Nouvelle demande</a>
            @endif
        </section>

        <section class="kova-dash-card">
            <div class="kova-dash-card__head">
                <h2 class="kova-dash-card__title">Mes demandes</h2>
            </div>
            @forelse ($tickets as $ticket)
                <a href="{{ route('account.support.show', $ticket) }}" class="kova-dash-order">
                    <span @class(['kova-dash-order__icon', 'kova-ticket-unread' => $ticket->unread_by_customer])><i class="fa-regular fa-message-lines"></i></span>
                    <span class="kova-dash-order__main">
                        <strong>{{ $ticket->title }}</strong>
                        <span>{{ $ticket->reference() }} · {{ $ticket->subject->getLabel() }}@if ($ticket->order) · Commande {{ $ticket->order->number }}@endif · {{ $ticket->last_message_at?->diffForHumans() }}</span>
                    </span>
                    <span @class(['kova-ticket-status', 'kova-ticket-status--'.$ticket->status->value])>
                        @if ($ticket->unread_by_customer)<span class="visually-hidden">Nouveau message : </span>@endif{{ $ticket->status->customerLabel() }}
                    </span>
                    <i class="fa-regular fa-chevron-right kova-dash-order__go" aria-hidden="true"></i>
                </a>
            @empty
                <div class="kova-dash-empty">
                    <span class="kova-dash-empty__icon"><i class="fa-regular fa-headset"></i></span>
                    <p class="kova-dash-empty__title">Aucune demande pour l’instant</p>
                    <p class="kova-dash-empty__text">Une commande en retard, un produit abîmé, une question ? Nous sommes là pour vous aider.</p>
                    <a class="rbt-btn rbt-btn-sm" href="{{ route('account.support.create') }}">Envoyer une demande</a>
                </div>
            @endforelse

            <div class="mt--16">{{ $tickets->links() }}</div>
        </section>
    </x-account-layout>
@endsection
