{{-- Comparison bar, fixed at the bottom while products are chosen: their thumbnails, "Comparer" and "Vider".
     Re-rendered by CompareController for public/assets/js/compare.js. --}}
<div class="kova-compare-bar" data-compare-bar @if ($compared->isEmpty()) hidden @endif>
    @if ($compared->isNotEmpty())
        <div class="container">
            <div class="kova-compare-bar-inner">
                <p class="kova-compare-bar-title mb-0"><strong>Comparateur</strong> <span>{{ $compared->count() }} / {{ \App\Services\Storefront\Comparison::MAX }}</span></p>
                <ul class="kova-compare-bar-items" aria-label="Produits à comparer">
                    @foreach ($compared as $product)
                        <li>
                            <img src="{{ asset($product->image) }}" alt="" width="44" height="44" loading="lazy">
                            <span>{{ $product->name }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="kova-compare-bar-actions">
                    @if ($compared->count() > 1)
                        <a class="rbt-btn rbt-btn-sm" href="{{ route('compare.index') }}">Comparer</a>
                    @else
                        <span class="b4">Ajoutez un autre produit pour comparer</span>
                    @endif
                    <form method="POST" action="{{ route('compare.clear') }}" class="m-0" data-compare-clear>
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rbt-btn rbt-btn-border rbt-btn-sm">Vider</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
