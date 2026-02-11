<?php
$dbOk = isset($DB_OK) ? (bool) $DB_OK : (isset($pdo) && $pdo instanceof PDO);
$dbName = $DB_NAME ?? 'unknown';
?>
<section class="footer-debug">
  <div class="container">
    <div class="row g-3">
      <div class="col-sm-6 col-lg-3">
        <h6>Environment</h6>
        <p class="mb-0">Development</p>
      </div>
      <div class="col-sm-6 col-lg-3">
        <h6>Server</h6>
        <p class="mb-0"><?php echo htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'unknown', ENT_QUOTES); ?></p>
      </div>
      <div class="col-sm-6 col-lg-3">
        <h6>PHP</h6>
        <p class="mb-0"><?php echo htmlspecialchars(PHP_VERSION, ENT_QUOTES); ?></p>
      </div>
      <div class="col-sm-6 col-lg-3">
        <h6>Database</h6>
        <p class="mb-0">
          <i class="fa-solid <?php echo $dbOk ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
          <span><?php echo htmlspecialchars($dbName, ENT_QUOTES); ?></span>
        </p>
      </div>
    </div>
    <div class="row g-3 mt-2">
      <div class="col-12 col-lg-9 ms-lg-auto">
        <h6>Content Map</h6>
        <?php
          $contentDebug = $GLOBALS['cms_content_debug'] ?? [];
        ?>
        <?php if (!empty($contentDebug)): ?>
          <div class="content-debug-list">
            <?php foreach ($contentDebug as $item): ?>
              <div>
                [<?php echo htmlspecialchars((string) ($item['id'] ?? ''), ENT_QUOTES); ?>]
                | <?php echo htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES); ?>
                {Layout: <?php echo htmlspecialchars((string) ($item['layout'] ?? ''), ENT_QUOTES); ?>
                | URL: <?php echo htmlspecialchars((string) ($item['layout_url'] ?? ''), ENT_QUOTES); ?>
                | <?php echo htmlspecialchars((string) ($item['layout_name'] ?? ''), ENT_QUOTES); ?>}
                | Sort: <?php echo htmlspecialchars((string) ($item['sort'] ?? ''), ENT_QUOTES); ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="mb-0">No content blocks found.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
