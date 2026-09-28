@extends('courier.layout')

@section('title', 'Connexion')
@section('heading', 'Espace livreur')

@section('content')
    <p>Connectez-vous avec le numéro et le mot de passe reçus par SMS.</p>

    <form method="POST" action="{{ route('courier.authenticate') }}">
        @csrf
        <label for="phone">Téléphone</label>
        <input id="phone" name="phone" type="tel" inputmode="tel" autocomplete="username" value="{{ old('phone') }}" placeholder="07 01 02 03 04" required autofocus>
        @error('phone')<p class="error">{{ $message }}</p>@enderror

        <label for="password">Mot de passe</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>

        <button type="submit" class="btn" style="margin-top: 20px">Se connecter</button>
    </form>

    <p class="muted" style="margin-top: 24px">Mot de passe oublié ? Demandez-en un nouveau à la boutique : il vous sera envoyé par SMS.</p>
@endsection
