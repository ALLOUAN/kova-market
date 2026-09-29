# Deployment and operations

Hosting: LWS shared hosting with SSH access (decision C-12). Two environments on the same account, each in its own
directory with its own database and domain:

| Environment | `APP_ENV` | Directory (example) | Domain (example) |
| --- | --- | --- | --- |
| Production | `production` | `~/kova` | `kovamarket.ci` |
| Preproduction | `staging` | `~/kova-preprod` | `preprod.kovamarket.ci` |

Shared hosting runs no permanent process: no Redis, no Horizon, no Supervisor. Queued notifications stay in the
`database` queue and the scheduler (host cron, every minute) empties it with `queue:work --stop-when-empty`.

## First installation (once per environment)

1. **LWS panel**: create the MySQL database and its user, the domain or sub-domain, choose PHP 8.4 for it, and enable
   SSH with a key (password login off). Check that the command-line PHP is 8.4 too (`php -v` over SSH); if not, note
   the right binary (e.g. `php8.4`) for `PHP_BIN`.
2. **Directories and web root**: copy `deploy/` and `.env.example` to the account, then
   `DEPLOY_PATH=~/kova WEB_ROOT=~/htdocs bash deploy/setup.sh` (use the folder the domain serves as `WEB_ROOT`; the
   existing one is kept aside, never deleted).
3. **`~/kova/shared/.env`**: `APP_ENV`, `APP_KEY` (`php artisan key:generate --show` on any copy), `APP_URL`,
   `APP_DEBUG=false`, `DB_CONNECTION=mysql` and `DB_*`, `CACHE_STORE=database`, `SESSION_DRIVER=database`,
   `QUEUE_CONNECTION=database`, `MAIL_*`, `SMS_*`, `ADMIN_PATH` (non-standard), `TURNSTILE_*`, `SENTRY_LARAVEL_DSN`,
   `CINETPAY_API_KEY` / `CINETPAY_API_PASSWORD` (KOVA MARKET's own CinetPay account, test keys on the preproduction)
   and `CINETPAY_FALLBACK_EMAIL`. CinetPay must reach `https://…/paiement/cinetpay/notification`: the site must be
   public over HTTPS (the preproduction lets that one address through its login).
   Preproduction also sets `PREPROD_USER` / `PREPROD_PASSWORD`.
4. **Cron** (LWS panel, every minute): `cd ~/kova/current && php artisan schedule:run >> /dev/null 2>&1`
   (`setup.sh` prints the exact line).
5. **GitHub** (Settings › Environments): create `preproduction` and `production`, each with the secrets `SSH_HOST`,
   `SSH_PORT`, `SSH_USER`, `SSH_PRIVATE_KEY`, `SSH_KNOWN_HOSTS` (`ssh-keyscan -p PORT HOST`), `BACKUP_PASSPHRASE`, and
   the variables `DEPLOY_PATH`, `PHP_BIN`, `HEALTHCHECK_URL` (`https://…/up`). Production: add yourself as required
   reviewer, plus `RCLONE_CONFIG` / `BACKUP_REMOTE` for the off-site backups. Preproduction: `STOREFRONT_URL` and the
   `PREPROD_USER` / `PREPROD_PASSWORD` secrets.
6. **First deploy** (below), then once on the server: `php artisan db:seed --force` and
   `php artisan app:create-super-admin` (see `docs/back-office.md`).

## Deploy and roll back (F-171)

- **Preproduction**: every push to `main` is tested (tests, dependency audit, deployment scripts on MySQL) then
  deployed.
- **Production**: Actions › Deploy › Run workflow › `production` (waits for the approval if a reviewer is set).
- **Rollback**: Actions › Rollback › Run workflow, or on the server `bash ~/kova/current/deploy/rollback.sh`.

`deploy/release.sh` unpacks the version in `releases/<date>`, links `shared/.env`, `shared/storage` and
`shared/uploads`, runs the migrations and caches, then switches the `current` link in one step: visitors never see a
half-deployed site. If `HEALTHCHECK_URL` does not answer 200 afterwards, the previous version is put back
automatically. The 5 latest versions are kept. Migrations must stay **additive** (new tables and nullable columns,
no renames or drops in the same release) so that the previous version still runs after a rollback; the database is
never rolled back.

## Monitoring (F-172)

- **Errors**: Sentry, once `SENTRY_LARAVEL_DSN` is set (one project, `SENTRY_ENVIRONMENT` tells production from
  preproduction). No personal data is sent.
- **Scheduler**: the queue worker reports to the Sentry cron monitor `file-attente`; Sentry alerts when the host's
  cron stops (orders would then wait for their SMS).
- **Availability**: add `https://kovamarket.ci/up` to an external uptime monitor (UptimeRobot, Better Stack…, free
  plans suffice), alert by e-mail and SMS.
- **Logs**: `~/kova/shared/storage/logs/laravel.log`.

## Backups (F-173) — RPO 6 h, RTO 4 h

| Copy | Where | Kept |
| --- | --- | --- |
| 1 | the live database and `shared/uploads` | — |
| 2 | `~/kova/backups/kova-production-<date>.tar.gz` on the hosting | 7 days |
| 3 | off-site storage (rclone), encrypted with `BACKUP_PASSPHRASE` | 90 days |

The Backup workflow runs every 6 hours: `deploy/backup.sh` dumps the database and packs the uploaded images and
private files, the archive is downloaded, encrypted and copied off-site. LWS's own daily backups add a fourth copy.
**Keep `BACKUP_PASSPHRASE` somewhere else than GitHub**: without it the off-site copies cannot be read.

**Restore test, every month**: the Restore test workflow restores a fresh production backup into the preproduction and
checks that it answers; it fails (and GitHub e-mails the admins) if the backup is unusable. The time taken is in its
log. The preproduction then holds real customer data: it stays behind its login, is never indexed, and sends no
e-mail or SMS (forced to the log whatever its `.env` says).

**Disaster**: decrypt an off-site copy (`gpg --decrypt kova-production-….tar.gz.gpg > backup.tar.gz`), copy it to the
server, then `RESTORE_PRODUCTION=oui bash ~/kova/current/deploy/restore.sh backup.tar.gz` (the store shows the
maintenance page meanwhile; the replaced images are kept in `shared/uploads.avant-restauration-*`). On a new hosting,
run the first installation, deploy, then restore.

## Server security on shared hosting (F-147)

LWS manages the server itself: firewall, intrusion protection, operating system updates. The specification's UFW,
Fail2ban and root SSH settings therefore do not apply; what stays in our hands:

- SSH by key only, password login disabled in the panel; one deploy key per environment, stored only in GitHub.
- Only `current/public` is served: `.env`, `storage` and the code are outside the web root.
- `shared/.env` and `backups/` readable by the account only (`setup.sh` sets 600 / 700).
- A non-standard `ADMIN_PATH`, two-factor authentication for staff, HTTPS forced (LWS Let's Encrypt certificate).
- Dependencies audited on every deploy and every Monday (CI); `composer update` and a redeploy once a month.
