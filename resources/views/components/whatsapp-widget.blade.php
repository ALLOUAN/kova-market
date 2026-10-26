@props(['settings', 'url', 'avatar', 'preview' => false])

{{--
    Floating WhatsApp button and its WhatsApp-like chat window (App\Services\Storefront\WhatsAppWidget), on the
    storefront and in the back-office preview (Contenus › Bouton WhatsApp). Styles: public/assets/css/whatsapp-widget.css;
    behaviour: the script of partials/overlays/whatsapp (not in the preview, which stays open).
--}}
<div @class([
        'wa-widget',
        'wa-'.$settings['position'],
        'wa-no-desktop' => ! $settings['show_desktop'] && ! $preview,
        'wa-no-mobile' => ! $settings['show_mobile'] && ! $preview,
        'is-open' => $preview && $settings['greeting_enabled'],
        'is-preview' => $preview,
    ])
    data-wa-widget
    data-greeting="{{ $settings['greeting_enabled'] ? 1 : 0 }}"
    data-delay="{{ $settings['greeting_delay'] }}">
    @if ($settings['greeting_enabled'])
        <div class="wa-card" role="dialog" aria-label="Discuter avec {{ $settings['agent_name'] }} sur WhatsApp" @unless ($preview) hidden @endunless data-wa-card>
            <div class="wa-head">
                <span class="wa-avatar"><img src="{{ $avatar }}" alt="" width="44" height="44" loading="lazy"></span>
                <div class="wa-who">
                    <div class="wa-name">{{ $settings['agent_name'] }}</div>
                    <div class="wa-status">{{ $settings['status_text'] }}</div>
                </div>
                <button type="button" class="wa-x" aria-label="Fermer" data-wa-close>×</button>
            </div>
            <div class="wa-body">
                <div class="wa-bubble">
                    <span class="wa-agent">{{ $settings['agent_name'] }}</span>
                    <span>{{ $settings['greeting'] }}</span>
                    <span class="wa-time">{{ now()->format('H:i') }}</span>
                </div>
            </div>
            <div class="wa-foot">
                <a class="wa-start" href="{{ $url }}" target="_blank" rel="noopener">
                    <x-whatsapp-icon />
                    <span>{{ $settings['button_label'] }}</span>
                </a>
                <small>Vous serez redirigé vers WhatsApp</small>
            </div>
        </div>
    @endif
    <a class="wa-fab" href="{{ $url }}" target="_blank" rel="noopener" aria-label="Discuter sur WhatsApp" title="Discuter sur WhatsApp"
        @if ($settings['greeting_enabled']) aria-expanded="{{ $preview ? 'true' : 'false' }}" data-wa-toggle @endif>
        <x-whatsapp-icon class="wa-ic-open" />
        <span class="wa-ic-close" aria-hidden="true">×</span>
        @if ($settings['greeting_enabled'])<span class="wa-badge" aria-hidden="true">1</span>@endif
    </a>
</div>
