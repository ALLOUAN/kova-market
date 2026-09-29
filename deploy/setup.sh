#!/usr/bin/env bash
#
# First installation on the hosting account, once per environment (production, preproduction):
#
#   DEPLOY_PATH=~/kova WEB_ROOT=~/htdocs bash setup.sh
#
# Creates the directory layout used by release.sh, an .env to complete, and points the web root to
# current/public (the existing web root is kept aside, never deleted). Prints the cron line to add in the
# hosting panel. Run it again safely: nothing existing is overwritten.

set -euo pipefail

BASE="${DEPLOY_PATH:-$HOME/kova}"
WEB_ROOT="${WEB_ROOT:?Indiquez WEB_ROOT, le dossier servi par le domaine (ex. ~/htdocs ou ~/preprod.example.ci)}"
PHP="${PHP_BIN:-php}"
HERE="$(cd "$(dirname "$0")" && pwd)"

mkdir -p "$BASE/releases" "$BASE/shared/uploads" "$BASE/shared/storage" "$BASE/backups" "$BASE/incoming"
chmod 700 "$BASE/backups"

if [ ! -f "$BASE/shared/.env" ]; then
    cp "$HERE/../.env.example" "$BASE/shared/.env" 2>/dev/null || touch "$BASE/shared/.env"
    chmod 600 "$BASE/shared/.env"
    echo "Créé : $BASE/shared/.env — complétez APP_KEY, APP_URL, APP_ENV, DB_*, MAIL_*, SMS_*, SENTRY_LARAVEL_DSN."
fi

if [ -L "$WEB_ROOT" ]; then
    echo "Racine web déjà liée : $WEB_ROOT -> $(readlink "$WEB_ROOT")"
else
    if [ -e "$WEB_ROOT" ]; then
        mv "$WEB_ROOT" "$WEB_ROOT.avant-kova-$(date +%Y%m%d%H%M%S)"
        echo "Ancienne racine web mise de côté : $WEB_ROOT.avant-kova-*"
    fi

    ln -s "$BASE/current/public" "$WEB_ROOT"
    echo "Racine web : $WEB_ROOT -> $BASE/current/public"
fi

echo
echo "Tâche cron à créer dans l'espace client (toutes les minutes) :"
echo "  cd $BASE/current && $PHP artisan schedule:run >> /dev/null 2>&1"
