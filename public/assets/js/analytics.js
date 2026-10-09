/**
 * Audience measurement and consent (F-155, F-156).
 *
 * The page carries window.kovaAnalytics = { ga4, meta, tiktok, currency, consent, events }. Nothing is requested
 * from Google, Meta or TikTok until the visitor clicks "Accepter" on the cookie banner; the choice is kept six
 * months in the first-party "kova_consent" cookie ("granted" or "denied") and can be changed with any
 * [data-cookie-settings] link. E-commerce events of the page (view_item, add_to_cart, begin_checkout, purchase, search,
 * sign_up, contact, and on the home page view_item_list and view_promotion) are sent once the trackers are loaded;
 * background answers (add to cart, favourites, newsletter) hand theirs to window.kovaTrack. Clicks on home banners
 * and on the products of its selections give select_promotion and select_item (Google Analytics only), clicks on the
 * store's WhatsApp give contact. Each event's id is given to Meta, which merges it with the copy sent by the server
 * (Conversions API).
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

    /** The store's events (GA4 names) for Meta and TikTok; the same list as App\Services\Storefront\MetaConversions. */
    var NAMES = {
        view_item: ['ViewContent', 'ViewContent'],
        add_to_cart: ['AddToCart', 'AddToCart'],
        begin_checkout: ['InitiateCheckout', 'InitiateCheckout'],
        purchase: ['Purchase', 'PlaceAnOrder'],
        search: ['Search', 'Search'],
        add_to_wishlist: ['AddToWishlist', 'AddToWishlist'],
        generate_lead: ['Lead', 'SubmitForm'],
        sign_up: ['CompleteRegistration', 'CompleteRegistration'],
        contact: ['Contact', 'Contact']
    };

    /** Meta's parameters, as MetaConversions::customData() sends them from the server. */
    function metaData(params) {
        var items = params.items || [];
        var data = { currency: params.currency, value: params.value, order_id: params.transaction_id, search_string: params.search_term, content_category: params.method };

        if (items.length) {
            data.content_type = 'product';
            data.content_ids = items.map(function (item) { return item.item_id; });
            data.contents = items.map(function (item) { return { id: item.item_id, quantity: item.quantity || 1, item_price: item.price }; });
            data.num_items = items.reduce(function (sum, item) { return sum + (item.quantity || 1); }, 0);
            if (items.length === 1) { data.content_name = items[0].item_name; }
        }

        Object.keys(data).forEach(function (key) { if (data[key] === undefined || data[key] === null) { delete data[key]; } });

        return data;
    }

    /** One event, in each tracker's own vocabulary. Its id lets Meta merge it with the server's copy. */
    function send(event) {
        var params = event.params;
        var items = params.items || [];
        var names = NAMES[event.name];

        if (config.ga4) {
            window.gtag('event', event.name, params);
        }

        if (!names) {
            return;
        }

        if (config.meta) {
            window.fbq('track', names[0], metaData(params), event.id ? { eventID: event.id } : undefined);
        }

        if (config.tiktok) {
            window.ttq.track(names[1], {
                contents: items.map(function (item) { return { content_id: item.item_id, content_name: item.item_name, quantity: item.quantity, price: item.price }; }),
                content_type: items.length ? 'product' : undefined,
                value: params.value,
                currency: params.currency,
                query: params.search_term
            }, event.id ? { event_id: event.id } : undefined);
        }
    }

    /**
     * Events given by a background answer (add to cart, favourites, newsletter): sent at once, or kept for the
     * moment the visitor accepts.
     */
    window.kovaTrack = function (events) {
        (events || []).forEach(function (event) {
            if (loaded) {
                send(event);
            } else {
                config.events = (config.events || []).concat([event]);
            }
        });
    };

    /** A click to write to the store on WhatsApp ("Contact"); sharing a product on WhatsApp does not count. */
    document.addEventListener('click', function (event) {
        var link = loaded && event.target.closest('a[href*="wa.me/"], a[href*="api.whatsapp.com/send"]');

        if (link && !/wa\.me\/\?/.test(link.getAttribute('href'))) {
            send({ name: 'contact', id: 'contact-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10), params: { method: 'whatsapp' } });
        }
    });

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

    function readJson(element, attribute) {
        try {
            return JSON.parse(element.getAttribute(attribute));
        } catch (error) {
            return null;
        }
    }

    /**
     * Home page clicks (GA4 only), once the trackers run: a banner ("select_promotion") or a product of a
     * selection ("select_item", with its list and its position in it).
     */
    document.addEventListener('click', function (event) {
        if (!loaded || !config.ga4) {
            return;
        }

        var link = event.target.closest('a[href]');

        if (!link || link.getAttribute('href') === '#') {
            return;
        }

        var banner = link.closest('[data-analytics-promotion]');

        if (banner) {
            var promotion = readJson(banner, 'data-analytics-promotion');

            if (promotion) {
                send({ name: 'select_promotion', params: { items: [promotion] } });
            }

            return;
        }

        var card = link.closest('[data-analytics-item]');
        var list = card && card.closest('[data-analytics-list]');

        if (!card || !list) {
            return;
        }

        var item = readJson(card, 'data-analytics-item');
        var listInfo = readJson(list, 'data-analytics-list');

        if (item && listInfo) {
            var cards = Array.prototype.slice.call(list.querySelectorAll('[data-analytics-item]'));
            item.index = cards.indexOf(card);
            item.item_list_id = listInfo.item_list_id;
            item.item_list_name = listInfo.item_list_name;
            send({ name: 'select_item', params: { item_list_id: listInfo.item_list_id, item_list_name: listInfo.item_list_name, items: [item] } });
        }
    });

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
