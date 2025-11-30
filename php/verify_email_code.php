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
  $code = trim($payload['verification_code'] ?? '');
  if ($code === '') {
    echo json_encode(['success' => false, 'message' => 'Verification code required']);
    exit;
  }

  $user = SessionManager::getCurrentUser();
  $db = new DatabaseConnection();
  $pdo = $db->getConnection();

  $stmt = $pdo->prepare("SELECT * FROM verification_tokens WHERE user_id = ? AND token_type = 'email' AND token = ? AND is_used = 0 AND expires_at >= NOW() ORDER BY id DESC LIMIT 1");
  $stmt->execute([$user['user_id'], $code]);
  $token = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired code']);
    exit;
  }

  $pdo->beginTransaction();
  try {
    $updTok = $pdo->prepare("UPDATE verification_tokens SET is_used = 1 WHERE id = ?");
    $updTok->execute([$token['id']]);

    $updUser = $pdo->prepare("UPDATE users SET email_verified = 1 WHERE user_id = ?");
    $updUser->execute([$user['user_id']]);

    $insHist = $pdo->prepare("INSERT INTO user_verification_history (user_id, verification_type, verification_method, verified_by, status, notes) VALUES (?, 'email', 'code', ?, 'verified', 'Email verified via code')");
    $insHist->execute([$user['user_id'], $user['user_id']]);

    $pdo->commit();
  } catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
  }

  echo json_encode(['success' => true, 'message' => 'Email verified successfully']);
} catch (Exception $e) {
  error_log('verify_email_code error: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Verification failed']);
}
?>
