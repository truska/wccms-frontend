<?php
/**
 * Announcement bar (single active entry by date range).
 * - Renders directly under the menu before page content.
 * - Supports collapsed/expanded states and optional cookie suppression.
 */

if (!$DB_OK || !($pdo instanceof PDO)) {
  return;
}

if (!function_exists('cms_table_exists_local')) {
  $tableExistsLocal = static function (string $table): bool {
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
  };
} else {
  $tableExistsLocal = static function (string $table): bool {
    return cms_table_exists_local($table);
  };
}

if (!$tableExistsLocal('announcement')) {
  return;
}

// Optional suppression via cookie (controlled by prefs).
$cookieEnabled = cms_pref('prefAnnouncementCookieOn', 'No') === 'Yes';
$cookieDays = (int) cms_pref('prefAnnouncementCookieDays', 7);

try {
  $sql = 'SELECT * FROM announcement
          WHERE showonweb = :show
            AND archived = 0
            AND showfrom <= CURDATE()
            AND showto >= CURDATE()
          ORDER BY sort ASC, id ASC
          LIMIT 1';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':show' => 'Yes']);
  $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $announcement = null;
}

if (!$announcement) {
  return;
}

$announcementId = (int) ($announcement['id'] ?? 0);
$cookieName = $announcementId ? 'announcement_seen_' . $announcementId : 'announcement_seen';
if ($cookieEnabled && !empty($_COOKIE[$cookieName])) {
  return;
}

$title = trim((string) ($announcement['title'] ?? ''));
$subtitle = trim((string) ($announcement['subtitle'] ?? ''));
$heading = trim((string) ($announcement['heading'] ?? ''));
$subheading = trim((string) ($announcement['subheading'] ?? ''));
$text = (string) ($announcement['text'] ?? '');
$subheading1 = trim((string) ($announcement['subheading1'] ?? ''));
$text1 = (string) ($announcement['text1'] ?? '');
$image1 = $announcement['image1'] ?? null;
$status = strtolower(trim((string) ($announcement['status'] ?? 'hide')));
$bgColorRaw = (string) ($announcement['bgcolor'] ?? '#000000');
$textColorRaw = (string) ($announcement['textcolor'] ?? '#ffffff');
$iconOpenRaw = (string) ($announcement['iconopencolor'] ?? 'green');
$iconCloseRaw = (string) ($announcement['iconclosecolor'] ?? 'red');
$image = $announcement['image'] ?? null;

$hasSecondColumn = ($subheading1 !== '' || trim($text1) !== '' || !empty($image1));
$isExpanded = $status === 'show';

// Sanitize color input to prevent invalid CSS injection.
$sanitizeColor = static function (string $value): string {
  $value = trim($value, " \t\n\r\0\x0B'\"");
  if ($value === '') {
    return '';
  }
  if (preg_match('/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $value)) {
    return $value;
  }
  if (preg_match('/^[a-zA-Z]+$/', $value)) {
    return strtolower($value);
  }
  return '';
};

$bgColor = $sanitizeColor($bgColorRaw) ?: '#000000';
$textColor = $sanitizeColor($textColorRaw) ?: '#ffffff';
$iconOpenColor = $sanitizeColor($iconOpenRaw) ?: 'green';
$iconCloseColor = $sanitizeColor($iconCloseRaw) ?: 'red';

// Image filename stored in the announcement table.
// Primary image filename stored in the announcement table.
$announcementWebRoot = dirname(__DIR__);
$announcementImageFolder = $announcementWebRoot . '/filestore/images/content';
$announcementImageBaseUrl = $baseURL . '/filestore/images/content';

$resolveAnnouncementImage = static function (?string $filename, string $size) use ($announcementImageFolder, $announcementImageBaseUrl): string {
  $filename = ltrim((string) $filename, '/');
  if ($filename === '') {
    $fallback = $announcementImageFolder . '/' . $size . '/no-image.png';
    return file_exists($fallback)
      ? $announcementImageBaseUrl . '/' . $size . '/no-image.png'
      : '';
  }

  $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
  $base = pathinfo($filename, PATHINFO_FILENAME);
  $sizePath = $announcementImageFolder . '/' . $size;
  $sizeUrl = $announcementImageBaseUrl . '/' . $size;

  if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
    $webpPath = $sizePath . '/' . $base . '.webp';
    if (file_exists($webpPath)) {
      return $sizeUrl . '/' . $base . '.webp';
    }
  }

  $filePath = $sizePath . '/' . $filename;
  if (file_exists($filePath)) {
    return $sizeUrl . '/' . $filename;
  }

  $fallback = $sizePath . '/no-image.png';
  return file_exists($fallback) ? $sizeUrl . '/no-image.png' : '';
};

$imageSize = $hasSecondColumn ? 'md' : 'lg';
$imageUrl = $resolveAnnouncementImage($image, $imageSize);
$imageUrl1 = $resolveAnnouncementImage($image1, $imageSize);
?>
<section
  class="announcement-bar <?php echo $isExpanded ? 'is-expanded' : 'is-collapsed'; ?>"
  style="--announcement-bg: <?php echo cms_h($bgColor); ?>; --announcement-text: <?php echo cms_h($textColor); ?>; --announcement-icon-open: <?php echo cms_h($iconOpenColor); ?>; --announcement-icon-close: <?php echo cms_h($iconCloseColor); ?>;"
  data-announcement
  data-cookie-enabled="<?php echo $cookieEnabled ? '1' : '0'; ?>"
  data-cookie-name="<?php echo cms_h($cookieName); ?>"
  data-cookie-days="<?php echo cms_h((string) $cookieDays); ?>"
  aria-expanded="<?php echo $isExpanded ? 'true' : 'false'; ?>"
>
  <button class="announcement-toggle" type="button" aria-expanded="<?php echo $isExpanded ? 'true' : 'false'; ?>">
    <div class="container announcement-toggle-inner">
      <div class="announcement-heading-group">
        <?php if ($title !== ''): ?>
          <div class="announcement-heading"><?php echo cms_h($title); ?></div>
        <?php endif; ?>
        <?php if ($subtitle !== ''): ?>
          <div class="announcement-subheading"><?php echo cms_h($subtitle); ?></div>
        <?php endif; ?>
      </div>
      <span class="announcement-icon">
        <i class="fa-solid fa-angles-down announcement-icon-open" aria-hidden="true"></i>
        <i class="fa-solid fa-angles-up announcement-icon-close" aria-hidden="true"></i>
      </span>
    </div>
  </button>

  <div class="announcement-body">
    <div class="container">
      <?php if ($hasSecondColumn): ?>
        <div class="row g-4">
          <?php if ($heading !== ''): ?>
            <div class="col-12">
              <div class="announcement-title announcement-title-centered"><?php echo cms_h($heading); ?></div>
            </div>
          <?php endif; ?>
          <div class="col-12 col-lg-6">
            <?php if ($subheading !== ''): ?>
              <div class="announcement-subheading"><?php echo cms_h($subheading); ?></div>
            <?php endif; ?>
            <?php if (trim($text) !== ''): ?>
              <div class="announcement-text"><?php echo $text; ?></div>
            <?php endif; ?>
            <?php if ($imageUrl): ?>
              <img src="<?php echo cms_h($imageUrl); ?>" alt="" class="announcement-image">
            <?php endif; ?>
          </div>
          <div class="col-12 col-lg-6">
            <?php if ($subheading1 !== ''): ?>
              <div class="announcement-subheading"><?php echo cms_h($subheading1); ?></div>
            <?php endif; ?>
            <?php if (trim($text1) !== ''): ?>
              <div class="announcement-text"><?php echo $text1; ?></div>
            <?php endif; ?>
            <?php if ($imageUrl1): ?>
              <img src="<?php echo cms_h($imageUrl1); ?>" alt="" class="announcement-image">
            <?php endif; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="announcement-content">
          <?php if ($heading !== ''): ?>
            <div class="announcement-title announcement-title-centered"><?php echo cms_h($heading); ?></div>
          <?php endif; ?>
          <?php if (trim($text) !== ''): ?>
            <div class="announcement-text"><?php echo $text; ?></div>
          <?php endif; ?>
          <?php if ($imageUrl): ?>
            <img src="<?php echo cms_h($imageUrl); ?>" alt="" class="announcement-image">
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
