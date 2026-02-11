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
      if (preg_match('/\\bhttps?:\\/\\//i', $valueTrimmed) || preg_match('/\\bwww\\./i', $valueTrimmed)) {
        $score += $points;
        $reasons[] = 'Rule match: link found in field.';
      }
      continue;
    }

    if ($type === 'field_ends_caps') {
      $letters = preg_replace('/[^A-Za-z]/', '', $valueTrimmed);
      if (strlen($letters) >= 2) {
        $tail = substr($letters, -2);
        if ($tail === strtoupper($tail)) {
          $score += $points;
          $reasons[] = 'Rule match: field ends with two uppercase letters.';
        }
      }
      continue;
    }
  }

  return $score;
}
