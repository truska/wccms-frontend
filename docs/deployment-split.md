# Deployment split: frontend + central wccms

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

## DB schema workflow (additive only)
Use tools in `wccms/sql/tools/`:
- `export_schema.sh`: export current DB schema (no data).
- `schema_diff_additive.php`: compare canonical schema vs target schema and output SQL with only:
  - `CREATE TABLE` for missing tables
  - `ALTER TABLE ... ADD COLUMN` for missing fields

No drop statements are generated.
