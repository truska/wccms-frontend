# Deployment split: frontend + central wccms

## Server Standard (Master Facts)
- Objective: keep `wccms` standardized across sites, while avoiding accidental frontend divergence.
- Scope for this server standardization:
- `dev-wc.witecanvas.com`
- `dev-mst.witecanvas.com`
- `itfix.com`
- live MST stack (`mst.witecanvas.com` -> `tradewood.com`)
- Excluded from this standardization scope: `dev-ildra.witecanvas.com` (and related ildra live stack).
- Branch policy:
- dev environments: `wccms` from `staging`
- live environments: `wccms` from `main`
- Frontend safety rule:
- do not modify shared frontend/common assets as part of routine CMS deploys
- keep frontend deploy action strictly to pull/sync from the site's frontend repo source of truth
- CMS safety rule:
- standardize deploy tooling (`tools-frontend.php`, deploy scripts, DB migration set) in `wccms-backend`
- apply additive DB migrations only; no destructive schema changes in rollout scripts
- Path convention target:
- preferred frontend checkout path per site root: `<site_root>/frontend`
- if a site differs, use `prefFrontendDeployRepoPath` until path is normalized

## Goal
- `web` repo keeps frontend/site code only.
- `wccms` becomes its own repo, cloned inside each site as `wccms/`.
- Site updates run two pulls: frontend repo + cms repo.

## One-time change in existing site repo
Already applied in this workspace:
- `wccms/` removed from frontend git tracking (`git rm --cached -r wccms`).
- `/wccms/` added to frontend `.gitignore`.

Commit this in frontend repo so future pulls do not reintroduce CMS files.

## Create and publish central CMS repo
From this server:

```bash
cd /var/www/itfix.com/web/wccms
git init -b main
git add .
git commit -m "Initial WCCMS split from site repo"
git remote add origin <NEW_WCCMS_REPO_URL>
git push -u origin main
```

## New site bootstrap
Run from any host with git access:

```bash
./tools/new_site_bootstrap.sh \
  --target-dir /var/www/example.com/web \
  --frontend-repo <FRONTEND_REPO_URL> \
  --frontend-branch main \
  --cms-repo <WCCMS_REPO_URL> \
  --profile live
```

For your dev/staging CMS host (`dev.witecanvas.com`), use:

```bash
./tools/new_site_bootstrap.sh \
  --target-dir /var/www/dev.witecanvas.com/web \
  --frontend-repo <FRONTEND_REPO_URL> \
  --frontend-branch main \
  --cms-repo <WCCMS_REPO_URL> \
  --profile dev
```

## Pull updates on a site
Inside the site web root:

```bash
./tools/site_sync.sh pull
```

`tools/site_sync.sh` now reads `tools/site_sync.conf` (if present), so each site can pin its own CMS branch:
- live sites: `CMS_BRANCH=main`
- dev/staging site: `CMS_BRANCH=staging`

Use `tools/site_sync.conf.example` as the template.

## WCCMS-only rollout (frontend managed separately)
Use `tools/wccms_deploy.sh` when frontend has its own deployment flow and you want backend-only actions.

### Stage 1: initial live deployment (`mstradewood.com`)
On the live server, in that site's frontend repo root:

```bash
cd /var/www/clients/client4/web11/web
cat > tools/site_sync.conf <<'EOF'
FRONTEND_BRANCH=main
CMS_REPO=git@github.com:truska/wccms-backend.git
CMS_BRANCH=main
CMS_DIR=wccms
EOF

bash ./tools/wccms_deploy.sh init
bash ./tools/wccms_deploy.sh status
```

### Stage 2: repeatable live updates after dev -> staging validation
After work is completed on dev and verified on staging:

```bash
cd /var/www/clients/client4/web11/web
bash ./tools/wccms_deploy.sh deploy
```

This pulls only `wccms` (`CMS_BRANCH=main` on live) and does not touch frontend code.

## DB schema workflow (additive only)
Use tools in `wccms/sql/tools/`:
- `export_schema.sh`: export current DB schema (no data).
- `schema_diff_additive.php`: compare canonical schema vs target schema and output SQL with only:
  - `CREATE TABLE` for missing tables
  - `ALTER TABLE ... ADD COLUMN` for missing fields

No drop statements are generated.

## Post-deploy checklist (per server)
- Confirm `private/dbcon.php` exists for that server environment (do not assume it migrates from dev).
- In ISPConfig, complete all DB steps:
  - create database
  - create database user
  - link/grant that user to the database
- Run a quick DB connection check:

```bash
php -r "require '/var/www/clients/client1/web9/private/dbcon.php'; echo (isset(\$DB_OK)&&\$DB_OK)?'DB OK':'DB FAIL: '.(\$DB_ERROR??'unknown'); echo PHP_EOL;"
```

- If the check returns `Access denied`, verify DB credentials and user-to-database grant in ISPConfig.
