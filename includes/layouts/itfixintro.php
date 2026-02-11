<?php
/**
 * Hero intro layout for ITFix.
 */

$introEyebrow = $contentTitle !== '' ? $contentTitle : 'Managed IT Services';
$introHeading = $contentHeading !== '' ? $contentHeading : 'Technology that stays out of your way and powers your business.';
$introLead = $contentSubheading !== '' ? $contentSubheading : 'We keep your systems secure, your people productive, and your budget predictable. From daily support to long-term strategy, ITFix becomes your on-call technology partner.';
$cardTitle = $contentText !== '' ? strip_tags($contentText) : 'Quick Response. Proactive Care.';
$cardBody = $contentText2 !== '' ? $contentText2 : 'Our local technicians respond in minutes, not days. We monitor your systems, patch vulnerabilities, and keep your team online.';
$cardListRaw = $contentText3 !== '' ? strip_tags($contentText3) : "Security-first support\nPerformance monitoring\nDedicated account management";
$cardItems = array_filter(array_map('trim', preg_split('/\\r?\\n/', $cardListRaw)));
?>
<!-- layout=itfixintro.php layout_url=<?php echo cms_h((string) ($contentItem['layout_url'] ?? '')); ?> content_id=<?php echo cms_h((string) ($contentItem['id'] ?? '')); ?> -->

<section id="intro" class="hero-section cms-content-block <?php echo cms_h($contentPaddingClass); ?>">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-6">
        <p class="eyebrow"><?php echo cms_h($introEyebrow); ?></p>
        <<?php echo cms_h($contentHeadingTag); ?> class="display-5"><?php echo cms_h($introHeading); ?></<?php echo cms_h($contentHeadingTag); ?>>
        <p class="lead"><?php echo cms_h($introLead); ?></p>
        <div class="d-flex flex-wrap gap-3">
          <a href="<?php echo cms_h($baseURL . '/contact-itfix'); ?>" class="btn btn-primary btn-lg">Talk to an Expert</a>
          <a href="<?php echo cms_h($baseURL . '/#services'); ?>" class="btn btn-outline-light btn-lg">Explore Services</a>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="hero-card">
          <div class="hero-card-inner">
            <h3><?php echo cms_h($cardTitle); ?></h3>
            <p><?php echo $cardBody; ?></p>
            <?php if (!empty($cardItems)): ?>
              <ul class="list-unstyled">
                <?php foreach ($cardItems as $item): ?>
                  <li><i class="fa-solid fa-check"></i> <?php echo cms_h($item); ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
