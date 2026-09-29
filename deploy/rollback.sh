#!/usr/bin/env bash
#
# Puts the previous version back online in one command (F-171):
#
#   bash ~/kova/current/deploy/rollback.sh
#
# Only the code goes back: the database keeps its schema (migrations are additive, so the previous version runs on
# it) and its data. Each release has its own cached configuration, routes and views, so nothing is rebuilt.

set -euo pipefail

BASE="${DEPLOY_PATH:-$HOME/kova}"
PHP="${PHP_BIN:-php}"

CURRENT="$(readlink "$BASE/current")"
CURRENT="${CURRENT%/}"
PREVIOUS=""

# Releases are named by date: the previous one is the most recent older than the current one.
for release in $(ls -1d "$BASE"/releases/*/ | sort -r); do
    release="${release%/}"

    if [[ "$release" < "$CURRENT" ]]; then
        PREVIOUS="$release"
        break
    fi
done

if [ -z "$PREVIOUS" ]; then
    echo "Aucune version antérieure à $CURRENT." >&2
    exit 1
fi

ln -sfn "$PREVIOUS" "$BASE/current.next"
mv -Tf "$BASE/current.next" "$BASE/current"
(cd "$PREVIOUS" && "$PHP" artisan queue:restart --no-interaction)

echo "Retour arrière effectué : $CURRENT -> $PREVIOUS"
