@props(['product'])

{{-- What a pack holds (F-093), each component linked to its own page. --}}
@if ($product->is_bundle && $product->bundleItems->isNotEmpty())
    <div class="rbt-bg-color-gray-light rbt-radius p-3 mt--24">
        <p class="b2 rbt-text-bold mb--8"><i class="fa-regular fa-box-open mr--4"></i> Ce pack contient</p>
        <ul class="list-unstyled mb-0">
            @foreach ($product->bundleItems as $item)
                <li class="b3 mb--4">
                    {{ $item->quantity }} ×
                    <a href="{{ $item->variant->product->url() }}">{{ $item->variant->product->name }}</a>
                    @if ($item->variant->attributeValues->isNotEmpty())
                        <span class="b4">({{ $item->variant->label() }})</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
