<!DOCTYPE html>
{{-- Maintenance page shown to visitors (App\Http\Middleware\MaintenanceMode), texts set in Administration › Maintenance.
     Standalone: KOVA MARKET charter, no theme script, plain colours. --}}
@php
    $store = app(\App\Services\Storefront\StoreSettings::class);
    $contact = $store->contact();
    $whatsappUrl = $store->whatsappUrl('Bonjour '.config('storefront.name').', ');
    $backAt = $maintenance->expectedBackAt();
    $progress = $maintenance->progress();
@endphp
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Maintenance en cours · {{ config('storefront.name') }}</title>
    <link rel="icon" href="{{ asset(config('storefront.favicon', 'assets/images/logo/kova-favicon.png')) }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap">
    <style>
        :root { --navy: #021732; --navy-50: #f2f5fa; --green: #0e7d42; --green-100: #e5f3eb; --gold: #c69e3e; --gold-100: #f8f0dc; --text: #3f4a5a; --muted: #667085; --line: #e3e8ef; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; font: 16px/1.6 Inter, "Segoe UI", Roboto, Arial, sans-serif; color: var(--text); background: var(--navy-50); }
        header { padding: 18px 24px; background: #fff; border-bottom: 4px solid var(--gold); text-align: center; }
        header img { height: 48px; }
        main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 16px; }
        .card { width: 100%; max-width: 600px; padding: 40px 36px; background: #fff; border: 1px solid var(--line); border-top: 4px solid var(--navy); border-radius: 16px; text-align: center; box-shadow: 0 10px 30px rgb(2 23 50 / .06); }
        .icon { display: inline-flex; align-items: center; justify-content: center; width: 72px; height: 72px; margin-bottom: 18px; border-radius: 50%; background: var(--gold-100); color: var(--gold); }
        h1 { margin: 0 0 12px; font: 700 28px/1.25 Poppins, "Segoe UI", sans-serif; color: var(--navy); }
        .message { margin: 0 auto 28px; max-width: 480px; font-size: 17px; }
        .progress { margin: 0 0 24px; text-align: left; }
        .progress-head { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; font-weight: 600; color: var(--navy); }
        .bar { height: 12px; border-radius: 999px; background: var(--line); overflow: hidden; }
        .bar span { display: block; height: 100%; border-radius: 999px; background: var(--green); }
        .facts { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; margin: 0 0 28px; padding: 0; list-style: none; }
        .facts li { padding: 10px 16px; border-radius: 10px; background: var(--navy-50); font-size: 14px; }
        .facts strong { display: block; font-size: 16px; color: var(--navy); }
        .contact { padding-top: 22px; border-top: 1px solid var(--line); font-size: 15px; }
        .contact p { margin: 0 0 12px; color: var(--muted); }
        .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; border: 1px solid var(--line); color: var(--navy); background: #fff; }
        .btn:hover { border-color: var(--navy); }
        .btn-green { background: var(--green); border-color: var(--green); color: #fff; }
        .btn-green:hover { background: #0b6536; border-color: #0b6536; }
        .btn:focus-visible { outline: 3px solid rgb(14 125 66 / .35); outline-offset: 2px; }
        footer { padding: 16px; text-align: center; font-size: 13px; color: #fff; background: var(--navy); }
        @media (max-width: 480px) { .card { padding: 28px 20px; } h1 { font-size: 23px; } }
    </style>
</head>
<body>
    <header>
        <img src="{{ asset('assets/images/logo/kova-logo-400.webp') }}" alt="{{ config('storefront.name') }}">
    </header>
    <main>
        <div class="card">
            <span class="icon" aria-hidden="true">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            </span>
            <h1>Nous revenons très vite</h1>
            <p class="message">{!! nl2br(e($maintenance->message())) !!}</p>

            <div class="progress" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100" aria-label="Avancement des travaux">
                <div class="progress-head"><span>Avancement des travaux</span><span>{{ $progress }} %</span></div>
                <div class="bar"><span style="width: {{ $progress }}%"></span></div>
            </div>

            <ul class="facts">
                <li>Durée estimée<strong>{{ $maintenance->durationLabel() }}</strong></li>
                @if ($backAt?->isFuture())
                    <li>Retour prévu<strong>{{ $backAt->isToday() ? 'vers '.$backAt->format('H\hi') : 'le '.$backAt->translatedFormat('d/m').' vers '.$backAt->format('H\hi') }}</strong></li>
                @endif
            </ul>

            <div class="contact">
                <p>Une commande en cours ou une question urgente ? Nous restons joignables.</p>
                <div class="actions">
                    @if ($whatsappUrl)
                        <a class="btn btn-green" href="{{ $whatsappUrl }}" target="_blank" rel="noopener">WhatsApp</a>
                    @endif
                    <a class="btn {{ $whatsappUrl ? '' : 'btn-green' }}" href="tel:{{ preg_replace('/[^\d+]/', '', $contact['phone']) }}">{{ $contact['phone'] }}</a>
                    <a class="btn" href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
                </div>
            </div>
        </div>
    </main>
    <footer>{{ config('storefront.name') }} · Merci de votre patience</footer>
</body>
</html>
