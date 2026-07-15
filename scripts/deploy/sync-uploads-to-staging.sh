#!/usr/bin/env bash
#
# Mirror the local dev media library files → staging (wp-content/uploads).
#
# Attachment rows ride in the DB (run `make push-db-staging` too), but the files
# themselves live under wp-content/uploads, which CI never ships. Without this the
# media library shows broken images and the favicon (site_icon) won't render.
#
# --delete makes staging match dev exactly: files uploaded on staging but not in
# dev are removed. Intended while the site is built dev-first.
#
# Usage:
#   make sync-uploads-staging
#   ./scripts/deploy/sync-uploads-to-staging.sh

set -euo pipefail

SSH_KEY="${SSH_KEY:-certs/deploy_staging}"
SSH_HOST="${SSH_HOST:-s156.cyber-folks.pl}"
SSH_USER="${SSH_USER:-arpinetwork}"
SSH_PORT="${SSH_PORT:-222}"
STAGING_DOC="${STAGING_DOC:-/home/arpinetwork/domains/staging.arpinetwork.cfolks.pl/public_html}"
LOCAL_UPLOADS="public_html/wp-content/uploads/"

echo "rsync uploadów dev → staging (--delete, lustro)…"
rsync -az --delete \
  -e "ssh -i $SSH_KEY -p $SSH_PORT -o BatchMode=yes" \
  "$LOCAL_UPLOADS" \
  "${SSH_USER}@${SSH_HOST}:${STAGING_DOC}/wp-content/uploads/"

echo "✓ Media zsynchronizowane."
