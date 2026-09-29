import { expect, test } from '@playwright/test';

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
