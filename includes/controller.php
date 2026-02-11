<?php
/**
 * Front-end page controller.
 * Resolves the slug, loads the page row, and prepares content/layout data.
 */

function cms_table_exists_local(string $table): bool {
  global $pdo, $DB_OK;

  if (!$DB_OK || !($pdo instanceof PDO)) {
    return false;
  }

  try {
    $stmt = $pdo->prepare('SHOW TABLES LIKE :table');
    $stmt->execute([':table' => $table]);
    return (bool) $stmt->fetchColumn();
  } catch (PDOException $e) {
    return false;
  }
}

function cms_table_has_column(string $table, string $column): bool {
  global $pdo, $DB_OK;

  if (!$DB_OK || !($pdo instanceof PDO)) {
    return false;
  }

  try {
    $stmt = $pdo->prepare('SHOW COLUMNS FROM ' . $table . ' LIKE :column');
    $stmt->execute([':column' => $column]);
    return (bool) $stmt->fetchColumn();
  } catch (PDOException $e) {
    return false;
  }
}

$rawSlug = trim((string) ($_GET['url'] ?? ''));
$rawSlug = trim($rawSlug, '/');
$pageSlug = $rawSlug !== '' ? $rawSlug : cms_pref('prefHomePageSlug', 'welcome');
$pageSegments = array_values(array_filter(explode('/', $pageSlug), 'strlen'));
$currentSlug = $pageSegments[0] ?? $pageSlug;

$pageData = null;
$pageContentItems = [];
$pageLayoutFile = null;
$pageNotFound = false;

if (!cms_table_exists_local('pages')) {
  $pageTitle = cms_pref('prefSiteName', 'ITFix');
  return;
}

try {
  $conditions = ['slug = :slug'];
  if (cms_table_has_column('pages', 'archived')) {
    $conditions[] = 'archived = 0';
  }
  if (cms_table_has_column('pages', 'showonweb')) {
    $conditions[] = "showonweb = 'Yes'";
  }
  $sql = 'SELECT * FROM pages WHERE ' . implode(' AND ', $conditions) . ' LIMIT 1';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':slug' => $currentSlug]);
  $pageData = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (PDOException $e) {
  $pageData = null;
}

if (!$pageData) {
  $pageNotFound = true;
  http_response_code(404);
  $pageTitle = 'Page Not Found';
  return;
}

$pageTitle = $pageData['titletag'] ?? $pageData['name'] ?? $pageData['title'] ?? cms_pref('prefSiteName', 'ITFix');
$pageMetaDescription = $pageData['metadescription'] ?? '';
$pageMetaKeywords = $pageData['metakeywords'] ?? '';

// Detect layout table for content blocks.
$layoutTable = null;
if (cms_table_exists_local('layout')) {
  $layoutTable = 'layout';
} elseif (cms_table_exists_local('cms_layouts')) {
  $layoutTable = 'cms_layouts';
}

// Load content blocks if available.
if (cms_table_exists_local('content')) {
  try {
    $layoutJoin = '';
    if ($layoutTable) {
      $layoutJoin = ' LEFT JOIN ' . $layoutTable . ' l ON l.id = c.layout ';
    }
    $sql = 'SELECT c.*'
      . ($layoutTable ? ', l.url AS layout_url, l.name AS layout_name' : '')
      . ' FROM content c' . $layoutJoin
      . ' WHERE c.page = :page_id';
    if (cms_table_has_column('content', 'archived')) {
      $sql .= ' AND c.archived = 0';
    }
    if (cms_table_has_column('content', 'showonweb')) {
      $sql .= " AND c.showonweb = 'Yes'";
    }
    $sql .= ' ORDER BY c.sort ASC, c.id ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':page_id' => $pageData['id']]);
    $pageContentItems = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch (PDOException $e) {
    $pageContentItems = [];
  }
}

// Optional full-page layout (legacy support).
if (!empty($pageData['layout'])) {
  try {
    $layoutValue = $pageData['layout'];
    $layoutUrl = '';

    if (ctype_digit((string) $layoutValue) && $layoutTable) {
      $stmt = $pdo->prepare('SELECT * FROM ' . $layoutTable . ' WHERE id = :id LIMIT 1');
      $stmt->execute([':id' => (int) $layoutValue]);
      $layoutRow = $stmt->fetch(PDO::FETCH_ASSOC);
      $layoutUrl = $layoutRow['url'] ?? '';
    } else {
      $layoutUrl = (string) $layoutValue;
    }

    if ($layoutUrl !== '') {
      $candidate = __DIR__ . '/' . ltrim($layoutUrl, '/');
      if (file_exists($candidate)) {
        $pageLayoutFile = $candidate;
      } else {
        $candidate = __DIR__ . '/layouts/' . ltrim($layoutUrl, '/');
        if (file_exists($candidate)) {
          $pageLayoutFile = $candidate;
        }
      }
    }
  } catch (PDOException $e) {
    $pageLayoutFile = null;
  }
}
