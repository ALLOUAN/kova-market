@extends('layouts.storefront')

@section('title', 'Nouvelle demande')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-account-layout title="Nouvelle demande">
        <section class="kova-dash-card">
            <p class="kova-dash-address__line mb--24">Décrivez votre demande : notre équipe vous répond sur cette page et par e-mail.</p>

            <form method="POST" action="{{ route('account.support.store') }}" class="row g-3" enctype="multipart/form-data" novalidate>
                @csrf
                <div class="col-md-6">
                    <label class="rbt-field-label" for="subject">Sujet<span class="rbt-text-color-danger">*</span></label>
                    <select id="subject" name="subject" class="form-select" required>
                        <option value="">Choisir</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->value }}" @selected(old('subject', $selectedOrder ? 'commande' : null) === $subject->value)>{{ $subject->getLabel() }}</option>
                        @endforeach
                    </select>
                    @error('subject')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="rbt-field-label" for="order">Commande concernée</label>
                    <select id="order" name="order" class="form-select">
                        <option value="">Aucune en particulier</option>
                        @foreach ($orders as $order)
                            <option value="{{ $order->number }}" @selected(old('order', $selectedOrder) === $order->number)>{{ $order->number }} · {{ $order->created_at->format('d/m/Y') }} · @money($order->total)</option>
                        @endforeach
                    </select>
                    @error('order')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                </div>
                <div class="col-12">
                    <label class="rbt-field-label" for="title">Titre<span class="rbt-text-color-danger">*</span></label>
                    <input class="rbt-input-field" id="title" name="title" value="{{ old('title') }}" maxlength="120" placeholder="Ex. : Ma commande n’est pas arrivée" required>
                    @error('title')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                </div>
                <div class="col-12">
                    <label class="rbt-field-label" for="message">Votre message<span class="rbt-text-color-danger">*</span></label>
                    <textarea class="rbt-input-field kova-wa-input" id="message" name="message" rows="6" maxlength="3000" placeholder="Donnez-nous un maximum de détails : ce qui s’est passé, l’article concerné…" required>{{ old('message') }}</textarea>
                    @error('message')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                    <x-support.attach />
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button type="submit" class="rbt-btn"><i class="fa-regular fa-paper-plane mr--8"></i>Envoyer ma demande</button>
                    <a class="rbt-btn rbt-btn-border" href="{{ route('account.support.index') }}">Annuler</a>
                </div>
            </form>
        </section>
    </x-account-layout>
@endsection
