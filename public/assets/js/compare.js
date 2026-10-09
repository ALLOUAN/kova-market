/**
 * Product comparison: the "Comparer" buttons ([data-compare-form]) add or remove the product without leaving the page;
 * every button of the same product, the header counts ([data-compare-count]) and the bottom bar ([data-compare-bar],
 * rendered by the server) are refreshed. Without JavaScript the forms post normally.
 */
(function () {
    'use strict';

    var live = document.createElement('div');
    live.className = 'visually-hidden';
    live.setAttribute('role', 'status');
    live.setAttribute('aria-live', 'polite');
    document.body.appendChild(live);

    function paint(ids) {
        document.querySelectorAll('[data-compare-form]').forEach(function (form) {
            var inList = ids.indexOf(parseInt(form.getAttribute('data-product-id'), 10)) !== -1;
            var button = form.querySelector('button');
            var label = button.querySelector('[data-compare-label]');

            button.classList.toggle('is-compared', inList);
            button.setAttribute('aria-pressed', inList ? 'true' : 'false');
            if (label) { label.textContent = inList ? 'Dans le comparateur' : 'Comparer'; }
            if (button.hasAttribute('data-tooltip')) { button.setAttribute('data-tooltip', inList ? 'Retirer du comparateur' : 'Comparer'); }
        });
    }

    function refresh(data) {
        paint(data.ids || []);
        document.querySelectorAll('[data-compare-count]').forEach(function (count) {
            count.textContent = data.count;
            count.hidden = data.count === 0;
        });

        var bar = document.querySelector('[data-compare-bar]');
        if (bar && data.bar !== undefined) {
            bar.outerHTML = data.bar;
        }

        live.textContent = data.message;
    }

    function send(form) {
        var button = form.querySelector('button');
        button.disabled = true;

        return fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
            .then(function (result) {
                refresh(result.data);
                if (!result.ok) { showNotice(result.data.message); }
            })
            .catch(function () { form.submit(); })
            .finally(function () { button.disabled = false; });
    }

    // A full list is said in the message window (assets/js/flash.js), else plainly next to the bar; no browser alert.
    function showNotice(message) {
        if (window.kovaFlash && window.kovaFlash(message, 'warning')) { return; }
        var bar = document.querySelector('[data-compare-bar]');
        if (!bar) { return; }
        var notice = document.createElement('p');
        notice.className = 'kova-compare-notice';
        notice.textContent = message;
        bar.appendChild(notice);
        setTimeout(function () { notice.remove(); }, 5000);
    }

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('[data-compare-form], [data-compare-clear]');

        if (!form) {
            return;
        }

        event.preventDefault();
        send(form);
    });
})();
