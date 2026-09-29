#!/usr/bin/env bash
#
# Puts a new version online without interruption (F-171). Run on the server by the deploy workflow:
#
#   bash release.sh /path/to/release.tar.gz
#
# Layout under DEPLOY_PATH (default ~/kova):
#   releases/<timestamp>/   one directory per version, the 5 most recent are kept
#   shared/.env             the environment file, never in a release
#   shared/storage/         logs, sessions, cache, private files (sitemap, CSV imports)
#   shared/uploads/         images uploaded from the back-office (served from public/uploads)
#   current -> releases/…   the version online; the web root points to current/public
#
# The new version is prepared entirely (files, migrations, caches) while the previous one keeps serving, then the
# "current" link is switched in one atomic step. Migrations must stay additive so the previous version still runs
# against the new schema (and after a rollback). If HEALTHCHECK_URL is set and does not answer 200 after the
# switch, the previous version is put back automatically.

set -euo pipefail

ARCHIVE="${1:?Usage: release.sh <release.tar.gz>}"
BASE="${DEPLOY_PATH:-$HOME/kova}"
PHP="${PHP_BIN:-php}"
KEEP="${KEEP_RELEASES:-5}"

RELEASE="$BASE/releases/$(date +%Y%m%d%H%M%S)"
PREVIOUS="$(readlink "$BASE/current" 2>/dev/null || true)"

log() { printf '[%s] %s\n' "$(date +%H:%M:%S)" "$*"; }

if [ ! -f "$BASE/shared/.env" ]; then
    echo "Fichier $BASE/shared/.env absent : lancez d'abord deploy/setup.sh puis complétez-le." >&2
    exit 1
fi

log "Préparation de $RELEASE"
mkdir -p "$RELEASE" "$BASE/shared/uploads" \
    "$BASE/shared/storage/app/private" "$BASE/shared/storage/app/public" "$BASE/shared/storage/logs" \
    "$BASE/shared/storage/framework/cache/data" "$BASE/shared/storage/framework/sessions" "$BASE/shared/storage/framework/views"
tar -xzf "$ARCHIVE" -C "$RELEASE"

# Everything that must survive a new version lives in shared/.
rm -rf "$RELEASE/storage" "$RELEASE/public/uploads" "$RELEASE/.env"
ln -s "$BASE/shared/storage" "$RELEASE/storage"
ln -s "$BASE/shared/uploads" "$RELEASE/public/uploads"
ln -s "$BASE/shared/.env" "$RELEASE/.env"

cd "$RELEASE"
log "Migrations"
"$PHP" artisan migrate --force --no-interaction
log "Mise en cache de la configuration, des routes, des vues et de Filament"
"$PHP" artisan optimize --no-interaction
"$PHP" artisan filament:optimize --no-interaction

switch_to() {
    ln -sfn "$1" "$BASE/current.next"
    mv -Tf "$BASE/current.next" "$BASE/current"
}

log "Bascule vers la nouvelle version"
switch_to "$RELEASE"
# Workers started by the scheduler finish their job and restart on the new code.
"$PHP" artisan queue:restart --no-interaction

if [ -n "${HEALTHCHECK_URL:-}" ]; then
    sleep 2
    status="$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$HEALTHCHECK_URL" || true)"

    if [ "$status" != "200" ]; then
        log "Contrôle de santé en échec ($HEALTHCHECK_URL : $status)"

        if [ -n "$PREVIOUS" ] && [ -d "$PREVIOUS" ]; then
            switch_to "$PREVIOUS"
            (cd "$PREVIOUS" && "$PHP" artisan queue:restart --no-interaction)
            log "Version précédente remise en ligne : $PREVIOUS"
        fi

        exit 1
    fi

    log "Contrôle de santé OK"
fi

log "Nettoyage des anciennes versions (conservées : $KEEP)"
ls -1dt "$BASE"/releases/*/ | tail -n +$((KEEP + 1)) | xargs -r rm -rf
rm -f "$ARCHIVE"

log "Version en ligne : $RELEASE"
