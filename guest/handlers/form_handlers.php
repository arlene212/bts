<?php
require_once __DIR__ . '/../../php/SessionManager.php';
require_once __DIR__ . '/../../php/DatabaseConnection.php';
require_once __DIR__ . '/../includes/functions.php';

SessionManager::startSession();
SessionManager::requireRole('guest');
$user = SessionManager::getCurrentUser();

$database = new DatabaseConnection();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['unenroll']) && isset($_POST['course_code'])) {
    $result = unenrollGuest($db, $user['user_id'], $_POST['course_code']);
    $_SESSION['success_message'] = $result['success'] ? $result['message'] : '';
    $_SESSION['error_message'] = $result['success'] ? '' : $result['message'];
    header('Location: ../index.php?current_tab=enrolled');
    exit;
  }
  if (isset($_POST['update_profile'])) {
    $result = updateGuestProfile($db, $user['user_id'], $_POST);
    $_SESSION['success_message'] = $result['success'] ? $result['message'] : '';
    $_SESSION['error_message'] = $result['success'] ? '' : $result['message'];
    header('Location: ../index.php?current_tab=home');
    exit;
  }
}

header('Location: ../index.php');
exit;
?>