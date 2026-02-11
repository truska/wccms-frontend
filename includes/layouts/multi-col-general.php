<?php
/**
 * Flexible multi-column text layout.
 * - 1 populated text field: single full-width column.
 * - 2 populated text fields: two equal columns.
 * - 3 populated text fields: three equal columns.
 */

$columns = [];

if ($contentText !== '') {
  $columns[] = [
    'subheading' => trim((string) ($contentSubheading1 ?? $contentItem['subheading1'] ?? $contentItem['subheading_1'] ?? '')),
    'text' => $contentText,
  ];
}

if ($contentText2 !== '') {
  $columns[] = [
    'subheading' => trim((string) ($contentSubheading2 ?? $contentItem['subheading2'] ?? $contentItem['subheading_2'] ?? $contentItem['heading2'] ?? $contentItem['heading_2'] ?? '')),
    'text' => $contentText2,
  ];
}

if ($contentText3 !== '') {
  $columns[] = [
    'subheading' => trim((string) ($contentSubheading3 ?? $contentItem['subheading3'] ?? $contentItem['subheading_3'] ?? $contentItem['heading3'] ?? $contentItem['heading_3'] ?? '')),
    'text' => $contentText3,
  ];
}

$columnCount = count($columns);
if ($columnCount === 0) {
  return;
}

$columnClass = 'col-12';
if ($columnCount === 2) {
  $columnClass = 'col-12 col-lg-6';
} elseif ($columnCount >= 3) {
  $columnClass = 'col-12 col-lg-4';
}
?>
<!-- layout=multi-col-general.php layout_url=<?php echo cms_h((string) ($contentItem['layout_url'] ?? '')); ?> content_id=<?php echo cms_h((string) ($contentItem['id'] ?? '')); ?> -->

<section class="services-section cms-content-block <?php echo cms_h($contentPaddingClass); ?>">
  <div class="container">
    <?php if (($contentHeading !== '' && $contentShowHeading === 'Yes') || $contentSubheading !== ''): ?>
      <div class="section-heading text-center">
        <?php if ($contentHeading !== '' && $contentShowHeading === 'Yes'): ?>
          <<?php echo cms_h($contentHeadingTag); ?>><?php echo cms_h($contentHeading); ?></<?php echo cms_h($contentHeadingTag); ?>>
        <?php endif; ?>
        <?php if ($contentSubheading !== ''): ?>
          <p class="lead"><?php echo cms_h($contentSubheading); ?></p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <?php foreach ($columns as $column): ?>
        <div class="<?php echo cms_h($columnClass); ?>">
          <div class="service-copy h-100 text-start">
            <?php if ($column['subheading'] !== ''): ?>
              <h3><?php echo cms_h($column['subheading']); ?></h3>
            <?php endif; ?>
            <div class="content-body"><?php echo $column['text']; ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
