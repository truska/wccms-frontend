<?php
/**
 * Menu loader and tree builder.
 */

function menu_load_menu(string $slug = 'main'): ?array {
  global $pdo, $DB_OK;

  if (!$DB_OK || !($pdo instanceof PDO)) {
    return null;
  }

  try {
    $stmt = $pdo->prepare(
      'SELECT * FROM menus WHERE archived = 0 AND active = 1 AND slug = :slug LIMIT 1'
    );
    $stmt->execute([':slug' => $slug]);
    $menu = $stmt->fetch(PDO::FETCH_ASSOC);
    return $menu ?: null;
  } catch (PDOException $e) {
    return null;
  }
}

function menu_load_menu_items(int $menuId): array {
  global $pdo, $DB_OK;

  if (!$DB_OK || !($pdo instanceof PDO)) {
    return [];
  }

  try {
    $stmt = $pdo->prepare(
      'SELECT mi.*,
              p.slug AS page_slug,
              p.name AS page_name,
              p.title AS page_title,
              p.showonweb AS page_showonweb,
              p.archived AS page_archived
       FROM menu_items mi
       LEFT JOIN pages p ON p.id = mi.page_id
       WHERE mi.menu_id = :menu_id
         AND mi.archived = 0
         AND mi.showonweb = "Yes"
       ORDER BY mi.parent_id ASC, mi.sort ASC, mi.id ASC'
    );
    $stmt->execute([':menu_id' => $menuId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    return [];
  }

  $items = [];
  foreach ($rows as $row) {
    if (!empty($row['page_id'])) {
      $pageOk = ($row['page_archived'] ?? 0) == 0 && ($row['page_showonweb'] ?? 'No') === 'Yes';
      if (!$pageOk) {
        continue;
      }
    }
    $items[] = $row;
  }

  return $items;
}

function menu_build_tree(array $items): array {
  $byId = [];
  foreach ($items as $item) {
    $item['children'] = [];
    $byId[(int) $item['id']] = $item;
  }

  $tree = [];
  foreach ($byId as $id => $item) {
    $parentId = $item['parent_id'] ?? null;
    $parentId = $parentId !== null ? (int) $parentId : 0;
    if ($parentId > 0 && isset($byId[$parentId])) {
      $byId[$parentId]['children'][] = &$byId[$id];
    } else {
      $tree[] = &$byId[$id];
    }
  }

  return $tree;
}

function menu_item_label(array $item): string {
  $label = $item['label'] ?? '';
  if ($label !== '') {
    return $label;
  }
  return $item['page_title'] ?? $item['page_name'] ?? '';
}

function menu_item_url(array $item, string $baseURL): string {
  $url = trim((string) ($item['url'] ?? ''));
  if ($url !== '') {
    return $url;
  }
  $slug = trim((string) ($item['page_slug'] ?? ''));
  if ($slug === '') {
    return '#';
  }
  return rtrim($baseURL, '/') . '/' . ltrim($slug, '/');
}

function menu_item_target(array $item): string {
  return trim((string) ($item['target'] ?? ''));
}

function menu_item_is_divider(array $item): bool {
  return strtolower((string) ($item['item_type'] ?? 'link')) === 'divider';
}
