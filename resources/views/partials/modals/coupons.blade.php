{{-- "Codes promo" window (EX-16, F-091): public codes currently usable ($coupons); "Appliquer" puts one on the cart. --}}

<x-modal id="couponCollectionModal" dialog-class="rbt-coupon-modal-dialog modal-dialog-centered" labelled>
    <div class="rbt-top-folder-shape-wrapper">
        <div class="rbt-coupon-wrapper rbt-gap--24 rbt-bg-color-white rbt-content-trs-portion">
            <h4 class="mb--0 rbt-text-bold" id="couponCollectionModalLabel">Codes promo disponibles</h4>
            @foreach ($coupons as $coupon)
                <div class="rbt-coupon">
                    <div class="inner">
                        <div class="left-part">
                            <input type="text" value="{{ $coupon->code }}" readonly class="rbt-coupon-code-text rbt-has-right-shepe-border" aria-label="Code promo">
                        </div>
                        <div class="coupon-details">
                            <h2 class="rbt-coupon-info-title b1">{{ Str::upper($coupon->benefitLabel()) }}</h2>
                            @if ($coupon->description)
                                <p class="rbt-coupon-info-sub-title b3 mt--4">{{ $coupon->description }}</p>
                            @endif
                            <ul class="rbt-coupon-info-list mt--12">
                                @if ($coupon->ends_at)
                                    <li><span>Valable jusqu’au {{ $coupon->ends_at->format('d/m/Y à H\hi') }}</span></li>
                                @endif
                                @if ($coupon->minimum_subtotal)
                                    <li><span>Dès <strong>@money($coupon->minimum_subtotal)</strong> d’achat</span></li>
                                @endif
                                @if ($coupon->target !== \App\Enums\CouponTarget::All)
                                    <li><span>Sur une sélection d’articles</span></li>
                                @endif
                            </ul>
                        </div>
                        <form method="POST" action="{{ route('cart.coupon.store') }}">
                            @csrf
                            <input type="hidden" name="code" value="{{ $coupon->code }}">
                            <button type="submit" class="rbt-btn rbt-btn-sm">Appliquer</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-modal>
