@extends('layouts.storefront')

@section('title', 'Désinscription de la newsletter')
@section('robots', 'noindex, nofollow')

@section('content')
    <div class="rbt-section-gap2">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center">
                    @if ($done)
                        <h1 class="h3 mb--16">Vous êtes désinscrit</h1>
                        <p class="mb--24">L’adresse <strong>{{ $subscriber->email }}</strong> ne recevra plus notre newsletter. Vos commandes et leurs messages de suivi ne sont pas concernés.</p>
                        <a class="rbt-btn" style="width: auto; padding: 0 24px" href="{{ route('home') }}">Retour à la boutique</a>
                    @else
                        <h1 class="h3 mb--16">Se désinscrire de la newsletter ?</h1>
                        <p class="mb--24">L’adresse <strong>{{ $subscriber->email }}</strong> ne recevra plus nos offres ni nos nouveautés.</p>
                        <form method="POST" action="{{ route('newsletter.unsubscribe.confirm', $subscriber) }}" class="d-flex flex-wrap justify-content-center gap-3">
                            @csrf
                            <button type="submit" class="rbt-btn kova-btn-danger" style="width: auto; padding: 0 24px">Me désinscrire</button>
                            <a class="rbt-btn rbt-btn-border" style="width: auto; padding: 0 24px" href="{{ route('home') }}">Rester inscrit</a>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
