@extends('layouts.storefront')

@inject('structuredData', 'App\Services\Storefront\StructuredData')

@php
    use App\Support\Money;

    $images = collect([$product->image, $product->hover_image])
        ->merge(collect($product->colors ?? [])->pluck('image'))
        ->filter()->unique()->values();
    $description = $product->meta_description ?: Str::limit(trim(strip_tags(Str::sanitizeHtml((string) $product->description))), 160, '…') ?: config('storefront.description');
    $trail = ['Boutique' => route('shop.index'), ...collect($breadcrumb)->mapWithKeys(fn ($ancestor) => [$ancestor->name => $ancestor->url()])->all()];
    $shareText = rawurlencode($product->name.' — '.Money::format($product->price).' : '.$product->url());
@endphp

@section('title', $product->meta_title ?: $product->name)
@section('whatsapp_message', 'Bonjour, je suis intéressé(e) par « '.$product->name.' » ('.Money::format($product->price).') : '.$product->url())
@section('description', $description)
{{-- Link previews on WhatsApp, Facebook… (F-037, F-153) --}}
@section('canonical', $product->url())
@section('og_type', 'product')
@section('og_image', asset($product->image))
@section('twitter_card', 'summary_large_image')

@push('meta')
    <meta property="product:price:amount" content="{{ $product->price }}">
    <meta property="product:price:currency" content="{{ config('storefront.currency') }}">
    <x-json-ld :data="$structuredData->product($product, $trail)" />
@endpush

@section('content')
    <x-page-header :title="$product->name" :trail="$trail" />

    <div class="rbt-section-gap2">
        <div class="container">
            <div class="row g-5">
                {{-- Gallery: every picture opens full size with zoom (F-031). --}}
                <div class="col-lg-6">
                    <a href="{{ asset($images->first()) }}" data-fancybox="product-gallery" class="d-block rbt-bg-color-gray-light rbt-radius text-center p-4">
                        {{-- The main picture is the page's largest element (LCP): loaded first, never lazily. --}}
                        <img class="img-fluid" src="{{ asset($images->first()) }}" @if ($srcset = \App\Services\Storefront\ImageOptimizer::srcset($images->first())) srcset="{{ $srcset }}" sizes="(max-width: 991px) 100vw, 50vw" @endif alt="{{ $product->name }}" id="product-main-image" fetchpriority="high">
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

                    <x-product.purchase :product="$product" :variants="$variants" :options="$options" />
                    <x-product.bundle-contents :product="$product" />

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
        document.querySelector('[data-copy-link]')?.addEventListener('click', (event) => {
            navigator.clipboard?.writeText(event.currentTarget.dataset.copyLink);
            event.currentTarget.setAttribute('aria-label', 'Lien copié');
        });
    </script>
@endpush
