<?php
session_start();

require_once __DIR__ . '/../private/dbcon.php';
require_once __DIR__ . '/includes/prefs.php';
require_once __DIR__ . '/includes/controller.php';
require_once __DIR__ . '/wccms/includes/auth.php';

include __DIR__ . '/includes/header-code.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/menu.php';

if (empty($pageNotFound)) {
  include __DIR__ . '/includes/announcement.php';
}

if (!empty($pageNotFound)) {
  $errorPage = __DIR__ . '/error/404.html';
  if (file_exists($errorPage)) {
    include $errorPage;
  }
} elseif (!empty($pageLayoutFile) && file_exists($pageLayoutFile)) {
  include $pageLayoutFile;
} elseif (!empty($pageContentItems)) {
  include __DIR__ . '/includes/content.php';
} else {
  include __DIR__ . '/includes/contentdev.php';
}

// Temporary legacy hero block for comparison on the home page.
if (!empty($pageData['id']) && (int) $pageData['id'] === 1) {
  $legacyHeroPath = __DIR__ . '/includes/partials/hero-dev.php';
  if (file_exists($legacyHeroPath)) {
    include $legacyHeroPath;
  }
}

include __DIR__ . '/includes/footer.php';

$debugAllowed = cms_frontend_edit_allowed();

if ($debugAllowed) {
  include __DIR__ . '/includes/footer-debug.php';
}

if (cms_pref('prefCookieCheck', 'No') === 'Yes') {
  $cookieAlertPath = __DIR__ . '/includes/cookiealert.php';
  if (file_exists($cookieAlertPath)) {
    include $cookieAlertPath;
  }
}

include __DIR__ . '/includes/footer-code.php';
