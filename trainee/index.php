<?php
require_once __DIR__ . '/../php/SessionManager.php';
require_once __DIR__ . '/../php/DatabaseConnection.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

SessionManager::startSession();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'trainee') {
  header('Location: ../landingpage.php');
  exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage !== 'force_change_password.php' && isset($_SESSION['user']['password_changed_at']) && $_SESSION['user']['password_changed_at'] === null) {
  header('Location: force_change_password.php');
  exit;
}

$user = SessionManager::getCurrentUser();
$currentTab = $_GET['current_tab'] ?? 'home';

$database = new DatabaseConnection();
$db = $database->getConnection();

$enrolled_courses = [];
$offered_courses = [];
$enrollment_requests = [];
$announcements = [];
$total_courses = 0;
$total_hours = 0;
$total_submissions = 0;
$total_activities = 0;
$progress = 0;
$pending_requests = 0;

try {
  $stmt = $db->prepare("SELECT e.*, c.course_name, c.course_code, c.hours, c.description, c.image FROM enrollments e JOIN courses c ON e.course_code = c.course_code WHERE e.trainee_id = ? AND e.status = 'approved'");
  $stmt->execute([$user['user_id']]);
  $enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $db->prepare("SELECT * FROM courses WHERE status = 'active'");
  $stmt->execute();
  $offered_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $db->prepare("SELECT e.*, c.course_name FROM enrollments e JOIN courses c ON e.course_code = c.course_code WHERE e.trainee_id = ? ORDER BY e.date_requested DESC");
  $stmt->execute([$user['user_id']]);
  $enrollment_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $db->prepare("SELECT * FROM announcements ORDER BY date_posted DESC LIMIT 5");
  $stmt->execute();
  $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $total_courses = count($enrolled_courses);
  foreach ($enrolled_courses as $course) {
    $total_hours += (int)$course['hours'];
    $submission_stmt = $db->prepare("SELECT COUNT(*) as count FROM submissions WHERE trainee_id = ? AND material_id IN (SELECT id FROM course_materials WHERE course_code = ?)");
    $submission_stmt->execute([$user['user_id'], $course['course_code']]);
    $submission_count = $submission_stmt->fetch(PDO::FETCH_ASSOC);
    $total_submissions += (int)$submission_count['count'];
    $activity_stmt = $db->prepare("SELECT COUNT(ta.id) as count FROM topic_activities ta JOIN course_topics ct ON ta.topic_id = ct.id WHERE ct.course_code = ?");
    $activity_stmt->execute([$course['course_code']]);
    $activity_count = $activity_stmt->fetch(PDO::FETCH_ASSOC);
    $total_activities += (int)$activity_count['count'];
  }
  // Calculate progress based on actual completion rate
  $progress = 0;
  if ($total_activities > 0) {
    $progress = round(($total_submissions / $total_activities) * 100);
  }
  $pending_requests = count(array_filter($enrollment_requests, function ($request) { return $request['status'] == 'pending'; }));
} catch (PDOException $exception) {
  error_log("Trainee dashboard error: " . $exception->getMessage());
}

include __DIR__ . '/views/layout.php';
?>