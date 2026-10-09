{{-- Top of the courier screens' header band: initials, title, subtitle, sign-out.
     @include('courier.partials.hero-top', ['courier' => $courier, 'title' => '…', 'subtitle' => '…']) --}}
@php($initials = collect(preg_split('/\s+/', trim($courier->name())))->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join(''))
<div class="ch-hero__top">
    <span class="ch-avatar" aria-hidden="true">{{ $initials }}</span>
    <div class="ch-hero__who">
        <h1>{{ $title }}</h1>
        <p>{{ $subtitle }}</p>
    </div>
    <form method="POST" action="{{ route('courier.logout') }}">
        @csrf
        <button type="submit" class="ch-hero__logout" aria-label="Déconnexion" title="Déconnexion">
            @include('courier.partials.icon', ['name' => 'logout'])
        </button>
    </form>
</div>
