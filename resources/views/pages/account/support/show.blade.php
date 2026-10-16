@extends('layouts.storefront')

@section('title', $ticket->title)
@section('robots', 'noindex, nofollow')

@section('content')
    <x-account-layout :title="$ticket->title">
        <section class="kova-dash-card mb--24">
            <div class="kova-ticket-meta">
                <span @class(['kova-ticket-status', 'kova-ticket-status--'.$ticket->status->value])>{{ $ticket->status->customerLabel() }}</span>
                <span>{{ $ticket->reference() }}</span>
                <span>{{ $ticket->subject->getLabel() }}</span>
                @if ($ticket->order)
                    <a href="{{ route('account.orders.show', $ticket->order) }}">Commande {{ $ticket->order->number }}</a>
                @endif
                <span>Ouverte le {{ $ticket->created_at->format('d/m/Y à H:i') }}</span>
            </div>

            <ol class="kova-ticket-thread" aria-label="Conversation">
                @foreach ($ticket->messages as $message)
                    <li @class(['kova-ticket-message', 'kova-ticket-message--staff' => $message->from_staff])>
                        <p class="kova-ticket-message__who">
                            @if ($message->from_staff)
                                <i class="fa-regular fa-headset" aria-hidden="true"></i>{{ config('storefront.name') }}
                            @else
                                Vous
                            @endif
                            <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('d/m/Y à H:i') }}</time>
                        </p>
                        @if (filled($message->body))
                            <div class="kova-ticket-message__body">{!! nl2br(e($message->body)) !!}</div>
                        @endif
                        <x-support.files :ticket="$ticket" :message="$message" />
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="kova-dash-card">
            <h2 class="kova-dash-card__title mb--12">{{ $ticket->isClosed() ? 'Rouvrir la demande' : 'Répondre' }}</h2>
            @if ($ticket->isClosed())
                <p class="kova-dash-address__line mb--12">Cette demande est fermée. Écrivez un message pour la rouvrir.</p>
            @endif
            <form method="POST" action="{{ route('account.support.reply', $ticket) }}" enctype="multipart/form-data" novalidate>
                @csrf
                <label class="visually-hidden" for="message">Votre message</label>
                <textarea class="rbt-input-field kova-wa-input" id="message" name="message" rows="4" maxlength="3000" placeholder="Votre message…" required>{{ old('message') }}</textarea>
                @error('message')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                <x-support.attach />
                <div class="d-flex flex-wrap gap-2 mt--12">
                    <button type="submit" class="rbt-btn rbt-btn-sm"><i class="fa-regular fa-paper-plane mr--8"></i>Envoyer</button>
                    <a class="rbt-btn rbt-btn-sm rbt-btn-border" href="{{ route('account.support.index') }}">Toutes mes demandes</a>
                </div>
            </form>
            @unless ($ticket->isClosed())
                <form method="POST" action="{{ route('account.support.close', $ticket) }}" class="mt--16">
                    @csrf
                    <button type="submit" class="kova-ticket-close"><i class="fa-regular fa-circle-check mr--4"></i>Mon problème est résolu, fermer la demande</button>
                </form>
            @endunless
        </section>
    </x-account-layout>
@endsection
