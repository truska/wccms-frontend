<?php
/**
 * Form utilities for dynamic form rendering and submission handling.
 * Keep logic here so the contact page stays lean and reusable.
 */
require_once __DIR__ . '/spam_rules.php';

function cms_table_exists(string $table): bool {
  global $pdo, $DB_OK;

  if (!$DB_OK || !($pdo instanceof PDO)) {
    return false;
  }

  try {
    $stmt = $pdo->prepare('SHOW TABLES LIKE :table');
    $stmt->execute([':table' => $table]);
    return (bool) $stmt->fetchColumn();
  } catch (PDOException $e) {
    return false;
  }
}

function cms_forms_tables_ready(): bool {
  return cms_table_exists('cms_contact_form') && cms_table_exists('cms_contact_form_fields') && cms_table_exists('cms_field');
}

function cms_form_submissions_table_ready(): bool {
  return cms_table_exists('contact_forms');
}

function cms_form_rules_table_ready(): bool {
  return cms_table_exists('cms_form_spam_rules') && cms_table_exists('cms_spam_rule_catalog');
}

function cms_form_site_key(): string {
  return $_SERVER['HTTP_HOST'] ?? 'default';
}

function cms_default_contact_form(): array {
  return [
    'id' => 0,
    'slug' => 'contact',
    'name' => 'Contact Us',
    'description' => 'Tell us about your request and we will reply shortly.',
    'success_message' => 'Thanks! Your message has been received. We will get back to you shortly.',
    'ack_subject' => 'We received your request',
    'admin_subject' => 'New contact form submission',
  ];
}

function cms_default_contact_fields(): array {
  return [
    [
      'name' => 'field_1',
      'input_name' => 'field_1',
      'field_id' => 1,
      'label' => 'Full Name',
      'field_type' => 'text',
      'placeholder' => 'Jane Smith',
      'help_text' => '',
      'required' => 1,
      'sort' => 10,
      'options_json' => null,
      'map_key' => 'name',
    ],
    [
      'name' => 'field_7',
      'input_name' => 'field_7',
      'field_id' => 7,
      'label' => 'Email Address',
      'field_type' => 'email',
      'placeholder' => 'jane@company.com',
      'help_text' => '',
      'required' => 1,
      'sort' => 20,
      'options_json' => null,
      'map_key' => 'email',
    ],
    [
      'name' => 'field_12',
      'input_name' => 'field_12',
      'field_id' => 12,
      'label' => 'Phone Number',
      'field_type' => 'tel',
      'placeholder' => '+1 555 123 4567',
      'help_text' => '',
      'required' => 0,
      'sort' => 30,
      'options_json' => null,
      'map_key' => 'tel',
    ],
    [
      'name' => 'field_20',
      'input_name' => 'field_20',
      'field_id' => 20,
      'label' => 'How can we help?',
      'field_type' => 'textarea',
      'placeholder' => 'Tell us about your issue or project...',
      'help_text' => '',
      'required' => 1,
      'sort' => 40,
      'options_json' => null,
      'map_key' => 'message',
    ],
  ];
}

function cms_load_form_by_slug(string $slug): array {
  global $pdo;

  if (!cms_forms_tables_ready()) {
    return cms_default_contact_form();
  }

  try {
    $stmt = $pdo->prepare(
      'SELECT *
       FROM cms_contact_form
       WHERE archived = 0 AND showonweb = "Yes" AND slug = :slug
       ORDER BY id DESC
       LIMIT 1'
    );
    $stmt->execute([
      ':slug' => $slug,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: cms_default_contact_form();
  } catch (PDOException $e) {
    return cms_default_contact_form();
  }
}

function cms_load_form_fields(int $formId): array {
  global $pdo;

  if (!cms_forms_tables_ready() || $formId <= 0) {
    return cms_default_contact_fields();
  }

  try {
    $stmt = $pdo->prepare(
      'SELECT
        j.id AS form_field_id,
        f.id AS field_id,
        f.title AS label,
        f.type AS field_type,
        j.label_override,
        j.placeholder,
        j.help_text,
        j.tooltip,
        j.required,
        j.sort
       FROM cms_contact_form_fields j
       JOIN cms_field f ON f.id = j.field_id
       WHERE j.form_id = :form_id
         AND j.archived = 0
         AND j.showonweb = "Yes"
         AND f.archived = 0
         AND f.showonweb = "Yes"
       ORDER BY j.sort ASC, j.id ASC'
    );
    $stmt->execute([':form_id' => $formId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
      return cms_default_contact_fields();
    }

    // Normalize override values so the form renderer can use a single key.
    foreach ($rows as &$row) {
      $row['label'] = $row['label_override'] ?: $row['label'];
      $row['placeholder'] = $row['placeholder'] ?? '';
      $row['help_text'] = $row['help_text'] ?? '';
      $row['input_name'] = 'field_' . $row['field_id'];
      $row['name'] = $row['input_name'];
    }
    unset($row);
    return $rows;
  } catch (PDOException $e) {
    return cms_default_contact_fields();
  }
}

function cms_get_client_ip(): string {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '';
  if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $candidate = trim($parts[0]);
    if (filter_var($candidate, FILTER_VALIDATE_IP)) {
      $ip = $candidate;
    }
  }
  return $ip;
}

function cms_parse_user_agent(string $ua): array {
  $browser = 'Unknown';
  $platform = 'Unknown';

  $uaLower = strtolower($ua);
  if (strpos($uaLower, 'windows') !== false) {
    $platform = 'Windows';
  } elseif (strpos($uaLower, 'mac os') !== false || strpos($uaLower, 'macintosh') !== false) {
    $platform = 'macOS';
  } elseif (strpos($uaLower, 'linux') !== false) {
    $platform = 'Linux';
  } elseif (strpos($uaLower, 'iphone') !== false || strpos($uaLower, 'ipad') !== false) {
    $platform = 'iOS';
  } elseif (strpos($uaLower, 'android') !== false) {
    $platform = 'Android';
  }

  if (strpos($uaLower, 'edg') !== false) {
    $browser = 'Edge';
  } elseif (strpos($uaLower, 'chrome') !== false) {
    $browser = 'Chrome';
  } elseif (strpos($uaLower, 'safari') !== false) {
    $browser = 'Safari';
  } elseif (strpos($uaLower, 'firefox') !== false) {
    $browser = 'Firefox';
  }

  return [
    'browser' => $browser,
    'platform' => $platform,
  ];
}

function cms_lookup_ip_data(string $ip): array {
  if ($ip === '') {
    return [];
  }

  $token = cms_pref('prefIPInfoToken', '');
  $query = $token !== '' ? ('?token=' . urlencode($token)) : '';
  $url = 'https://ipinfo.io/' . urlencode($ip) . '/json' . $query;
  $context = stream_context_create([
    'http' => [
      'timeout' => 3,
      'header' => "User-Agent: itfix-contact-form\r\n",
    ],
  ]);

  $response = @file_get_contents($url, false, $context);
  if ($response === false) {
    return [];
  }

  $data = json_decode($response, true);
  if (!is_array($data)) {
    return [];
  }

  if (!empty($data['loc'])) {
    $parts = explode(',', (string) $data['loc']);
    if (count($parts) === 2) {
      $data['loc_lat'] = trim($parts[0]);
      $data['loc_lon'] = trim($parts[1]);
    }
  }

  return $data;
}

function cms_collect_request_meta(bool $doIpLookup): array {
  $ip = cms_get_client_ip();
  $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
  $language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
  $referer = $_SERVER['HTTP_REFERER'] ?? '';

  $meta = [
    'ip' => $ip,
    'user_agent' => $userAgent,
    'language' => $language,
    'referer' => $referer,
  ];

  $uaInfo = cms_parse_user_agent($userAgent);
  $meta['browser'] = $uaInfo['browser'];
  $meta['platform'] = $uaInfo['platform'];

  if ($doIpLookup) {
    $meta['ip_lookup'] = cms_lookup_ip_data($ip);
  }

  return $meta;
}

function cms_country_lookup(string $code): ?array {
  global $pdo, $DB_OK;

  if (!$DB_OK || !($pdo instanceof PDO) || $code === '') {
    return null;
  }

  try {
    $stmt = $pdo->prepare('SELECT * FROM country WHERE archived = 0 AND code = :code LIMIT 1');
    $stmt->execute([':code' => strtoupper($code)]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  } catch (PDOException $e) {
    return null;
  }
}

function cms_score_honeypot(array $honeypotValues, array &$reasons, int $points = 40): int {
  foreach ($honeypotValues as $name => $value) {
    if (trim((string) $value) !== '') {
      $reasons[] = 'Honeypot field "' . $name . '" had a value.';
      return $points;
    }
  }
  return 0;
}

function cms_load_spam_rules(int $formId): array {
  global $pdo;

  if (!cms_form_rules_table_ready()) {
    return [];
  }

  try {
    $stmt = $pdo->prepare(
      'SELECT r.*, c.code AS rule_code
       FROM cms_form_spam_rules r
       JOIN cms_spam_rule_catalog c ON c.id = r.rule_id
       JOIN cms_contact_form_fields f ON f.id = r.form_field_id
       WHERE r.archived = 0 AND r.active = 1
         AND f.form_id = :form_id
         AND c.archived = 0 AND c.active = 1
       ORDER BY r.sort ASC, r.id ASC'
    );
    $stmt->execute([':form_id' => $formId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $rows ?: [];
  } catch (PDOException $e) {
    return [];
  }
}

function cms_score_spam_rules(array $valuesByFormFieldId, array $rules, array &$reasons): int {
  return cms_run_spam_rules($valuesByFormFieldId, $rules, $reasons);
}
