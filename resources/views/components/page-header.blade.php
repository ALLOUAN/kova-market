@props(['title', 'trail' => []])

{{-- Title band with breadcrumb, shared by content and catalog pages. "trail" lists the intermediate links as [label => url]. --}}
<div class="rbt-breadcrumb-default rbt-bg-color-gray-light ptb--40">
    <div class="container">
        <div class="rbt-breadcrumb-inner text-center">
            <h1 class="rbt-breadcrumb-title h3 mb--8">{{ $title }}</h1>
            <nav aria-label="Fil d’Ariane">
                <ul class="rbt-breadcrumb-page-list d-flex flex-wrap justify-content-center gap-2 list-unstyled mb-0">
                    <li class="rbt-breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
                    @foreach ($trail as $label => $url)
                        <li class="rbt-breadcrumb-item" aria-hidden="true">/</li>
                        <li class="rbt-breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
                    @endforeach
                    <li class="rbt-breadcrumb-item" aria-hidden="true">/</li>
                    <li class="rbt-breadcrumb-item active" aria-current="page">{{ $title }}</li>
                </ul>
            </nav>
        </div>
    </div>
</div>
