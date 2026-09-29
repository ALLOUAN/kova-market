/**
 * "Mes favoris": the heart buttons ([data-wishlist-form]) add or remove the product without leaving the page, then
 * every heart of the same product, the header counts ([data-wishlist-count]) and the status line are updated.
 * Without JavaScript the forms post normally and the page comes back with the same message.
 */
(function () {
    'use strict';

    var live = document.createElement('div');
    live.className = 'visually-hidden';
    live.setAttribute('role', 'status');
    live.setAttribute('aria-live', 'polite');
    document.body.appendChild(live);

    function paint(form, added) {
        var button = form.querySelector('button');
        var icon = button.querySelector('.fa-heart');
        var label = button.querySelector('[data-wishlist-label]');

        button.classList.toggle('is-wishlisted', added);
        button.setAttribute('aria-pressed', added ? 'true' : 'false');
        icon.classList.toggle('fa-solid', added);
        icon.classList.toggle('fa-regular', !added);

        if (label) {
            label.textContent = added ? 'Dans vos favoris' : 'Ajouter aux favoris';
        }

        if (button.hasAttribute('data-tooltip')) {
            button.setAttribute('data-tooltip', added ? 'Retirer des favoris' : 'Ajouter aux favoris');
        }
    }

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('[data-wishlist-form]');

        if (!form) {
            return;
        }

        event.preventDefault();
        var button = form.querySelector('button');
        button.disabled = true;

        fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) {
                if (!response.ok) { throw new Error(); }
                return response.json();
            })
            .then(function (data) {
                document.querySelectorAll('[data-wishlist-form][action="' + form.getAttribute('action') + '"]').forEach(function (other) {
                    paint(other, data.added);
                });
                document.querySelectorAll('[data-wishlist-count]').forEach(function (count) {
                    count.textContent = data.count;
                    count.hidden = data.count === 0;
                });
                live.textContent = data.message;

                // On the favourites page, a removed product leaves the list.
                var card = !data.added && form.closest('[data-wishlist-card]');
                if (card) {
                    card.remove();
                    if (!document.querySelector('[data-wishlist-card]')) { location.reload(); }
                }
            })
            .catch(function () { form.submit(); })
            .finally(function () { button.disabled = false; });
    });
})();
