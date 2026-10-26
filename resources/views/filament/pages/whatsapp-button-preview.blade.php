{{-- Live preview of the WhatsApp button (App\Filament\Pages\WhatsAppButton): the chat window as visitors see it,
     in a fake browser, on a computer or a phone. Redrawn as the form changes. --}}
@php
    $settings = [...app(\App\Services\Storefront\WhatsAppWidget::class)->settings(), ...collect($preview)->only([
        'enabled', 'greeting_enabled', 'agent_name', 'status_text', 'greeting', 'button_label', 'greeting_delay', 'position', 'show_desktop', 'show_mobile',
    ])->all()];
    $settings['position'] = ($settings['position'] ?? 'left') === 'right' ? 'right' : 'left';
    $settings['greeting_delay'] = (int) ($settings['greeting_delay'] ?? 0);
    foreach (['agent_name', 'status_text', 'greeting', 'button_label'] as $field) {
        $settings[$field] = (string) ($settings[$field] ?? '');
    }
    $number = preg_replace('/\D/', '', (string) ($preview['phone'] ?? ''));
    $url = 'https://wa.me/'.$number.'?text='.rawurlencode((string) ($preview['message'] ?? ''));
@endphp
<link rel="stylesheet" href="{{ asset('assets/css/whatsapp-widget.css') }}?v={{ @filemtime(public_path('assets/css/whatsapp-widget.css')) }}">

<div class="kwp" x-data="{ device: 'desktop' }">
    <div class="kwp-top">
        <strong>Aperçu</strong>
        <div class="kwp-tabs" role="group" aria-label="Mode d’aperçu">
            <button type="button" :class="{ 'is-active': device === 'desktop' }" @click="device = 'desktop'">Ordinateur</button>
            <button type="button" :class="{ 'is-active': device === 'mobile' }" @click="device = 'mobile'">Mobile</button>
        </div>
    </div>

    <div class="kwp-browser" :class="{ 'is-mobile': device === 'mobile' }">
        <div class="kwp-bar"><span></span><span></span><span></span><div class="kwp-url">{{ parse_url($storeUrl, PHP_URL_HOST) }}</div></div>
        <div class="kwp-page">
            <div class="kwp-sk" style="height: 22px; width: 40%"></div>
            <div class="kwp-sk" style="height: 120px"></div>
            <div class="kwp-row"><div class="kwp-sk"></div><div class="kwp-sk"></div><div class="kwp-sk"></div></div>
            <div class="kwp-sk" style="height: 10px; width: 90%"></div>
            <div class="kwp-sk" style="height: 10px; width: 70%"></div>

            @if (! ($preview['enabled'] ?? false))
                <p class="kwp-off">Bouton désactivé : il n’apparaît pas sur le site.</p>
            @elseif ($number === '')
                <p class="kwp-off">Saisissez le numéro WhatsApp pour afficher le bouton.</p>
            @else
                <div x-show="(device === 'desktop' && {{ ($preview['show_desktop'] ?? true) ? 'true' : 'false' }}) || (device === 'mobile' && {{ ($preview['show_mobile'] ?? true) ? 'true' : 'false' }})">
                    <x-whatsapp-widget :settings="$settings" :url="$url" :avatar="asset(config('storefront.favicon'))" preview />
                </div>
                <p class="kwp-off" x-show="(device === 'desktop' && !{{ ($preview['show_desktop'] ?? true) ? 'true' : 'false' }}) || (device === 'mobile' && !{{ ($preview['show_mobile'] ?? true) ? 'true' : 'false' }})">Masqué sur cet écran.</p>
            @endif
        </div>
    </div>

    <div class="kwp-actions">
        @if ($number !== '')
            <a class="kwp-link" href="{{ $url }}" target="_blank" rel="noopener">Tester le lien</a>
        @endif
        <a class="kwp-link" href="{{ $storeUrl }}" target="_blank" rel="noopener">Voir le site</a>
    </div>
</div>
