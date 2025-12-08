<?php
require_once __DIR__ . '/../../php/SessionManager.php';
require_once __DIR__ . '/../../php/DatabaseConnection.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

SessionManager::startSession();
SessionManager::requireRole('trainee');
$user = SessionManager::getCurrentUser();

$database = new DatabaseConnection();
$db = $database->getConnection();

$action = $_POST['action'] ?? ($_GET['action'] ?? null);
if ($action) {
  try {
    switch ($action) {
      case 'enroll_request':
        $courseCode = $_POST['course_code'] ?? '';
        echo json_encode(requestEnroll($db, $user['user_id'], $courseCode));
        break;
      case 'cancel_request':
        $requestId = $_POST['request_id'] ?? '';
        echo json_encode(cancelEnrollmentRequest($db, $user['user_id'], $requestId));
        break;
      case 'update_profile':
        $res = updateTraineeProfile($db, $user['user_id'], $_POST);
        if (!empty($res['success']) && !empty($res['user'])) {
          $_SESSION['user'] = array_merge($_SESSION['user'], $res['user']);
        }
        echo json_encode($res);
        break;
      case 'get_course_details':
        $courseCode = $_POST['course_code'] ?? $_GET['course_code'] ?? '';
        echo json_encode(getCourseDetailsForTrainee($db, $courseCode, $user['user_id']));
        break;
      case 'get_activity_summary':
        echo json_encode(getActivitySummary($db, $user['user_id']));
        break;
      default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
    }
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
  }
  exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);

function getActivitySummary(PDO $db, string $traineeId): array {
  try {
    $enStmt = $db->prepare("SELECT course_code, course_name, COALESCE(batch_name,'') AS batch_name FROM enrollments WHERE trainee_id = ? AND status = 'approved'");
    $enStmt->execute([$traineeId]);
    $enrolls = $enStmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($enrolls)) { return ['success'=>true,'items'=>[],'courses'=>[]]; }
    $courses = [];
    $courseCodes = [];
    foreach ($enrolls as $e) { $courses[] = ['code'=>$e['course_code'],'name'=>$e['course_name']]; $courseCodes[] = $e['course_code']; }
    $inPlaceholders = implode(',', array_fill(0, count($courseCodes), '?'));
    $actStmt = $db->prepare(
      "SELECT ta.id, ta.activity_title, ta.activity_type, ta.start_date, ta.due_date, ta.max_score,
              ct.topic_name, ct.course_code, c.course_name
       FROM topic_activities ta
       JOIN course_topics ct ON ta.topic_id = ct.id
       JOIN courses c ON ct.course_code = c.course_code
       WHERE ct.course_code IN ($inPlaceholders)
       ORDER BY ta.due_date DESC, ta.start_date DESC"
    );
    $actStmt->execute($courseCodes);
    $acts = $actStmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($acts)) { return ['success'=>true,'items'=>[],'courses'=>$courses]; }
    $sumStmt = $db->prepare("SELECT ct.course_code, SUM(ta.max_score) AS total_max FROM topic_activities ta JOIN course_topics ct ON ta.topic_id = ct.id WHERE ct.course_code IN ($inPlaceholders) GROUP BY ct.course_code");
    $sumStmt->execute($courseCodes);
    $totals = $sumStmt->fetchAll(PDO::FETCH_ASSOC);
    $totalMap = [];
    foreach ($totals as $t) { $totalMap[$t['course_code']] = (int)$t['total_max']; }
    $subStmt = $db->prepare("SELECT activity_id, id, score, graded_at FROM activity_submissions WHERE guest_id = ?");
    $subStmt->execute([$traineeId]);
    $subs = $subStmt->fetchAll(PDO::FETCH_ASSOC);
    $subMap = [];
    foreach ($subs as $s) { $subMap[(string)$s['activity_id']] = $s; }
    $items = [];
    foreach ($acts as $a) {
      $dueDate = $a['due_date'];
      $dueTime = '';
      if (!empty($dueDate)) {
        $dt = new DateTime($dueDate);
        $dueTime = strtolower($dt->format('h:i a'));
        $dueDate = $dt->format('M d');
      }
      $startDate = $a['start_date'] ? (new DateTime($a['start_date']))->format('M d') : '';
      $sub = $subMap[(string)$a['id']] ?? null;
      $submitted = $sub !== null;
      $graded = $sub !== null && !empty($sub['graded_at']);
      $score = $graded ? (float)$sub['score'] : null;
      $max = (int)$a['max_score'];
      $pct = ($graded && $max > 0 && $score !== null) ? round(($score / $max) * 100) : null;
      $weight = 0;
      $totalMax = $totalMap[$a['course_code']] ?? 0;
      if ($totalMax > 0) { $weight = round(($max / $totalMax) * 100, 1); }
      $items[] = [
        'activity_id' => (string)$a['id'],
        'activity_title' => (string)$a['activity_title'],
        'topic_name' => (string)$a['topic_name'],
        'course_code' => (string)$a['course_code'],
        'course_name' => (string)$a['course_name'],
        'start_date' => $startDate,
        'due_date' => $dueDate,
        'due_time' => $dueTime,
        'weight' => $weight,
        'submitted' => $submitted,
        'graded' => $graded,
        'score' => $score !== null ? round($score) : null,
        'max_score' => $max,
        'percentage' => $pct,
        'grade_text' => gradeTextFromPct($pct, $graded),
        'icon' => iconForType($a['activity_type'])
      ];
    }
    // Return actual items; if empty, return as-is without static demo
    return ['success'=>true,'items'=>$items,'courses'=>$courses];
  } catch (Exception $e) {
    return ['success'=>false,'message'=>'Failed to load activities'];
  }
}

function gradeTextFromPct($pct, $graded){
  if(!$graded || $pct === null) return 'M';
  $p = (int)$pct;
  if ($p >= 96) return '1.00';
  if ($p >= 91) return '1.25';
  if ($p >= 86) return '1.75';
  if ($p >= 81) return '2.00';
  if ($p >= 76) return '2.25';
  if ($p >= 71) return '2.50';
  if ($p >= 66) return '2.75';
  if ($p >= 61) return '3.00';
  if ($p >= 56) return '4.00';
  return '5.00';
}

function iconForType($type){
  $t = strtolower((string)$type);
  if($t === 'assignment') return 'fa-file';
  if($t === 'quiz') return 'fa-pen';
  if($t === 'project') return 'fa-cube';
  return 'fa-file';
}

?>
