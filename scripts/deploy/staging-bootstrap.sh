#!/usr/bin/env bash
#
# Staging content bootstrap for the ARPI theme.
#
# Run ONCE, from the staging docroot (public_html) over SSH, AFTER:
#   1. WordPress core is present         (wp core download)
#   2. our env-aware wp-config.php + root .env/vendor are in place (see runbook)
#   3. the theme has been deployed        (first CI run, or a manual rsync)
#   4. the staging DB is reachable
#
# It installs WP (if needed), activates the theme, and creates the header nav menu
# so the header/footer actually render. Safe to re-run.
#
# Usage:
#   SITE_URL=https://staging.arpiaccounting.com ADMIN_PASS='…' ./staging-bootstrap.sh

set -euo pipefail

SITE_URL="${SITE_URL:?set SITE_URL=https://staging.arpiaccounting.com}"
ADMIN_USER="${ADMIN_USER:-arpi_admin}"
ADMIN_PASS="${ADMIN_PASS:?set ADMIN_PASS to a strong password}"
ADMIN_EMAIL="${ADMIN_EMAIL:-dev@arpiaccounting.com}"

# 1. Install WordPress if it isn't already.
if ! wp core is-installed 2>/dev/null; then
  wp core install \
    --url="$SITE_URL" \
    --title="ARPI Accounting (staging)" \
    --admin_user="$ADMIN_USER" \
    --admin_password="$ADMIN_PASS" \
    --admin_email="$ADMIN_EMAIL" \
    --skip-email
fi

# 2. Activate the theme.
wp theme activate arpi

# 3. Primary navigation (header) — create + assign once.
if ! wp menu list --fields=slug --format=csv | grep -qx 'primary'; then
  wp menu create "Primary"
  wp menu item add-custom primary "Home"    "$SITE_URL"          >/dev/null
  wp menu item add-custom primary "O nas"   "$SITE_URL/o-nas"    >/dev/null
  wp menu item add-custom primary "Kontakt" "$SITE_URL/kontakt"  >/dev/null
fi
wp menu location assign primary primary_navigation || true

# 4. Belt-and-suspenders with the theme's noindex filter: discourage indexing.
wp option update blog_public 0

echo "✓ Staging bootstrap complete → $SITE_URL"
