/**
 * Add to cart without leaving the page: the forms marked [data-cart-form] (product cards, "+", product page) are
 * sent in the background; the header figures ([data-cart-count], [data-cart-total]) and the mini-cart are refreshed
 * from the answer, the mini-cart slides open when the form asks for it, and a short message confirms. "Acheter
 * maintenant" still goes to the cart. Without JavaScript the forms post normally.
 */
(function () {
    'use strict';

    var toast = document.createElement('div');
    toast.className = 'kova-cart-toast';
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    document.body.appendChild(toast);
    toast.dataset.cartUrl = document.currentScript?.dataset.cartUrl || '/panier';
    var hideTimer;

    function notify(message, isError) {
        toast.innerHTML = '';
        var icon = document.createElement('i');
        icon.className = 'fa-regular ' + (isError ? 'fa-circle-exclamation' : 'fa-circle-check');
        var text = document.createElement('span');
        text.textContent = message;
        toast.append(icon, text);

        if (!isError) {
            var link = document.createElement('a');
            link.href = toast.dataset.cartUrl || '/panier';
            link.textContent = 'Voir le panier';
            toast.append(link);
        }

        toast.classList.toggle('is-error', !!isError);
        toast.classList.add('is-visible');
        clearTimeout(hideTimer);
        hideTimer = setTimeout(function () { toast.classList.remove('is-visible'); }, 4500);
    }

    function refresh(data) {
        document.querySelectorAll('[data-cart-count]').forEach(function (count) { count.textContent = data.count; });
        document.querySelectorAll('[data-cart-total]').forEach(function (total) { total.textContent = data.total; });

        var panel = document.querySelector('.rbt-cart-side-menu');
        if (panel && data.mini_cart) {
            var holder = document.createElement('div');
            holder.innerHTML = data.mini_cart;
            var fresh = holder.querySelector('.rbt-cart-side-menu');
            if (fresh) { panel.innerHTML = fresh.innerHTML; }
        }
    }

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('[data-cart-form]');

        if (!form || (event.submitter && event.submitter.name === 'buy_now')) {
            return;
        }

        event.preventDefault();
        var buttons = form.querySelectorAll('button[type="submit"]');
        buttons.forEach(function (button) { button.disabled = true; button.classList.add('is-loading'); });
        var body = new FormData(form);
        if (event.submitter && event.submitter.name) { body.append(event.submitter.name, event.submitter.value); }

        fetch(form.action, { method: 'POST', body: body, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) {
                return response.json().then(function (data) { return { ok: response.ok, data: data }; });
            })
            .then(function (result) {
                if (!result.ok) {
                    notify(result.data.message || 'Ce produit n’a pas pu être ajouté.', true);
                    return;
                }

                refresh(result.data);
                if (window.kovaTrack) { window.kovaTrack(result.data.analytics); }
                var open = form.querySelector('input[name="open"]');
                // The mini-cart shows the product just added; elsewhere (product page) a short message confirms.
                if (open && open.value === 'sidenav') {
                    document.querySelector('.rbt-cart-side-menu')?.classList.add('side-menu-active');
                    document.body.classList.add('cart-sidenav-menu-active');
                } else {
                    notify(result.data.message, false);
                }

                // A quick view closes once its product is in the cart.
                var modal = form.closest('.modal');
                if (modal && window.bootstrap) { window.bootstrap.Modal.getInstance(modal)?.hide(); }
            })
            .catch(function () { form.submit(); })
            .finally(function () {
                buttons.forEach(function (button) { button.disabled = false; button.classList.remove('is-loading'); });
            });
    });
})();
