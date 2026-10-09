<!DOCTYPE html>
{{-- Error and maintenance pages other than 404 (403, 419, 429, 500, 503...): KOVA MARKET charter, and nothing that
     needs the database or the theme, so they show even when the site is down. --}}
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · {{ config('storefront.name', 'KOVA MARKET') }}</title>
    <link rel="icon" href="{{ asset(config('storefront.favicon', 'assets/images/logo/kova-favicon.png')) }}">
    <style>
        :root { --navy: #021732; --orange: #cc4400; --gold: #c69e3e; --text: #3f4a5a; --line: #e3e8ef; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; font: 16px/1.55 "Segoe UI", Roboto, Arial, sans-serif; color: var(--text); background: #fff; }
        header { padding: 18px 24px; border-bottom: 3px solid var(--gold); }
        header img { height: 44px; display: block; }
        main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 48px 20px; text-align: center; }
        .code { font-size: 56px; font-weight: 700; color: var(--gold); line-height: 1; margin: 0 0 12px; }
        h1 { font-size: 24px; color: var(--navy); margin: 0 0 12px; }
        p { max-width: 460px; margin: 0 auto 28px; }
        a.btn { display: inline-block; padding: 12px 24px; border-radius: 8px; background: var(--orange); color: #fff; font-weight: 600; text-decoration: none; }
        a.btn:hover { background: #b83a00; }
        a.btn:focus-visible { outline: 3px solid rgb(204 68 0 / 0.35); outline-offset: 2px; }
        footer { padding: 16px; text-align: center; font-size: 13px; color: #667085; border-top: 1px solid var(--line); }
    </style>
</head>
<body>
    <header>
        <a href="{{ url('/') }}"><img src="{{ asset('assets/images/logo/kova-logo-400.webp') }}" alt="{{ config('storefront.name', 'KOVA MARKET') }}"></a>
    </header>
    <main>
        <div>
            <p class="code">@yield('code')</p>
            <h1>@yield('message')</h1>
            <p>
                @switch(trim($__env->yieldContent('code')))
                    @case('503') La boutique est en cours de mise à jour. Elle revient dans quelques minutes. @break
                    @case('419') La page est restée ouverte trop longtemps. Rechargez-la puis recommencez. @break
                    @case('429') Trop de demandes en peu de temps. Patientez un instant puis réessayez. @break
                    @case('403') Vous n’avez pas accès à cette page. @break
                    @default Un incident nous empêche d’afficher cette page. Réessayez dans un instant.
                @endswitch
            </p>
            <a class="btn" href="{{ url('/') }}">Retour à la boutique</a>
        </div>
    </main>
    <footer>{{ config('storefront.name', 'KOVA MARKET') }}</footer>
</body>
</html>
