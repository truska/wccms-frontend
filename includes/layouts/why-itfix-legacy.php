<?php
/**
 * Legacy hardcoded home content for comparison.
 */
echo '<!-- layout=why-itfix-legacy.php layout_url=' . cms_h((string) ($contentItem['layout_url'] ?? '')) . ' content_id=' . cms_h((string) ($contentItem['id'] ?? '')) . ' -->';
include __DIR__ . '/../contentdev.php';
