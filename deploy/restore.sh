#!/usr/bin/env bash
#
# Restores a backup made by backup.sh into this environment (F-173): database, uploaded images, private files.
#
#   bash ~/kova-preprod/current/deploy/restore.sh /path/to/kova-production-20261001-060000.tar.gz
#
# Meant for the preproduction (monthly restore test) and, in a disaster, for the production: there it asks for
# RESTORE_PRODUCTION=oui, since the current data are replaced. The images replaced are kept aside in
# shared/uploads.avant-restauration-*. Pending migrations run afterwards, in case the backup is older than the code.

set -euo pipefail

ARCHIVE="${1:?Usage: restore.sh <sauvegarde.tar.gz>}"
BASE="${DEPLOY_PATH:-$HOME/kova}"
PHP="${PHP_BIN:-php}"
HERE="$(cd "$(dirname "$0")" && pwd)"

# shellcheck source=deploy/lib.sh
source "$HERE/lib.sh"

ENVIRONMENT="$(env_value "$BASE/shared/.env" APP_ENV)"

if [ "$ENVIRONMENT" = "production" ] && [ "${RESTORE_PRODUCTION:-}" != "oui" ]; then
    echo "Restauration sur la PRODUCTION : les données actuelles seront remplacées. Relancez avec RESTORE_PRODUCTION=oui." >&2
    exit 1
fi

START="$(date +%s)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

tar -xzf "$ARCHIVE" -C "$WORK"
mysql_options_file "$BASE/shared/.env" "$WORK/my.cnf"
DATABASE="$(env_value "$BASE/shared/.env" DB_DATABASE)"

cd "$BASE/current"
"$PHP" artisan down --retry=60 --no-interaction || true

echo "Base de données : $DATABASE"
gunzip -c "$WORK/database.sql.gz" | mysql --defaults-extra-file="$WORK/my.cnf" "$DATABASE"

echo "Images et fichiers privés"
mkdir -p "$WORK/files"
tar -xzf "$WORK/files.tar.gz" -C "$WORK/files"
if [ -d "$BASE/shared/uploads" ] && [ -n "$(ls -A "$BASE/shared/uploads")" ]; then
    mv "$BASE/shared/uploads" "$BASE/shared/uploads.avant-restauration-$(date +%Y%m%d%H%M%S)"
fi
rm -rf "$BASE/shared/uploads"
mv "$WORK/files/uploads" "$BASE/shared/uploads"
rm -rf "$BASE/shared/storage/app/private"
mv "$WORK/files/storage/app/private" "$BASE/shared/storage/app/private"

"$PHP" artisan migrate --force --no-interaction
"$PHP" artisan cache:clear --no-interaction
"$PHP" artisan up --no-interaction

echo "Restauration terminée en $(( $(date +%s) - START )) s (objectif RTO : 4 h)."
