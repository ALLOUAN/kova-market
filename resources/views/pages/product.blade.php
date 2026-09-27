@extends('layouts.storefront')

@php
    use App\Support\Money;

    $default = $product->variants->first();
    $images = collect([$product->image, $product->hover_image])
        ->merge(collect($product->colors ?? [])->pluck('image'))
        ->filter()->unique()->values();
    $description = $product->meta_description ?: Str::limit(trim(strip_tags(Str::sanitizeHtml((string) $product->description))), 160, '…') ?: config('storefront.description');
    $trail = ['Boutique' => route('shop.index'), ...collect($breadcrumb)->mapWithKeys(fn ($ancestor) => [$ancestor->name => $ancestor->url()])->all()];
    $shareText = rawurlencode($product->name.' — '.Money::format($product->price).' : '.$product->url());
@endphp

@section('title', $product->meta_title ?: $product->name)
@section('description', $description)

@push('meta')
    {{-- Link previews on WhatsApp, Facebook… (F-037, F-153) --}}
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $product->name }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image" content="{{ asset($product->image) }}">
    <meta property="og:url" content="{{ $product->url() }}">
    <meta property="product:price:amount" content="{{ $product->price }}">
    <meta property="product:price:currency" content="{{ config('storefront.currency') }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="canonical" href="{{ $product->url() }}">
@endpush

@section('content')
    <x-page-header :title="$product->name" :trail="$trail" />

    <div class="rbt-section-gap2">
        <div class="container">
            <div class="row g-5">
                {{-- Gallery: every picture opens full size with zoom (F-031). --}}
                <div class="col-lg-6">
                    <a href="{{ asset($images->first()) }}" data-fancybox="product-gallery" class="d-block rbt-bg-color-gray-light rbt-radius text-center p-4">
                        <img class="img-fluid" src="{{ asset($images->first()) }}" alt="{{ $product->name }}" id="product-main-image">
                    </a>
                    @if ($images->count() > 1)
                        <ul class="d-flex flex-wrap gap-2 list-unstyled mt--16" aria-label="Autres photos">
                            @foreach ($images->slice(1) as $image)
                                <li>
                                    <a href="{{ asset($image) }}" data-fancybox="product-gallery" class="d-block rbt-bg-color-gray-light rbt-radius p-2" style="width: 88px">
                                        <img class="img-fluid" src="{{ asset($image) }}" alt="{{ $product->name }}, photo {{ $loop->iteration + 1 }}" loading="lazy">
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="col-lg-6">
                    @if ($product->brand)
                        <a class="b3 rbt-text-color-primary" href="{{ $product->brand->url() }}">{{ $product->brand->name }}</a>
                    @endif
                    <h2 class="h3 mt--8 mb--16">{{ $product->name }}</h2>

                    @if ($product->reviews_count > 0)
                        <div class="rbt-review d-flex align-items-center gap-2 mb--16">
                            <x-product.rating :rating="$product->rating" :count="$product->reviews_count" />
                        </div>
                    @endif

                    {{-- Price, stock and SKU of the selected variant (updated by the script below). --}}
                    <div class="pricing-part mb--16" data-product-price>
                        <del class="price-text" data-compare @if (! $default?->compare_at_price) hidden @endif>{{ $default?->compare_at_price ? Money::format($default->compare_at_price) : '' }}</del>
                        <span class="price-text h4" data-price>{{ Money::format($default?->price ?? $product->price) }}</span>
                    </div>
                    <p class="b3 mb--8" data-stock></p>
                    <p class="b4 mb--24">Réf. : <span data-sku>{{ $default?->sku }}</span></p>

                    @if ($options->isNotEmpty())
                        @foreach ($options as $attribute => $values)
                            <fieldset class="mb--16">
                                <legend class="b2 rbt-text-bold mb--8">{{ $attribute }}</legend>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($values as $value)
                                        <input type="radio" class="btn-check" name="attribute-{{ $value->attribute_id }}" id="value-{{ $value->id }}" value="{{ $value->id }}" data-variant-option @checked($default?->attributeValues->contains($value))>
                                        <label class="rbt-btn rbt-btn-border rbt-btn-sm" for="value-{{ $value->id }}">
                                            @if ($value->color_hex)
                                                <span class="d-inline-block rounded-circle border align-middle mr--4" style="width: 14px; height: 14px; background: {{ $value->color_hex }}"></span>
                                            @endif
                                            {{ $value->value }}
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    @endif

                    <div class="d-flex flex-wrap align-items-center gap-3 mt--24">
                        <input type="hidden" name="variant_id" value="{{ $default?->id }}" data-variant-id>
                        <label class="visually-hidden" for="product-quantity">Quantité</label>
                        <input id="product-quantity" class="rbt-input-field text-center" type="number" name="quantity" value="1" min="1" max="{{ max(1, $default?->stock ?? 1) }}" style="width: 90px" data-quantity>
                        {{-- The cart arrives with the next module (F-040): the buttons are shown but not active yet. --}}
                        <button type="button" class="rbt-btn" disabled data-buy>Ajouter au panier</button>
                        <button type="button" class="rbt-btn rbt-btn-border" disabled data-buy>Acheter maintenant</button>
                    </div>
                    <p class="b4 mt--8 rbt-text-color-secondary">La commande en ligne ouvre très bientôt.</p>

                    <div class="mt--24">
                        <x-product.perks :product="$product" />
                    </div>

                    {{-- Sharing (F-037) --}}
                    <div class="d-flex align-items-center gap-3 mt--24">
                        <span class="b3">Partager :</span>
                        <a href="https://wa.me/?text={{ $shareText }}" target="_blank" rel="noopener" aria-label="Partager sur WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($product->url()) }}" target="_blank" rel="noopener" aria-label="Partager sur Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                        <button type="button" class="border-0 bg-transparent p-0" data-copy-link="{{ $product->url() }}" aria-label="Copier le lien"><i class="fa-regular fa-link"></i></button>
                    </div>
                </div>
            </div>

            @if (filled($product->description) || $product->specifications)
                <div class="row mt--60">
                    <div class="col-lg-10">
                        @if (filled($product->description))
                            <h2 class="h5 mb--16">Description</h2>
                            <div class="mb--40">{!! str($product->description)->sanitizeHtml() !!}</div>
                        @endif
                        @if ($product->specifications)
                            <h2 class="h5 mb--16">Caractéristiques</h2>
                            <table class="table">
                                <tbody>
                                    @foreach ($product->specifications as $specification)
                                        <tr>
                                            <th scope="row" class="w-25">{{ $specification['label'] }}</th>
                                            <td>{!! nl2br(e($specification['value'])) !!}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            @endif

            @if ($recommended->isNotEmpty())
                <div class="mt--60">
                    <h2 class="h4 mb--24">Vous aimerez aussi</h2>
                    <div class="row row--12 mt_dec--24">
                        @foreach ($recommended as $item)
                            <div class="col-xl-3 col-md-4 col-6 mt--24">
                                <x-product.card :product="$item" :order="$loop->iteration % 4 + 1" shadow />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const variants = @json($variants);
            const money = (amount) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(amount).replace(/\s/g, ' ') + ' FCFA';
            const $ = (selector) => document.querySelector(selector);

            // The selected variant is the one carrying exactly the chosen attribute values.
            const selected = () => {
                const chosen = [...document.querySelectorAll('[data-variant-option]:checked')].map((input) => Number(input.value));
                return variants.find((variant) => variant.values.length === chosen.length && chosen.every((id) => variant.values.includes(id)));
            };

            const render = () => {
                const variant = selected();
                const buy = document.querySelectorAll('[data-buy]');

                if (!variant) {
                    $('[data-stock]').textContent = 'Cette combinaison n’est pas disponible.';
                    return;
                }

                $('[data-price]').textContent = money(variant.price);
                $('[data-compare]').hidden = !variant.compare_at_price;
                $('[data-compare]').textContent = variant.compare_at_price ? money(variant.compare_at_price) : '';
                $('[data-sku]').textContent = variant.sku;
                $('[data-variant-id]').value = variant.id;
                $('[data-quantity]').max = Math.max(1, variant.stock);
                $('[data-stock]').textContent = variant.stock === 0
                    ? 'Épuisé'
                    : (variant.stock <= {{ config('storefront.product_card.limited_stock_threshold') }} ? `Plus que ${variant.stock} en stock` : 'En stock');
            };

            document.querySelectorAll('[data-variant-option]').forEach((input) => input.addEventListener('change', render));
            document.querySelector('[data-copy-link]')?.addEventListener('click', (event) => {
                navigator.clipboard?.writeText(event.currentTarget.dataset.copyLink);
                event.currentTarget.setAttribute('aria-label', 'Lien copié');
            });
            render();
        })();
    </script>
@endpush
