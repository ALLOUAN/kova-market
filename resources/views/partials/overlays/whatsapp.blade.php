{{-- Floating WhatsApp button (F-081) on every page, the message typed in advance by the page
     (@section('whatsapp_message')): product viewed, order summary… Bottom left, above the mobile toolbar,
     so it never covers a buy button nor the back-to-top button. Hidden while no WhatsApp number is set. --}}
@php
    $whatsappUrl = app(\App\Services\Storefront\StoreSettings::class)->whatsappUrl(
        // @section() escapes its inline value for HTML: back to plain text for the message.
        trim(html_entity_decode($__env->yieldContent('whatsapp_message'), ENT_QUOTES | ENT_HTML5)) ?: 'Bonjour '.config('storefront.name').', j’ai une question.'
    );
@endphp

@if ($whatsappUrl)
    <a href="{{ $whatsappUrl }}" class="kova-whatsapp-button" target="_blank" rel="noopener" aria-label="Nous écrire sur WhatsApp">
        <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
    </a>
    <style>
        .kova-whatsapp-button {
            position: fixed; left: 16px; bottom: 88px; z-index: 990;
            display: flex; align-items: center; justify-content: center;
            width: 52px; height: 52px; border-radius: 50%;
            background: #25d366; color: #fff; font-size: 28px; box-shadow: 0 4px 12px rgba(0, 0, 0, .2);
        }
        .kova-whatsapp-button:hover, .kova-whatsapp-button:focus { color: #fff; background: #1ebe5a; }
        @media (min-width: 1200px) { .kova-whatsapp-button { bottom: 24px; } }
    </style>
@endif
