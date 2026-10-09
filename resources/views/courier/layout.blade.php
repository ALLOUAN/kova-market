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
        /* KOVA MARKET charter: night blue (identity), orange of the logo (actions), gold (accent), green (success only). Plain colours only. */
        :root { --primary: #021732; --action: #cc4400; --accent: #c69e3e; --ink: #0f1d33; --muted: #667085; --line: #e3e8ef; --bg: #f4f6fa; --ok: #0e7d42; --danger: #c0362c; --warn: #a86b12; }
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
        .btn:focus-visible { outline: 3px solid rgb(204 68 0 / 0.35); outline-offset: 2px; }
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
        input:focus, textarea:focus, select:focus { outline: none; border-color: var(--action); box-shadow: 0 0 0 3px rgb(204 68 0 / 0.2); }
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
        .field .toggle:focus-visible { outline: 3px solid rgb(204 68 0 / 0.35); outline-offset: 1px; }
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


        /* ------------------------------------------------------------ Home (courier.index) and tab bar
           Mobile first, thumb-sized targets (≥ 44 px). Night blue header, orange for what to do now, green for
           done, gold for money. Every state is also written. */
        .ico { width: 22px; height: 22px; flex: none; }
        .ico--sm { width: 16px; height: 16px; }
        .ico--xs { width: 13px; height: 13px; vertical-align: -2px; }
        .ico--lg { width: 40px; height: 40px; }

        .ch-hero { display: block; position: relative; padding: 14px 16px 16px; background: var(--primary); color: #fff; border-bottom: 3px solid var(--accent); border-radius: 0 0 22px 22px; box-shadow: 0 8px 24px rgb(2 23 50 / .18); }
        .ch-hero__top { max-width: 608px; margin: 0 auto; display: flex; align-items: center; gap: 12px; }
        .ch-avatar { width: 46px; height: 46px; flex: none; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: rgb(255 255 255 / .12); box-shadow: 0 0 0 2px var(--accent); font-weight: 700; font-size: 16px; }
        .ch-hero__who { flex: 1; min-width: 0; }
        .ch-hero__who h1 { margin: 0; font-size: 20px; line-height: 1.2; color: #fff; }
        .ch-hero__who p { margin: 2px 0 0; font-size: 13px; color: rgb(255 255 255 / .7); }
        .ch-hero__logout { width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 12px; background: rgb(255 255 255 / .1); color: #fff; cursor: pointer; }
        .ch-progress { max-width: 608px; margin: 14px auto 0; }
        .ch-progress__text { display: flex; justify-content: space-between; gap: 8px; font-size: 13px; color: rgb(255 255 255 / .75); }
        .ch-progress__text strong { color: #fff; }
        .ch-progress__bar { height: 8px; margin-top: 6px; border-radius: 999px; background: rgb(255 255 255 / .14); overflow: hidden; }
        .ch-progress__bar span { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #4cc38a, #0e7d42); transition: width .4s ease; }

        .ch-pill { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
        .ch-pill::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .ch-pill--live { background: #fff4ec; color: #a33400; }
        .ch-pill--ready { background: #e8edf5; color: var(--primary); }
        .ch-pill--wait { background: #f1f3f6; color: #4b5565; }

        .ch-next { margin: 4px 0 16px; padding: 18px; border-radius: 20px; background: #fff; box-shadow: 0 10px 30px rgb(2 23 50 / .1), inset 0 0 0 1px var(--line); border-top: 4px solid var(--action); }
        .ch-next--ready { border-top-color: var(--primary); }
        .ch-next__head { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .ch-next__number { font-size: 13px; color: var(--muted); font-weight: 600; }
        .ch-next__place { margin: 12px 0 2px; font-size: 24px; line-height: 1.2; color: var(--primary); }
        .ch-next__sub { display: flex; align-items: center; gap: 6px; margin: 0; color: var(--muted); font-size: 15px; }
        .ch-next__meta { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 6px 12px; margin: 14px 0; padding: 12px 14px; border-radius: 14px; background: var(--bg); font-size: 15px; }
        .ch-next__meta span { display: inline-flex; align-items: center; gap: 6px; }
        .ch-next__cash strong { color: var(--primary); font-size: 17px; }
        .ch-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 10px; }
        .ch-action { min-height: 64px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; border-radius: 14px; background: #fff4ec; color: #a33400; font-size: 13px; font-weight: 700; text-decoration: none; transition: transform .12s, background-color .12s; }
        .ch-action:active { transform: scale(.97); background: #ffe8da; }
        .ch-next__open { margin-top: 4px; }

        .ch-calm { display: flex; align-items: center; gap: 14px; margin: 4px 0 16px; padding: 18px; border-radius: 20px; background: #e5f3eb; color: #0a502c; }
        .ch-calm strong { display: block; font-size: 16px; }
        .ch-calm p { margin: 4px 0 0; font-size: 14px; color: #2c5a41; }

        .ch-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .ch-stat { display: grid; gap: 4px; padding: 14px; border-radius: 16px; background: #fff; box-shadow: inset 0 0 0 1px var(--line); color: inherit; text-decoration: none; }
        .ch-stat--link { box-shadow: inset 0 0 0 1px var(--line), inset 0 3px 0 var(--accent); }
        .ch-stat--link:active { background: #fbf6ea; }
        .ch-stat__icon { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; }
        .ch-stat__icon .ico { width: 20px; height: 20px; }
        .ch-stat__value { margin-top: 4px; font-size: 26px; font-weight: 800; line-height: 1.1; color: var(--primary); font-variant-numeric: tabular-nums; }
        .ch-stat__value--money { font-size: 18px; }
        .ch-stat__label { font-size: 13px; color: var(--muted); }
        .ch-tone-green { background: #e5f3eb; color: #0e7d42; }
        .ch-tone-navy { background: #e8edf5; color: var(--primary); }
        .ch-tone-orange { background: #fff4ec; color: #a33400; }
        .ch-tone-gold { background: #f8f0dc; color: #8a6a1f; }

        .ch-tabs { position: sticky; top: 8px; z-index: 5; display: none; gap: 4px; padding: 4px; margin-bottom: 12px; border-radius: 14px; background: #e8edf5; }
        .has-tabs .ch-tabs { display: flex; }
        .has-tabs .ch-panel__title { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }
        .ch-tab { flex: 1; min-height: 46px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 0; border-radius: 11px; background: transparent; color: var(--primary); font: inherit; font-size: 15px; font-weight: 700; cursor: pointer; }
        .ch-tab.is-active { background: #fff; box-shadow: 0 1px 4px rgb(2 23 50 / .12); }
        .ch-count { min-width: 22px; height: 22px; padding: 0 7px; display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; background: rgb(2 23 50 / .08); font-size: 12px; }
        .ch-count.is-hot { background: var(--action); color: #fff; }
        .ch-panel__title { font-size: 16px; margin: 20px 0 8px; }

        .ch-card { position: relative; display: flex; align-items: stretch; margin-bottom: 10px; border-radius: 16px; background: #fff; box-shadow: inset 0 0 0 1px var(--line), inset 4px 0 0 #cfd6e0; overflow: hidden; }
        .ch-card--live { box-shadow: inset 0 0 0 1px var(--line), inset 4px 0 0 var(--action); }
        .ch-card--ready { box-shadow: inset 0 0 0 1px var(--line), inset 4px 0 0 var(--primary); }
        .ch-card__link { position: absolute; inset: 0; z-index: 1; border-radius: 16px; }
        .ch-card__link:focus-visible { outline: 3px solid rgb(204 68 0 / .4); outline-offset: -3px; }
        .ch-card__body { flex: 1; min-width: 0; padding: 14px 12px 14px 18px; }
        .ch-card__row { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .ch-card__place { font-size: 17px; color: var(--primary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ch-card__sub { margin: 2px 0 8px; font-size: 14px; color: var(--muted); }
        .ch-card__foot { font-size: 13px; }
        .ch-card__meta { color: var(--muted); }
        .ch-card__cash { font-weight: 800; color: var(--primary); white-space: nowrap; }
        .ch-card__quick { position: relative; z-index: 2; display: flex; flex-direction: column; border-left: 1px solid var(--line); }
        .ch-card__quick a { flex: 1; width: 56px; min-height: 48px; display: flex; align-items: center; justify-content: center; color: #a33400; }
        .ch-card__quick a + a { border-top: 1px solid var(--line); }
        .ch-card__quick a:active { background: #fff4ec; }
        .ch-card__take { margin-top: 12px; }

        .ch-empty { display: flex; align-items: center; gap: 14px; padding: 18px; border-radius: 16px; background: #fff; box-shadow: inset 0 0 0 1px var(--line); color: var(--muted); }
        .ch-empty .ico { color: #98a2b3; }
        .ch-empty p { margin: 0; font-size: 14px; }
        .ch-empty strong { color: var(--ink); }
        .ch-updated { display: flex; align-items: center; justify-content: center; gap: 6px; margin: 20px 0 0; font-size: 13px; color: var(--muted); }

        .tabbar { position: fixed; left: 0; right: 0; bottom: 0; z-index: 20; display: flex; justify-content: center; gap: 4px; padding: 6px 12px calc(6px + env(safe-area-inset-bottom)); background: rgb(255 255 255 / .96); backdrop-filter: blur(8px); border-top: 1px solid var(--line); box-shadow: 0 -6px 20px rgb(2 23 50 / .08); }
        .tabbar__item { flex: 1; max-width: 200px; min-height: 52px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; border-radius: 12px; color: var(--muted); font-size: 12px; font-weight: 600; text-decoration: none; }
        .tabbar__item.is-active { color: var(--primary); background: #e8edf5; }
        .tabbar__item.is-active .ico { color: var(--action); }

        /* ------------------------------------------------------------ "Mes encaissements" (courier.money) */
        .cm-due { display: flex; gap: 14px; align-items: flex-start; margin: 4px 0 16px; padding: 18px; border-radius: 20px; background: #fff; box-shadow: 0 10px 30px rgb(2 23 50 / .1), inset 0 0 0 1px var(--line); border-top: 4px solid var(--action); }
        .cm-due.is-clear { border-top-color: var(--ok); }
        .cm-due__icon { flex: none; width: 46px; height: 46px; display: inline-flex; align-items: center; justify-content: center; border-radius: 14px; background: #fff4ec; color: #a33400; }
        .cm-due.is-clear .cm-due__icon { background: #e5f3eb; color: var(--ok); }
        .cm-due__label { margin: 0; font-size: 14px; color: var(--muted); }
        .cm-due__amount { margin: 2px 0 4px; font-size: 30px; font-weight: 800; line-height: 1.1; color: var(--primary); font-variant-numeric: tabular-nums; }
        .cm-due__hint { margin: 0; font-size: 14px; color: var(--muted); }
        .cm-due.is-clear .cm-due__hint { color: #0a502c; font-weight: 600; }

        .ch-seg { display: flex; gap: 4px; padding: 4px; margin: 0 0 12px; border-radius: 14px; background: #e8edf5; }
        .ch-seg__item { flex: 1; min-height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 11px; font-size: 14px; font-weight: 700; color: var(--primary); text-decoration: none; }
        .ch-seg__item.is-active { background: #fff; box-shadow: 0 1px 4px rgb(2 23 50 / .12); }

        .cm-note { display: flex; align-items: center; gap: 6px; margin: -8px 0 4px; font-size: 13px; color: var(--muted); }
        .cm-note strong { color: var(--primary); }
        .cm-title { margin: 24px 0 10px; font-size: 17px; color: var(--primary); }

        .cm-pay { display: flex; gap: 12px; margin-bottom: 10px; padding: 14px; border-radius: 16px; background: #fff; box-shadow: inset 0 0 0 1px var(--line); }
        .cm-pay__icon { flex: none; width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; border-radius: 12px; background: #e5f3eb; color: var(--ok); }
        .cm-pay.is-cancelled { opacity: .65; }
        .cm-pay.is-cancelled .cm-pay__icon { background: #f1f3f6; color: var(--muted); }
        .cm-pay.is-cancelled .cm-pay__amount { text-decoration: line-through; }
        .cm-pay__body { flex: 1; min-width: 0; }
        .cm-pay__amount { font-size: 18px; color: var(--primary); }
        .cm-pay__meta { margin: 4px 0 8px; font-size: 13px; color: var(--muted); }
        .cm-pay__receipt { display: inline-flex; align-items: center; gap: 6px; min-height: 40px; padding: 8px 14px; border-radius: 10px; background: #fff4ec; color: #a33400; font-size: 14px; font-weight: 700; text-decoration: none; }

        .cm-timeline { position: relative; margin: 0; padding: 0 0 0 4px; list-style: none; }
        .cm-timeline::before { content: ""; position: absolute; left: 19px; top: 8px; bottom: 8px; width: 2px; background: var(--line); }
        .cm-timeline__item { position: relative; display: flex; gap: 12px; padding: 8px 0; }
        .cm-timeline__dot { position: relative; z-index: 1; flex: none; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 18px; font-weight: 800; box-shadow: 0 0 0 4px var(--bg); }
        .cm-timeline__item.is-in .cm-timeline__dot { background: #fff4ec; color: #a33400; }
        .cm-timeline__item.is-out .cm-timeline__dot { background: #e5f3eb; color: var(--ok); }
        .cm-timeline__body { flex: 1; min-width: 0; padding: 10px 12px; border-radius: 14px; background: #fff; box-shadow: inset 0 0 0 1px var(--line); }
        .cm-timeline__label { font-size: 14px; min-width: 0; }
        .cm-timeline__amount { white-space: nowrap; font-size: 14px; }
        .cm-timeline__item.is-in .cm-timeline__amount { color: #a33400; }
        .cm-timeline__item.is-out .cm-timeline__amount { color: var(--ok); }
        .cm-timeline__meta { margin: 2px 0 0; font-size: 12px; color: var(--muted); }
        .cm-timeline__item.is-cancelled { opacity: .55; }
        .cm-timeline__item.is-cancelled .cm-timeline__amount, .cm-timeline__item.is-cancelled .cm-timeline__label { text-decoration: line-through; }
        .cm-legend { margin: 10px 0 0; font-size: 12px; color: var(--muted); }
        .cm-legend .is-in { color: #a33400; font-weight: 800; }
        .cm-legend .is-out { color: var(--ok); font-weight: 800; }

        /* ------------------------------------------------------------ "Mon compte" (courier.password) */
        .ca-profile { margin: 4px 0 16px; padding: 6px 16px; border-radius: 20px; background: #fff; box-shadow: 0 10px 30px rgb(2 23 50 / .08), inset 0 0 0 1px var(--line); }
        .ca-profile__row { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--line); }
        .ca-profile__row--zones { align-items: flex-start; }
        .ca-profile__label { display: inline-flex; align-items: center; gap: 8px; font-size: 14px; color: var(--muted); white-space: nowrap; }
        .ca-profile__row strong { text-align: right; color: var(--primary); }
        .ca-zones { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 6px; }
        .ca-profile__note { margin: 0; padding: 12px 0; font-size: 13px; color: var(--muted); }
        .ca-card { margin-bottom: 16px; padding: 18px; border-radius: 20px; background: #fff; box-shadow: inset 0 0 0 1px var(--line); }
        .ca-card__title { display: flex; align-items: center; gap: 8px; margin: 0 0 4px; font-size: 17px; color: var(--primary); }
        .ca-card .muted { margin: 0 0 12px; font-size: 14px; }
        .ca-rule { display: flex; align-items: center; gap: 6px; }
        .ca-rule.is-ok { color: var(--ok); font-weight: 600; }
        .ca-rule[hidden] { display: none; }
        .ca-help__actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 8px; }
    </style>
</head>
<body @hasSection('bare') class="auth" @endif>
    @hasSection('bare')
        @yield('bare')
    @else
    @hasSection('hero')
        @yield('hero')
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
    @endif

    <main>
        @foreach (['courier_status' => 'ok', 'courier_error' => 'error'] as $key => $type)
            @if (session($key))
                <div class="alert {{ $type }}" role="{{ $type === 'error' ? 'alert' : 'status' }}">{{ session($key) }}</div>
            @endif
        @endforeach

        @yield('content')
    </main>

    @yield('actions')

    {{-- Bottom tab bar of the main screens (thumb reach): deliveries, money, account. --}}
    @hasSection('tab')
        <nav class="tabbar" aria-label="Navigation">
            @foreach ([
                'home' => [route('courier.home'), 'list', 'Livraisons'],
                'money' => [route('courier.money'), 'wallet', 'Encaissements'],
                'account' => [route('courier.password.edit'), 'user', 'Compte'],
            ] as $key => [$url, $icon, $label])
                <a href="{{ $url }}" @class(['tabbar__item', 'is-active' => trim($__env->yieldContent('tab')) === $key]) @if (trim($__env->yieldContent('tab')) === $key) aria-current="page" @endif>
                    @include('courier.partials.icon', ['name' => $icon])
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </nav>
    @endif
    @endif

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register(@json(route('courier.service-worker', absolute: false)), { scope: '/livreur/' }).catch(() => {});
        }
    </script>
</body>
</html>
