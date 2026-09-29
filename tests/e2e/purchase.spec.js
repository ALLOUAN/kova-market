import { expect, test } from '@playwright/test';

test('un visiteur achète un produit en paiement à la livraison, puis suit sa commande', async ({ page }) => {
    await page.goto('/boutique');
    // "Pertinence" lists the products in stock first.
    await page.goto(await page.getByRole('main').locator('.rbt-product-card .rbt-card-title a').first().getAttribute('href'));
    await expect(page).toHaveURL(/\/produit\//);

    await page.getByRole('button', { name: 'Acheter maintenant' }).click();
    await expect(page).toHaveURL(/\/panier$/);

    // Choosing the commune reloads the cart with its delivery fee.
    const commune = page.locator('#cart-commune');
    const firstCommune = await commune.locator('option:not([value=""])').first().getAttribute('value');
    await commune.selectOption(firstCommune);
    await page.getByRole('main').getByRole('link', { name: 'Commander' }).click();
    await expect(page).toHaveURL(/\/commande$/);

    await page.locator('#customer_name').fill('Awa Koné');
    await page.locator('#phone').fill('07 01 02 03 04');
    await page.locator('#district').fill('Riviera 2');
    // The theme draws its own box over the real checkbox, and its label holds links: tick the box itself.
    await page.locator('#terms').check({ force: true });
    await page.getByRole('button', { name: 'Valider ma commande' }).click();

    await expect(page).toHaveURL(/\/commande\/KM-\d{6}-\d{4}\/merci$/);
    const number = page.url().match(/KM-\d{6}-\d{4}/)[0];
    await expect(page.getByText(number).first()).toBeVisible();

    await page.goto('/suivi');
    await page.locator('#number').fill(number.toLowerCase());
    await page.locator('#phone').fill('+225 0701020304');
    await page.getByRole('button', { name: 'Suivre' }).click();
    await expect(page.getByRole('heading', { name: `Commande ${number}` })).toBeVisible();
    await expect(page.getByText('Reçue').first()).toBeVisible();
});

test('une commande sans acceptation des conditions de vente est refusée, le formulaire gardé', async ({ page }) => {
    await page.goto('/boutique');
    await page.goto(await page.getByRole('main').locator('.rbt-product-card .rbt-card-title a').first().getAttribute('href'));
    await Promise.all([
        page.waitForResponse((response) => response.url().endsWith('/panier/articles')),
        page.getByRole('button', { name: 'Ajouter au panier' }).click(),
    ]);

    await page.goto('/commande');
    await page.locator('#customer_name').fill('Awa Koné');
    await page.locator('#phone').fill('07 01 02 03 04');
    const commune = page.locator('#commune_id');
    await commune.selectOption(await commune.locator('option:not([value=""])').first().getAttribute('value'));
    await page.locator('#district').fill('Riviera 2');
    await page.getByRole('button', { name: 'Valider ma commande' }).click();

    await expect(page.getByText('Merci d’accepter les conditions générales de vente')).toBeVisible();
    await expect(page.locator('#customer_name')).toHaveValue('Awa Koné');
});
