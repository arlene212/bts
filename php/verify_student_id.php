<?php
require_once __DIR__ . '/SessionManager.php';
require_once __DIR__ . '/DatabaseConnection.php';

header('Content-Type: application/json');

SessionManager::startSession();
if (!SessionManager::isLoggedIn()) {
  echo json_encode(['success' => false, 'message' => 'Not authenticated']);
  exit;
}

try {
  $payload = json_decode(file_get_contents('php://input'), true) ?? [];
  $studentId = trim($payload['student_id'] ?? '');
  $studentName = trim($payload['student_name'] ?? '');

  if ($studentId === '' || $studentName === '') {
    echo json_encode(['success' => false, 'message' => 'Student ID and name are required']);
    exit;
  }

  $user = SessionManager::getCurrentUser();
  $db = new DatabaseConnection();
  $pdo = $db->getConnection();

  $pdo->beginTransaction();
  try {
    $updUser = $pdo->prepare("UPDATE users SET student_id = ? WHERE user_id = ?");
    $updUser->execute([$studentId, $user['user_id']]);

    $insVer = $pdo->prepare("INSERT INTO user_verification (user_id, verification_type, verification_data, is_verified, created_at) VALUES (?, 'student_id', ?, 1, NOW())");
    $insVer->execute([$user['user_id'], $studentName]);

    $insHist = $pdo->prepare("INSERT INTO user_verification_history (user_id, verification_type, verification_method, verified_by, status, notes) VALUES (?, 'student_id', 'manual', ?, 'verified', 'Student ID verified')");
    $insHist->execute([$user['user_id'], $user['user_id']]);

    $pdo->commit();
  } catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
  }

  echo json_encode(['success' => true, 'message' => 'Student ID verified successfully']);
} catch (Exception $e) {
  error_log('verify_student_id error: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Verification failed']);
}
?>
