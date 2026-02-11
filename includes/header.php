<?php if (!empty($IS_LOCAL)): ?>
  <div class="dev-banner">
    Development Site
  </div>
<?php endif; ?>
<header class="site-header border-bottom">
  <div class="container py-3 py-lg-4">
    <div class="row align-items-center g-3">
      <div class="col-lg-5 text-center text-lg-start">
        <div class="logo-block d-inline-flex align-items-center gap-3">
          <a href="<?php echo $baseURL; ?>/" class="logo-link">
            <img src="<?php echo $baseURL; ?>/filestore/images/logos/itfix-logo.png" alt="<?php echo cms_h(cms_pref('prefSiteName', 'ITFix')); ?> logo" class="img-fluid site-logo">
          </a>
        </div>
      </div>
      <div class="col-lg-7">
        <?php $telData = cms_tel_data('prefTel1', 'prefTelIntCode', ''); ?>
        <div class="contact-bar d-flex flex-wrap justify-content-center justify-content-lg-end gap-3">
          <a class="contact-item" href="tel:<?php echo cms_h($telData['dial']); ?>">
            <i class="fa-solid fa-phone"></i>
            <span><?php echo cms_h($telData['display']); ?></span>
          </a>
          <a class="contact-item" href="mailto:<?php echo cms_h(cms_pref('prefEmail', '')); ?>">
            <i class="fa-solid fa-envelope"></i>
            <span><?php echo cms_h(cms_pref('prefEmail', 'hardcoded')); ?></span>
          </a>
        </div>
      </div>
    </div>
  </div>
</header>
