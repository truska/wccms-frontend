<?php
/**
 * Full-width centered text block.
 */
?>
<!-- layout=fullwidthtext.php layout_url=<?php echo cms_h((string) ($contentItem['layout_url'] ?? '')); ?> content_id=<?php echo cms_h((string) ($contentItem['id'] ?? '')); ?> -->

<section class="services-section cms-content-block <?php echo cms_h($contentPaddingClass); ?>">
  <div class="container">
    <div class="section-heading text-center">
      <?php if ($contentHeading !== '' && $contentShowHeading === 'Yes'): ?>
        <<?php echo cms_h($contentHeadingTag); ?>><?php echo cms_h($contentHeading); ?></<?php echo cms_h($contentHeadingTag); ?>>
      <?php endif; ?>
      <?php if ($contentSubheading !== ''): ?>
        <p class="lead"><?php echo cms_h($contentSubheading); ?></p>
      <?php endif; ?>
      <?php if ($contentText !== ''): ?>
        <div class="content-body"><?php echo $contentText; ?></div>
      <?php endif; ?>
    </div>
  </div>
</section>
