@props(['title'])

{{-- Customer area frame: menu on the left, page on the right, flash messages on top. --}}
@php
    $links = [
        'account.show' => ['Mon compte', 'fa-user'],
        'account.orders' => ['Mes commandes', 'fa-bag-shopping'],
        'account.addresses.index' => ['Mes adresses', 'fa-location-dot'],
    ];
@endphp

<x-page-header :title="$title" :trail="request()->routeIs('account.show') ? [] : ['Mon compte' => route('account.show')]" />

<div class="rbt-section-gap2">
    <div class="container">
        <div class="row g-5">
            <aside class="col-lg-3">
                <nav class="rbt-bg-color-gray-light rbt-radius p-3" aria-label="Espace client">
                    <ul class="list-unstyled mb-0">
                        @foreach ($links as $route => [$label, $icon])
                            <li>
                                <a @class(['d-block py-2 px-2 rbt-radius', 'rbt-text-bold rbt-bg-color-white' => request()->routeIs($route.'*')]) href="{{ route($route) }}">
                                    <i class="fa-regular {{ $icon }} mr--8"></i>{{ $label }}
                                </a>
                            </li>
                        @endforeach
                        <li>
                            <a class="d-block py-2 px-2" href="{{ route('tracking.show') }}"><i class="fa-regular fa-truck mr--8"></i>Suivre une commande</a>
                        </li>
                        <li class="border-top mt-2 pt-2">
                            <a class="d-block py-2 px-2" href="#" data-logout><i class="fa-regular fa-right-from-bracket mr--8"></i>Se déconnecter</a>
                        </li>
                    </ul>
                </nav>
            </aside>
            <div class="col-lg-9">
                @foreach (['account_status' => 'success', 'account_error' => 'danger'] as $key => $type)
                    @if (session($key))
                        <div class="alert alert-{{ $type }} mb--24" role="{{ $type === 'danger' ? 'alert' : 'status' }}">{{ session($key) }}</div>
                    @endif
                @endforeach
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
