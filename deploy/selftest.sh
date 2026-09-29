#!/usr/bin/env bash
#
# End-to-end check of the deployment scripts, run by the CI on a Linux runner with MySQL (F-171, F-173):
# two releases, a rollback, a backup, lost data, a restore. Needs a built project (composer install done) and
# a MySQL database reachable with the DB_* variables below.
#
#   DB_DATABASE=kova DB_USERNAME=kova DB_PASSWORD=secret bash deploy/selftest.sh

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TMP="$(mktemp -d)"
export DEPLOY_PATH="$TMP/kova"
export PHP_BIN="${PHP_BIN:-php}"
trap 'rm -rf "$TMP"' EXIT

pass() { printf '  ok  %s\n' "$*"; }
fail() { printf '  ÉCHEC  %s\n' "$*" >&2; exit 1; }

build() {
    tar --exclude=./.git --exclude=./node_modules --exclude=./tests --exclude=./storage --exclude=./public/uploads --exclude=./.env \
        -czf "$1" -C "$ROOT" .
}

query() {
    mysql -h "${DB_HOST:-127.0.0.1}" -u "$DB_USERNAME" -p"$DB_PASSWORD" -N -B "$DB_DATABASE" -e "$1"
}

echo "Installation"
WEB_ROOT="$TMP/htdocs" bash "$ROOT/deploy/setup.sh" > /dev/null
cat > "$DEPLOY_PATH/shared/.env" <<EOF
APP_NAME="KOVA MARKET"
APP_ENV=staging
APP_KEY=base64:$(head -c 32 /dev/urandom | base64)
APP_DEBUG=false
APP_URL=http://localhost
DB_CONNECTION=mysql
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=$DB_DATABASE
DB_USERNAME=$DB_USERNAME
DB_PASSWORD=$DB_PASSWORD
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
SMS_DRIVER=log
MAIL_MAILER=log
EOF
[ "$(readlink "$TMP/htdocs")" = "$DEPLOY_PATH/current/public" ] && pass "racine web liée à current/public" || fail "racine web"

echo "Première version"
build "$TMP/r1.tar.gz"
bash "$ROOT/deploy/release.sh" "$TMP/r1.tar.gz" > /dev/null
FIRST="$(readlink "$DEPLOY_PATH/current")"
[ -f "$DEPLOY_PATH/current/artisan" ] && pass "version en ligne : $(basename "$FIRST")" || fail "première version"
[ "$(readlink "$DEPLOY_PATH/current/.env")" = "$DEPLOY_PATH/shared/.env" ] && pass ".env partagé" || fail ".env partagé"
[ -f "$DEPLOY_PATH/current/bootstrap/cache/config.php" ] && pass "configuration en cache" || fail "cache de configuration"
[ "$(query "SELECT COUNT(*) FROM migrations")" -gt 0 ] && pass "migrations jouées" || fail "migrations"

echo "Deuxième version puis retour arrière"
sleep 1
build "$TMP/r2.tar.gz"
bash "$ROOT/deploy/release.sh" "$TMP/r2.tar.gz" > /dev/null
SECOND="$(readlink "$DEPLOY_PATH/current")"
[ "$SECOND" != "$FIRST" ] && pass "nouvelle version en ligne : $(basename "$SECOND")" || fail "deuxième version"
bash "$DEPLOY_PATH/current/deploy/rollback.sh" > /dev/null
[ "$(readlink "$DEPLOY_PATH/current")" = "$FIRST" ] && pass "retour arrière vers $(basename "$FIRST")" || fail "retour arrière"

echo "Sauvegarde puis restauration"
query "INSERT INTO settings (\`key\`, value, created_at, updated_at) VALUES ('selftest.marker', 'avant', NOW(), NOW())"
echo "image" > "$DEPLOY_PATH/shared/uploads/test.webp"
ARCHIVE="$(bash "$DEPLOY_PATH/current/deploy/backup.sh" | tail -n 1)"
[ -f "$ARCHIVE" ] && pass "sauvegarde : $(basename "$ARCHIVE")" || fail "sauvegarde"

query "DELETE FROM settings WHERE \`key\` = 'selftest.marker'"
rm "$DEPLOY_PATH/shared/uploads/test.webp"
bash "$DEPLOY_PATH/current/deploy/restore.sh" "$ARCHIVE" > /dev/null
[ "$(query "SELECT value FROM settings WHERE \`key\` = 'selftest.marker'")" = "avant" ] && pass "données restaurées" || fail "données restaurées"
[ -f "$DEPLOY_PATH/shared/uploads/test.webp" ] && pass "images restaurées" || fail "images restaurées"
[ "$(query "SELECT COUNT(*) FROM customers")" -ge 0 ] && pass "vue customers restaurée" || fail "vue customers"

echo "Tout est bon."
