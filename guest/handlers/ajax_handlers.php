<?php
require_once __DIR__ . '/../../php/SessionManager.php';
require_once __DIR__ . '/../../php/DatabaseConnection.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

SessionManager::startSession();
SessionManager::requireRole('guest');
$user = SessionManager::getCurrentUser();

$database = new DatabaseConnection();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];
  try {
    switch ($action) {
      case 'enroll':
        $courseCode = $_POST['course_code'] ?? '';
        echo json_encode(enrollGuest($db, $user['user_id'], $courseCode));
        break;
      case 'unenroll':
        $courseCode = $_POST['course_code'] ?? '';
        echo json_encode(unenrollGuest($db, $user['user_id'], $courseCode));
        break;
      case 'update_profile':
        $res = updateGuestProfile($db, $user['user_id'], $_POST);
        if (!empty($res['success']) && !empty($res['user'])) {
          $_SESSION['user'] = array_merge($_SESSION['user'], $res['user']);
        }
        echo json_encode($res);
        break;
      case 'get_course_details':
        $courseCode = $_POST['course_code'] ?? $_GET['course_code'] ?? '';
        echo json_encode(getCourseDetailsForGuest($db, $courseCode, $user['user_id']));
        break;
      default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
    }
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
  }
  exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
