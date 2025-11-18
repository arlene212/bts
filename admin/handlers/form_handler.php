<?php
require_once __DIR__ . '/../../php/SessionManager.php';
require_once __DIR__ . '/../../php/DatabaseConnection.php';
require_once __DIR__ . '/../includes/functions.php';

SessionManager::startSession();
SessionManager::requireRole('admin');

$database = new DatabaseConnection();
$pdo = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['archive_user'])) {
    $userId = $_POST['user_id'] ?? '';
    $tab = $_POST['current_tab'] ?? 'home';
    $result = archiveUser($pdo, $userId);
    $_SESSION['success_message'] = $result['success'] ? $result['message'] : '';
    $_SESSION['error_message'] = $result['success'] ? '' : $result['message'];
    header("Location: ../index.php?current_tab=$tab");
    exit;
  }
  if (isset($_POST['unarchive_user'])) {
    $userId = $_POST['user_id'] ?? '';
    $tab = $_POST['current_tab'] ?? 'home';
    $result = unarchiveUser($pdo, $userId);
    $_SESSION['success_message'] = $result['success'] ? $result['message'] : '';
    $_SESSION['error_message'] = $result['success'] ? '' : $result['message'];
    header("Location: ../index.php?current_tab=$tab");
    exit;
  }
  if (isset($_POST['delete_user'])) {
    $userId = $_POST['user_id'] ?? '';
    $tab = $_POST['current_tab'] ?? 'home';
    $result = deleteUser($pdo, $userId);
    $_SESSION['success_message'] = $result['success'] ? $result['message'] : '';
    $_SESSION['error_message'] = $result['success'] ? '' : $result['message'];
    header("Location: ../index.php?current_tab=$tab");
    exit;
  }
}

header('Location: ../index.php');
exit;
?>