<?php
require_once __DIR__ . '/../../php/SessionManager.php';
require_once __DIR__ . '/../../php/DatabaseConnection.php';
require_once __DIR__ . '/../includes/functions.php';

SessionManager::startSession();
$database = new DatabaseConnection();
$pdo = $database->getConnection();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
  $action = $_POST['ajax_action'];
  try {
    switch ($action) {
      case 'search':
        $searchType = $_POST['search_type'] ?? '';
        $searchQuery = $_POST['search_query'] ?? '';
        $results = performSearch($pdo, $searchType, $searchQuery);
        echo json_encode(['success' => true, 'data' => $results]);
        break;
      case 'create_account':
        $result = createAccount($pdo, $_POST);
        echo json_encode($result);
        break;
      case 'reset_password':
        $result = resetPassword($pdo, $_POST['user_id']);
        echo json_encode($result);
        break;
      case 'archive_user':
        $result = archiveUser($pdo, $_POST['user_id']);
        echo json_encode($result);
        break;
      case 'unarchive_user':
        $result = unarchiveUser($pdo, $_POST['user_id']);
        echo json_encode($result);
        break;
      case 'delete_user':
        $result = deleteUser($pdo, $_POST['user_id']);
        echo json_encode($result);
        break;
      case 'process_enrollment':
        $result = processEnrollment($pdo, $_POST);
        echo json_encode($result);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_search'])) {
  $searchType = $_POST['search_type'] ?? '';
  $searchQuery = $_POST['search_query'] ?? '';
  try {
    $results = performSearch($pdo, $searchType, $searchQuery);
    echo json_encode($results);
  } catch (Exception $e) {
    echo json_encode([]);
  }
  exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>