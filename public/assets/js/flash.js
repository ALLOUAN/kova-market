/*
 * KOVA MARKET: shows the action messages of the page (".alert[data-popup]": saved, added to the cart, form errors…)
 * in the #kovaFlashModal window instead of inline. Without JavaScript they simply stay in the page.
 * After an error, closing the window brings the visitor to the first field to correct.
 */
(() => {
    const TYPES = {
        success: { icon: 'fa-circle-check', title: 'C’est fait !' },
        danger: { icon: 'fa-circle-exclamation', title: 'Attention' },
        warning: { icon: 'fa-triangle-exclamation', title: 'Attention' },
        info: { icon: 'fa-circle-info', title: 'Information' },
    };

    const typeOf = (alert) => Object.keys(TYPES).find((type) => alert.classList.contains(`alert-${type}`)) || 'info';

    const init = () => {
        const modal = document.getElementById('kovaFlashModal');
        const alerts = [...document.querySelectorAll('.alert[data-popup]')].filter((alert) => !alert.closest('.modal'));

        if (!modal || !alerts.length || !window.bootstrap) {
            return;
        }

        // An error wins over any success shown with it.
        const types = alerts.map(typeOf);
        const type = types.includes('danger') ? 'danger' : types[0];
        const message = modal.querySelector('[data-flash-message]');

        alerts.forEach((alert) => {
            const item = document.createElement('div');
            item.className = 'kova-flash__item';
            item.innerHTML = alert.innerHTML;
            message.appendChild(item);
            (alert.closest('[data-popup-host]') || alert).hidden = true;
        });

        modal.dataset.type = type;
        modal.querySelector('[data-flash-icon]').className = `fa-solid ${TYPES[type].icon}`;
        modal.querySelector('[data-flash-title]').textContent = TYPES[type].title;

        // Never two windows at once: the newsletter invitation waits for another page.
        document.getElementById('welcomebannerModal')?.remove();

        if (type === 'danger') {
            modal.addEventListener('hidden.bs.modal', () => {
                // Fields flagged by Bootstrap, or the field next to the first red error text of a form.
                // (the red "*" of required fields sits in their label: not an error).
                const errorText = [...document.querySelectorAll('main form .rbt-text-color-danger')].find((el) => !el.closest('label'));
                const field = document.querySelector('.is-invalid, [aria-invalid="true"]')
                    || errorText?.parentElement.querySelector('input:not([type="hidden"]), select, textarea');
                if (field) {
                    field.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    field.focus({ preventScroll: true });
                }
            }, { once: true });
        }

        const show = () => bootstrap.Modal.getOrCreateInstance(modal).show();

        // A window opened by the page (sign-in form…) goes first; the message follows once it is closed.
        if (document.body.classList.contains('modal-open')) {
            document.addEventListener('hidden.bs.modal', show, { once: true });
        } else {
            show();
        }
    };

    // After the page's own DOMContentLoaded handlers (they may open the sign-in window).
    document.addEventListener('DOMContentLoaded', () => setTimeout(init, 0));
})();
