<?php
require_once __DIR__ . '/lib/menu.php';

$menu = menu_load_menu('main');
$menuItems = $menu ? menu_load_menu_items((int) $menu['id']) : [];
$menuTree = $menuItems ? menu_build_tree($menuItems) : [];

function render_menu_level(array $items, string $baseURL, int $level = 1): void {
  if (!$items) {
    return;
  }
  $ulClass = $level === 1 ? 'navbar-nav' : 'dropdown-menu';
  echo '<ul class="' . $ulClass . '">';
  foreach ($items as $item) {
    if (menu_item_is_divider($item)) {
      if ($level === 1) {
        echo '<li class="nav-divider" aria-hidden="true"></li>';
      } else {
        echo '<li><hr class="dropdown-divider"></li>';
      }
      continue;
    }

    $label = menu_item_label($item);
    $url = menu_item_url($item, $baseURL);
    $target = menu_item_target($item);
    $hasChildren = !empty($item['children']);

    if ($level === 1) {
      $liClass = $hasChildren ? 'nav-item dropdown' : 'nav-item';
      $linkClass = $hasChildren ? 'nav-link dropdown-toggle' : 'nav-link';
      $linkAttrs = $hasChildren
        ? 'role="button" data-bs-toggle="dropdown" aria-expanded="false"'
        : '';
      echo '<li class="' . $liClass . '">';
      echo '<a class="' . $linkClass . '" href="' . cms_h($url) . '" ' . $linkAttrs . '>' . cms_h($label) . '</a>';
      if ($hasChildren) {
        render_menu_level($item['children'], $baseURL, $level + 1);
      }
      echo '</li>';
      continue;
    }

    $liClass = $hasChildren ? 'dropdown-submenu' : '';
    $linkClass = $hasChildren ? 'dropdown-item dropdown-toggle' : 'dropdown-item';
    $linkAttrs = $hasChildren ? 'role="button" data-bs-toggle="dropdown" aria-expanded="false"' : '';
    $targetAttr = $target !== '' ? ' target="' . cms_h($target) . '"' : '';
    echo '<li class="' . $liClass . '">';
    echo '<a class="' . $linkClass . '" href="' . cms_h($url) . '"' . $targetAttr . ' ' . $linkAttrs . '>' . cms_h($label) . '</a>';
    if ($hasChildren) {
      render_menu_level($item['children'], $baseURL, $level + 1);
    }
    echo '</li>';
  }
  echo '</ul>';
}
?>
<nav class="navbar navbar-expand-lg navbar-dark site-nav">
  <div class="container">
    <a class="navbar-brand d-lg-none" href="#">Menu</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#primaryNav" aria-controls="primaryNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-center" id="primaryNav">
      <?php if (!empty($menuTree)): ?>
        <?php render_menu_level($menuTree, $baseURL, 1); ?>
      <?php endif; ?>
    </div>
  </div>
</nav>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var submenus = document.querySelectorAll('.dropdown-submenu');
    submenus.forEach(function (submenu) {
      submenu.addEventListener('shown.bs.dropdown', function () {
        var menu = submenu.querySelector('.dropdown-menu');
        if (!menu) return;
        menu.classList.remove('dropdown-submenu-left');
        var rect = menu.getBoundingClientRect();
        if (rect.right > window.innerWidth) {
          menu.classList.add('dropdown-submenu-left');
        }
      });
    });
  });
</script>
