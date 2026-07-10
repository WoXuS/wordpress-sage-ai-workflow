# Staging setup — cyberFolks (runbook)

**Goal:** a cyberFolks staging subdomain running the new ARPI theme, auto-deployed
from `main` via GitHub Actions + rsync. Fresh minimal WordPress (theme + nav menu);
real content migration comes later.

Follows spec §4–5 (`docs/superpowers/specs/2026-07-01-arpi-rewrite-design.md`).

## Architecture

```
merge/push to main ──► GitHub Actions ──► yarn build + composer install (theme)
                                     └──► rsync themes/arpi ──ssh──► cyberFolks staging
```

- **Only the theme ships.** WP core and `wp-content/plugins` on staging are never
  touched (client manages plugins in wp-admin).
- **Env-aware config:** `wp-config.php` reads `.env` + root `vendor/` (phpdotenv)
  from **one level above `public_html`** (outside the docroot). `WP_ENV=staging`.
- **noindex:** theme filter (`app/filters.php`) blocks indexing on any non-prod env.
- **Basic Auth:** enable at the hosting level (cyberFolks panel), not in code.

---

## Phase A — cyberFolks panel (YOU)

1. Create a **staging subdomain**, e.g. `staging.arpiaccounting.com`, with its own
   docroot (its own `public_html`).
2. Create a **separate MySQL database** + user for staging. Note name/user/pass/host.
3. Confirm on this plan (spec risk items):
   - **SSH** access is enabled (note host, port, username).
   - You can create files/dirs **above** the subdomain's `public_html` (for `.env`
     + root `vendor/`). If not, we fall back to keeping `.env` inside `public_html`
     with a deny rule — tell me and I'll adjust `wp-config.php`.
   - **WP-CLI** is available over SSH (`wp --info`).
4. Add the deploy key: paste the **public** key below into the staging account's
   `~/.ssh/authorized_keys`:

   ```
   (contents of certs/deploy_staging.pub — I'll paste it in chat)
   ```

## Phase B — GitHub repo secrets/vars (YOU)

Repo → Settings → **Environments → `staging`** (or Secrets/variables → Actions).

| Kind   | Name              | Value                                                        |
|--------|-------------------|-------------------------------------------------------------|
| secret | `SSH_PRIVATE_KEY` | full contents of `certs/deploy_staging` (the private key)   |
| secret | `SSH_HOST`        | staging SSH host                                            |
| secret | `SSH_USER`        | staging SSH username                                        |
| secret | `DEPLOY_PATH`     | absolute path to the staging `public_html`                  |
| var    | `SSH_PORT`        | SSH port (e.g. `22`) — omit if 22                           |

The private key lives only in `certs/` locally (gitignored) and in this secret.

## Phase C — one-time server bootstrap (over SSH, run WITH me)

From the staging docroot (`public_html`):

```bash
# 1. WordPress core
wp core download --locale=pl_PL

# 2. Config layout (above public_html): upload our env-aware wp-config.php into
#    public_html, and root composer.json → run composer install for phpdotenv.
#    (I provide the exact files; paths depend on the account layout.)
#    Create .env above public_html from .env.staging.example and fill it in.

# 3. First theme deploy: trigger the GitHub Action (Phase D) OR rsync once manually.

# 4. Install WP + theme + header menu:
SITE_URL=https://staging.arpiaccounting.com \
ADMIN_PASS='<strong-pass>' \
bash scripts/deploy/staging-bootstrap.sh
```

## Phase D — deploy

- **Automatic:** merge `feat/header-footer` → `main` and push. The Action builds and
  deploys. (First run may need Phase C done first so the target dir + WP exist.)
- **Manual:** Actions tab → *Deploy to staging* → **Run workflow**.

## Phase E — verify

- Visit `https://staging.arpiaccounting.com` → header (sticky, scroll-shrink logo)
  and footer render.
- `curl -sI` shows the page; view-source has `<meta name="robots" content="noindex…">`.
- Basic Auth prompt appears (once enabled in the panel).

---

## What's still deferred (not needed for this milestone)

- ACF PRO license (footer/header use static placeholder data for now).
- Real content migration (WXR + MailPoet) + PII anonymization.
- Production pipeline (gated by `v*` tag / manual approval) — added after staging is proven.
