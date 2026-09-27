@props(['title'])

{{-- Title band with breadcrumb, shared by the simple content pages (legal pages, FAQ). --}}
<div class="rbt-breadcrumb-default rbt-bg-color-gray-light ptb--40">
    <div class="container">
        <div class="rbt-breadcrumb-inner text-center">
            <h1 class="rbt-breadcrumb-title h3 mb--8">{{ $title }}</h1>
            <ul class="rbt-breadcrumb-page-list d-flex justify-content-center gap-2 list-unstyled mb-0">
                <li class="rbt-breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
                <li class="rbt-breadcrumb-item">/</li>
                <li class="rbt-breadcrumb-item active">{{ $title }}</li>
            </ul>
        </div>
    </div>
</div>
