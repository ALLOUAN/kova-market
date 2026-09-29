import { defineConfig, devices } from '@playwright/test';

/**
 * End-to-end tests (section G of the specification): real browser journeys against the application served by
 * `php artisan serve` on a throwaway SQLite database, seeded with the demo catalog (tests/e2e/global-setup.js).
 * Run with `npm run e2e` (PHP_BIN chooses the PHP binary, `php` by default).
 */
const port = 8123;

export default defineConfig({
    testDir: 'tests/e2e',
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : 'list',
    globalSetup: './tests/e2e/global-setup.js',
    timeout: 60_000,
    use: {
        baseURL: `http://127.0.0.1:${port}`,
        locale: 'fr-FR',
        trace: 'retain-on-failure',
        // The consent banner is answered ("Refuser") for every test but the consent one.
        storageState: 'tests/e2e/consent-denied.json',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    webServer: {
        command: `"${process.env.PHP_BIN || 'php'}" artisan serve --host=127.0.0.1 --port=${port}`,
        url: `http://127.0.0.1:${port}/up`,
        reuseExistingServer: false,
        timeout: 60_000,
        env: (await import('./tests/e2e/environment.js')).default,
    },
});
