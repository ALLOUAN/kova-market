@extends('layouts.storefront')

@section('title', 'Commande '.$order->number)
@section('robots', 'noindex, nofollow')

@section('content')
    <x-account-layout :title="'Commande '.$order->number">
        <div class="row g-4">
            <div class="col-md-5">
                <h2 class="h6 mb--16">Suivi</h2>
                <x-order-timeline :order="$order" vertical />
                <x-order-delivery :order="$order" />
            </div>
            <div class="col-md-7">
                <h2 class="h6 mb--16">Articles</h2>
                <ul class="list-unstyled mb--16">
                    @foreach ($order->items as $item)
                        <li class="d-flex justify-content-between gap-3 mb--8">
                            <span>{{ $item->quantityLabel() }} × {{ $item->product_name }}@if ($item->variant_label)<span class="b4 d-block">{{ $item->variant_label }}</span>@endif@if ($item->contentsSummary())<span class="b4 d-block">{{ $item->contentsSummary() }}</span>@endif@if ($item->weighNote())<span class="b4 d-block">{{ $item->weighNote() }}</span>@endif</span>
                            <span class="text-nowrap">@money($item->line_total)</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="border-top pt-3 mb--24">
                    <div class="d-flex justify-content-between mb--8"><dt class="fw-normal">Sous-total</dt><dd class="mb-0">@money($order->subtotal)</dd></div>
                    @if ($order->discount > 0)<div class="d-flex justify-content-between mb--8"><dt class="fw-normal">Remise ({{ $order->coupon_code }})</dt><dd class="mb-0">−@money($order->discount)</dd></div>@endif
                    <div class="d-flex justify-content-between mb--8"><dt class="fw-normal">Livraison</dt><dd class="mb-0">{{ $order->shipping_fee === 0 ? 'Offerte' : \App\Support\Money::format($order->shipping_fee) }}</dd></div>
                    <div class="d-flex justify-content-between border-top pt-2 mt-2"><dt class="h6 mb-0">Total</dt><dd class="h6 mb-0">@money($order->total)</dd></div>
                </dl>
                <p class="b3 mb--4"><strong>Livraison :</strong> {{ $order->customer_name }}, {{ $order->district }}, {{ $order->commune_name }}@if ($order->landmark) ({{ $order->landmark }})@endif</p>
                <p class="b3 mb-0"><strong>Paiement :</strong> {{ $order->payment_method->getLabel() }} — {{ $order->payment_status->getLabel() }}</p>
                <a class="rbt-btn rbt-btn-border rbt-btn-sm mt--16" style="width: auto; padding: 0 18px" href="{{ route('orders.receipt', $order) }}" target="_blank" rel="noopener">
                    <i class="fa-regular fa-file-invoice me-2" aria-hidden="true"></i>Télécharger le reçu (PDF)
                </a>
            </div>
        </div>

        {{-- Verified-purchase reviews: once delivered, one review per article, published after moderation. --}}
        @if ($order->status === \App\Enums\OrderStatus::Delivered && $order->items->whereNotNull('product_id')->isNotEmpty())
            <div class="mt--40">
                <h2 class="h6 mb--8">Votre avis sur vos articles</h2>
                <p class="b3 mb--16">Votre note aide les autres clients. Elle est publiée avec votre prénom et l’initiale de votre nom, après vérification.</p>
                @if ($errors->hasAny(['rating', 'comment']))
                    <div class="alert alert-danger mb--16" role="alert">{{ $errors->first('rating') ?: $errors->first('comment') }}</div>
                @endif
                @foreach ($order->items->whereNotNull('product_id') as $item)
                    @php($review = $reviews->get($item->getKey()))
                    <div class="kova-review-box">
                        <p class="mb--8"><strong>{{ $item->product_name }}</strong>@if ($item->variant_label) <span class="b4">({{ $item->variant_label }})</span>@endif</p>
                        @if ($review)
                            <p class="b3 mb-0">
                                <span class="kova-stars" aria-label="Votre note : {{ $review->rating }} sur 5">{{ str_repeat('★', $review->rating) }}<span class="off">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                                — {{ $review->status === \App\Enums\ReviewStatus::Approved ? 'Avis publié, merci !' : ($review->status === \App\Enums\ReviewStatus::Rejected ? 'Avis non publié.' : 'Avis envoyé, en cours de vérification.') }}
                            </p>
                        @else
                            <form method="POST" action="{{ route('account.reviews.store', [$order, $item]) }}">
                                @csrf
                                <fieldset class="border-0 p-0 m-0 mb--8">
                                    <legend class="b3 mb--4">Votre note<span class="rbt-text-color-danger">*</span></legend>
                                    <span class="kova-rating-input">
                                        @for ($star = 5; $star >= 1; $star--)
                                            <input type="radio" id="rating-{{ $item->getKey() }}-{{ $star }}" name="rating" value="{{ $star }}" required>
                                            <label for="rating-{{ $item->getKey() }}-{{ $star }}" title="{{ $star }} sur 5"><span class="visually-hidden">{{ $star }} sur 5</span>★</label>
                                        @endfor
                                    </span>
                                </fieldset>
                                <label class="rbt-field-label" for="comment-{{ $item->getKey() }}">Commentaire <span class="b4">(facultatif)</span></label>
                                <textarea id="comment-{{ $item->getKey() }}" name="comment" rows="2" maxlength="1000" class="w-100" placeholder="Qualité, conformité, livraison…"></textarea>
                                <button type="submit" class="rbt-btn rbt-btn-sm mt--8" style="width: auto; padding: 0 20px">Envoyer mon avis</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-account-layout>
@endsection
