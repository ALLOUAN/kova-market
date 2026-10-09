/*
 * KOVA MARKET: shows the action messages in the #kovaFlashModal window instead of inline.
 *  - On load: the page's ".alert[data-popup]" (saved, added to the cart, form errors…). Without JavaScript they
 *    simply stay in the page. After an error, closing the window brings the visitor to the first field to correct.
 *  - From a script: window.kovaFlash(message, type) for the answers that come without reloading the page
 *    (newsletter sign-up, comparator full…). type: success, danger, warning or info.
 */
(() => {
    const TYPES = {
        success: { icon: 'fa-circle-check', title: 'C’est fait !' },
        danger: { icon: 'fa-circle-exclamation', title: 'Attention' },
        warning: { icon: 'fa-triangle-exclamation', title: 'Attention' },
        info: { icon: 'fa-circle-info', title: 'Information' },
    };

    const typeOf = (alert) => Object.keys(TYPES).find((type) => alert.classList.contains(`alert-${type}`)) || 'info';

    /**
     * Fills the window with its messages (HTML of the page's alerts, or plain text) and opens it.
     */
    const open = (items, type, { focusError = false } = {}) => {
        const modal = document.getElementById('kovaFlashModal');

        if (!modal || !window.bootstrap) {
            return false;
        }

        type = TYPES[type] ? type : 'info';
        const message = modal.querySelector('[data-flash-message]');
        message.innerHTML = '';

        items.forEach(({ html, text }) => {
            const item = document.createElement('div');
            item.className = 'kova-flash__item';
            if (html !== undefined) {
                item.innerHTML = html;
            } else {
                item.textContent = text;
            }
            message.appendChild(item);
        });

        modal.dataset.type = type;
        modal.querySelector('[data-flash-icon]').className = `fa-solid ${TYPES[type].icon}`;
        modal.querySelector('[data-flash-title]').textContent = TYPES[type].title;

        // Never two windows at once: the newsletter invitation, if not on screen yet, waits for another page.
        const invitation = document.getElementById('newsletterModal');
        if (invitation && !invitation.classList.contains('show')) {
            invitation.dataset.suppressed = '1';
        }

        if (focusError && type === 'danger') {
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
        const openModal = document.querySelector('.modal.show');

        // Another window is open (sign-in form, newsletter invitation…): it closes first, the message follows.
        if (openModal && openModal !== modal) {
            openModal.addEventListener('hidden.bs.modal', show, { once: true });
            bootstrap.Modal.getOrCreateInstance(openModal).hide();
        } else {
            show();
        }

        return true;
    };

    window.kovaFlash = (message, type = 'success') => open([{ text: message }], type);

    const init = () => {
        const alerts = [...document.querySelectorAll('.alert[data-popup]')].filter((alert) => !alert.closest('.modal'));

        if (!alerts.length) {
            return;
        }

        // An error wins over any success shown with it.
        const types = alerts.map(typeOf);
        const type = types.includes('danger') ? 'danger' : types[0];
        const items = alerts.map((alert) => ({ html: alert.innerHTML }));

        const opened = () => alerts.forEach((alert) => { (alert.closest('[data-popup-host]') || alert).hidden = true; });

        // A window opened by the page itself on load (sign-in form…) goes first; the message follows once closed.
        if (document.body.classList.contains('modal-open')) {
            document.addEventListener('hidden.bs.modal', () => { if (open(items, type, { focusError: true })) { opened(); } }, { once: true });
        } else if (open(items, type, { focusError: true })) {
            opened();
        }
    };

    // After the page's own DOMContentLoaded handlers (they may open the sign-in window).
    document.addEventListener('DOMContentLoaded', () => setTimeout(init, 0));
})();
