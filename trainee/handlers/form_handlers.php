<?php
require_once __DIR__ . '/../../php/SessionManager.php';
require_once __DIR__ . '/../../php/DatabaseConnection.php';
require_once __DIR__ . '/../includes/functions.php';

SessionManager::startSession();
SessionManager::requireRole('trainee');
$user = SessionManager::getCurrentUser();

$database = new DatabaseConnection();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['cancel_request']) && isset($_POST['request_id'])) {
    $result = cancelEnrollmentRequest($db, $user['user_id'], $_POST['request_id']);
    $_SESSION['success_message'] = $result['success'] ? $result['message'] : '';
    $_SESSION['error_message'] = $result['success'] ? '' : $result['message'];
    header('Location: ../index.php?current_tab=enrollment-requests');
    exit;
  }
  if (isset($_POST['update_profile'])) {
    $result = updateTraineeProfile($db, $user['user_id'], $_POST);
    $_SESSION['success_message'] = $result['success'] ? $result['message'] : '';
    $_SESSION['error_message'] = $result['success'] ? '' : $result['message'];
    header('Location: ../index.php?current_tab=home');
    exit;
  }
}

header('Location: ../index.php');
exit;
?>