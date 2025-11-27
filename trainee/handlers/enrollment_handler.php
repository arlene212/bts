<?php
require_once __DIR__ . '/../../php/SessionManager.php';
require_once __DIR__ . '/../../php/DatabaseConnection.php';

header('Content-Type: application/json');
SessionManager::startSession();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'trainee') {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

$user = SessionManager::getCurrentUser();
$db = (new DatabaseConnection())->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Invalid request method']);
  exit;
}

$action = $_POST['action'] ?? '';
try {
  if ($action === 'request_enroll') {
    $courseCode = trim($_POST['course_code'] ?? '');
    $courseName = trim($_POST['course_name'] ?? '');
    if ($courseCode === '') {
      echo json_encode(['success' => false, 'message' => 'Course code is required']);
      exit;
    }
    $cstmt = $db->prepare("SELECT course_name, course_status FROM courses WHERE course_code = ? LIMIT 1");
    $cstmt->execute([$courseCode]);
    $course = $cstmt->fetch(PDO::FETCH_ASSOC);
    if (!$course) { echo json_encode(['success'=>false,'message'=>'Course not found']); exit; }
    if (($course['course_status'] ?? 'draft') !== 'published') { echo json_encode(['success'=>false,'message'=>'Course is not published']); exit; }
    $existing = $db->prepare("SELECT id, status FROM enrollments WHERE trainee_id = ? AND course_code = ? LIMIT 1");
    $existing->execute([$user['user_id'], $courseCode]);
    $row = $existing->fetch(PDO::FETCH_ASSOC);
    if ($row) {
      if ($row['status'] === 'pending') { echo json_encode(['success'=>true,'message'=>'Request already pending']); exit; }
      if ($row['status'] === 'approved') { echo json_encode(['success'=>false,'message'=>'Already enrolled']); exit; }
      // if rejected or other, allow resubmission by updating status and date
      $upd = $db->prepare("UPDATE enrollments SET status='pending', date_requested = NOW() WHERE id = ?");
      $upd->execute([$row['id']]);
      echo json_encode(['success'=>true]);
      exit;
    }
    $ins = $db->prepare("INSERT INTO enrollments (trainee_id, course_code, course_name, status, date_requested) VALUES (?, ?, ?, 'pending', NOW())");
    $ins->execute([$user['user_id'], $courseCode, $course['course_name'] ?? $courseName]);
    echo json_encode(['success' => true]);
    exit;
  }
  echo json_encode(['success' => false, 'message' => 'Unknown action']);
} catch (PDOException $e) {
  error_log('Enroll request error: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
