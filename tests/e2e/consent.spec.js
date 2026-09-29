import { expect, test } from '@playwright/test';

/**
 * F-156 acceptance, in a real browser: without consent, no request reaches an advertising domain.
 * Requests to those domains are recorded and stopped (the test never calls Google, Meta or TikTok for real).
 */
const ADVERTISING = /googletagmanager\.com|google-analytics\.com|analytics\.google\.com|facebook\.(com|net)|tiktok\.com/;

test.use({ storageState: { cookies: [], origins: [] } });

async function recordAdvertising(page) {
    const requests = [];
    await page.route(ADVERTISING, (route) => {
        requests.push(route.request().url());
        return route.abort();
    });

    return requests;
}

test('rien n’est demandé aux régies avant « Accepter », puis les trois traceurs se chargent', async ({ page, context }) => {
    const requests = await recordAdvertising(page);
    await page.goto('/');

    const banner = page.locator('[data-cookie-banner]');
    await expect(banner).toHaveClass(/isVisible/);
    await page.goto('/boutique');
    await expect(banner).toHaveClass(/isVisible/);
    expect(requests).toEqual([]);

    await banner.getByRole('button', { name: 'Accepter' }).click();
    await expect(banner).not.toHaveClass(/isVisible/);
    await expect.poll(() => requests.length).toBeGreaterThanOrEqual(3);
    expect(requests.some((url) => url.includes('googletagmanager.com/gtag/js?id=G-E2ETEST01'))).toBe(true);
    expect(requests.some((url) => url.includes('connect.facebook.net'))).toBe(true);
    expect(requests.some((url) => url.includes('analytics.tiktok.com'))).toBe(true);

    const consent = (await context.cookies()).find((cookie) => cookie.name === 'kova_consent');
    expect(consent?.value).toBe('granted');

    // The choice is kept: the next page loads the trackers at once, without the banner.
    requests.length = 0;
    await page.goto('/boutique');
    await expect.poll(() => requests.length).toBeGreaterThanOrEqual(3);
    await expect(banner).not.toHaveClass(/isVisible/);
});

test('« Refuser » : aucun traceur, ni sur cette page ni sur les suivantes', async ({ page }) => {
    const requests = await recordAdvertising(page);
    await page.goto('/');

    const banner = page.locator('[data-cookie-banner]');
    await expect(banner).toHaveClass(/isVisible/);
    await banner.getByRole('button', { name: 'Refuser', exact: true }).click();
    await page.goto('/boutique');
    await page.goto('/');
    // Time for any tracker script to start (the page polls, so "network idle" never comes).
    await page.waitForTimeout(2000);

    expect(requests).toEqual([]);
    await expect(banner).not.toHaveClass(/isVisible/);
});

test('« Gérer les cookies » rouvre le bandeau pour changer d’avis', async ({ page }) => {
    await recordAdvertising(page);
    await page.goto('/');
    const banner = page.locator('[data-cookie-banner]');
    await expect(banner).toHaveClass(/isVisible/);
    await banner.getByRole('button', { name: 'Refuser', exact: true }).click();
    await expect(banner).not.toHaveClass(/isVisible/);

    await page.getByRole('button', { name: 'Gérer les cookies' }).click();
    await expect(page.locator('[data-cookie-banner]')).toHaveClass(/isVisible/);
});
