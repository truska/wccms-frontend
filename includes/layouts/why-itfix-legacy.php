<?php
/**
 * Legacy hardcoded home content for comparison.
 */
echo '<!-- layout=why-itfix-legacy.php layout_url=' . cms_h((string) ($contentItem['layout_url'] ?? '')) . ' content_id=' . cms_h((string) ($contentItem['id'] ?? '')) . ' -->';
echo '<div class="cms-edit-target">';
echo cms_render_frontend_edit_button($contentItem, ['form_id' => $contentSourceFormId ?? null]);
include __DIR__ . '/../contentdev.php';
echo '</div>';
