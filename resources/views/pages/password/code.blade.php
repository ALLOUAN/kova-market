@extends('layouts.storefront')

@section('title', 'Nouveau mot de passe')

@section('robots', 'noindex, nofollow')

@section('content')
    <x-page-header title="Nouveau mot de passe" />

    <div class="rbt-section-gap2">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    {{-- Same text whether or not the number has an account (F-076). --}}
                    <p class="b2 mb--24">Si un compte correspond au {{ $phone }}, un code à 6 chiffres vient de vous être envoyé par SMS. Il est valable {{ \App\Services\Account\PasswordRecovery::CODE_MINUTES }} minutes.</p>

                    <form method="POST" action="{{ route('password.code.update') }}" class="row g-3" novalidate>
                        @csrf
                        <div class="col-12">
                            <label class="rbt-field-label" for="code">Code reçu par SMS</label>
                            <input class="rbt-input-field" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required>
                            @error('code')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
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

                    <p class="b3 mt--24 mb-0">Pas de SMS ? <a href="{{ route('password.request') }}">Demander un nouveau code</a> (une minute après le précédent).</p>
                </div>
            </div>
        </div>
    </div>
@endsection
