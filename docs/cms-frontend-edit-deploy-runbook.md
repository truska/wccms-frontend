# CMS Frontend Edit Link Deploy Runbook

Use this runbook to deploy the frontend CMS edit button feature to another site.

## 1) Code to copy

From this site (`dev-wc`) copy these files/changes into the target site:

1. `includes/lib/cms_frontend_edit.php` (full file)
2. `includes/prefs.php`
   - Ensure this line exists:
   - `require_once __DIR__ . '/lib/cms_frontend_edit.php';`
3. `inside.php`
   - Ensure this line exists so frontend can detect CMS login state:
   - `require_once __DIR__ . '/wccms/includes/auth.php';`
4. `css/site.css`
   - Copy the `.cms-edit-target` and `.cms-frontend-edit-button` CSS block.
5. Layout templates used by that site
   - Add this near the top of each rendered content section:
   - `<?php echo cms_render_frontend_edit_button($contentItem, ['form_id' => $contentSourceFormId ?? null]); ?>`
   - Ensure parent wrapper has class `cms-edit-target`.

## 2) Required preference keys (Web scope)

The allow logic is OR-based. Button is shown when any one condition is true:

1. `prefFooterDebugOn = Yes`
2. Visitor IP matches one of:
   - `prefTruskaIP`
   - `prefCoderIP`
   - `prefClientIP`
   - `prefClient1IP`
3. Visitor is logged into WCCMS (`cms_is_logged_in()`)

Optional color prefs:

1. `prefContentEditBgColor` (hex like `#198754`)
2. `prefContentEditTextColor` (hex like `#FFD54A`)

Back-compat color fallbacks:

1. `prefFrontendEditBgColor`
2. `prefFrontendEditTextColor`

Notes:

1. Preferences are loaded from `cms_preferences` (or `preferences`) where `archived=0` and `showonweb='Yes'`.
2. If a pref exists but is not `showonweb='Yes'`, frontend will not see it.

## 3) Source form/record requirements

The edit URL is built as:

1. `/wccms/recordEditv5.php?frm={formId}&id={recordId}`

For a button to render on a block, both values must resolve:

1. `formId` from one of:
   - `$options['form_id']` (recommended from `$contentSourceFormId`)
   - `$contentItem['source_form_id']`
   - `$contentItem['frm']`
2. `recordId` from one of:
   - `$options['record_id']`
   - `$contentItem['id']`

If either ID is missing/0, no button is rendered.

## 4) Target-site implementation checklist

1. Copy `includes/lib/cms_frontend_edit.php`.
2. Wire include in `includes/prefs.php`.
3. Wire auth include in `inside.php`.
4. Add CSS block to `css/site.css`.
5. Update all active layout files to include:
   - `cms-edit-target` on wrapper
   - `cms_render_frontend_edit_button(...)` call
6. If contact page uses a partial, ensure button is in the partial output path.
7. Ensure Font Awesome is available (button uses `fa-pen-to-square` icon).
8. Set prefs in CMS for allowed IP/login/color behavior.

## 5) Verification commands (run on target site root)

List layouts missing direct edit-button call:

```bash
for f in includes/layouts/*.php; do
  if rg -q "cms_render_frontend_edit_button" "$f"; then
    echo "HAS  $f"
  else
    echo "MISS $f"
  fi
done
```

Check required wiring:

```bash
rg -n "cms_frontend_edit.php|wccms/includes/auth.php|cms-edit-target|cms-frontend-edit-button|cms_render_frontend_edit_button" includes inside.php css/site.css -S
```

## 6) Quick functional test

1. Open a content page from an allowed IP (or while logged into WCCMS).
2. Confirm edit icon appears on each block.
3. Click icon and verify it opens correct `recordEditv5.php` record in a new tab.
4. Log out of WCCMS and test from non-allowed IP; icon should disappear (unless debug pref is on).

## 7) Current dev-wc coverage snapshot

At the time of writing:

1. `includes/layouts/contact.php` does not call button directly.
2. It is rendered in `includes/partials/contact_form.php`.
3. All other current layout files in `includes/layouts/` have direct calls.
