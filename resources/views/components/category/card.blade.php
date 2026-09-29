@props(['category', 'order' => 1])

{{-- Department card: name, three sub-category shortcuts and a transparent product image. --}}
<div class="rbt-cat-box rbt-cat-box-7 rbt-scroll-trigger fade_in animation-order-{{ $order }}">
    <div class="inner">
        <div class="content">
            <h2 class="title h5"><a href="{{ $category->url() }}">{{ $category->name }}</a></h2>
            <ul class="quick-link-list rbt-link-hover">
                @foreach ($category->children->take(3) as $child)
                    <li><a href="{{ $child->url() }}" class="quick-link">{{ $child->name }}</a></li>
                @endforeach
            </ul>
        </div>
        <div class="rbt-image-portion">
            <a href="{{ $category->url() }}">
                <img class="rbt-scroll-trigger" src="{{ asset($category->image) }}" alt="{{ $category->name }}" loading="lazy" decoding="async">
            </a>
            <a href="{{ $category->url() }}" class="rbt-icon-overlay-link-btn" aria-label="{{ $category->name }}">
                <span class="rbt-btn-overlay">
                    <i class="rbt-icon fa-solid fa-arrow-up-right"></i>
                    <i class="rbt-icon-bottom fa-solid fa-arrow-up-right"></i>
                </span>
            </a>
        </div>
    </div>
</div>
