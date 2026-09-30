{{-- Mini-cart side panel, fed by the visitor's cart ($cartSummary from the layout composer). --}}
<div class="rbt-cart-side-menu rbt-sidebar-cart">
    <div class="inner-wrapper">
        <div class="inner-top">
            <div class="rbt-cart-header">
                <div class="title-section">
                    <h2 class="title mb--0 h6"><i class="fa-sharp fa-regular fa-cart-shopping mr--12"></i> Votre panier
                        @if ($cartSummary->count())
                            <span class="b3">({{ $cartSummary->count() }})</span>
                        @endif
                    </h2>
                </div>
                <div class="rbt-btn-close" id="btn_sideNavClose">
                    <button class="minicart-close-button rbt-round-btn" aria-label="Fermer le panier"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
            <nav class="side-nav w-100" aria-label="Articles du panier">
                @if ($cartSummary->isEmpty())
                    <div class="text-center py-5">
                        <p class="mb--16">Votre panier est vide.</p>
                        <a class="rbt-btn rbt-btn-sm" href="{{ route('shop.index') }}">Découvrir la boutique</a>
                    </div>
                @else
                    <ul class="rbt-minicart-wrapper">
                        @foreach ($cartSummary->lines as $line)
                            <li class="minicart-item">
                                <div class="thumbnail">
                                    <a href="{{ $line->product->url() }}">
                                        <img src="{{ asset($line->product->image) }}" alt="{{ $line->product->name }}" loading="lazy" decoding="async">
                                    </a>
                                </div>
                                <div class="product-content">
                                    <h3 class="title h6"><a href="{{ $line->product->url() }}">{{ $line->product->name }}</a></h3>
                                    @if ($line->variant->attributeValues->isNotEmpty())
                                        <p class="b4 mb--4">{{ $line->variant->label() }}</p>
                                    @endif
                                    <span class="quantity">{{ $line->saleQuantity()->format($line->item->quantity) }} × <span class="price">@money($line->unitPrice()){{ str_replace(" / ", "/", $line->saleQuantity()->priceSuffix()) }}</span></span>
                                    @if ($line->notice)
                                        <p class="b4 mt--4 rbt-text-color-danger">{{ $line->notice }}</p>
                                    @endif
                                </div>
                                <div class="close-btn">
                                    <form method="POST" action="{{ route('cart.items.destroy', $line->item->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rbt-round-btn" type="submit" aria-label="Retirer {{ $line->product->name }}"><i class="fa-solid fa-xmark"></i></button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </nav>
        </div>
        @unless ($cartSummary->isEmpty())
            <div class="rbt-minicart-footer">
                <hr class="mb--0 mt--16">
                <div class="rbt-cart-subttotal">
                    <p>Sous-total ({{ $cartSummary->count() }} {{ Str::plural('article', $cartSummary->count()) }})</p>
                    <p class="price">@money($cartSummary->subtotal)</p>
                </div>
                @if ($cartSummary->discount > 0)
                    <div class="rbt-cart-subttotal">
                        <p>Remise ({{ $cartSummary->coupon->code }})</p>
                        <p class="price">−@money($cartSummary->discount)</p>
                    </div>
                @endif
                <div class="rbt-cart-subttotal">
                    <p>Livraison</p>
                    <p class="price">
                        @if ($cartSummary->shippingFee === null)
                            Selon votre commune
                        @elseif ($cartSummary->shippingFee === 0)
                            Offerte
                        @else
                            @money($cartSummary->shippingFee)
                        @endif
                    </p>
                </div>
                <hr class="mb--0">
                <div class="rbt-cart-subttotal">
                    <p class="subtotal"><strong>Total</strong></p>
                    <p class="price">@money($cartSummary->total())</p>
                </div>
                @if ($missing = $cartSummary->missingForFreeShipping())
                    <div class="offer-progress-area">
                        <p class="offer-text">Plus que <strong>@money($missing)</strong> pour la <strong>livraison offerte</strong></p>
                        <div class="progress" role="progressbar" aria-label="Progression vers la livraison offerte" aria-valuenow="{{ intdiv($cartSummary->subtotal * 100, $cartSummary->freeShippingThreshold) }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: {{ intdiv($cartSummary->subtotal * 100, $cartSummary->freeShippingThreshold) }}%"></div>
                        </div>
                    </div>
                @endif
                <div class="rbt-minicart-bottom mt--24">
                    <div class="checkout-btn mt--20">
                        <a class="rbt-btn w-100 text-center" href="{{ route('cart.show') }}">
                            <span class="btn-text">Voir le panier et commander</span>
                        </a>
                    </div>
                </div>
            </div>
        @endunless
    </div>
</div>
