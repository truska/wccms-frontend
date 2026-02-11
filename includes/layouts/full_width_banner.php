<?php
/**
 * Full browser-width banner image layout.
 * Keeps rendering simple and ready for future text overlay support.
 */

$bannerItems = function_exists('cms_content_images')
  ? cms_content_images($contentItem, [
    'form_id' => $contentSourceFormId ?? null,
    'form_name' => $contentSourceFormName ?? null,
  ])
  : [];
$bannerImage = $bannerItems[0] ?? null;
$bannerSrc = (string) ($bannerImage['display'] ?? '');
$bannerAlt = trim((string) ($bannerImage['alt'] ?? $contentHeading ?? ''));
$bannerSrcset = (string) ($bannerImage['srcset'] ?? '');
?>
<!-- layout=full_width_banner.php layout_url=<?php echo cms_h((string) ($contentItem['layout_url'] ?? '')); ?> content_id=<?php echo cms_h((string) ($contentItem['id'] ?? '')); ?> -->

<section class="full-width-banner cms-content-block <?php echo cms_h($contentPaddingClass); ?>">
  <?php if ($bannerSrc !== ''): ?>
    <img
      src="<?php echo cms_h($bannerSrc); ?>"
      alt="<?php echo cms_h($bannerAlt); ?>"
      class="full-width-banner-image"
      <?php if ($bannerSrcset !== ''): ?>
        srcset="<?php echo cms_h($bannerSrcset); ?>"
        sizes="100vw"
      <?php endif; ?>
    >
  <?php else: ?>
    <div class="full-width-banner-placeholder">No Image</div>
  <?php endif; ?>
</section>
