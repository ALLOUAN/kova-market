/*
 * KOVA MARKET storefront behaviour written for the shop (the template's own code is main.min.js).
 */
(() => {
    const money = (amount) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(amount).replace(/\s/g, ' ') + ' FCFA';

    /**
     * Keeps a purchase block (x-product.purchase) in step with the chosen attribute values: price, stock,
     * reference, variant sent to the cart. The selected variant carries exactly the chosen values.
     */
    const initPurchase = (root) => {
        const variants = JSON.parse(root.dataset.variants || '[]');
        const limited = Number(root.dataset.limitedStock || 0);
        const find = (selector) => root.querySelector(selector);

        const render = () => {
            const chosen = [...root.querySelectorAll('[data-variant-option]:checked')].map((input) => Number(input.value));
            const variant = variants.find((item) => item.values.length === chosen.length && chosen.every((id) => item.values.includes(id)));
            const buy = root.querySelectorAll('[data-buy]');

            const alert = find('[data-stock-alert]');

            if (!variant) {
                find('[data-stock]').textContent = 'Cette combinaison n’est pas disponible.';
                buy.forEach((button) => button.disabled = true);
                if (alert) {
                    alert.hidden = true;
                }
                return;
            }

            buy.forEach((button) => button.disabled = variant.stock === 0);

            // A sold-out variant offers the back-in-stock alert for that very variant (EX-17).
            if (alert) {
                alert.hidden = variant.stock !== 0;
                alert.querySelector('[data-stock-alert-variant]').value = variant.id;
            }
            find('[data-price]').textContent = money(variant.price);
            find('[data-compare]').hidden = !variant.compare_at_price;
            find('[data-compare]').textContent = variant.compare_at_price ? money(variant.compare_at_price) : '';
            find('[data-sku]').textContent = variant.sku;
            find('[data-variant-id]').value = variant.id;
            find('[data-quantity]').max = Math.max(1, variant.stock);
            find('[data-stock]').textContent = variant.stock === 0
                ? 'Épuisé'
                : (variant.stock <= limited ? `Plus que ${variant.stock} en stock` : 'En stock');
        };

        root.querySelectorAll('[data-variant-option]').forEach((input) => input.addEventListener('change', render));
        render();
    };

    /**
     * Quick view (EX-18): the button of a product card carries the URL of the product's fragment, loaded into
     * the modal or side panel it opens (whichever holds a [data-quick-view-body]).
     */
    const loadQuickView = async (button) => {
        const body = document.querySelector('[data-quick-view-body]');

        if (!body) {
            return;
        }

        body.innerHTML = '<p class="text-center py-5 mb-0">Chargement…</p>';

        try {
            const response = await fetch(button.dataset.quickViewUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });

            if (!response.ok) {
                throw new Error(response.statusText);
            }

            body.innerHTML = await response.text();
            body.querySelectorAll('[data-purchase]').forEach(initPurchase);
            body.querySelectorAll('[data-gallery-thumb]').forEach((thumb) => thumb.addEventListener('click', () => {
                body.querySelector('[data-gallery-main]').src = thumb.dataset.galleryThumb;
            }));
        } catch (error) {
            body.innerHTML = `<p class="text-center py-5 mb-0">L’aperçu n’a pas pu être chargé. <a href="${button.dataset.productUrl}">Voir la fiche du produit</a></p>`;
        }
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-quick-view-url]');

        if (button) {
            loadQuickView(button);
        }
    });

    // "Me prévenir" on a sold-out product card: the alert window is shared, the button tells which product.
    document.getElementById('notifyModal')?.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        const modal = event.currentTarget;

        modal.querySelector('[data-stock-alert-product]').value = button?.dataset.productId || '';
        modal.querySelector('[data-stock-alert-variant]').value = '';
        modal.querySelector('[data-stock-alert-name]').textContent = button?.dataset.productName ? `« ${button.dataset.productName} »` : 'ce produit';
    });

    document.querySelectorAll('[data-purchase]').forEach(initPurchase);
})();
