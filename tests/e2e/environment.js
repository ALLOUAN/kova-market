import fs from 'node:fs';
import path from 'node:path';

/**
 * Environment of the application under test: its own SQLite file, nothing sent outside (SMS and e-mails in the log),
 * no cache kept between requests.
 *
 * `php artisan serve` passes only a few variables (APP_ENV among them) to the PHP server it starts, so the settings
 * go in a `.env.e2e` file, which Laravel loads instead of `.env` when APP_ENV=e2e: the tests can never reach the
 * database of the `.env`. Written when this module loads, before Playwright starts the server.
 */
export const database = path.resolve('database/e2e.sqlite');

const appKey = (() => {
    const line = fs.existsSync('.env') ? fs.readFileSync('.env', 'utf8').split(/\r?\n/).find((l) => l.startsWith('APP_KEY=')) : null;

    return line?.slice('APP_KEY='.length) || `base64:${Buffer.from(Array.from({ length: 32 }, () => Math.floor(Math.random() * 256))).toString('base64')}`;
})();

const settings = {
    APP_NAME: '"KOVA MARKET"',
    APP_ENV: 'e2e',
    APP_KEY: appKey,
    APP_DEBUG: 'true',
    APP_URL: 'http://127.0.0.1:8123',
    APP_LOCALE: 'fr',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: `"${database.replaceAll('\\', '/')}"`,
    CACHE_STORE: 'array',
    SESSION_DRIVER: 'file',
    QUEUE_CONNECTION: 'database',
    MAIL_MAILER: 'log',
    SMS_DRIVER: 'log',
    SCOUT_DRIVER: 'database',
    ADMIN_PATH: 'admin',
    LOG_CHANNEL: 'single',
    // The fake CinetPay of tests/e2e/fake-cinetpay.js.
    CINETPAY_API_KEY: 'e2e-key',
    CINETPAY_API_PASSWORD: 'e2e-password',
    CINETPAY_BASE_URL: 'http://127.0.0.1:8124',
    CINETPAY_FALLBACK_EMAIL: 'paiements@kova.test',
    // Its payment page lives on another origin than CinetPay's real one: the policy only reports there.
    SECURITY_CSP_REPORT_ONLY: 'true',
};

fs.writeFileSync('.env.e2e', `${Object.entries(settings).map(([key, value]) => `${key}=${value}`).join('\n')}\n`);

/**
 * Environment of the server and of the artisan commands run by the tests themselves (global setup). Several PHP
 * workers where the built-in server supports them (not on Windows), for the pages that call back (Livewire).
 */
export default { ...process.env, APP_ENV: 'e2e', PHP_CLI_SERVER_WORKERS: process.platform === 'win32' ? '1' : '4' };
