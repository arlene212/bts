<?php
require_once __DIR__ . '/../../php/SessionManager.php';
require_once __DIR__ . '/../../php/DatabaseConnection.php';
require_once __DIR__ . '/../includes/certification.php';

SessionManager::startSession();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guest') {
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'Access denied']);
  exit;
}

$database = new DatabaseConnection();
$pdo = $database->getConnection();
$user = SessionManager::getCurrentUser();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$courseCode = trim($_POST['course_code'] ?? '');
if ($courseCode === '') { echo json_encode(['success' => false, 'message' => 'Missing course_code']); exit; }

try {
  if ($action === 'check_eligibility') {
    $result = cert_evaluate($pdo, $user['user_id'], $courseCode);
    cert_log_audit($user['user_id'], $courseCode, $result['eligible'] ? 'ELIGIBLE' : 'INELIGIBLE', $result['codes']);
    echo json_encode(['success' => true, 'eligible' => $result['eligible'], 'codes' => $result['codes'], 'details' => $result['details']]);
  } elseif ($action === 'generate_certificate') {
    $result = cert_evaluate($pdo, $user['user_id'], $courseCode);
    if (!$result['eligible']) { echo json_encode(['success' => false, 'message' => 'Not eligible', 'codes' => $result['codes'], 'details' => $result['details']]); exit; }
    cert_store_record($user['user_id'], $courseCode, $result);
    echo json_encode(['success' => true, 'redirect' => '/bts/guest/certificate.php?course_code=' . urlencode($courseCode)]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
  }
} catch (Exception $e) {
  cert_log_audit($user['user_id'], $courseCode, 'ERROR', ['error' => $e->getMessage()]);
  echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
