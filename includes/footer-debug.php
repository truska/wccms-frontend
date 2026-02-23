<?php
$dbOk = isset($DB_OK) ? (bool) $DB_OK : (isset($pdo) && $pdo instanceof PDO);
$dbName = $DB_NAME ?? 'unknown';
$captchaEnabledPref = (string) ($prefs['prefCaptchaEnabled'] ?? ($prefs['prefCaptcha'] ?? 'No'));
$captchaVerPref = (string) ($prefs['prefCaptchaVer'] ?? '');
if ($captchaVerPref === '') {
  $captchaVerPref = '2';
}
$forwardedFor = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
$requestIp = $forwardedFor !== ''
  ? trim((string) explode(',', $forwardedFor)[0])
  : (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$requestHost = (string) ($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'unknown'));
$requestScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$requestUrl = $requestScheme . '://' . $requestHost . $requestUri;
$debugPageId = (int) ($rowpage['id'] ?? ($pageData['id'] ?? ($slugID ?? 0)));
?>
<section class="footer-debug">
  <style>
    .footer-debug {
      padding-top: 1rem;
      padding-bottom: 1rem;
      margin-top: 0.75rem;
    }
    .footer-debug .small {
      font-size: 0.875rem;
      line-height: 1.45;
    }
    .footer-debug .small > div,
    .footer-debug .content-debug-list > div {
      overflow-wrap: anywhere;
      word-break: break-word;
    }
    .footer-debug .small strong {
      white-space: nowrap;
    }
    .footer-debug .content-debug-list {
      font-size: 0.875rem;
      line-height: 1.5;
    }
    .footer-debug .content-debug-list > div {
      margin-bottom: 0.25rem;
    }
  </style>
  <div class="container">
    <div class="row g-3">
      <div class="col-sm-6 col-lg-3">
        <h6>Environment</h6>
        <?php
          $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
          $requestPath = trim((string) parse_url($requestUri, PHP_URL_PATH), '/');
          $segs = $requestPath !== '' ? array_values(array_filter(explode('/', $requestPath), 'strlen')) : [];
          if (!$segs && !empty($pageSegments) && is_array($pageSegments)) {
            $segs = array_values($pageSegments);
          }
        ?>
        <div class="small mt-2">
          <div><strong>Page</strong></div>
          <div>Page ID: <?php echo htmlspecialchars((string) $debugPageId, ENT_QUOTES); ?></div>
          <div><strong>Segments</strong></div>
          <?php if (!$segs): ?>
            <div class="text-muted">No URL segments</div>
          <?php endif; ?>
          <?php for ($i = 0; $i <= 4; $i++): ?>
            <div>
              segs[<?php echo $i; ?>]:
              <?php echo htmlspecialchars((string) ($segs[$i] ?? ''), ENT_QUOTES); ?>
            </div>
          <?php endfor; ?>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <h6>Server Stuff</h6>
        <div class="small">
          <div><strong>URL:</strong> <?php echo htmlspecialchars($requestUrl, ENT_QUOTES); ?></div>
          <div><strong>IP:</strong> <?php echo htmlspecialchars($requestIp, ENT_QUOTES); ?></div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <h6>Setting</h6>
        <div class="small">
          <div><strong>PHP:</strong> <?php echo htmlspecialchars(PHP_VERSION, ENT_QUOTES); ?></div>
          <div><strong>Captcha On:</strong> <?php echo htmlspecialchars($captchaEnabledPref, ENT_QUOTES); ?></div>
          <div><strong>Captcha Ver:</strong> <?php echo htmlspecialchars($captchaVerPref, ENT_QUOTES); ?></div>
          <div><strong>Spam Check:</strong> <?php echo htmlspecialchars((string) ($prefs['prefIPSpamCheck'] ?? 'No'), ENT_QUOTES); ?></div>
          <div><strong>Spam Thresholds:</strong> <?php echo htmlspecialchars((string) ($prefs['prefSpamOK'] ?? '10'), ENT_QUOTES); ?> | <?php echo htmlspecialchars((string) ($prefs['prefSpamNoSend'] ?? '30'), ENT_QUOTES); ?> | <?php echo htmlspecialchars((string) ($prefs['prefSpamNoSave'] ?? '60'), ENT_QUOTES); ?></div>
        </div>
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
