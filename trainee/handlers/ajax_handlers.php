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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];
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
?>