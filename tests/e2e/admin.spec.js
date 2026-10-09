import { expect, test } from '@playwright/test';
import { ADMIN_TOTP_SECRET, totp } from './support.js';

test('le super-admin se connecte avec sa double authentification et voit le catalogue', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByRole('textbox', { name: /Adresse e-mail/ }).fill('admin-kovamarket@mail.com');
    await page.getByRole('textbox', { name: /Mot de passe/ }).fill('123456789');
    await page.getByRole('button', { name: 'Connexion' }).click();

    // Second step: the code of the authenticator app.
    const code = page.getByRole('textbox').first();
    await expect(code).toBeVisible();
    await code.fill(totp(ADMIN_TOTP_SECRET));
    await page.getByRole('button', { name: 'Confirmer la connexion' }).click();

    // The first back-office page compiles its components: slower on the PHP built-in server.
    await expect(page.getByText('Produits à réapprovisionner')).toBeVisible({ timeout: 30_000 });

    await page.goto('/admin/products');
    await expect(page.getByRole('heading', { name: /Produits/ })).toBeVisible();
    await expect(page.locator('table tbody tr').first()).toBeVisible();
});
