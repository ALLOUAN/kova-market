@extends('courier.layout')

@section('title', 'Mot de passe')
@section('heading', 'Mon mot de passe')

@section('content')
    @if ($mustChange)
        <div class="alert ok">Bienvenue ! Choisissez votre propre mot de passe pour remplacer celui reçu sur WhatsApp.</div>
    @endif

    <form method="POST" action="{{ route('courier.password.update') }}">
        @csrf
        @method('PUT')
        @unless ($mustChange)
            <label for="current_password">Mot de passe actuel</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
            @error('current_password')<p class="error">{{ $message }}</p>@enderror
        @endunless
        <label for="password">Nouveau mot de passe <span class="muted">(8 caractères minimum)</span></label>
        <input id="password" name="password" type="password" autocomplete="new-password" required>
        @error('password')<p class="error">{{ $message }}</p>@enderror

        <label for="password_confirmation">Confirmer le mot de passe</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>

        <button type="submit" class="btn" style="margin-top: 20px">Enregistrer</button>
    </form>

    @unless ($mustChange)
        <p style="margin-top: 16px"><a href="{{ route('courier.home') }}">← Retour à mes livraisons</a></p>
    @endunless
@endsection
