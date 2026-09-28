@extends('layouts.storefront')

@section('title', 'Suivre ma commande')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-page-header title="Suivre ma commande" />

    <div class="rbt-section-gap2">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <form method="POST" action="{{ route('tracking.search') }}" class="row g-3 mb--32" novalidate>
                        @csrf
                        <div class="col-md-6">
                            <label class="rbt-field-label" for="number">Numéro de commande</label>
                            <input class="rbt-input-field" id="number" name="number" value="{{ old('number', request('number')) }}" placeholder="KM-260927-0042" required>
                            @error('number')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="rbt-field-label" for="phone">Téléphone utilisé pour la commande</label>
                            <input class="rbt-input-field" id="phone" name="phone" type="tel" value="{{ old('phone', request('phone')) }}" placeholder="07 01 02 03 04" required>
                            @error('phone')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-12"><x-turnstile /></div>
                        <div class="col-12"><button type="submit" class="rbt-btn">Suivre</button></div>
                    </form>

                    @if ($notFound ?? false)
                        {{-- Same answer whatever is wrong: the page never reveals whether an order number exists. --}}
                        <div class="alert alert-warning" role="status">Commande introuvable. Vérifiez le numéro et le téléphone indiqués lors de la commande.</div>
                    @elseif ($order)
                        <div class="rbt-bg-color-gray-light rbt-radius p-4">
                            <div class="d-flex flex-wrap justify-content-between gap-2 mb--16">
                                <h2 class="h5 mb-0">Commande {{ $order->number }}</h2>
                                <span>du {{ $order->created_at->format('d/m/Y') }}</span>
                            </div>
                            <x-order-timeline :order="$order" />
                            <x-order-delivery :order="$order" />
                            <p class="b3 mt--16 mb--4">{{ $order->itemCount() }} {{ Str::plural('article', $order->itemCount()) }} · @money($order->total) · {{ $order->payment_method->getLabel() }}</p>
                            {{-- Only the commune: the full address and the e-mail are never shown here. --}}
                            <p class="b3 mb-0">Livraison à {{ $order->commune_name }}.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
