#!/usr/bin/env bash
#
# Backup of one environment (F-173): database dump + uploaded images + private files, in one archive under
# DEPLOY_PATH/backups, kept BACKUP_KEEP_DAYS days (7 by default). Prints the archive path on its last line.
#
#   bash ~/kova/current/deploy/backup.sh
#
# The backup workflow runs it every 6 hours (RPO 6 h), downloads the archive, encrypts it and stores it off the
# server: with the live data and the copy on the server, that makes 3 copies on 2 media, 1 off-site (3-2-1).

set -euo pipefail

BASE="${DEPLOY_PATH:-$HOME/kova}"
KEEP_DAYS="${BACKUP_KEEP_DAYS:-7}"
HERE="$(cd "$(dirname "$0")" && pwd)"

# shellcheck source=deploy/lib.sh
source "$HERE/lib.sh"

STAMP="$(date +%Y%m%d-%H%M%S)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
mkdir -p "$BASE/backups"

mysql_options_file "$BASE/shared/.env" "$WORK/my.cnf"
DATABASE="$(env_value "$BASE/shared/.env" DB_DATABASE)"

# DEFINER clauses (the "customers" view) name the production database user: removed, so that the dump imports
# into another account (preproduction, new hosting) without the SUPER privilege shared hosting never grants.
mysqldump --defaults-extra-file="$WORK/my.cnf" --single-transaction --quick --routines --no-tablespaces --default-character-set=utf8mb4 "$DATABASE" \
    | sed -E 's/DEFINER=`[^`]+`@`[^`]+`//g' \
    | gzip -9 > "$WORK/database.sql.gz"

tar -czf "$WORK/files.tar.gz" -C "$BASE/shared" uploads storage/app/private

ARCHIVE="$BASE/backups/kova-$(env_value "$BASE/shared/.env" APP_ENV)-$STAMP.tar.gz"
tar -czf "$ARCHIVE" -C "$WORK" database.sql.gz files.tar.gz
chmod 600 "$ARCHIVE"

find "$BASE/backups" -name 'kova-*.tar.gz' -mtime +"$KEEP_DAYS" -delete

echo "$ARCHIVE"
