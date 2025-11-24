<?php
require_once __DIR__ . '/../../php/SessionManager.php';
require_once __DIR__ . '/../../php/DatabaseConnection.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

SessionManager::startSession();
SessionManager::requireRole('trainer');
$user = SessionManager::getCurrentUser();

$database = new DatabaseConnection();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];
  try {
    switch ($action) {
      case 'view_enrollment_details':
        $id = $_POST['request_id'] ?? '';
        echo json_encode(getEnrollmentDetails($db, $id, $user['user_id']));
        break;
      case 'get_enrollment_status':
        $id = $_POST['request_id'] ?? '';
        echo json_encode(getEnrollmentStatus($db, $id, $user['user_id']));
        break;
      case 'update_profile':
        $res = updateTrainerProfile($db, $user['user_id'], $_POST);
        if (!empty($res['success']) && !empty($res['user'])) {
          $_SESSION['user'] = array_merge($_SESSION['user'], $res['user']);
        }
        echo json_encode($res);
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