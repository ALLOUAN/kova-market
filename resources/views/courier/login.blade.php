@extends('courier.layout')

@section('title', 'Connexion')

@php
    $phoneError = $errors->first('phone');
    $storePhone = config('storefront.contact.phone');
    $whatsapp = preg_replace('/\D/', '', (string) config('storefront.contact.whatsapp'));
@endphp

@section('bare')
    {{-- Same look as the back-office sign-in: plain night blue with solid orange and gold shapes, one white card. --}}
    <div class="auth-shapes" aria-hidden="true">
        <span class="auth-shapes__ring"></span>
        <span class="auth-shapes__square"></span>
        <span class="auth-shapes__triangle"></span>
        <span class="auth-shapes__disc"></span>
        <span class="auth-shapes__dot"></span>
    </div>
    <div class="auth-wrap">
        <div class="auth-panel">
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

        </div>

        <p class="auth-foot">Vous êtes client ? <a href="{{ route('home') }}">Aller à la boutique</a></p>
        <p class="auth-copy">© {{ now()->year }} {{ config('storefront.name') }} · Espace réservé aux livreurs</p>
    </div>

    <style>
        body.auth { position: relative; min-height: 100vh; overflow-x: hidden; background: var(--primary); }
        .auth-shapes { position: fixed; inset: 0; z-index: 0; pointer-events: none; }
        .auth-shapes span { position: absolute; display: block; }
        .auth-shapes__ring { top: -120px; left: -120px; width: 300px; height: 300px; border-radius: 50%; border: 42px solid var(--action); }
        .auth-shapes__square { top: 9%; right: 7%; width: 46px; height: 46px; border-radius: 12px; background: #f85000; transform: rotate(18deg); }
        .auth-shapes__triangle { right: 6%; bottom: 9%; width: 0; height: 0; border-left: 50px solid transparent; border-right: 50px solid transparent; border-bottom: 86px solid var(--accent); transform: rotate(-14deg); }
        .auth-shapes__disc { right: -80px; bottom: -80px; width: 220px; height: 220px; border-radius: 50%; background: #0a2a52; }
        .auth-shapes__dot { bottom: 22%; left: 8%; width: 16px; height: 16px; border-radius: 50%; background: #eac658; }
        .auth-wrap { position: relative; z-index: 1; max-width: 460px; padding-top: 48px; }
        .auth-panel { position: relative; overflow: hidden; padding: 34px 24px 24px; border-radius: 22px; background: #fff; box-shadow: 0 30px 70px rgb(0 0 0 / .45); }
        .auth-panel::before { content: ""; position: absolute; inset: 0 0 auto; height: 6px; background: #f85000; }
        .auth-brand .eyebrow { background: #fff4ec; color: var(--action); }
        .auth-card { border: 0; padding: 0; box-shadow: none; }
        .help { margin-top: 22px; background: #f4f6fa; }
        .auth-foot { color: rgb(255 255 255 / .75); }
        .auth-foot a { color: #fff; font-weight: 700; }
        .auth-copy { margin: 10px 0 0; text-align: center; font-size: 12px; color: rgb(255 255 255 / .5); }
        .auth .alert { margin-bottom: 14px; }
        @media (min-width: 768px) {
            .auth-wrap { padding-top: 7vh; }
            .auth-panel { padding: 40px 36px 30px; }
            .auth-shapes__ring { width: 420px; height: 420px; border-width: 56px; top: -140px; left: -140px; }
            .auth-shapes__square { width: 64px; height: 64px; top: 14%; right: 14%; }
            .auth-shapes__triangle { border-left-width: 70px; border-right-width: 70px; border-bottom-width: 120px; right: 10%; bottom: 12%; }
            .auth-shapes__disc { width: 300px; height: 300px; right: -90px; bottom: -90px; }
        }
    </style>

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
