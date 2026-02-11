<?php
/**
 * Two-column content layout with optional image.
 * Flips image/text order on even rows for visual rhythm.
 */

$imageLeft = !$contentIsEven;
$sectionClasses = 'service-row row align-items-center g-4';
if (!$imageLeft) {
  $sectionClasses .= ' flex-lg-row-reverse';
}
$imageHtml = '';
$imageDebugValue = strtolower(trim((string) ($_GET['imgdebug'] ?? '')));
$imageDebugEnabled = in_array($imageDebugValue, ['1', 'yes', 'true', 'on', 'debug'], true);
$imageDebugGallery = [];
$imageDebugResolved = [];
$imageDebugQueries = [];
if (function_exists('cms_render_content_images')) {
  if ($imageDebugEnabled && function_exists('cms_content_gallery_images') && function_exists('cms_content_images')) {
    $imageDebugGallery = cms_content_gallery_images($contentItem, [
      'form_id' => $contentSourceFormId ?? null,
      'form_name' => $contentSourceFormName ?? null,
      'debug' => $imageDebugEnabled,
    ]);
    $imageDebugQueries = $GLOBALS['cms_image_debug_queries'] ?? [];
    $imageDebugResolved = cms_content_images($contentItem, [
      'form_id' => $contentSourceFormId ?? null,
      'form_name' => $contentSourceFormName ?? null,
    ]);
  }
  $imageHtml = cms_render_content_images($contentItem, [
    'form_id' => $contentSourceFormId ?? null,
    'form_name' => $contentSourceFormName ?? null,
    'wrapper_class' => 'service-image',
    'img_class' => 'img-fluid',
    'sizes' => '(max-width: 992px) 100vw, 50vw',
  ]);
}
?>
<!-- layout=col2general.php layout_url=<?php echo cms_h((string) ($contentItem['layout_url'] ?? '')); ?> content_id=<?php echo cms_h((string) ($contentItem['id'] ?? '')); ?> -->

<section class="services-section cms-content-block <?php echo cms_h($contentPaddingClass); ?>">
  <?php if ($imageDebugEnabled): ?>
    <!-- col2general image debug enabled -->
  <?php endif; ?>
  <div class="container">
    <div class="<?php echo cms_h($sectionClasses); ?>">
      <div class="col-lg-6">
        <?php if ($imageHtml !== ''): ?>
          <?php echo $imageHtml; ?>
        <?php else: ?>
          <div class="service-image-placeholder">
            <span>Image Placeholder</span>
          </div>
        <?php endif; ?>
        <?php if ($imageDebugEnabled): ?>
          <div class="mt-2 p-2 border rounded bg-light small text-start">
            <strong>Image Debug</strong><br>
            content.id=<?php echo cms_h((string) ($contentItem['id'] ?? '')); ?> |
            source_form_id=<?php echo cms_h((string) ($contentSourceFormId ?? '')); ?> |
            source_form_name=<?php echo cms_h((string) ($contentSourceFormName ?? '')); ?><br>
            gallery_matches=<?php echo cms_h((string) count($imageDebugGallery)); ?> |
            resolved_images=<?php echo cms_h((string) count($imageDebugResolved)); ?><br>
            <?php if ($imageDebugGallery): ?>
              <?php foreach ($imageDebugGallery as $idx => $img): ?>
                g<?php echo cms_h((string) ($idx + 1)); ?>:
                file=<?php echo cms_h((string) ($img['filename'] ?? '')); ?>,
                mediatype=<?php echo cms_h((string) ($img['mediatype'] ?? '')); ?>,
                folder=<?php echo cms_h((string) ($img['folder'] ?? '')); ?>,
                display=<?php echo cms_h((string) ($img['display'] ?? '')); ?><br>
              <?php endforeach; ?>
            <?php else: ?>
              no gallery rows matched.<br>
            <?php endif; ?>
            <?php if ($imageDebugResolved && !$imageDebugGallery): ?>
              fallback:
              file=<?php echo cms_h((string) ($imageDebugResolved[0]['filename'] ?? '')); ?>,
              display=<?php echo cms_h((string) ($imageDebugResolved[0]['display'] ?? '')); ?>
            <?php endif; ?>
            <br>
            <strong>Gallery SQL</strong><br>
            <?php if ($imageDebugQueries): ?>
              <?php foreach ($imageDebugQueries as $q): ?>
                [<?php echo cms_h((string) ($q['label'] ?? 'query')); ?>]
                rows=<?php echo cms_h((string) ($q['rows'] ?? 0)); ?><br>
                <?php if (!empty($q['sql'])): ?>
                  <code><?php echo cms_h((string) $q['sql']); ?></code><br>
                <?php endif; ?>
                <?php if (!empty($q['params']) && is_array($q['params'])): ?>
                  <?php foreach ($q['params'] as $pKey => $pVal): ?>
                    <?php echo cms_h((string) $pKey); ?>=<?php echo cms_h((string) $pVal); ?>;
                  <?php endforeach; ?>
                  <br>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php else: ?>
              no query data captured.
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="col-lg-6">
        <div class="service-copy">
          <?php if ($contentHeading !== '' && $contentShowHeading === 'Yes'): ?>
            <<?php echo cms_h($contentHeadingTag); ?>><?php echo cms_h($contentHeading); ?></<?php echo cms_h($contentHeadingTag); ?>>
          <?php endif; ?>
          <?php if ($contentSubheading !== ''): ?>
            <p class="lead"><?php echo cms_h($contentSubheading); ?></p>
          <?php endif; ?>
          <?php if ($contentText !== ''): ?>
            <div class="content-body"><?php echo $contentText; ?></div>
          <?php endif; ?>
          <?php if ($contentText2 !== ''): ?>
            <div class="content-body"><?php echo $contentText2; ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
