<footer id="contact" class="site-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <h4><?php echo cms_h(cms_pref('prefCompanyName', 'ITFix')); ?></h4>
        <p>Dependable technology support, tailored for small and mid-sized businesses.</p>
      </div>
      <div class="col-md-6 col-lg-3">
        <h5>Contact</h5>
        <?php $telData = cms_tel_data('prefTel1', 'prefTelIntCode', ''); ?>
        <ul class="footer-list">
          <li>
            <a href="tel:<?php echo cms_h($telData['dial']); ?>">
              <i class="fa-solid fa-phone"></i>
              <span><?php echo cms_h($telData['display']); ?></span>
            </a>
          </li>
          <li>
            <a href="mailto:<?php echo cms_h(cms_pref('prefEmail', '')); ?>">
              <i class="fa-solid fa-envelope"></i>
              <span><?php echo cms_h(cms_pref('prefEmail', 'scott@itfix.com')); ?></span>
            </a>
          </li>
        </ul>
      </div>
      <div class="col-md-6 col-lg-3">
        <h5>Services</h5>
        <ul class="footer-list">
          <li><a href="<?php echo $baseURL; ?>/#service-helpdesk">Helpdesk Support</a></li>
          <li><a href="<?php echo $baseURL; ?>/#service-network">Network Management</a></li>
          <li><a href="<?php echo $baseURL; ?>/#service-cybersecurity">Cybersecurity</a></li>
          <li><a href="<?php echo $baseURL; ?>/#service-strategy">Strategic Planning</a></li>
        </ul>
      </div>
      <div class="col-md-6 col-lg-3">
        <h5>Follow</h5>
        <div class="social-links">
          <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin"></i></a>
          <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
          <a href="#" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a>
        </div>
        <p class="small">Business hours: Mon-Fri 8am-6pm</p>
        <img src="<?php echo $baseURL; ?>/filestore/images/logos/itfix-logo-sq.png" alt="ITFix logo highlight" class="img-fluid footer-logo">
      </div>
    </div>
    <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <span>© 2026 <?php echo cms_h(cms_pref('prefCompanyName', 'ITFix')); ?>. All rights reserved.</span>
      <span>Built on <a href="https://triska.com">wITeCanvas</a> — By Truska</span>
    </div>
  </div>
</footer>
