/**
 * Audience measurement and consent (F-155, F-156).
 *
 * The page carries window.kovaAnalytics = { ga4, meta, tiktok, currency, consent, events }. Nothing is requested
 * from Google, Meta or TikTok until the visitor clicks "Accepter" on the cookie banner; the choice is kept six
 * months in the first-party "kova_consent" cookie ("granted" or "denied") and can be changed with any
 * [data-cookie-settings] link. E-commerce events of the page (view_item, add_to_cart, begin_checkout, purchase)
 * are sent once the trackers are loaded.
 */
(function () {
    'use strict';

    var config = window.kovaAnalytics;

    if (!config) {
        return;
    }

    var COOKIE = 'kova_consent';
    var SIX_MONTHS = 60 * 60 * 24 * 182;
    var banner = document.querySelector('[data-cookie-banner]');
    var loaded = false;

    function readConsent() {
        var match = document.cookie.match(/(?:^|; )kova_consent=(granted|denied)/);

        return match ? match[1] : null;
    }

    function saveConsent(value) {
        document.cookie = COOKIE + '=' + value + '; path=/; max-age=' + SIX_MONTHS + '; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
    }

    function inject(src) {
        var script = document.createElement('script');
        script.async = true;
        script.src = src;
        document.head.appendChild(script);
    }

    function loadTrackers() {
        if (loaded) {
            return;
        }

        loaded = true;

        if (config.ga4) {
            window.dataLayer = window.dataLayer || [];
            window.gtag = function () { window.dataLayer.push(arguments); };
            window.gtag('js', new Date());
            window.gtag('config', config.ga4);
            inject('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(config.ga4));
        }

        if (config.meta) {
            var fbq = window.fbq = function () {
                fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments);
            };
            window._fbq = fbq;
            fbq.push = fbq;
            fbq.loaded = true;
            fbq.version = '2.0';
            fbq.queue = [];
            fbq('init', config.meta);
            fbq('track', 'PageView');
            inject('https://connect.facebook.net/fr_FR/fbevents.js');
        }

        if (config.tiktok) {
            var ttq = window.ttq = window.ttq || [];
            ['page', 'track', 'identify'].forEach(function (method) {
                ttq[method] = function () { ttq.push([method].concat(Array.prototype.slice.call(arguments))); };
            });
            ttq.load = function (id) {
                inject('https://analytics.tiktok.com/i18n/pixel/events.js?sdkid=' + encodeURIComponent(id) + '&lib=ttq');
            };
            ttq.load(config.tiktok);
            ttq.page();
        }

        (config.events || []).forEach(send);
    }

    /** One e-commerce event, in each tracker's own vocabulary. */
    function send(event) {
        var params = event.params;
        var items = params.items || [];
        var ids = items.map(function (item) { return item.item_id; });
        var names = { view_item: ['ViewContent', 'ViewContent'], add_to_cart: ['AddToCart', 'AddToCart'], begin_checkout: ['InitiateCheckout', 'InitiateCheckout'], purchase: ['Purchase', 'PlaceAnOrder'] }[event.name];

        if (config.ga4) {
            window.gtag('event', event.name, params);
        }

        if (!names) {
            return;
        }

        if (config.meta) {
            window.fbq('track', names[0], { content_ids: ids, content_type: 'product', value: params.value, currency: params.currency, num_items: items.length });
        }

        if (config.tiktok) {
            window.ttq.track(names[1], {
                contents: items.map(function (item) { return { content_id: item.item_id, content_name: item.item_name, quantity: item.quantity, price: item.price }; }),
                content_type: 'product',
                value: params.value,
                currency: params.currency
            });
        }
    }

    function showBanner() {
        if (banner) {
            banner.classList.add('isVisible');
        }
    }

    function hideBanner() {
        if (banner) {
            banner.classList.remove('isVisible');
        }
    }

    document.addEventListener('click', function (event) {
        var target = event.target.closest('[data-cookie-accept], [data-cookie-decline], [data-cookie-settings]');

        if (!target) {
            return;
        }

        if (target.hasAttribute('data-cookie-settings')) {
            event.preventDefault();
            showBanner();

            return;
        }

        var granted = target.hasAttribute('data-cookie-accept');
        saveConsent(granted ? 'granted' : 'denied');
        hideBanner();

        if (granted) {
            loadTrackers();
        } else if (loaded) {
            // Trackers already running on this page stop at the next page.
            location.reload();
        }
    });

    var consent = readConsent();

    if (consent === 'granted') {
        loadTrackers();
    } else if (consent === null) {
        setTimeout(showBanner, 1500);
    }
})();
