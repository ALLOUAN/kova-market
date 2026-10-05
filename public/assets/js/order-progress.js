/*
 * KOVA MARKET: keeps the order steps of the confirmation page up to date ([data-order-progress]).
 * Every 20 s, and when the tab comes back to the front, the steps are fetched again; they move as the store
 * confirms, the picker prepares and the courier takes, leaves and delivers. Stops once the order is delivered;
 * a cancelled order reloads the page, which then shows its cancelled state.
 */
(() => {
    const box = document.querySelector('[data-order-progress]');

    if (!box || box.hasAttribute('data-settled') || !window.fetch) {
        return;
    }

    const EVERY = 20000;
    let timer = null;
    let busy = false;

    const stop = () => {
        clearTimeout(timer);
        timer = null;
    };

    const schedule = () => {
        stop();
        if (!document.hidden) {
            timer = setTimeout(check, EVERY);
        }
    };

    async function check() {
        if (busy) {
            return;
        }
        busy = true;

        try {
            const response = await fetch(box.dataset.url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (!response.ok) {
                // Server busy or session over: try again at the next turn.
                busy = false;
                schedule();
                return;
            }

            if (response.headers.get('X-Order-Status') === 'annulee') {
                window.location.reload();
                return;
            }

            const html = await response.text();
            if (html.trim() !== box.innerHTML.trim()) {
                box.innerHTML = html;
            }

            if (response.headers.get('X-Order-Settled') === '1') {
                box.setAttribute('data-settled', '');
                document.removeEventListener('visibilitychange', onVisibility);
                stop();
                return;
            }
        } catch (error) {
            // Offline for a moment: try again at the next turn.
        } finally {
            busy = false;
        }

        if (!box.hasAttribute('data-settled')) {
            schedule();
        }
    }

    function onVisibility() {
        if (document.hidden) {
            stop();
        } else {
            check();
        }
    }

    document.addEventListener('visibilitychange', onVisibility);
    schedule();
})();
