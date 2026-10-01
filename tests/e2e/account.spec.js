import { expect, test } from '@playwright/test';

test('un visiteur crée son compte par téléphone et retrouve son espace client', async ({ page }) => {
    // A page that needs an account opens the sign-in window; "Créer un compte" switches to sign-up.
    await page.goto('/?connexion=1');
    await page.locator('#signinModal').getByRole('button', { name: 'Créer un compte' }).click();

    const signup = page.locator('#signupModal');
    await expect(signup).toBeVisible();
    await signup.locator('#modal_register_name').fill('Yao Kouassi');
    await signup.locator('#modal_register_number').fill('05 06 07 08 09');
    await signup.locator('#modal_register_password').fill('mot-de-passe-e2e');
    await signup.locator('#modal_register_password_confirmation').fill('mot-de-passe-e2e');
    await signup.getByRole('button', { name: 'Créer mon compte' }).click();

    // The new customer lands straight on the account dashboard, welcomed.
    await expect(page).toHaveURL(/\/compte$/);
    await expect(page.getByText('votre compte est créé')).toBeVisible();
    await expect(page.locator('#profile_name')).toHaveValue('Yao Kouassi');
});

test('le mot de passe oublié par téléphone mène à la saisie du code', async ({ page }) => {
    await page.goto('/?connexion=1');
    await page.locator('#signinModal').getByRole('link', { name: 'Mot de passe oublié ?' }).click();

    await page.locator('#login').fill('07 99 99 99 99');
    await page.getByRole('button', { name: 'Recevoir mon code ou mon lien' }).click();

    // Same answer whether or not the number has an account.
    await expect(page).toHaveURL(/\/mot-de-passe-oublie\/code$/);
    await expect(page.getByText('Si un compte correspond au +225 07 99 99 99 99')).toBeVisible();
});
