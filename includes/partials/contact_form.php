<?php
$contactSlug = $contactSlug ?? 'contact-itfix';
?>
<main class="contact-page">
  <section class="contact-hero">
    <div class="container">
      <div class="row align-items-center g-4">
        <div class="col-lg-6">
          <p class="eyebrow">Contact</p>
          <h1 class="display-5"><?php echo cms_h($form['name'] ?? 'Contact ITFix'); ?></h1>
          <p class="lead"><?php echo cms_h($form['description'] ?? 'Tell us about your needs and we will be in touch.'); ?></p>
          <div class="contact-card">
            <h5>Contact Details</h5>
            <ul class="list-unstyled contact-details">
              <?php
                $contactTel = cms_tel_data('prefTel1', 'prefTelIntCode', '');
                $contactEmail = cms_pref('prefEmail', '');
                $contactAddress = cms_pref('prefAddress1', '');
              ?>
              <?php if ($contactTel['display'] !== ''): ?>
                <li>
                  <i class="fa-solid fa-phone"></i>
                  <a href="tel:<?php echo cms_h($contactTel['dial']); ?>"><?php echo cms_h($contactTel['display']); ?></a>
                </li>
              <?php endif; ?>
              <?php if ($contactEmail !== ''): ?>
                <li><i class="fa-solid fa-envelope"></i> <?php echo cms_h($contactEmail); ?></li>
              <?php endif; ?>
              <?php if ($contactAddress !== ''): ?>
                <li><i class="fa-solid fa-location-dot"></i> <?php echo cms_h($contactAddress); ?></li>
              <?php endif; ?>
            </ul>
            <p class="small text-muted">Support hours: <?php echo cms_h(cms_pref('prefHours', 'Mon-Fri 8am-6pm')); ?></p>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="contact-form-card">
            <h4>Send us a message</h4>
            <p class="text-muted">We respond quickly during business hours.</p>

            <?php if ($formStatus): ?>
              <div class="form-status form-status-<?php echo cms_h($formStatus['type']); ?>">
                <?php echo cms_h($formStatus['message']); ?>
              </div>
            <?php endif; ?>

            <form method="post" action="<?php echo cms_h($baseURL . '/' . $contactSlug); ?>" novalidate>
              <input type="hidden" name="csrf_token" value="<?php echo cms_h($_SESSION['contact_form_token']); ?>">

              <div class="hp-field">
                <label for="name">Name</label>
                <input type="text" name="name" id="name" autocomplete="off">
              </div>
              <div class="hp-field">
                <label for="email">Email</label>
                <input type="text" name="email" id="email" autocomplete="off">
              </div>
              <div class="hp-field">
                <label for="tel">Telephone</label>
                <input type="text" name="tel" id="tel" autocomplete="off">
              </div>
              <div class="hp-field">
                <label for="realname">Real name</label>
                <input type="text" name="realname" id="realname" autocomplete="off">
              </div>
              <div class="hp-field">
                <label for="company">Company</label>
                <input type="text" name="company" id="company" autocomplete="off">
              </div>

              <?php foreach ($fields as $field): ?>
                <?php
                  $name = $field['input_name'] ?? $field['name'] ?? '';
                  if ($name === '') {
                    continue;
                  }
                  $label = $field['label'] ?? $name;
                  $type = strtolower((string) ($field['field_type'] ?? 'text'));
                  $placeholder = $field['placeholder'] ?? '';
                  $required = (int) ($field['required'] ?? 0) === 1;
                  $value = $fieldValues[$name] ?? '';
                  $hasError = isset($fieldErrors[$name]);
                  $tooltip = $field['tooltip'] ?? '';
                ?>
                <div class="mb-3">
                  <?php if ($type === 'checkbox'): ?>
                    <div class="form-check">
                      <input class="form-check-input <?php echo $hasError ? 'is-invalid' : ''; ?>" type="checkbox" name="<?php echo cms_h($name); ?>" id="<?php echo cms_h($name); ?>" value="Yes" <?php echo ($value === 'Yes') ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="<?php echo cms_h($name); ?>">
                        <?php echo cms_h($label); ?>
                        <?php if ($required): ?><span class="text-danger">*</span><?php endif; ?>
                        <?php if ($tooltip !== ''): ?>
                          <span class="form-tooltip" title="<?php echo cms_h($tooltip); ?>">?</span>
                        <?php endif; ?>
                      </label>
                    </div>
                  <?php else: ?>
                    <label class="form-label" for="<?php echo cms_h($name); ?>">
                      <?php echo cms_h($label); ?>
                      <?php if ($required): ?><span class="text-danger">*</span><?php endif; ?>
                      <?php if ($tooltip !== ''): ?>
                        <span class="form-tooltip" title="<?php echo cms_h($tooltip); ?>">?</span>
                      <?php endif; ?>
                    </label>
                    <?php if ($type === 'textarea'): ?>
                      <textarea class="form-control <?php echo $hasError ? 'is-invalid' : ''; ?>" name="<?php echo cms_h($name); ?>" id="<?php echo cms_h($name); ?>" rows="4" placeholder="<?php echo cms_h($placeholder); ?>"><?php echo cms_h($value); ?></textarea>
                    <?php else: ?>
                      <input class="form-control <?php echo $hasError ? 'is-invalid' : ''; ?>" type="<?php echo cms_h($type); ?>" name="<?php echo cms_h($name); ?>" id="<?php echo cms_h($name); ?>" value="<?php echo cms_h($value); ?>" placeholder="<?php echo cms_h($placeholder); ?>">
                    <?php endif; ?>
                  <?php endif; ?>
                  <?php if (!empty($field['help_text'])): ?>
                    <small class="form-text text-muted"><?php echo cms_h((string) $field['help_text']); ?></small>
                  <?php endif; ?>
                  <?php if ($hasError): ?>
                    <div class="invalid-feedback"><?php echo cms_h($fieldErrors[$name]); ?></div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>

              <?php if ($captchaEnabled): ?>
                <div class="mb-3">
                  <div class="g-recaptcha" data-sitekey="<?php echo cms_h($captchaSiteKey); ?>"></div>
                </div>
              <?php endif; ?>

              <button type="submit" class="btn btn-primary btn-lg w-100">Send Message</button>
              <p class="form-note">By submitting, you agree to be contacted about your request.</p>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php if ($captchaEnabled): ?>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
