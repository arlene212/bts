<?php
require_once __DIR__ . '/../php/SessionManager.php';
require_once __DIR__ . '/../php/DatabaseConnection.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

SessionManager::startSession();
SessionManager::requireRole('guest');

$user = SessionManager::getCurrentUser();
$currentTab = $_GET['current_tab'] ?? 'home';

$database = new DatabaseConnection();
$db = $database->getConnection();

$offered_courses = [];
$enrolled_courses = [];
$enrollment_requests = [];
$announcements = [];

try {
  $stmt = $db->prepare("SELECT * FROM courses WHERE course_status = 'published'");
  $stmt->execute();
  $offered_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $db->prepare("SELECT 
    e.*, 
    c.course_name, 
    c.course_code, 
    c.hours, 
    c.description, 
    c.image,
    COALESCE((SELECT SUM(hc.hours) FROM hour_credits hc WHERE hc.trainee_id = e.trainee_id AND hc.course_code = e.course_code), 0) AS credited_hours
  FROM enrollments e 
  JOIN courses c ON e.course_code = c.course_code 
  WHERE e.trainee_id = ? AND e.status = 'approved'");
  $stmt->execute([$user['user_id']]);
  $enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $db->prepare("SELECT e.*, c.course_name FROM enrollments e JOIN courses c ON e.course_code = c.course_code WHERE e.trainee_id = ? ORDER BY e.date_requested DESC");
  $stmt->execute([$user['user_id']]);
  $enrollment_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
  error_log("Guest dashboard error: " . $exception->getMessage());
}

include __DIR__ . '/views/layout.php';
?>
