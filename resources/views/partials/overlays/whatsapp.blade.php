{{-- Floating WhatsApp button (F-081) and its chat window, set up in Contenus › Bouton WhatsApp
     (App\Services\Storefront\WhatsAppWidget). The message is the page's own when it has one
     (@section('whatsapp_message'): product viewed, order summary…), else the one set in the back-office. --}}
@php
    $whatsappWidget = app(\App\Services\Storefront\WhatsAppWidget::class);
@endphp

@if ($whatsappWidget->shownOn(request()))
    @php
        $whatsappSettings = $whatsappWidget->settings();
        // @section() escapes its inline value for HTML: back to plain text for the message.
        $whatsappUrl = $whatsappWidget->url(
            trim(html_entity_decode($__env->yieldContent('whatsapp_message'), ENT_QUOTES | ENT_HTML5)) ?: null,
            url()->current(),
        );
    @endphp
    <link rel="stylesheet" href="{{ asset('assets/css/whatsapp-widget.css') }}?v={{ @filemtime(public_path('assets/css/whatsapp-widget.css')) }}">
    <x-whatsapp-widget :settings="$whatsappSettings" :url="$whatsappUrl" :avatar="asset(config('storefront.favicon'))" />

    @if ($whatsappSettings['greeting_enabled'])
        <script>
            (() => {
                const widget = document.querySelector('[data-wa-widget]');
                const card = widget.querySelector('[data-wa-card]');
                const toggle = widget.querySelector('[data-wa-toggle]');
                const seenKey = 'kova-wa-seen';
                const remember = () => { try { sessionStorage.setItem(seenKey, '1'); } catch {} };
                const seen = () => { try { return sessionStorage.getItem(seenKey) === '1'; } catch { return false; } };

                const setOpen = (open) => {
                    card.hidden = !open;
                    widget.classList.toggle('is-open', open);
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    if (open) { widget.classList.add('is-seen'); remember(); }
                };

                // The round button opens the window; the window's own button goes to WhatsApp.
                toggle.addEventListener('click', (event) => { event.preventDefault(); setOpen(card.hidden); });
                widget.querySelector('[data-wa-close]').addEventListener('click', () => setOpen(false));
                document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !card.hidden) setOpen(false); });

                // Opens by itself once per visit, on a computer only: on a phone the red dot is enough.
                const delay = Number(widget.dataset.delay);
                if (seen()) {
                    widget.classList.add('is-seen');
                } else if (delay > 0 && window.matchMedia('(min-width: 1200px)').matches) {
                    setTimeout(() => { if (card.hidden && !seen()) setOpen(true); }, delay * 1000);
                }
            })();
        </script>
    @endif
@endif
