@extends('layouts.storefront')

@section('title', 'Mot de passe oublié')

@section('robots', 'noindex, nofollow')

@section('content')
    <x-page-header title="Mot de passe oublié" />

    <div class="rbt-section-gap2">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    @if (session('status'))
                        <div class="alert alert-success" role="status">{{ session('status') }}</div>
                    @endif

                    @if (\App\Services\Security\SmsCode::available())
                        <p class="b2 mb--24">Indiquez le numéro de téléphone ou l’e-mail de votre compte. Avec un numéro, vous recevez un code par {{ \App\Services\Security\SmsCode::channelLabel() }} ; avec un e-mail, un lien pour choisir un nouveau mot de passe.</p>
                    @else
                        {{-- No phone channel switched on: only the e-mail link works; the shop helps the others. --}}
                        <p class="b2 mb--24">Indiquez l’e-mail de votre compte : vous recevez un lien pour choisir un nouveau mot de passe. Votre compte n’a pas d’e-mail ? Contactez-nous au {{ app(\App\Services\Storefront\StoreSettings::class)->contact()['phone'] }}.</p>
                    @endif

                    <form method="POST" action="{{ route('password.send') }}" class="row g-3" novalidate>
                        @csrf
                        <div class="col-12">
                            <label class="rbt-field-label" for="login">Téléphone ou e-mail</label>
                            <input class="rbt-input-field" id="login" name="login" value="{{ old('login') }}" placeholder="07 01 02 03 04 ou vous@exemple.ci" autocomplete="username" required>
                            @error('login')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-12"><x-turnstile /></div>
                        <div class="col-12"><button type="submit" class="rbt-btn">Recevoir mon code ou mon lien</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
