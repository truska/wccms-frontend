<?php
require_once __DIR__ . '/../lib/cms_log.php';
require_once __DIR__ . '/../lib/forms.php';
require_once __DIR__ . '/../../wccms/includes/email.php';

$baseURL = cms_base_url();
$contactSlug = $pageData['slug'] ?? 'contact-itfix';

$formSlug = 'contact';
$form = cms_load_form_by_slug($formSlug);
$fields = cms_load_form_fields((int) ($form['id'] ?? 0));

// Generate a CSRF token for the form.
if (empty($_SESSION['contact_form_token'])) {
  $_SESSION['contact_form_token'] = bin2hex(random_bytes(16));
}

$captchaSiteKey = cms_pref('prefCaptchaSiteKey', '');
$captchaSecret = cms_pref('prefCaptchaSecret', '');
$captchaEnabled = cms_pref('prefCaptchaEnabled', 'No') === 'Yes' && $captchaSiteKey !== '' && $captchaSecret !== '';

$spamLookupEnabled = cms_pref('prefIPSpamCheck', 'No') === 'Yes';
$spamOkThreshold = (int) cms_pref('prefSpamOK', 10);
$spamNoSendThreshold = (int) cms_pref('prefSpamNoSend', 30);
$spamNoSaveThreshold = (int) cms_pref('prefSpamNoSave', 60);
$honeypotPoints = 40;

$honeypotFields = [
  'name',
  'email',
  'tel',
  'realname',
  'company',
];

$formStatus = null;
$fieldErrors = [];
$fieldValues = $_SESSION['contact_form_values'] ?? [];
unset($_SESSION['contact_form_values']);

if (!empty($_SESSION['contact_form_flash'])) {
  $formStatus = $_SESSION['contact_form_flash'];
  unset($_SESSION['contact_form_flash']);
}

/**
 * Map dynamic fields onto the standard submission columns.
 */
function cms_map_form_fields(array $fields, array $values): array {
  $mapped = [
    'name' => null,
    'email' => null,
    'tel' => null,
    'message' => null,
    'alt1' => null,
    'alt2' => null,
    'alt3' => null,
    'alt4' => null,
    'alt5' => null,
  ];

  $idMap = [
    1 => 'name',
    7 => 'email',
    12 => 'tel',
    20 => 'message',
  ];

  foreach ($fields as $field) {
    $name = $field['input_name'] ?? $field['name'] ?? '';
    $fieldId = (int) ($field['field_id'] ?? 0);
    $mapKey = $idMap[$fieldId] ?? '';

    if ($name === '') {
      continue;
    }

    // Avoid clobbering canonical keys (name/email/tel/message) when the same
    // field type appears multiple times (e.g. two "text" fields).
    if ($mapKey !== '' && !empty($mapped[$mapKey])) {
      $mapKey = '';
    }

    if ($mapKey === '' && array_key_exists($name, $mapped) && empty($mapped[$name])) {
      $mapKey = $name;
    }

    if ($mapKey === '') {
      foreach (['alt1', 'alt2', 'alt3', 'alt4', 'alt5'] as $altKey) {
        if (empty($mapped[$altKey])) {
          $mapKey = $altKey;
          break;
        }
      }
    }

    if ($mapKey === '') {
      continue;
    }

    $mapped[$mapKey] = $values[$name] ?? null;
  }

  return $mapped;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $postedToken = $_POST['csrf_token'] ?? '';
  if ($postedToken === '' || !hash_equals($_SESSION['contact_form_token'], $postedToken)) {
    $formStatus = [
      'type' => 'error',
      'message' => 'Your session expired. Please refresh and try again.',
    ];
  } else {
    // Collect field values and validate required inputs.
    foreach ($fields as $field) {
      $name = $field['input_name'] ?? $field['name'] ?? '';
      if ($name === '') {
        continue;
      }
      $value = trim((string) ($_POST[$name] ?? ''));
      $fieldValues[$name] = $value;

      $required = (int) ($field['required'] ?? 0) === 1;
      if ($required && $value === '') {
        $fieldErrors[$name] = 'This field is required.';
        continue;
      }

      $type = strtolower((string) ($field['field_type'] ?? 'text'));
      if ($type === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        $fieldErrors[$name] = 'Please enter a valid email address.';
      }
    }

    // Verify captcha only when enabled and configured.
    if ($captchaEnabled) {
      $captchaToken = $_POST['g-recaptcha-response'] ?? '';
      if ($captchaToken === '') {
        $formStatus = [
          'type' => 'error',
          'message' => 'Please complete the captcha check.',
        ];
      } else {
        $verifyContext = stream_context_create([
          'http' => [
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query([
              'secret' => $captchaSecret,
              'response' => $captchaToken,
              'remoteip' => cms_get_client_ip(),
            ]),
            'timeout' => 4,
          ],
        ]);
        $captchaResponse = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $verifyContext);
        $captchaData = $captchaResponse ? json_decode($captchaResponse, true) : null;
        if (!is_array($captchaData) || empty($captchaData['success'])) {
          $formStatus = [
            'type' => 'error',
            'message' => 'Captcha verification failed. Please try again.',
          ];
        }
      }
    }

    if ($formStatus === null && !empty($fieldErrors)) {
      $formStatus = [
        'type' => 'error',
        'message' => 'Please fix the highlighted fields and resubmit.',
      ];
    }
  }

  if ($formStatus === null) {
    // Honeypot fields are always scored, even if the spam system is disabled.
    $honeypotValues = [];
    foreach ($honeypotFields as $honeypotField) {
      $honeypotValues[$honeypotField] = trim((string) ($_POST[$honeypotField] ?? ''));
    }

    // Gather metadata for logging and admin email context.
    $meta = cms_collect_request_meta($spamLookupEnabled);
    $spamNotesMode = cms_spam_notes_mode();
    $includeZeroChecks = ($spamNotesMode === 'all');
    $spamReasons = [];
    $spamAuditLines = [];
    $spamScore = cms_score_honeypot($honeypotValues, $spamReasons, $honeypotPoints);
    foreach ($honeypotValues as $honeypotField => $honeypotValue) {
      $honeypotHit = (trim((string) $honeypotValue) !== '');
      if ($honeypotHit || $includeZeroChecks) {
        $spamAuditLines[] = 'Honeypot [' . $honeypotField . '] ' . ($honeypotHit ? $honeypotPoints : 0);
      }
    }

    $mappedFields = cms_map_form_fields($fields, $fieldValues);
    $valuesByFormFieldId = [];
    foreach ($fields as $field) {
      $formFieldId = (int) ($field['form_field_id'] ?? 0);
      $inputName = $field['input_name'] ?? $field['name'] ?? '';
      if ($formFieldId > 0 && $inputName !== '') {
        $valuesByFormFieldId[$formFieldId] = $fieldValues[$inputName] ?? '';
      }
    }
    // Apply dynamic rules only when IP spam lookup is enabled.
    $rules = cms_load_spam_rules((int) ($form['id'] ?? 0));
    $spamScore += cms_score_spam_rules($valuesByFormFieldId, $rules, $spamReasons);
    $spamAuditLines = array_merge($spamAuditLines, cms_build_spam_rule_audit($valuesByFormFieldId, $rules, $includeZeroChecks));

    $spamAction = 'ok';
    $sendAdmin = true;
    $sendUser = true;
    $saveSubmission = true;

    if ($spamScore > $spamNoSaveThreshold) {
      $spamAction = 'nosave';
      $sendAdmin = false;
      $sendUser = false;
      $saveSubmission = false;
    } elseif ($spamScore > $spamOkThreshold) {
      $spamAction = 'nosend';
      $sendAdmin = false;
    }

    $ipData = $meta['ip_lookup'] ?? [];
    $countryRow = null;
    if (!empty($ipData['country'])) {
      $countryRow = cms_country_lookup((string) $ipData['country']);
    }
    $countryName = $countryRow['name'] ?? ($ipData['country'] ?? null);
    $countrySpamScore = (int) ($countryRow['formspamscore'] ?? 0);
    $countryCode = strtoupper(trim((string) ($ipData['country'] ?? '')));

    if ($countrySpamScore > 0) {
      $spamScore += $countrySpamScore;
      $spamReasons[] = 'Country [' . $countryCode . '] ' . $countrySpamScore;
    }
    if ($countryCode !== '' || $includeZeroChecks) {
      $spamAuditLines[] = 'Country [' . ($countryCode !== '' ? $countryCode : 'n/a') . '] ' . $countrySpamScore;
    }
    $spamNotesOutput = $includeZeroChecks ? $spamAuditLines : $spamReasons;
    $spamNotesText = !empty($spamNotesOutput) ? implode("\n", $spamNotesOutput) : null;
    $submissionId = null;

    // Persist the submission before sending emails so we can track delivery outcomes.
    if ($saveSubmission && cms_form_submissions_table_ready()) {
      try {
        $stmt = $pdo->prepare(
          'INSERT INTO contact_forms
            (form_id, name, email, tel, message, alt1, alt2, alt3, alt4, alt5,
             spam_score, spam_action, spam_notes, is_spam, honeypot_hit,
             ip, ip_country, ip_country_code, ip_region, ip_city, ip_timezone, ip_postal,
             ip_org, ip_isp, ip_lat, ip_lon,
             user_agent, browser, platform, language, referer,
             data_json, meta_json, showonweb, archived)
           VALUES
            (:form_id, :name, :email, :tel, :message, :alt1, :alt2, :alt3, :alt4, :alt5,
             :spam_score, :spam_action, :spam_notes, :is_spam, :honeypot_hit,
             :ip, :ip_country, :ip_country_code, :ip_region, :ip_city, :ip_timezone, :ip_postal,
             :ip_org, :ip_isp, :ip_lat, :ip_lon,
             :user_agent, :browser, :platform, :language, :referer,
             :data_json, :meta_json, :showonweb, :archived)'
        );
        $stmt->execute([
          ':form_id' => (int) ($form['id'] ?? 0),
          ':name' => $mappedFields['name'],
          ':email' => $mappedFields['email'],
          ':tel' => $mappedFields['tel'],
          ':message' => $mappedFields['message'],
          ':alt1' => $mappedFields['alt1'],
          ':alt2' => $mappedFields['alt2'],
          ':alt3' => $mappedFields['alt3'],
          ':alt4' => $mappedFields['alt4'],
          ':alt5' => $mappedFields['alt5'],
          ':spam_score' => $spamScore,
          ':spam_action' => $spamAction,
          ':spam_notes' => $spamNotesText,
          ':is_spam' => ($spamAction !== 'ok') ? 1 : 0,
          ':honeypot_hit' => ($spamScore > 0 && !empty(array_filter($honeypotValues))) ? 1 : 0,
          ':ip' => $meta['ip'] ?? null,
          ':ip_country' => $countryName,
          ':ip_country_code' => $ipData['country'] ?? null,
          ':ip_region' => $ipData['region'] ?? null,
          ':ip_city' => $ipData['city'] ?? null,
          ':ip_timezone' => $ipData['timezone'] ?? null,
          ':ip_postal' => $ipData['postal'] ?? null,
          ':ip_org' => $ipData['org'] ?? null,
          ':ip_isp' => $ipData['org'] ?? null,
          ':ip_lat' => $ipData['loc_lat'] ?? null,
          ':ip_lon' => $ipData['loc_lon'] ?? null,
          ':user_agent' => $meta['user_agent'] ?? null,
          ':browser' => $meta['browser'] ?? null,
          ':platform' => $meta['platform'] ?? null,
          ':language' => $meta['language'] ?? null,
          ':referer' => $meta['referer'] ?? null,
          ':data_json' => json_encode($fieldValues, JSON_UNESCAPED_UNICODE),
          ':meta_json' => json_encode([
            'honeypot' => $honeypotValues,
            'spam_reasons' => $spamReasons,
            'ip_lookup' => $ipData,
          ], JSON_UNESCAPED_UNICODE),
          ':showonweb' => 'Yes',
          ':archived' => 0,
        ]);
        $submissionId = (int) $pdo->lastInsertId();
      } catch (PDOException $e) {
        $saveSubmission = false;
      }
    }

    $userEmail = $mappedFields['email'];
    $adminEmail = cms_pref('prefManageEmail', cms_pref('prefEmail', ''));
    $adminSubject = $form['admin_subject'] ?? 'New contact form submission';
    $userSubject = $form['ack_subject'] ?? 'We received your request';

    // Send the acknowledgement to the submitter with minimal details.
    if ($sendUser && $userEmail) {
      $userBody = '<h2>Thanks for reaching out!</h2>'
        . '<p>We have received your message and will reply shortly.</p>'
        . '<p><strong>Name:</strong> ' . cms_h((string) ($mappedFields['name'] ?? '')) . '<br>'
        . '<strong>Email:</strong> ' . cms_h((string) $userEmail) . '</p>';
      cms_send_mail($userEmail, $userSubject, $userBody, '', 'web');
    }

    // Send the admin notification with full detail and metadata.
    if ($sendAdmin && $adminEmail) {
      $honeypotPassed = empty(array_filter($honeypotValues));
      $honeypotLabel = $honeypotPassed ? 'PASSED' : 'FAILED';
      $honeypotColor = $honeypotPassed ? '#198754' : '#dc3545';
      $submissionLabel = $submissionId ? (' [' . (int) $submissionId . ']') : '';

      $detailsRows = '';
      foreach ($fieldValues as $key => $value) {
        $label = $key;
        foreach ($fields as $field) {
          $fieldName = $field['input_name'] ?? $field['name'] ?? '';
          if ($fieldName === $key) {
            $label = $field['label'] ?? $key;
            break;
          }
        }
        $detailsRows .= '<tr><td style="padding:6px 10px;border:1px solid #e5e7eb;"><strong>' . cms_h($label) . '</strong></td>'
          . '<td style="padding:6px 10px;border:1px solid #e5e7eb;">' . nl2br(cms_h((string) $value)) . '</td></tr>';
      }

      $metaRows = '';
      $metaRows .= '<tr><td style="padding:6px 10px;border:1px solid #e5e7eb;"><strong>IP</strong></td>'
        . '<td style="padding:6px 10px;border:1px solid #e5e7eb;">' . cms_h((string) ($meta['ip'] ?? '')) . '</td></tr>';
      $metaRows .= '<tr><td style="padding:6px 10px;border:1px solid #e5e7eb;"><strong>Browser</strong></td>'
        . '<td style="padding:6px 10px;border:1px solid #e5e7eb;">' . cms_h((string) ($meta['browser'] ?? '')) . '</td></tr>';
      $metaRows .= '<tr><td style="padding:6px 10px;border:1px solid #e5e7eb;"><strong>Platform</strong></td>'
        . '<td style="padding:6px 10px;border:1px solid #e5e7eb;">' . cms_h((string) ($meta['platform'] ?? '')) . '</td></tr>';
      $metaRows .= '<tr><td style="padding:6px 10px;border:1px solid #e5e7eb;"><strong>Language</strong></td>'
        . '<td style="padding:6px 10px;border:1px solid #e5e7eb;">' . cms_h((string) ($meta['language'] ?? '')) . '</td></tr>';
      $metaRows .= '<tr><td style="padding:6px 10px;border:1px solid #e5e7eb;"><strong>Spam Score</strong></td>'
        . '<td style="padding:6px 10px;border:1px solid #e5e7eb;">' . cms_h((string) $spamScore) . ' (' . cms_h((string) $spamAction) . ') [' . cms_h((string) $spamOkThreshold) . ' | ' . cms_h((string) $spamNoSendThreshold) . ' | ' . cms_h((string) $spamNoSaveThreshold) . ']</td></tr>';

      if (!empty($ipData)) {
        $metaRows .= '<tr><td style="padding:6px 10px;border:1px solid #e5e7eb;"><strong>Country Code</strong></td>'
          . '<td style="padding:6px 10px;border:1px solid #e5e7eb;">' . cms_h((string) ($ipData['country'] ?? '')) . '</td></tr>';
        $metaRows .= '<tr><td style="padding:6px 10px;border:1px solid #e5e7eb;"><strong>Location</strong></td>'
          . '<td style="padding:6px 10px;border:1px solid #e5e7eb;">'
          . cms_h(trim((string) (($ipData['city'] ?? '') . ', ' . ($ipData['region'] ?? '') . ' ' . ($countryName ?? ''))))
          . '</td></tr>';
        $metaRows .= '<tr><td style="padding:6px 10px;border:1px solid #e5e7eb;"><strong>Timezone</strong></td>'
          . '<td style="padding:6px 10px;border:1px solid #e5e7eb;">' . cms_h((string) ($ipData['timezone'] ?? '')) . '</td></tr>';
        $metaRows .= '<tr><td style="padding:6px 10px;border:1px solid #e5e7eb;"><strong>ISP/Org</strong></td>'
          . '<td style="padding:6px 10px;border:1px solid #e5e7eb;">' . cms_h((string) ($ipData['org'] ?? '')) . '</td></tr>';
      }

      if (!empty($spamReasons)) {
        $metaRows .= '<tr><td style="padding:6px 10px;border:1px solid #e5e7eb;"><strong>Spam Notes</strong></td>'
          . '<td style="padding:6px 10px;border:1px solid #e5e7eb;">' . cms_h(implode(' | ', $spamReasons)) . '</td></tr>';
      }

      $adminBody = '<h2>New Contact Submission'
        . '<span style="font-size:12px;font-weight:400;color:#9ca3af;">' . cms_h($submissionLabel) . '</span>'
        . '</h2>'
        . '<p><strong>Form:</strong> ' . cms_h((string) ($form['name'] ?? 'Contact'))
        . ' <span style="font-size:12px;font-weight:600;color:#b8860b;margin-left:8px;"><i class="fa-regular fa-honey-pot" aria-hidden="true"></i></span>'
        . ' <span style="font-size:12px;font-weight:600;color:' . $honeypotColor . ';margin-left:2px;">' . $honeypotLabel . '</span>'
        . '</p>'
        . '<h3>Submitted Details</h3>'
        . '<table style="border-collapse:collapse;width:100%;">' . $detailsRows . '</table>'
        . '<h3 style="margin-top:18px;">Meta / Device Info</h3>'
        . '<table style="border-collapse:collapse;width:100%;">' . $metaRows . '</table>';

      cms_send_mail($adminEmail, $adminSubject, $adminBody, '', 'web');
    }

    // Update delivery flags after the email sends.
    cms_log_action('contact_form_submit', 'contact_forms', $submissionId, null, $form['name'] ?? 'Contact', 'web');

    $_SESSION['contact_form_flash'] = [
      'type' => 'success',
      'message' => $form['success_message'] ?? 'Thanks! Your message has been received.',
    ];
    // When headers are already sent (layout rendered after header output),
    // skip the redirect and show the success message inline.
    if (!headers_sent()) {
      header('Location: ' . $baseURL . '/' . $contactSlug . '?sent=1');
      exit;
    }
    $formStatus = $_SESSION['contact_form_flash'];
    unset($_SESSION['contact_form_flash']);
    $fieldValues = [];
  } else {
    $_SESSION['contact_form_values'] = $fieldValues;
  }
}

echo '<!-- layout=contact.php layout_url=' . cms_h((string) ($contentItem['layout_url'] ?? '')) . ' content_id=' . cms_h((string) ($contentItem['id'] ?? '')) . ' -->';
include __DIR__ . '/../partials/contact_form.php';
