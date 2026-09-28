@extends('layouts.storefront')

@section('title', 'Nouveau mot de passe')

@section('robots', 'noindex, nofollow')

@section('content')
    <x-page-header title="Nouveau mot de passe" />

    <div class="rbt-section-gap2">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <form method="POST" action="{{ route('password.reset.update') }}" class="row g-3" novalidate>
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <div class="col-12">
                            <label class="rbt-field-label" for="email">E-mail du compte</label>
                            <input class="rbt-input-field" type="email" id="email" name="email" value="{{ old('email', $email) }}" autocomplete="username" required>
                            @error('email')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="rbt-field-label" for="password">Nouveau mot de passe</label>
                            <input class="rbt-input-field" type="password" id="password" name="password" autocomplete="new-password" required>
                            @error('password')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="rbt-field-label" for="password_confirmation">Confirmer le mot de passe</label>
                            <input class="rbt-input-field" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                        </div>
                        <div class="col-12"><button type="submit" class="rbt-btn">Changer mon mot de passe</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
