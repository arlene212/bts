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
  $user = SessionManager::getCurrentUser();
  $db = new DatabaseConnection();
  $pdo = $db->getConnection();

  $stmt = $pdo->prepare("SELECT email_verified, student_id FROM users WHERE user_id = ?");
  $stmt->execute([$user['user_id']]);
  $u = $stmt->fetch(PDO::FETCH_ASSOC);

  $verStmt = $pdo->prepare("SELECT verification_type, is_verified FROM user_verification WHERE user_id = ? ORDER BY created_at DESC");
  $verStmt->execute([$user['user_id']]);
  $verifications = $verStmt->fetchAll(PDO::FETCH_ASSOC);

  $status = [
    'email_verified' => (int)($u['email_verified'] ?? 0) === 1,
    'student_id_verified' => false,
  ];

  foreach ($verifications as $v) {
    if ($v['verification_type'] === 'student_id' && (int)$v['is_verified'] === 1) { $status['student_id_verified'] = true; }
    if ($v['verification_type'] === 'email' && (int)$v['is_verified'] === 1) { $status['email_verified'] = true; }
  }

  // If student_id is present in users table, treat as verified
  if (!empty($u['student_id'])) { $status['student_id_verified'] = true; }

  echo json_encode(['success' => true, 'status' => $status]);
} catch (Exception $e) {
  error_log('get_verification_status error: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
