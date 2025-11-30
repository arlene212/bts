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

  $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
  $expires = (new DateTime('+30 minutes'))->format('Y-m-d H:i:s');

  $insToken = $pdo->prepare("INSERT INTO verification_tokens (user_id, token_type, token, expires_at, is_used, created_at) VALUES (?, 'email', ?, ?, 0, NOW())");
  $insToken->execute([$user['user_id'], $code, $expires]);

  $insVer = $pdo->prepare("INSERT INTO user_verification (user_id, verification_type, verification_code, verification_data, is_verified, created_at) VALUES (?, 'email', ?, NULL, 0, NOW())");
  $insVer->execute([$user['user_id'], $code]);

  echo json_encode(['success' => true, 'message' => 'Verification code sent to your email']);
} catch (Exception $e) {
  error_log('send_email_verification error: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Failed to send verification']);
}
?>
