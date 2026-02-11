<?php
/**
 * Render page content blocks using layout templates.
 */

if (empty($pageContentItems) || !is_array($pageContentItems)) {
  return;
}

$headingUsed = false;
$contentIndex = 0;
$GLOBALS['cms_content_debug'] = [];
foreach ($pageContentItems as $contentItem) {
  $contentIndex++;
  $GLOBALS['cms_content_debug'][] = [
    'id' => $contentItem['id'] ?? null,
    'name' => $contentItem['name'] ?? $contentItem['title'] ?? $contentItem['heading'] ?? '',
    'layout' => $contentItem['layout'] ?? $contentItem['layout_url'] ?? '',
    'layout_url' => $contentItem['layout_url'] ?? '',
    'layout_name' => $contentItem['layout_name'] ?? '',
    'sort' => $contentItem['sort'] ?? null,
  ];
  $layoutUrl = $contentItem['layout_url'] ?? '';
  $layoutFile = null;

  if ($layoutUrl !== '') {
    $candidate = __DIR__ . '/layouts/' . ltrim($layoutUrl, '/');
    if (file_exists($candidate)) {
      $layoutFile = $candidate;
    } else {
      $candidate = __DIR__ . '/' . ltrim($layoutUrl, '/');
      if (file_exists($candidate)) {
        $layoutFile = $candidate;
      }
    }
  }

  if ($layoutFile) {
    // Normalize content fields for layout templates.
    $contentTitle = $contentItem['title'] ?? '';
    $contentHeading = $contentItem['heading'] ?? '';
    $contentShowHeading = $contentItem['showheading'] ?? 'Yes';
    $contentSubheading = $contentItem['subheading'] ?? '';
    $contentSubheading1 = $contentItem['subheading1'] ?? $contentItem['subheading_1'] ?? '';
    $contentSubheading2 = $contentItem['subheading2'] ?? $contentItem['subheading_2'] ?? '';
    $contentSubheading3 = $contentItem['subheading3'] ?? $contentItem['subheading_3'] ?? '';
    $contentText1 = cms_apply_shortcodes($contentItem['text1'] ?? $contentItem['text'] ?? '');
    $contentText = $contentText1; // Backward-compatible alias for existing layouts.
    $contentText2 = cms_apply_shortcodes($contentItem['text2'] ?? '');
    $contentText3 = cms_apply_shortcodes($contentItem['text3'] ?? '');
    $contentSourceFormId = isset($contentItem['source_form_id']) && is_numeric($contentItem['source_form_id'])
      ? (int) $contentItem['source_form_id']
      : null;
    $contentSourceFormName = trim((string) ($contentItem['source_form_name'] ?? ''));
    if ($contentSourceFormName === '') {
      $contentSourceFormName = null;
    }
    $contentImage = $contentItem['image'] ?? '';
    $contentPaddingTop = $contentItem['padding-top'] ?? null;
    $contentPaddingBottom = $contentItem['padding-bottom'] ?? null;
    $contentAlignment = $contentItem['halignment'] ?? 'left';
    $contentBgColor = $contentItem['bgcolor'] ?? 'medium';
    $contentVideoRef = $contentItem['videoref'] ?? '';
    $contentLeftWidth = $contentItem['left_width'] ?? '60%';
    $contentCurvePosition = $contentItem['curveposition'] ?? 'none';
    $contentQtyRecords = $contentItem['qtyrecords'] ?? '';
    $contentAcrossGrid = $contentItem['acrossgrid'] ?? 4;
    $contentPageId = $contentItem['pageid'] ?? null;
    $contentDate = $contentItem['date'] ?? null;
    $contentAuthor = $contentItem['author'] ?? '';
    $contentIndexValue = $contentIndex;
    $contentIsEven = ($contentIndex % 2) === 0;
    $contentHeadingTag = 'h2';
    if ($contentShowHeading === 'Yes' && $contentHeading !== '' && !$headingUsed) {
      $contentHeadingTag = 'h1';
      $headingUsed = true;
    }

    $contentPaddingClass = '';
    $paddingTop = is_numeric($contentPaddingTop) ? (int) $contentPaddingTop : null;
    $paddingBottom = is_numeric($contentPaddingBottom) ? (int) $contentPaddingBottom : null;
    if ($paddingTop !== null || $paddingBottom !== null) {
      $contentPaddingClass = 'content-block-' . (int) ($contentItem['id'] ?? 0);
      $cssRules = [];
      if ($paddingTop !== null) {
        $cssRules[] = 'margin-top:' . $paddingTop . 'px';
      }
      if ($paddingBottom !== null) {
        $cssRules[] = 'margin-bottom:' . $paddingBottom . 'px';
      }
      echo '<style>.' . cms_h($contentPaddingClass) . '{' . implode(';', $cssRules) . ';}</style>';
    }

    include $layoutFile;
    continue;
  }

  // Fallback renderer if no layout template exists yet.
  $heading = $contentItem['heading'] ?? $contentItem['title'] ?? '';
  $subheading = $contentItem['subheading'] ?? '';
  $body = cms_apply_shortcodes($contentItem['text1'] ?? $contentItem['text'] ?? $contentItem['content'] ?? '');
  ?>
  <section class="content-block">
    <div class="container">
      <?php if ($heading !== ''): ?>
        <h2><?php echo cms_h($heading); ?></h2>
      <?php endif; ?>
      <?php if ($subheading !== ''): ?>
        <p class="lead"><?php echo cms_h($subheading); ?></p>
      <?php endif; ?>
      <?php if ($body !== ''): ?>
        <div class="content-body"><?php echo $body; ?></div>
      <?php endif; ?>
    </div>
  </section>
  <?php
}
