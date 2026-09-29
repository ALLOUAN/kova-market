import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import environment, { database } from './environment.js';
import { startFakeCinetPay } from './fake-cinetpay.js';

/**
 * A fresh database for every run: migrations, the demo catalog, a known authenticator secret for the super-admin
 * (tests/e2e/support.js computes its codes) and fake tracker identifiers so the consent banner is live. A fake
 * CinetPay (tests/e2e/fake-cinetpay.js) answers the payments; it is stopped when the run ends.
 */
export default async function globalSetup() {
    const stopFakeCinetPay = await startFakeCinetPay();

    fs.writeFileSync(database, '');

    const artisan = (...args) => execFileSync(process.env.PHP_BIN || 'php', ['artisan', ...args, '--no-interaction'], { env: environment, stdio: 'inherit' });

    artisan('migrate:fresh', '--seed', '--force');
    artisan('tinker', '--execute', [
        "App\\Models\\User::where('email', 'test@example.com')->firstOrFail()->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');",
        "App\\Models\\Setting::store(['analytics.ga4_id' => 'G-E2ETEST01', 'analytics.meta_pixel_id' => '123456789012345', 'analytics.tiktok_pixel_id' => 'CE2ETEST000000']);",
    ].join(' '));

    return stopFakeCinetPay;
}
