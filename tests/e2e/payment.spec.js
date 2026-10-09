import { expect, test } from '@playwright/test';
import { ADMIN_TOTP_SECRET, totp } from './support.js';

/**
 * F-060 to F-066 in a real browser, against the fake CinetPay: product → cart → order → initiation → redirection to
 * the payment page → payment → notification (server to server) → status check → order paid → confirmation.
 */
async function orderToPayOnline(page) {
    await page.goto('/boutique');
    await page.goto(await page.getByRole('main').locator('.rbt-product-card .rbt-card-title a').first().getAttribute('href'));
    await Promise.all([
        page.waitForResponse((response) => response.url().endsWith('/panier/articles')),
        page.getByRole('button', { name: 'Ajouter au panier' }).click(),
    ]);

    await page.goto('/commande');
    await page.locator('#customer_name').fill('Awa Koné');
    await page.locator('#phone').fill('07 07 07 07 00');
    const commune = page.locator('#commune_id');
    await commune.selectOption(await commune.locator('option:not([value=""])').first().getAttribute('value'));
    await page.locator('#district').fill('Riviera 2');
    await page.locator('#payment-cinetpay').check({ force: true });
    await page.locator('#terms').check({ force: true });
    await page.getByRole('button', { name: 'Valider ma commande' }).click();

    // On CinetPay's page (the fake one).
    await expect(page).toHaveURL(/127\.0\.0\.1:8124\/pay\//);
    await expect(page.getByRole('heading', { name: /Paiement de \d+ XOF/ })).toBeVisible();
}

test('le client paie en ligne et revient sur sa commande confirmée', async ({ page }) => {
    await orderToPayOnline(page);

    await page.getByRole('button', { name: 'Payer' }).click();

    await expect(page).toHaveURL(/\/commande\/KM-\d{6}-\d{4}\/merci$/);
    await expect(page.getByText('Paiement reçu, merci ! Votre commande est enregistrée.')).toBeVisible();
    await expect(page.getByText('Paiement reçu : votre commande est enregistrée.')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Payer maintenant' })).toHaveCount(0);

    // The back-office's payments space shows it, with CinetPay's journal.
    const number = page.url().match(/KM-\d{6}-\d{4}/)[0];
    await page.goto('/admin/login');
    await page.getByRole('textbox', { name: /Adresse e-mail/ }).fill('admin-kovamarket@mail.com');
    await page.getByRole('textbox', { name: /Mot de passe/ }).fill('123456789');
    await page.getByRole('button', { name: 'Connexion' }).click();
    const code = page.getByRole('textbox').first();
    await expect(code).toBeVisible();
    // Next 30 s step: the current code may already have been used by the other back-office journey.
    await code.fill(totp(ADMIN_TOTP_SECRET, Date.now() + 30_000));
    await page.getByRole('button', { name: 'Confirmer la connexion' }).click();
    await expect(page.getByText('Produits à réapprovisionner')).toBeVisible({ timeout: 30_000 });

    await page.goto('/admin/payments');
    await expect(page.getByText('Encaissé aujourd’hui')).toBeVisible({ timeout: 30_000 });
    const row = page.locator('table tbody tr', { hasText: number });
    await expect(row.getByText('Réussi')).toBeVisible();

    await row.getByRole('link', { name: 'Ouvrir' }).click();
    await expect(page.getByText('Paiement confirmé par CinetPay')).toBeVisible();
});

test('un paiement abandonné laisse la commande en attente, puis le client paie', async ({ page }) => {
    await orderToPayOnline(page);

    await page.getByRole('button', { name: 'Annuler' }).click();

    await expect(page).toHaveURL(/\/commande\/KM-\d{6}-\d{4}\/merci$/);
    await expect(page.getByText('Le paiement n’a pas abouti. Vous pouvez réessayer.')).toBeVisible();

    await page.getByRole('button', { name: 'Payer maintenant' }).click();
    await expect(page).toHaveURL(/127\.0\.0\.1:8124\/pay\//);
    await page.getByRole('button', { name: 'Payer' }).click();

    await expect(page.getByText('Paiement reçu : votre commande est enregistrée.')).toBeVisible();
});
