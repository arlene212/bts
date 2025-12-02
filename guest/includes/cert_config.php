<?php
return [
  'required_activity_types' => ['assignment', 'project'],
  'require_quiz_pass' => true,
  'passing_score_fallback' => 70,
  'hours_strategy' => 'competency_sum',
  'eligibility_cache_ttl' => 30,
  'audit_retention_days' => 365
];
?>
