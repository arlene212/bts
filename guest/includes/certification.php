<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/crypto.php';

function cert_get_config()
{
  return include __DIR__ . '/cert_config.php';
}

function cert_verify_enrollment($pdo, $userId, $courseCode)
{
  $stmt = $pdo->prepare("SELECT 1 FROM enrollments WHERE trainee_id = ? AND course_code = ? AND status = 'approved' LIMIT 1");
  $stmt->execute([$userId, $courseCode]);
  return (bool)$stmt->fetchColumn();
}

function cert_fetch_course($pdo, $courseCode)
{
  $stmt = $pdo->prepare("SELECT course_name, course_code, hours, description, image FROM courses WHERE course_code = ?");
  $stmt->execute([$courseCode]);
  return $stmt->fetch(PDO::FETCH_ASSOC);
}

function cert_fetch_competencies($pdo, $courseCode)
{
  $codesStmt = $pdo->prepare("SELECT DISTINCT competency_id FROM course_topics WHERE course_code = ?");
  $codesStmt->execute([$courseCode]);
  $codeRows = $codesStmt->fetchAll(PDO::FETCH_ASSOC);
  $codes = array_map(function($r){ return $r['competency_id']; }, $codeRows);
  if (empty($codes)) { return []; }
  $placeholders = implode(',', array_fill(0, count($codes), '?'));
  $cstmt = $pdo->prepare("SELECT competency_code, competency_name, module_title, competency_type, nominal_hours FROM competencies WHERE competency_code IN ($placeholders) AND status = 'active' AND competency_type = 'basic'");
  $cstmt->execute($codes);
  $res = [];
  foreach ($cstmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $res[$row['competency_code']] = ['code' => $row['competency_code'], 'name' => $row['competency_name'], 'hours' => (float)$row['nominal_hours']];
  }
  return $res;
}

function cert_fetch_activities_by_competency($pdo, $courseCode)
{
  $stmt = $pdo->prepare("SELECT ct.id as topic_id, ct.competency_id, ta.id as activity_id, ta.activity_title, ta.activity_type, ta.due_date FROM course_topics ct LEFT JOIN topic_activities ta ON ct.id = ta.topic_id WHERE ct.course_code = ? ORDER BY ct.created_at ASC, ta.created_at ASC");
  $stmt->execute([$courseCode]);
  $items = [];
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    if (!$r['activity_id'] || !$r['competency_id']) { continue; }
    $items[$r['competency_id']][] = ['id' => $r['activity_id'], 'title' => $r['activity_title'], 'type' => strtolower($r['activity_type'] ?? ''), 'due_date' => $r['due_date']];
  }
  return $items;
}

function cert_fetch_course_quizzes($pdo, $courseCode)
{
  $stmt = $pdo->prepare("SELECT id, title, passing_score FROM quizzes WHERE course_code = ? AND status IN ('active','published')");
  $stmt->execute([$courseCode]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function cert_has_submission($pdo, $activityId, $userId)
{
  $stmt = $pdo->prepare("SELECT 1 FROM activity_submissions WHERE activity_id = ? AND guest_id = ? LIMIT 1");
  $stmt->execute([$activityId, $userId]);
  return (bool)$stmt->fetchColumn();
}

function cert_quiz_passed($pdo, $quizId, $userId)
{
  $stmt = $pdo->prepare("SELECT qa.score, q.passing_score FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id = q.id WHERE qa.quiz_id = ? AND qa.trainee_id = ? ORDER BY qa.completed_at DESC LIMIT 1");
  $stmt->execute([$quizId, $userId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) { return false; }
  $score = (int)$row['score'];
  $pass = (int)($row['passing_score'] ?? 0);
  return $score >= $pass;
}

function cert_evaluate($pdo, $userId, $courseCode)
{
  $cfg = cert_get_config();
  $course = cert_fetch_course($pdo, $courseCode);
  if (!$course) { return ['eligible' => false, 'codes' => ['COURSE_NOT_FOUND'], 'details' => []]; }
  if (!cert_verify_enrollment($pdo, $userId, $courseCode)) { return ['eligible' => false, 'codes' => ['ENROLLMENT_NOT_FOUND'], 'details' => []]; }
  $competencies = cert_fetch_competencies($pdo, $courseCode);
  $activities = cert_fetch_activities_by_competency($pdo, $courseCode);
  $quizzes = cert_fetch_course_quizzes($pdo, $courseCode);
  $requiredTypes = array_map('strtolower', $cfg['required_activity_types']);
  $competencyStatus = [];
  $missingActivities = [];
  foreach ($competencies as $code => $c) {
    $list = $activities[$code] ?? [];
    $required = array_filter($list, function($a) use ($requiredTypes){ return in_array($a['type'], $requiredTypes, true); });
    $miss = [];
    foreach ($required as $a) {
      if (!cert_has_submission($pdo, $a['id'], $userId)) { $miss[] = ['activity_id' => $a['id'], 'title' => $a['title'], 'type' => $a['type']]; }
    }
    $completed = empty($miss);
    $competencyStatus[$code] = ['code' => $code, 'name' => $c['name'], 'hours' => $c['hours'], 'completed' => $completed, 'missing' => $miss];
    foreach ($miss as $m) { $missingActivities[] = array_merge($m, ['competency_code' => $code]); }
  }
  $quizMissing = [];
  if ($cfg['require_quiz_pass']) {
    foreach ($quizzes as $q) {
      $passed = cert_quiz_passed($pdo, $q['id'], $userId);
      if (!$passed) { $quizMissing[] = ['quiz_id' => $q['id'], 'title' => $q['title'] ?? ('Quiz '.$q['id'])]; }
    }
  }
  $hoursRequired = array_reduce($competencies, function($acc,$c){ return $acc + (float)$c['hours']; }, 0.0);
  $hoursCompleted = array_reduce($competencyStatus, function($acc,$s){ return $acc + ($s['completed'] ? (float)$s['hours'] : 0.0); }, 0.0);
  $codes = [];
  if (!empty($missingActivities)) { $codes[] = 'ACTIVITY_MISSING'; }
  if (!empty($quizMissing)) { $codes[] = 'QUIZ_NOT_PASSED'; }
  if ($hoursCompleted < $hoursRequired) { $codes[] = 'HOURS_INSUFFICIENT'; }
  $eligible = empty($codes);
  $details = [
    'missing_activities' => $missingActivities,
    'missing_quizzes' => $quizMissing,
    'hours_required' => $hoursRequired,
    'hours_completed' => $hoursCompleted,
    'competencies' => array_values($competencyStatus),
    'course' => $course
  ];
  return ['eligible' => $eligible, 'codes' => $eligible ? ['ELIGIBLE'] : $codes, 'details' => $details];
}

function cert_log_audit($userId, $courseCode, $decision, $details)
{
  $dir = __DIR__ . '/../data';
  if (!is_dir($dir)) { mkdir($dir, 0770, true); }
  $file = $dir . '/cert_audit.jsonl';
  $payload = json_encode(['ts' => date('c'), 'user_id' => $userId, 'course_code' => $courseCode, 'decision' => $decision, 'details' => $details]);
  $enc = cert_encrypt($payload);
  file_put_contents($file, $enc . PHP_EOL, FILE_APPEND | LOCK_EX);
  if (in_array($decision, ['INELIGIBLE','ERROR'], true)) {
    $nf = $dir . '/admin_notifications.json';
    $existing = file_exists($nf) ? json_decode(cert_decrypt(file_get_contents($nf)), true) : [];
    if (!is_array($existing)) { $existing = []; }
    $key = $courseCode;
    $existing[$key] = isset($existing[$key]) ? (int)$existing[$key] + 1 : 1;
    file_put_contents($nf, cert_encrypt(json_encode($existing)));
  }
}

function cert_store_record($userId, $courseCode, $result)
{
  $dir = __DIR__ . '/../data';
  if (!is_dir($dir)) { mkdir($dir, 0770, true); }
  $file = $dir . '/cert_records.json';
  $existing = file_exists($file) ? json_decode(cert_decrypt(file_get_contents($file)), true) : [];
  if (!is_array($existing)) { $existing = []; }
  $existing[$userId . ':' . $courseCode] = ['created_at' => date('c'), 'result' => $result];
  file_put_contents($file, cert_encrypt(json_encode($existing)));
}

function cert_get_record($userId, $courseCode)
{
  $file = __DIR__ . '/../data/cert_records.json';
  if (!file_exists($file)) { return null; }
  $data = json_decode(cert_decrypt(file_get_contents($file)), true);
  if (!is_array($data)) { return null; }
  return $data[$userId . ':' . $courseCode] ?? null;
}
?>
