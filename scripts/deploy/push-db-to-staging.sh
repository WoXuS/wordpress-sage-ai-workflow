#!/usr/bin/env bash
#
# Push the local dev database → staging (OVERWRITES the staging DB).
#
# On-demand full clone: while the site is being built, staging mirrors dev 1:1.
# Dumps the dev DB from the local `php` container, ships it over SSH, imports it
# on staging, then search-replaces the dev URL with the staging URL. The staging
# .env / wp-config (WP_HOME constant, DB creds) are untouched — only DB rows move.
#
# After this runs, the staging admin login becomes the DEV admin (e.g. admin/admin).
#
# Usage:
#   make push-db-staging
#   ./scripts/deploy/push-db-to-staging.sh          # from repo root
#
# Requires: local containers up (`make up`), the deploy key in certs/.

set -euo pipefail

# --- config (override via env) ---
SSH_KEY="${SSH_KEY:-certs/deploy_staging}"
SSH_HOST="${SSH_HOST:-s156.cyber-folks.pl}"
SSH_USER="${SSH_USER:-arpinetwork}"
SSH_PORT="${SSH_PORT:-222}"
STAGING_DOC="${STAGING_DOC:-/home/arpinetwork/domains/staging.arpinetwork.cfolks.pl/public_html}"
STAGING_URL="${STAGING_URL:-https://staging.arpinetwork.cfolks.pl}"
REMOTE_DUMP="/tmp/arpi-dev-dump.sql"
LOCAL_DUMP="$(mktemp -t arpi-dev-dump.XXXXXX.sql)"

SSH_BASE="ssh -i $SSH_KEY -p $SSH_PORT -o BatchMode=yes ${SSH_USER}@${SSH_HOST}"
# wp-cli on cyberFolks must run under PHP 8.3 (default CLI is 8.2, theme needs 8.3)
REMOTE_WP="/opt/alt/php83/usr/bin/php \$(command -v wp) --path=$STAGING_DOC"

cleanup() { rm -f "$LOCAL_DUMP"; }
trap cleanup EXIT

echo "1/5  Dump lokalnej bazy dev (kontener db)…"
DEV_URL="$(docker compose exec -T php wp option get home --allow-root | tr -d '\r\n')"
# Dump prosto z kontenera db — mariadb-dump przez wp-cli wymusza TLS, którego lokalny serwer nie ma.
docker compose exec -T db sh -c 'exec mariadb-dump --no-tablespaces --default-character-set=utf8mb4 -u root -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' > "$LOCAL_DUMP"
echo "     dev URL = ${DEV_URL}   ($(wc -c < "$LOCAL_DUMP" | tr -d ' ') bajtów)"

echo "2/5  Transfer na staging…"
scp -q -i "$SSH_KEY" -P "$SSH_PORT" "$LOCAL_DUMP" "${SSH_USER}@${SSH_HOST}:${REMOTE_DUMP}"

echo "3/5  Import na staging (nadpisuje bazę)…"
$SSH_BASE "$REMOTE_WP db import $REMOTE_DUMP && rm -f $REMOTE_DUMP"

echo "4/5  search-replace ${DEV_URL} → ${STAGING_URL} + ustawienia staging…"
$SSH_BASE "
  $REMOTE_WP search-replace '${DEV_URL}' '${STAGING_URL}' --all-tables-with-prefix --skip-columns=guid --report-changed-only
  $REMOTE_WP option update blog_public 0
  $REMOTE_WP cache flush
  $REMOTE_WP acorn optimize:clear >/dev/null 2>&1 || true
  $REMOTE_WP acorn view:cache     >/dev/null 2>&1 || true
"

echo "5/5  Gotowe → ${STAGING_URL}"
echo "     Uwaga: login do wp-admin = dane admina z dev."
