@extends('courier.layout')

@section('title', 'Connexion')

@php
    $phoneError = $errors->first('phone');
    $storePhone = config('storefront.contact.phone');
    $whatsapp = preg_replace('/\D/', '', (string) config('storefront.contact.whatsapp'));
@endphp

@section('bare')
    <div class="auth-top"></div>
    <div class="auth-wrap">
        <div class="auth-brand">
            <img src="{{ asset(config('storefront.logo_small')) }}" alt="{{ config('storefront.name') }}">
            <div><span class="eyebrow">Espace livreur</span></div>
            <h1>Connexion</h1>
            <p>Avec votre numéro et le mot de passe reçu sur WhatsApp.</p>
        </div>

        @foreach (['courier_status' => 'ok', 'courier_error' => 'error'] as $key => $type)
            @if (session($key))
                <div class="alert {{ $type }}" role="{{ $type === 'error' ? 'alert' : 'status' }}">{{ session($key) }}</div>
            @endif
        @endforeach

        @if ($phoneError)
            <div class="alert error" role="alert">{{ $phoneError }}</div>
        @endif

        <form method="POST" action="{{ route('courier.authenticate') }}" class="auth-card" data-courier-login novalidate>
            @csrf
            <label for="phone">Numéro de téléphone</label>
            <div class="field">
                <span class="prefix" aria-hidden="true">+225</span>
                <input id="phone" name="phone" type="tel" inputmode="tel" autocomplete="username" class="with-prefix"
                    value="{{ old('phone') }}" placeholder="07 01 02 03 04" required @if (! old('phone')) autofocus @endif
                    aria-describedby="phone-hint" @if ($phoneError) aria-invalid="true" @endif>
            </div>
            <p class="hint" id="phone-hint">Le numéro enregistré par la boutique, avec ou sans espaces.</p>

            <label for="password">Mot de passe</label>
            <div class="field">
                <input id="password" name="password" type="password" autocomplete="current-password" class="with-toggle" required
                    @if (old('phone')) autofocus @endif @error('password') aria-invalid="true" @enderror>
                <button type="button" class="toggle" data-toggle-password aria-controls="password" aria-pressed="false">Afficher</button>
            </div>
            @error('password')<p class="error">{{ $message }}</p>@enderror

            <button type="submit" class="btn">Se connecter</button>
            <p class="hint" style="text-align: center; margin-top: 10px">Vous restez connecté sur ce téléphone.</p>
        </form>

        <div class="help">
            <strong>Mot de passe oublié ou compte bloqué ?</strong>
            <p>La boutique vous envoie un nouveau mot de passe sur WhatsApp.</p>
            <div class="{{ $whatsapp ? 'grid2' : '' }}">
                @if (filled($storePhone))
                    <a class="btn secondary" href="tel:{{ preg_replace('/[^\d+]/', '', $storePhone) }}">Appeler</a>
                @endif
                @if ($whatsapp)
                    <a class="btn whatsapp" href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Bonjour, je suis livreur et j’ai besoin d’un nouveau mot de passe.') }}" rel="noopener">WhatsApp</a>
                @endif
            </div>
        </div>

        <p class="auth-foot">Vous êtes client ? <a href="{{ route('home') }}">Aller à la boutique</a></p>
    </div>

    <script>
        // Show / hide the password, and no double submission on a slow network.
        document.querySelector('[data-toggle-password]')?.addEventListener('click', (event) => {
            const input = document.getElementById('password');
            const shown = input.type === 'text';
            input.type = shown ? 'password' : 'text';
            event.currentTarget.textContent = shown ? 'Afficher' : 'Masquer';
            event.currentTarget.setAttribute('aria-pressed', String(! shown));
        });
        document.querySelector('[data-courier-login]')?.addEventListener('submit', (event) => {
            const button = event.currentTarget.querySelector('button[type="submit"]');
            if (button.getAttribute('aria-busy') === 'true') { event.preventDefault(); return; }
            button.setAttribute('aria-busy', 'true');
            button.textContent = 'Connexion…';
        });
    </script>
@endsection
