<?php
/**
 * Frontend edit-link helpers for content blocks.
 *
 * Default usage inside a layout:
 *   echo cms_render_frontend_edit_button($contentItem, ['form_id' => $contentSourceFormId ?? null]);
 *
 * If source_form_id is present on the content row, the explicit form_id option
 * is optional. The option exists for layouts that need a manual override.
 */

function cms_frontend_edit_first_ip_value(?string $value): string {
  $value = trim((string) $value);
  if ($value === '') {
    return '';
  }

  if (strpos($value, ',') !== false) {
    $parts = explode(',', $value);
    $value = trim((string) ($parts[0] ?? ''));
  }

  return $value;
}

function cms_frontend_edit_allowed(): bool {
  static $allowed = null;

  if ($allowed !== null) {
    return $allowed;
  }

  $remoteAddr = cms_frontend_edit_first_ip_value((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
  if ($remoteAddr === '') {
    $remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
  }

  $allowed = cms_pref('prefFooterDebugOn', 'No') === 'Yes'
    || ($remoteAddr !== '' && $remoteAddr === cms_pref('prefTruskaIP', ''))
    || ($remoteAddr !== '' && $remoteAddr === cms_pref('prefCoderIP', ''))
    || ($remoteAddr !== '' && $remoteAddr === cms_pref('prefClientIP', ''))
    || ($remoteAddr !== '' && $remoteAddr === cms_pref('prefClient1IP', ''))
    || (function_exists('cms_is_logged_in') && cms_is_logged_in());

  return $allowed;
}

function cms_frontend_edit_hex(string $value, string $fallback): string {
  $value = trim($value);
  if (preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
    return strtoupper($value);
  }

  return $fallback;
}

function cms_frontend_edit_button_colors(): array {
  $bg = (string) cms_pref('prefContentEditBgColor', cms_pref('prefFrontendEditBgColor', '#198754'));
  $fg = (string) cms_pref('prefContentEditTextColor', cms_pref('prefFrontendEditTextColor', '#FFD54A'));

  return [
    'bg' => cms_frontend_edit_hex($bg, '#198754'),
    'fg' => cms_frontend_edit_hex($fg, '#FFD54A'),
  ];
}

function cms_frontend_edit_form_id(array $contentItem, array $options = []): int {
  $candidateKeys = ['form_id', 'source_form_id', 'frm'];

  foreach ($candidateKeys as $key) {
    if (isset($options[$key]) && is_numeric((string) $options[$key])) {
      return (int) $options[$key];
    }
  }

  foreach ($candidateKeys as $key) {
    if (isset($contentItem[$key]) && is_numeric((string) $contentItem[$key])) {
      return (int) $contentItem[$key];
    }
  }

  return 0;
}

function cms_frontend_edit_record_id(array $contentItem, array $options = []): int {
  $candidateKeys = ['record_id', 'id'];

  foreach ($candidateKeys as $key) {
    if (isset($options[$key]) && is_numeric((string) $options[$key])) {
      return (int) $options[$key];
    }
  }

  foreach ($candidateKeys as $key) {
    if (isset($contentItem[$key]) && is_numeric((string) $contentItem[$key])) {
      return (int) $contentItem[$key];
    }
  }

  return 0;
}

function cms_frontend_edit_record_url(int $formId, int $recordId): string {
  if ($formId <= 0 || $recordId <= 0) {
    return '';
  }

  return cms_base_url('/wccms/recordEditv5.php')
    . '?frm=' . rawurlencode((string) $formId)
    . '&id=' . rawurlencode((string) $recordId);
}

function cms_render_frontend_edit_button(array $contentItem, array $options = []): string {
  if (!cms_frontend_edit_allowed()) {
    return '';
  }

  $formId = cms_frontend_edit_form_id($contentItem, $options);
  $recordId = cms_frontend_edit_record_id($contentItem, $options);
  $url = cms_frontend_edit_record_url($formId, $recordId);
  if ($url === '') {
    return '';
  }

  $label = trim((string) ($options['label'] ?? 'Edit'));
  if ($label === '') {
    $label = 'Edit';
  }

  $title = trim((string) ($options["title"] ?? ""));
  $titleSubject = trim((string) ($options["form_name"] ?? $options["table_name"] ?? $contentItem["source_form_name"] ?? $contentItem["form_name"] ?? $contentItem["tableName"] ?? $contentItem["tablename"] ?? "content"));
  if ($titleSubject === "") {
    $titleSubject = "content";
  }
  if ($title === "") {
    $title = "Edit " . $titleSubject . " id: [" . (string) $recordId . "] in WCCMS";
  } elseif (preg_match("/^Edit this (.+) in WCCMS$/i", $title, $matches)) {
    $title = "Edit " . trim((string) $matches[1]) . " id: [" . (string) $recordId . "] in WCCMS";
  }

  $extraClass = trim((string) ($options['class'] ?? ''));
  $classes = 'cms-frontend-edit-button';
  if ($extraClass !== '') {
    $classes .= ' ' . $extraClass;
  }

  $colors = cms_frontend_edit_button_colors();

  return '<a class="' . cms_h($classes) . '"'
    . ' href="' . cms_h($url) . '"'
    . ' target="_blank"'
    . ' rel="noopener"'
    . ' title="' . cms_h($title) . '"'
    . ' aria-label="' . cms_h($title) . '"'
    . ' style="--cms-edit-bg:' . cms_h($colors['bg']) . ';--cms-edit-fg:' . cms_h($colors['fg']) . ';"'
    . '>'
    . '<i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>'
    . '</a>';
}
