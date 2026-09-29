import http from 'node:http';
import crypto from 'node:crypto';

/**
 * A stand-in for CinetPay's API v1 and payment page, for the end-to-end journeys (never used outside tests):
 * POST /v1/oauth/login, POST /v1/payment, GET /v1/payment/{token}, and a payment page whose "Payer" / "Annuler"
 * buttons notify the store (server to server, with the notify_token) then send the customer back, as CinetPay does.
 */
export const FAKE_CINETPAY_PORT = 8124;

export function startFakeCinetPay() {
    const payments = new Map();

    const json = (response, status, body) => {
        response.writeHead(status, { 'Content-Type': 'application/json' });
        response.end(JSON.stringify(body));
    };

    const readBody = (request) => new Promise((resolve) => {
        let data = '';
        request.on('data', (chunk) => { data += chunk; });
        request.on('end', () => resolve(data));
    });

    const notify = (payment) => fetch(payment.notify_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ notify_token: payment.notify_token, merchant_transaction_id: payment.merchant_transaction_id, transaction_id: payment.transaction_id }),
    });

    const server = http.createServer(async (request, response) => {
        const url = new URL(request.url, `http://127.0.0.1:${FAKE_CINETPAY_PORT}`);

        if (request.method === 'POST' && url.pathname === '/v1/oauth/login') {
            return json(response, 200, { code: 200, status: 'OK', access_token: 'fake-token', token_type: 'bearer', expires_in: 86400 });
        }

        if (request.method === 'POST' && url.pathname === '/v1/payment') {
            const body = JSON.parse(await readBody(request));
            const token = crypto.randomBytes(12).toString('hex');
            const payment = { ...body, payment_token: token, notify_token: crypto.randomBytes(8).toString('hex'), transaction_id: `fake-${token.slice(0, 8)}`, status: 'INITIATED' };
            payments.set(token, payment);

            return json(response, 200, {
                code: 200, status: 'OK', payment_token: token, notify_token: payment.notify_token, transaction_id: payment.transaction_id,
                merchant_transaction_id: body.merchant_transaction_id, payment_url: `http://127.0.0.1:${FAKE_CINETPAY_PORT}/pay/${token}`,
                details: { code: 2001, status: 'INITIATED', must_be_redirected: true },
            });
        }

        const status = url.pathname.match(/^\/v1\/payment\/(.+)$/);
        if (request.method === 'GET' && status) {
            const payment = payments.get(status[1]);
            if (!payment) return json(response, 200, { code: 404, status: 'NOT_FOUND' });

            return json(response, 200, {
                code: { SUCCESS: 100, FAILED: 2010 }[payment.status] ?? 2001, status: payment.status,
                merchant_transaction_id: payment.merchant_transaction_id, transaction_id: payment.transaction_id, payment_method: 'OM',
                user: { name: 'Client Test', email: payment.client_email, phone_number: payment.client_phone_number },
            });
        }

        const page = url.pathname.match(/^\/pay\/([a-f0-9]+)(?:\/(confirm|cancel))?$/);
        if (page && payments.has(page[1])) {
            const payment = payments.get(page[1]);

            if (request.method === 'POST' && page[2]) {
                payment.status = page[2] === 'confirm' ? 'SUCCESS' : 'FAILED';
                await notify(payment);
                response.writeHead(302, { Location: page[2] === 'confirm' ? payment.success_url : payment.failed_url });

                return response.end();
            }

            response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
            return response.end(`<!doctype html><title>CinetPay (test)</title><h1>Paiement de ${payment.amount} ${payment.currency}</h1>
                <p>${payment.designation}</p>
                <form method="post" action="/pay/${page[1]}/confirm"><button>Payer</button></form>
                <form method="post" action="/pay/${page[1]}/cancel"><button>Annuler</button></form>`);
        }

        json(response, 404, { code: 404, status: 'NOT_FOUND' });
    });

    return new Promise((resolve) => server.listen(FAKE_CINETPAY_PORT, '127.0.0.1', () => resolve(() => new Promise((done) => server.close(done)))));
}
