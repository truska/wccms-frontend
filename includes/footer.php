<footer id="contact" class="site-footer">
  <?php
  if (!function_exists('cms_render_copyright_notice')) {
    /**
     * Build a standard copyright notice from site preferences.
     */
    function cms_render_copyright_notice(string $companyName): string {
      $currentYear = date('Y');
      $startYear = trim((string) cms_pref('prefCopyrightStartYear', ''));
      $yearText = $currentYear;

      if ($startYear !== '' && preg_match('/^\d{4}$/', $startYear) && $startYear !== $currentYear) {
        $yearText = $startYear . ' &mdash; ' . $currentYear;
      }

      return '&copy; ' . $yearText . ' ' . cms_h($companyName) . '. All rights reserved.';
    }
  }

  $footerCompanyName = trim((string) cms_pref('prefCompanyName', 'ITFix'));
  $siteFooterLogo = trim((string) cms_pref('prefLogo1', 'itfix-logo-sq.png'));
  $footerServiceLinks = [];
  require_once __DIR__ . '/lib/menu.php';
  $mainMenu = menu_load_menu('main');
  if ($mainMenu) {
    $footerColumnItems = menu_load_footer_items((int) $mainMenu['id'], 3);
    foreach ($footerColumnItems as $footerItem) {
      $linkLabel = trim(menu_item_label($footerItem));
      if ($linkLabel === '') {
        continue;
      }
      $footerServiceLinks[] = [
        'label' => $linkLabel,
        'url' => menu_item_url($footerItem, $baseURL),
        'target' => menu_item_target($footerItem),
      ];
    }

    if (empty($footerServiceLinks)) {
      $mainItems = menu_load_menu_items((int) $mainMenu['id']);
      $mainTree = menu_build_tree($mainItems);
      foreach ($mainTree as $topItem) {
        if (strtolower(menu_item_label($topItem)) !== 'services') {
          continue;
        }
        foreach (($topItem['children'] ?? []) as $childItem) {
          if (menu_item_is_divider($childItem)) {
            continue;
          }
          $linkLabel = trim(menu_item_label($childItem));
          if ($linkLabel === '') {
            continue;
          }
          $footerServiceLinks[] = [
            'label' => $linkLabel,
            'url' => menu_item_url($childItem, $baseURL),
            'target' => menu_item_target($childItem),
          ];
        }
        break;
      }
    }
  }
  ?>
  <div class="container">
    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <h4><?php echo cms_h($footerCompanyName); ?></h4>
        <p>Dependable technology support, tailored for small and mid-sized businesses.</p>
      </div>
      <div class="col-md-6 col-lg-3">
        <h5>Contact</h5>
        <?php $telData = cms_tel_data('prefTel1', 'prefTelIntCode', ''); ?>
        <ul class="footer-list">
          <li>
            <a href="tel:<?php echo cms_h($telData['dial']); ?>">
              <i class="fa-solid fa-phone"></i>
              <span><?php echo cms_h($telData['display']); ?></span>
            </a>
          </li>
          <li>
            <a href="mailto:<?php echo cms_h(cms_pref('prefEmail', '')); ?>">
              <i class="fa-solid fa-envelope"></i>
              <span><?php echo cms_h(cms_pref('prefEmail', 'scott@itfix.com')); ?></span>
            </a>
          </li>
        </ul>
      </div>
      <div class="col-md-6 col-lg-3">
        <h5>Services</h5>
        <ul class="footer-list">
          <?php if (!empty($footerServiceLinks)): ?>
            <?php foreach ($footerServiceLinks as $serviceLink): ?>
              <li>
                <a href="<?php echo cms_h($serviceLink['url']); ?>"<?php echo $serviceLink['target'] !== '' ? ' target="' . cms_h($serviceLink['target']) . '" rel="noopener"' : ''; ?>>
                  <?php echo cms_h($serviceLink['label']); ?>
                </a>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li><a href="<?php echo $baseURL; ?>/#service-helpdesk">Helpdesk Support</a></li>
            <li><a href="<?php echo $baseURL; ?>/#service-network">Network Management</a></li>
            <li><a href="<?php echo $baseURL; ?>/#service-cybersecurity">Cybersecurity</a></li>
            <li><a href="<?php echo $baseURL; ?>/#service-strategy">Strategic Planning</a></li>
          <?php endif; ?>
        </ul>
      </div>
      <div class="col-md-6 col-lg-3">
        <h5>Follow</h5>
        <div class="social-links">
          <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin"></i></a>
          <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
          <a href="#" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a>
        </div>
        <p class="small">Business hours: Mon-Fri 8am-6pm</p>
        <img src="<?php echo $baseURL; ?>/filestore/images/logos/<?php echo cms_h($siteFooterLogo !== '' ? $siteFooterLogo : 'itfix-logo-sq.png'); ?>" alt="ITFix logo highlight" class="img-fluid footer-logo">
      </div>
    </div>
    <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <span><?php echo cms_render_copyright_notice($footerCompanyName); ?></span>
      <span>Built on <a href="https://triska.com">wITeCanvas</a> — By Truska</span>
    </div>
  </div>
</footer>
