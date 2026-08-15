# VS Code Site Color Runbook

Purpose: apply a persistent per-site color identity in VS Code so the wrong site is less likely to be edited.

## Inputs

- `site_host` (example: `dev-mst.witecanvas.com`)
- `base_color` in hex (example: `#69654e`)
- `activity_color` darker matching shade (example: `#54503e`)

## Files to set every time

Set both files for each site:

1. `/<site>/web/.vscode/settings.json`
2. `/<site>/web/<site_host>.code-workspace`

Reason: if only `peacock.color` is set in the workspace file, VS Code can revert to default UI colors.
`workbench.colorCustomizations` must exist in both files.

## Required JSON block

```json
{
  "window.title": "<site_host> LOCAL - ${activeEditorShort}",
  "peacock.color": "<base_color>",
  "workbench.colorCustomizations": {
    "titleBar.activeBackground": "<base_color>",
    "titleBar.activeForeground": "#ffffff",
    "statusBar.background": "<base_color>",
    "statusBar.foreground": "#ffffff",
    "activityBar.background": "<activity_color>",
    "activityBar.foreground": "#ffffff"
  }
}
```

Workspace file structure:

```json
{
  "folders": [
    { "name": "web", "path": "." },
    { "name": "private", "path": "../private" },
    { "name": "log", "path": "../log" }
  ],
  "settings": { "...same settings block as above..." }
}
```

## Local git excludes (do not commit local color files)

In `/<site>/web/.git/info/exclude`, ensure:

```text
.vscode/settings.json
<site_host>.code-workspace
```

These are local excludes only (not shared in repo history).

## Verification checklist

1. Open site folder mode: `/<site>/web` and confirm color shows.
2. Open workspace mode: `/<site>/web/<site_host>.code-workspace` and confirm color shows.
3. Confirm workspace file still contains `workbench.colorCustomizations` (Peacock may rewrite and remove it).

## Fast update workflow for future sites

1. Create/update `web/.vscode/settings.json` with the block above.
2. Create/update `web/<site_host>.code-workspace` with:
- folders: `web`, `../private`, `../log`
- same settings block
3. Add both paths to `web/.git/info/exclude`.
4. Open the relevant window; color should apply immediately or after reload.
