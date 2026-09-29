<!doctype html>
{{-- Courier area (F-125): light pages for entry-level Android phones, used with one hand: big touch targets,
     main actions at the bottom of the screen, no theme scripts. Installable (manifest + service worker). --}}
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#021732">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Livraisons') · {{ config('storefront.name') }}</title>
    <link rel="manifest" href="{{ route('courier.manifest') }}">
    <link rel="icon" href="{{ asset(config('storefront.favicon')) }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/courier/icon-192.png') }}">
    <style>
        /* KOVA MARKET charter: night blue (identity), green (confirming actions), gold (accent). Plain colours only. */
        :root { --primary: #021732; --action: #0e7d42; --accent: #c69e3e; --ink: #0f1d33; --muted: #667085; --line: #e3e8ef; --bg: #f4f6fa; --ok: #0e7d42; --danger: #c0362c; --warn: #a86b12; }
        * { box-sizing: border-box; }
        body { margin: 0; font: 16px/1.45 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: var(--ink); background: var(--bg); }
        a { color: var(--primary); }
        header { position: sticky; top: 0; z-index: 10; display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 12px 16px; background: var(--primary); color: #fff; border-bottom: 3px solid var(--accent); }
        header a, header button { color: #fff; }
        header h1 { margin: 0; font-size: 18px; }
        main { max-width: 640px; margin: 0 auto; padding: 16px 16px 120px; }
        h2 { font-size: 16px; margin: 24px 0 8px; }
        .card { display: block; background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; color: inherit; text-decoration: none; }
        .row { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; }
        .muted { color: var(--muted); font-size: 14px; }
        .big { font-size: 20px; font-weight: 700; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 13px; font-weight: 600; background: #e8edf5; color: var(--primary); }
        .badge.ok { background: #e5f3eb; color: var(--ok); } .badge.warn { background: #fdf3e2; color: var(--warn); }
        .btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; min-height: 52px; padding: 12px 16px; border: 0; border-radius: 12px; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; background: var(--primary); color: #fff; }
        .btn:focus-visible { outline: 3px solid rgb(14 125 66 / 0.35); outline-offset: 2px; }
        .btn:disabled { background: #d5dbe4; color: #667085; cursor: not-allowed; }
        .btn.secondary { background: #fff; color: var(--primary); border: 1.5px solid var(--primary); }
        .btn.ok { background: var(--action); } .btn.danger { background: #fff; color: var(--danger); border: 1.5px solid #f5c2bd; } .btn.whatsapp { background: #25d366; }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .grid .btn { min-height: 64px; flex-direction: column; gap: 2px; font-size: 14px; }
        .actions { position: fixed; left: 0; right: 0; bottom: 0; padding: 12px 16px calc(12px + env(safe-area-inset-bottom)); background: #fff; border-top: 1px solid var(--line); }
        .actions > * + * { margin-top: 8px; }
        .actions .inner { max-width: 608px; margin: 0 auto; }
        label { display: block; font-weight: 600; margin: 12px 0 6px; }
        input, textarea, select { width: 100%; min-height: 48px; padding: 10px 12px; border: 1px solid #cfd6e0; border-radius: 8px; font: inherit; background: #fff; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: var(--action); box-shadow: 0 0 0 3px rgb(14 125 66 / 0.2); }
        .alert { padding: 12px 14px; border-radius: 10px; margin-bottom: 12px; font-weight: 600; }
        .alert.ok { background: #e5f3eb; color: #0a502c; border-left: 4px solid var(--ok); } .alert.error { background: #fdeceb; color: #8a241c; border-left: 4px solid var(--danger); }
        .error { color: var(--danger); font-size: 14px; margin-top: 4px; }
        details summary { cursor: pointer; font-weight: 600; min-height: 48px; display: flex; align-items: center; }
        ul.plain { list-style: none; margin: 0; padding: 0; } ul.plain li { padding: 6px 0; border-bottom: 1px solid var(--line); }

        /* Sign-in screen: no bar, the brand, one card, help at hand. */
        body.auth { background: #fff; }
        .auth-top { height: 6px; background: var(--primary); border-bottom: 3px solid var(--accent); }
        .auth-wrap { max-width: 420px; margin: 0 auto; padding: 32px 20px calc(32px + env(safe-area-inset-bottom)); }
        .auth-brand { text-align: center; margin-bottom: 24px; }
        .auth-brand img { height: 64px; width: auto; }
        .auth-brand .eyebrow { display: inline-block; margin-top: 14px; padding: 4px 10px; border-radius: 6px; background: #e8edf5; color: var(--primary); font-size: 13px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
        .auth-brand h1 { margin: 12px 0 6px; font-size: 24px; color: var(--primary); }
        .auth-brand p { margin: 0; color: var(--muted); }
        .auth-card { border: 1px solid var(--line); border-radius: 12px; padding: 20px; box-shadow: 0 1px 2px rgb(2 23 50 / 0.06); }
        .auth-card label:first-of-type { margin-top: 0; }
        .field { position: relative; }
        .field .prefix { position: absolute; left: 0; top: 0; bottom: 0; display: flex; align-items: center; padding: 0 12px; color: var(--muted); font-weight: 600; border-right: 1px solid var(--line); pointer-events: none; }
        .field input.with-prefix { padding-left: 76px; }
        .field input.with-toggle { padding-right: 96px; }
        .field .toggle { position: absolute; right: 6px; top: 6px; bottom: 6px; min-width: 84px; border: 0; border-radius: 6px; background: #f2f5fa; color: var(--primary); font: inherit; font-size: 14px; font-weight: 600; cursor: pointer; }
        .field .toggle:focus-visible { outline: 3px solid rgb(14 125 66 / 0.35); outline-offset: 1px; }
        input[aria-invalid="true"] { border-color: var(--danger); }
        .hint { color: var(--muted); font-size: 13px; margin-top: 6px; }
        .auth-card .btn { margin-top: 20px; background: var(--action); }
        .btn[aria-busy="true"] { opacity: 0.75; cursor: progress; }
        .help { margin-top: 20px; padding: 16px; border-radius: 12px; background: #f4f6fa; }
        .help strong { display: block; color: var(--primary); margin-bottom: 4px; }
        .help p { margin: 0 0 12px; color: var(--muted); font-size: 14px; }
        .help .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .help .btn { min-height: 48px; font-size: 15px; }
        .auth-foot { margin-top: 24px; text-align: center; font-size: 14px; color: var(--muted); }
    </style>
</head>
<body @hasSection('bare') class="auth" @endif>
    @hasSection('bare')
        @yield('bare')
    @else
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
    @endif

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register(@json(route('courier.service-worker', absolute: false)), { scope: '/livreur/' }).catch(() => {});
        }
    </script>
</body>
</html>
