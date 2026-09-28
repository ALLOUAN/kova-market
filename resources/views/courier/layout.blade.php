<!doctype html>
{{-- Courier area (F-125): light pages for entry-level Android phones, used with one hand: big touch targets,
     main actions at the bottom of the screen, no theme scripts. Installable (manifest + service worker). --}}
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#1d3fbf">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Livraisons') · {{ config('storefront.name') }}</title>
    <link rel="manifest" href="{{ route('courier.manifest') }}">
    <link rel="icon" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/courier/icon-192.png') }}">
    <style>
        :root { --primary: #1d3fbf; --ink: #1f2937; --muted: #6b7280; --line: #e5e7eb; --bg: #f3f4f6; --ok: #15803d; --danger: #b91c1c; --warn: #b45309; }
        * { box-sizing: border-box; }
        body { margin: 0; font: 16px/1.45 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: var(--ink); background: var(--bg); }
        a { color: var(--primary); }
        header { position: sticky; top: 0; z-index: 10; display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 12px 16px; background: var(--primary); color: #fff; }
        header a, header button { color: #fff; }
        header h1 { margin: 0; font-size: 18px; }
        main { max-width: 640px; margin: 0 auto; padding: 16px 16px 120px; }
        h2 { font-size: 16px; margin: 24px 0 8px; }
        .card { display: block; background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; color: inherit; text-decoration: none; }
        .row { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; }
        .muted { color: var(--muted); font-size: 14px; }
        .big { font-size: 20px; font-weight: 700; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 13px; font-weight: 600; background: #e0e7ff; color: var(--primary); }
        .badge.ok { background: #dcfce7; color: var(--ok); } .badge.warn { background: #fef3c7; color: var(--warn); }
        .btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; min-height: 52px; padding: 12px 16px; border: 0; border-radius: 12px; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; background: var(--primary); color: #fff; }
        .btn.secondary { background: #fff; color: var(--ink); border: 1px solid var(--line); }
        .btn.ok { background: var(--ok); } .btn.danger { background: #fff; color: var(--danger); border: 1px solid #fecaca; } .btn.whatsapp { background: #25d366; }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .grid .btn { min-height: 64px; flex-direction: column; gap: 2px; font-size: 14px; }
        .actions { position: fixed; left: 0; right: 0; bottom: 0; padding: 12px 16px calc(12px + env(safe-area-inset-bottom)); background: #fff; border-top: 1px solid var(--line); }
        .actions > * + * { margin-top: 8px; }
        .actions .inner { max-width: 608px; margin: 0 auto; }
        label { display: block; font-weight: 600; margin: 12px 0 6px; }
        input, textarea, select { width: 100%; min-height: 48px; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 10px; font: inherit; background: #fff; }
        .alert { padding: 12px 14px; border-radius: 10px; margin-bottom: 12px; font-weight: 600; }
        .alert.ok { background: #dcfce7; color: var(--ok); } .alert.error { background: #fee2e2; color: var(--danger); }
        .error { color: var(--danger); font-size: 14px; margin-top: 4px; }
        details summary { cursor: pointer; font-weight: 600; min-height: 48px; display: flex; align-items: center; }
        ul.plain { list-style: none; margin: 0; padding: 0; } ul.plain li { padding: 6px 0; border-bottom: 1px solid var(--line); }
    </style>
</head>
<body>
    <header>
        <h1>@yield('heading', 'Mes livraisons')</h1>
        @auth
            <form method="POST" action="{{ route('courier.logout') }}">
                @csrf
                <button type="submit" style="background: none; border: 0; font: inherit; padding: 8px 0;">Déconnexion</button>
            </form>
        @endauth
    </header>

    <main>
        @foreach (['courier_status' => 'ok', 'courier_error' => 'error'] as $key => $type)
            @if (session($key))
                <div class="alert {{ $type }}" role="{{ $type === 'error' ? 'alert' : 'status' }}">{{ session($key) }}</div>
            @endif
        @endforeach

        @yield('content')
    </main>

    @yield('actions')

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register(@json(route('courier.service-worker', absolute: false)), { scope: '/livreur/' }).catch(() => {});
        }
    </script>
</body>
</html>
