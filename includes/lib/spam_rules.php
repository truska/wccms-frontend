<?php
/**
 * Spam rule engine.
 * Keep all rule handlers here so the main form logic stays clean.
 */

function cms_run_spam_rules(array $valuesByFormFieldId, array $rules, array &$reasons): int {
  $score = 0;

  foreach ($rules as $rule) {
    $type = strtolower($rule['rule_code'] ?? '');
    $formFieldId = (int) ($rule['form_field_id'] ?? 0);
    $matchValue = (string) ($rule['match_value'] ?? '');
    $points = (int) ($rule['points'] ?? 0);

    if ($type === '' || $points === 0) {
      continue;
    }

    $value = (string) ($valuesByFormFieldId[$formFieldId] ?? '');
    $valueTrimmed = trim($value);
    if ($valueTrimmed === '') {
      continue;
    }

    if ($type === 'field_has_link') {
      if (cms_value_has_link($valueTrimmed)) {
        $ruleName = trim((string) ($rule['name'] ?? ''));
        if ($ruleName === '') {
          $ruleName = trim((string) ($rule['rule_code'] ?? 'rule'));
        }
        $score += $points;
        $reasons[] = 'Rule [' . $ruleName . '] ' . $points;
      }
      continue;
    }

    if ($type === 'field_ends_caps') {
      $letters = preg_replace('/[^A-Za-z]/', '', $valueTrimmed);
      if (strlen($letters) >= 2) {
        $tail = substr($letters, -2);
        if ($tail === strtoupper($tail)) {
          $ruleName = trim((string) ($rule['name'] ?? ''));
          if ($ruleName === '') {
            $ruleName = trim((string) ($rule['rule_code'] ?? 'rule'));
          }
          $score += $points;
          $reasons[] = 'Rule [' . $ruleName . '] ' . $points;
        }
      }
      continue;
    }
  }

  return $score;
}

function cms_value_has_link(string $value): bool {
  if ($value === '') {
    return false;
  }

  if (preg_match('/\\bhttps?:\\/\\/\\S+/i', $value) || preg_match('/\\bwww\\.[^\\s]+/i', $value)) {
    return true;
  }

  // Catch plain domains like "example.com" while skipping email addresses.
  return (bool) preg_match('/(?<!@)\\b[a-z0-9][a-z0-9-]{0,62}\\.[a-z]{2,24}\\b/i', $value);
}
