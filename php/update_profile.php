<?php
require_once __DIR__ . '/DatabaseConnection.php';
require_once __DIR__ . '/SessionManager.php';

header('Content-Type: application/json');

SessionManager::startSession();
if (!SessionManager::isLoggedIn()) { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }

try {
  $db = new DatabaseConnection();
  $pdo = $db->getConnection();
  $user = SessionManager::getCurrentUser();
  $userId = $user['user_id'];

  $firstName = trim($_POST['first_name'] ?? '');
  $middleName = trim($_POST['middle_name'] ?? '');
  $lastName = trim($_POST['last_name'] ?? '');
  $suffix = trim($_POST['suffix'] ?? '');
  $contactNumber = trim($_POST['contact_number'] ?? '');
  $email = trim($_POST['email'] ?? '');

  $profilePictureName = null;
  if (isset($_FILES['profile_picture']) && is_uploaded_file($_FILES['profile_picture']['tmp_name'])) {
    $uploadDir = __DIR__ . '/../uploads/profiles/';
    if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }
    $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
    $fileName = $userId . '_' . time() . '.' . strtolower($safeExt ?: 'jpg');
    $dest = $uploadDir . $fileName;
    if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $dest)) { echo json_encode(['success' => false, 'message' => 'Failed to upload profile picture']); exit; }
    $profilePictureName = $fileName;
  }

  $fields = ['first_name' => $firstName, 'middle_name' => $middleName, 'last_name' => $lastName, 'suffix' => $suffix, 'contact_number' => $contactNumber, 'email' => $email];
  $setParts = [];
  $params = [];
  foreach ($fields as $col => $val) { if ($val !== '') { $setParts[] = "$col = ?"; $params[] = $val; } }
  if ($profilePictureName) { $setParts[] = "profile_picture = ?"; $params[] = $profilePictureName; }
  $params[] = $userId;

  $sql = "UPDATE users SET " . implode(', ', $setParts) . " WHERE user_id = ?";
  $stmt = $pdo->prepare($sql);
  if (!empty($setParts)) { $stmt->execute($params); }

  $getStmt = $pdo->prepare("SELECT user_id, role, first_name, middle_name, last_name, suffix, email, contact_number, profile_picture, status, date_created, last_login, password_changed_at FROM users WHERE user_id = ?");
  $getStmt->execute([$userId]);
  $updated = $getStmt->fetch(PDO::FETCH_ASSOC);
  if ($updated) { $_SESSION['user'] = $updated; }

  echo json_encode(['success' => true, 'message' => 'Profile updated successfully', 'user' => $updated]);
} catch (PDOException $e) {
  error_log('Update profile error: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>