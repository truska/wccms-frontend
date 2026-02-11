<?php
/**
 * Front-end image helpers for content/gallery rendering.
 */

require_once __DIR__ . '/../../wccms/includes/lib/cms_media.php';

function cms_content_table_exists(string $table): bool {
  global $pdo, $DB_OK;

  static $cache = [];
  if (array_key_exists($table, $cache)) {
    return $cache[$table];
  }

  if (!$DB_OK || !($pdo instanceof PDO)) {
    $cache[$table] = false;
    return false;
  }

  try {
    $stmt = $pdo->prepare('SHOW TABLES LIKE :table');
    $stmt->execute([':table' => $table]);
    $cache[$table] = (bool) $stmt->fetchColumn();
    return $cache[$table];
  } catch (PDOException $e) {
    $cache[$table] = false;
    return false;
  }
}

function cms_content_table_columns(string $table): array {
  global $pdo, $DB_OK;

  static $cache = [];
  if (isset($cache[$table])) {
    return $cache[$table];
  }

  if (!$DB_OK || !($pdo instanceof PDO) || !cms_content_table_exists($table)) {
    $cache[$table] = [];
    return $cache[$table];
  }

  try {
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
  } catch (PDOException $e) {
    $rows = [];
  }

  $cache[$table] = array_map(static fn($row) => strtolower((string) ($row['Field'] ?? '')), $rows);
  return $cache[$table];
}

function cms_content_table_has_column(string $table, string $column): bool {
  $cols = cms_content_table_columns($table);
  return in_array(strtolower($column), $cols, true);
}

function cms_form_context_for_table(string $tableName): array {
  global $pdo, $DB_OK;

  static $cache = [];
  $key = strtolower(trim($tableName));
  if (isset($cache[$key])) {
    return $cache[$key];
  }

  $default = ['form_id' => null, 'form_name' => null];
  if ($key === '' || !$DB_OK || !($pdo instanceof PDO) || !cms_content_table_exists('cms_form')) {
    $cache[$key] = $default;
    return $default;
  }

  $formCols = cms_content_table_columns('cms_form');
  $tableField = in_array('table', $formCols, true) ? 'table' : (in_array('tableid', $formCols, true) ? 'tableid' : null);
  $nameField = in_array('name', $formCols, true) ? 'name' : (in_array('title', $formCols, true) ? 'title' : null);
  $showField = in_array('showonweb', $formCols, true) ? 'showonweb' : null;
  $archivedField = in_array('archived', $formCols, true) ? 'archived' : null;

  if ($tableField === null) {
    $cache[$key] = $default;
    return $default;
  }

  $tableId = null;
  if (cms_content_table_exists('cms_table')) {
    $tableCols = cms_content_table_columns('cms_table');
    $idField = in_array('id', $tableCols, true) ? 'id' : null;
    $tableNameField = in_array('name', $tableCols, true) ? 'name' : (in_array('tablename', $tableCols, true) ? 'tablename' : null);
    if ($idField && $tableNameField) {
      try {
        $stmt = $pdo->prepare("SELECT `{$idField}` FROM cms_table WHERE LOWER(`{$tableNameField}`) = :name LIMIT 1");
        $stmt->execute([':name' => $key]);
        $value = $stmt->fetchColumn();
        if ($value !== false && $value !== null && is_numeric($value)) {
          $tableId = (int) $value;
        }
      } catch (PDOException $e) {
        $tableId = null;
      }
    }
  }

  $whereParts = [];
  $params = [];
  if ($tableId !== null) {
    $whereParts[] = "(`{$tableField}` = :table_id OR LOWER(`{$tableField}`) = :table_name)";
    $params[':table_id'] = $tableId;
    $params[':table_name'] = $key;
  } else {
    $whereParts[] = "LOWER(`{$tableField}`) = :table_name";
    $params[':table_name'] = $key;
  }
  if ($archivedField) {
    $whereParts[] = "`{$archivedField}` = 0";
  }
  if ($showField) {
    $whereParts[] = "`{$showField}` = 'Yes'";
  }

  $sql = "SELECT `id` AS form_id" . ($nameField ? ", `{$nameField}` AS form_name" : ", '' AS form_name")
    . " FROM cms_form WHERE " . implode(' AND ', $whereParts)
    . " ORDER BY `id` ASC LIMIT 1";
  try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    $row = null;
  }

  if (!$row) {
    $cache[$key] = $default;
    return $default;
  }

  $cache[$key] = [
    'form_id' => isset($row['form_id']) && is_numeric($row['form_id']) ? (int) $row['form_id'] : null,
    'form_name' => trim((string) ($row['form_name'] ?? '')) ?: null,
  ];
  return $cache[$key];
}

function cms_content_media_sizes(): array {
  return ['xs', 'sm', 'md', 'lg', 'xl', 'master', ''];
}

function cms_content_pick_image_url(string $mediatype, string $folder, string $filename, array $preferredSizes): string {
  foreach ($preferredSizes as $size) {
    $path = cms_media_path($mediatype, $folder, $size) . $filename;
    if (file_exists($path)) {
      return cms_media_url($mediatype, $folder, $filename, $size);
    }

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
      $base = pathinfo($filename, PATHINFO_FILENAME);
      $webp = $base . '.webp';
      $webpPath = cms_media_path($mediatype, $folder, $size) . $webp;
      if (file_exists($webpPath)) {
        return cms_media_url($mediatype, $folder, $filename, $size);
      }
    }
  }
  return '';
}

function cms_content_no_image_url(string $mediatype = 'images', string $folder = 'content', string $size = 'md'): string {
  $sizes = [$size, 'lg', 'md', 'sm', 'xs', ''];
  foreach ($sizes as $candidate) {
    $path = cms_media_path($mediatype, $folder, $candidate) . 'no-image.png';
    if (file_exists($path)) {
      return cms_media_url($mediatype, $folder, 'no-image.png', $candidate, false);
    }
  }
  return '';
}

function cms_content_image_srcset(string $mediatype, string $folder, string $filename): string {
  $map = [
    'xs' => 576,
    'sm' => 768,
    'md' => 992,
    'lg' => 1200,
    'xl' => 1600,
  ];
  $parts = [];
  foreach ($map as $size => $width) {
    $path = cms_media_path($mediatype, $folder, $size) . $filename;
    if (file_exists($path)) {
      $parts[] = cms_media_url($mediatype, $folder, $filename, $size) . ' ' . $width . 'w';
      continue;
    }

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
      $base = pathinfo($filename, PATHINFO_FILENAME);
      $webp = $base . '.webp';
      $webpPath = cms_media_path($mediatype, $folder, $size) . $webp;
      if (file_exists($webpPath)) {
        $parts[] = cms_base_url('/filestore/' . trim($mediatype, '/') . '/' . trim($folder, '/') . '/' . $size . '/' . $webp) . ' ' . $width . 'w';
      }
    }
  }

  return implode(', ', $parts);
}

function cms_content_gallery_images(array $contentItem, array $options = []): array {
  global $pdo, $DB_OK;

  $recordId = (int) ($contentItem['id'] ?? 0);
  if ($recordId <= 0 || !$DB_OK || !($pdo instanceof PDO) || !cms_content_table_exists('gallery')) {
    return [];
  }

  $baseWhere = ['record_id = :record_id'];
  $baseParams = [':record_id' => $recordId];

  if (cms_content_table_has_column('gallery', 'archived')) {
    $baseWhere[] = "(archived IS NULL OR archived = 0 OR archived = '0' OR LOWER(CAST(archived AS CHAR)) IN ('no','false'))";
  }
  if (cms_content_table_has_column('gallery', 'showonweb')) {
    $baseWhere[] = "(showonweb IS NULL OR showonweb = 1 OR showonweb = '1' OR LOWER(CAST(showonweb AS CHAR)) IN ('yes','true','on'))";
  }

  $queryGallery = static function (array $where, array $params) use ($pdo): array {
    $sql = 'SELECT * FROM gallery WHERE ' . implode(' AND ', $where);
    if (cms_content_table_has_column('gallery', 'sort')) {
      $sql .= ' ORDER BY sort ASC, id ASC';
    } else {
      $sql .= ' ORDER BY id ASC';
    }
    try {
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
      return [];
    }
  };
  $debugEnabled = isset($options['debug']) && $options['debug'] === true;
  if ($debugEnabled) {
    $GLOBALS['cms_image_debug_queries'] = [];
  }

  $runGalleryQuery = static function (array $where, array $params, string $label) use ($queryGallery, $debugEnabled): array {
    $sql = 'SELECT * FROM gallery WHERE ' . implode(' AND ', $where);
    if (cms_content_table_has_column('gallery', 'sort')) {
      $sql .= ' ORDER BY sort ASC, id ASC';
    } else {
      $sql .= ' ORDER BY id ASC';
    }
    $rows = $queryGallery($where, $params);
    if ($debugEnabled) {
      $GLOBALS['cms_image_debug_queries'][] = [
        'label' => $label,
        'sql' => $sql,
        'params' => $params,
        'rows' => count($rows),
      ];
    }
    return $rows;
  };

  $rows = [];
  $formId = isset($options['form_id']) && is_numeric($options['form_id']) ? (int) $options['form_id'] : 0;
  $formName = trim((string) ($options['form_name'] ?? ''));
  $hasFormIdColumn = cms_content_table_has_column('gallery', 'form_id');
  $hasFormNameColumn = cms_content_table_has_column('gallery', 'form_name');

  // In multi-form setups we require explicit ownership to avoid cross-form collisions.
  if (($hasFormIdColumn || $hasFormNameColumn) && $formId <= 0 && $formName === '') {
    if ($debugEnabled) {
      $GLOBALS['cms_image_debug_queries'][] = [
        'label' => 'skipped-no-form-context',
        'sql' => '',
        'params' => [],
        'rows' => 0,
      ];
    }
    return [];
  }

  if ($formId > 0 && $hasFormIdColumn) {
    $where = $baseWhere;
    $params = $baseParams;
    $where[] = 'form_id = :form_id';
    $params[':form_id'] = $formId;
    $rows = $runGalleryQuery($where, $params, 'by-form-id');
  } elseif ($formName !== '' && $hasFormNameColumn) {
    $where = $baseWhere;
    $params = $baseParams;
    $where[] = 'LOWER(form_name) = :form_name';
    $params[':form_name'] = strtolower($formName);
    $rows = $runGalleryQuery($where, $params, 'by-form-name');
  } else {
    // Legacy schema with no explicit form ownership columns.
    $rows = $runGalleryQuery($baseWhere, $baseParams, 'legacy-by-record-only');
  }

  $items = [];
  foreach ($rows as $row) {
    $filename = ltrim((string) ($row['image'] ?? ''), '/');
    if ($filename === '') {
      continue;
    }

    $folderName = trim((string) ($row['folder_name'] ?? ''), '/');
    $mediatype = trim((string) ($row['mediatype'] ?? ''), '/');
    $folder = '';

    // Gallery rows often store folder_name as "images/content".
    if ($folderName !== '') {
      $parts = array_values(array_filter(explode('/', $folderName), 'strlen'));
      if (count($parts) > 1) {
        if ($mediatype === '') {
          $mediatype = (string) ($parts[0] ?? '');
        }
        $folder = implode('/', array_slice($parts, 1));
      } elseif (count($parts) === 1) {
        $single = (string) $parts[0];
        if ($mediatype === '' && in_array(strtolower($single), ['images', 'files', 'docs', 'documents', 'media'], true)) {
          $mediatype = $single;
        } else {
          $folder = $single;
        }
      }
    }

    if ($mediatype === '') {
      $mediatype = 'images';
    }
    if ($folder === '') {
      $folder = 'content';
    }

    $display = cms_content_pick_image_url($mediatype, $folder, $filename, ['lg', 'md', 'sm', 'xs', '']);
    $zoom = cms_content_pick_image_url($mediatype, $folder, $filename, ['xl', 'master', 'lg', 'md', '']);
    $thumb = cms_content_pick_image_url($mediatype, $folder, $filename, ['sm', 'xs', 'md', '']);
    $srcset = cms_content_image_srcset($mediatype, $folder, $filename);
    $alt = trim((string) ($row['alttag'] ?? ''));
    $caption = trim((string) ($row['caption'] ?? ''));

    if ($display === '') {
      $display = cms_content_no_image_url($mediatype, $folder, 'md');
    }
    if ($zoom === '') {
      $zoom = $display;
    }
    if ($thumb === '') {
      $thumb = $display;
    }

    $items[] = [
      'display' => $display,
      'zoom' => $zoom,
      'thumb' => $thumb,
      'srcset' => $srcset,
      'alt' => $alt,
      'caption' => $caption,
      'filename' => $filename,
      'folder' => $folder,
      'mediatype' => $mediatype,
    ];
  }

  return $items;
}

function cms_content_single_image_fallback(array $contentItem): array {
  $filename = ltrim((string) ($contentItem['image'] ?? ''), '/');
  $folder = 'content';
  $mediatype = 'images';

  if ($filename === '') {
    $placeholder = cms_content_no_image_url($mediatype, $folder, 'md');
    if ($placeholder === '') {
      return [];
    }
    return [[
      'display' => $placeholder,
      'zoom' => $placeholder,
      'thumb' => $placeholder,
      'srcset' => '',
      'alt' => '',
      'caption' => '',
      'filename' => 'no-image.png',
      'folder' => $folder,
      'mediatype' => $mediatype,
    ]];
  }

  $display = cms_content_pick_image_url($mediatype, $folder, $filename, ['lg', 'md', 'sm', 'xs', '']);
  $zoom = cms_content_pick_image_url($mediatype, $folder, $filename, ['xl', 'master', 'lg', 'md', '']);
  $thumb = cms_content_pick_image_url($mediatype, $folder, $filename, ['sm', 'xs', 'md', '']);
  $srcset = cms_content_image_srcset($mediatype, $folder, $filename);

  if ($display === '') {
    $display = cms_content_no_image_url($mediatype, $folder, 'md');
  }
  if ($display === '') {
    return [];
  }
  if ($zoom === '') {
    $zoom = $display;
  }
  if ($thumb === '') {
    $thumb = $display;
  }

  return [[
    'display' => $display,
    'zoom' => $zoom,
    'thumb' => $thumb,
    'srcset' => $srcset,
    'alt' => '',
    'caption' => '',
    'filename' => $filename,
    'folder' => $folder,
    'mediatype' => $mediatype,
  ]];
}

function cms_content_images(array $contentItem, array $options = []): array {
  $gallery = cms_content_gallery_images($contentItem, $options);
  if (!empty($gallery)) {
    return $gallery;
  }
  return cms_content_single_image_fallback($contentItem);
}

function cms_magictoolbox_mode(?string $override = null): string {
  $mode = strtolower(trim((string) ($override ?? cms_pref('prefMagicToolboxMode', 'none'))));
  if (in_array($mode, ['magiczoom', 'magiczoomplus'], true)) {
    return $mode;
  }
  return 'none';
}

function cms_magictoolbox_assets_html(?string $mode = null): string {
  $resolved = cms_magictoolbox_mode($mode);
  if ($resolved === 'none') {
    return '';
  }

  $zoomCss = trim((string) cms_pref('prefMagicZoomCssUrl', ''));
  $zoomJs = trim((string) cms_pref('prefMagicZoomJsUrl', ''));
  $zoomPlusCss = trim((string) cms_pref('prefMagicZoomPlusCssUrl', ''));
  $zoomPlusJs = trim((string) cms_pref('prefMagicZoomPlusJsUrl', ''));

  if ($resolved === 'magiczoomplus' && $zoomPlusCss !== '' && $zoomPlusJs !== '') {
    return '<link rel="stylesheet" href="' . cms_h($zoomPlusCss) . '">' . "\n"
      . '<script src="' . cms_h($zoomPlusJs) . '"></script>';
  }

  if ($resolved === 'magiczoom' && $zoomCss !== '' && $zoomJs !== '') {
    return '<link rel="stylesheet" href="' . cms_h($zoomCss) . '">' . "\n"
      . '<script src="' . cms_h($zoomJs) . '"></script>';
  }

  $assetBase = rtrim((string) cms_pref('prefMagicToolboxAssetBase', ''), '/');
  if ($assetBase === '') {
    return '';
  }

  if ($resolved === 'magiczoomplus') {
    return '<link rel="stylesheet" href="' . cms_h($assetBase . '/magiczoomplus/magiczoomplus.css') . '">' . "\n"
      . '<script src="' . cms_h($assetBase . '/magiczoomplus/magiczoomplus.js') . '"></script>';
  }

  return '<link rel="stylesheet" href="' . cms_h($assetBase . '/magiczoom/magiczoom.css') . '">' . "\n"
    . '<script src="' . cms_h($assetBase . '/magiczoom/magiczoom.js') . '"></script>';
}

function cms_render_content_images(array $contentItem, array $options = []): string {
  $images = cms_content_images($contentItem, $options);
  if (empty($images)) {
    return '';
  }

  $mode = cms_magictoolbox_mode($options['mode'] ?? null);
  $wrapperClass = trim((string) ($options['wrapper_class'] ?? 'service-image'));
  $imgClass = trim((string) ($options['img_class'] ?? 'img-fluid'));
  $sizesAttr = trim((string) ($options['sizes'] ?? '(max-width: 992px) 100vw, 50vw'));
  $idBase = 'content-image-' . (int) ($contentItem['id'] ?? 0);
  $count = count($images);

  if ($count === 1) {
    $item = $images[0];
    $alt = $item['alt'] !== '' ? $item['alt'] : trim((string) ($contentItem['heading'] ?? ''));
    $srcsetAttr = $item['srcset'] !== '' ? ' srcset="' . cms_h($item['srcset']) . '" sizes="' . cms_h($sizesAttr) . '"' : '';

    if ($mode === 'magiczoomplus') {
      return '<div class="' . cms_h($wrapperClass) . '">'
        . '<a id="' . cms_h($idBase) . '" class="MagicZoomPlus" href="' . cms_h($item['zoom']) . '">'
        . '<img src="' . cms_h($item['display']) . '" alt="' . cms_h($alt) . '" class="' . cms_h($imgClass) . '"' . $srcsetAttr . '>'
        . '</a>'
        . '</div>';
    }

    if ($mode === 'magiczoom') {
      return '<div class="' . cms_h($wrapperClass) . '">'
        . '<a id="' . cms_h($idBase) . '" class="MagicZoom" href="' . cms_h($item['zoom']) . '">'
        . '<img src="' . cms_h($item['display']) . '" alt="' . cms_h($alt) . '" class="' . cms_h($imgClass) . '"' . $srcsetAttr . '>'
        . '</a>'
        . '</div>';
    }

    return '<div class="' . cms_h($wrapperClass) . '">'
      . '<img src="' . cms_h($item['display']) . '" alt="' . cms_h($alt) . '" class="' . cms_h($imgClass) . '"' . $srcsetAttr . '>'
      . '</div>';
  }

  $main = $images[0];
  $mainAlt = $main['alt'] !== '' ? $main['alt'] : trim((string) ($contentItem['heading'] ?? ''));
  $mainSrcset = $main['srcset'] !== '' ? ' srcset="' . cms_h($main['srcset']) . '" sizes="' . cms_h($sizesAttr) . '"' : '';

  if ($mode === 'magiczoomplus' || $mode === 'magiczoom') {
    $toolClass = $mode === 'magiczoomplus' ? 'MagicZoomPlus' : 'MagicZoom';
    $html = '<div class="' . cms_h($wrapperClass) . '">';
    $html .= '<a id="' . cms_h($idBase) . '" class="' . cms_h($toolClass) . '" href="' . cms_h($main['zoom']) . '">';
    $html .= '<img src="' . cms_h($main['display']) . '" alt="' . cms_h($mainAlt) . '" class="' . cms_h($imgClass) . '"' . $mainSrcset . '>';
    $html .= '</a>';
    $html .= '<div class="mt-3 d-flex flex-wrap gap-2">';
    foreach ($images as $item) {
      $thumbAlt = $item['alt'] !== '' ? $item['alt'] : $mainAlt;
      $html .= '<a data-zoom-id="' . cms_h($idBase) . '" href="' . cms_h($item['zoom']) . '" data-image="' . cms_h($item['display']) . '" class="d-inline-block">';
      $html .= '<img src="' . cms_h($item['thumb']) . '" alt="' . cms_h($thumbAlt) . '" class="img-thumbnail" style="width:80px;height:80px;object-fit:cover;">';
      $html .= '</a>';
    }
    $html .= '</div></div>';
    return $html;
  }

  $html = '<div class="' . cms_h($wrapperClass) . '"><div class="row g-2">';
  foreach ($images as $item) {
    $alt = $item['alt'] !== '' ? $item['alt'] : $mainAlt;
    $srcsetAttr = $item['srcset'] !== '' ? ' srcset="' . cms_h($item['srcset']) . '" sizes="(max-width: 992px) 50vw, 33vw"' : '';
    $html .= '<div class="col-6 col-md-4">';
    $html .= '<img src="' . cms_h($item['display']) . '" alt="' . cms_h($alt) . '" class="img-fluid rounded"' . $srcsetAttr . '>';
    $html .= '</div>';
  }
  $html .= '</div></div>';
  return $html;
}
