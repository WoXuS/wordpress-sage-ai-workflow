#!/usr/bin/env bash
#
# Mirror the local dev plugins → staging (files only; activation state rides in
# the DB, so run this alongside `make push-db-staging`).
#
# CI never touches wp-content/plugins (only the theme ships), so plugins are
# synced here. --delete makes staging match dev exactly: plugins added on staging
# via wp-admin but not present in dev will be removed. That's intended while the
# site is built dev-first; revisit once the client manages staging plugins.
#
# Usage:
#   make sync-plugins-staging
#   ./scripts/deploy/sync-plugins-to-staging.sh

set -euo pipefail

SSH_KEY="${SSH_KEY:-certs/deploy_staging}"
SSH_HOST="${SSH_HOST:-s156.cyber-folks.pl}"
SSH_USER="${SSH_USER:-arpinetwork}"
SSH_PORT="${SSH_PORT:-222}"
STAGING_DOC="${STAGING_DOC:-/home/arpinetwork/domains/staging.arpinetwork.cfolks.pl/public_html}"
LOCAL_PLUGINS="public_html/wp-content/plugins/"

SSH_CMD="ssh -i $SSH_KEY -p $SSH_PORT -o BatchMode=yes"
REMOTE_WP="/opt/alt/php83/usr/bin/php \$(command -v wp) --path=$STAGING_DOC"

echo "1/2  rsync wtyczek dev → staging (--delete, lustro)…"
rsync -az --delete \
  -e "$SSH_CMD" \
  "$LOCAL_PLUGINS" \
  "${SSH_USER}@${SSH_HOST}:${STAGING_DOC}/wp-content/plugins/"

echo "2/2  lista wtyczek na stagingu + czyszczenie cache…"
$SSH_CMD "${SSH_USER}@${SSH_HOST}" "
  $REMOTE_WP plugin list --fields=name,status,version
  $REMOTE_WP acorn optimize:clear >/dev/null 2>&1 || true
  $REMOTE_WP cache flush          >/dev/null 2>&1 || true
"
echo "✓ Wtyczki zsynchronizowane."
