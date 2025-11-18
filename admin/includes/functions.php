<?php
function performSearch($pdo, $type, $query)
{
  $searchParam = "%$query%";
  $results = [];

  switch ($type) {
    case 'trainer':
    case 'trainee':
    case 'guest':
      $stmt = $pdo->prepare("SELECT user_id as id, CONCAT(first_name, ' ', last_name) as name, email, contact_number, date_created FROM users WHERE role = ? AND (first_name LIKE ? OR last_name LIKE ? OR user_id LIKE ? OR email LIKE ? OR contact_number LIKE ?) LIMIT 10");
      $stmt->execute([$type, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
      $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
      break;
    case 'enrollment':
      $stmt = $pdo->prepare("SELECT e.id, CONCAT(u.first_name, ' ', u.last_name) as trainee_name, c.course_name, e.status, e.date_requested FROM enrollments e JOIN users u ON e.trainee_id = u.user_id JOIN courses c ON e.course_code = c.course_code WHERE u.first_name LIKE ? OR u.last_name LIKE ? OR c.course_name LIKE ? LIMIT 10");
      $stmt->execute([$searchParam, $searchParam, $searchParam]);
      $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
      break;
  }

  return $results;
}

function createAccount($pdo, $data)
{
  try {
    $role = $data['role'];
    $firstName = trim($data['first_name']);
    $lastName = trim($data['last_name']);
    $middleName = trim($data['middle_name'] ?? '');
    $suffix = trim($data['suffix'] ?? '');
    $contactNumber = trim($data['contact_number']);

    if (!preg_match('/^(09\d{9}|\+639\d{9}|63\d{10})$/', preg_replace('/\D/', '', $contactNumber))) {
      return ['success' => false, 'message' => 'Invalid Philippine phone number format'];
    }

    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (first_name = ? AND last_name = ? AND middle_name = ?) OR contact_number = ?");
    $checkStmt->execute([$firstName, $lastName, $middleName, $contactNumber]);

    if ($checkStmt->fetchColumn() > 0) {
      return ['success' => false, 'message' => 'A user with similar details already exists'];
    }

    $email = generateEmail($firstName, $lastName);
    $tempPassword = generateTempPassword();
    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
    $userId = generateUserId($pdo, $role);

    $stmt = $pdo->prepare("INSERT INTO users (user_id, first_name, middle_name, last_name, suffix, email, password, contact_number, role, status, date_created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");

    $stmt->execute([
      $userId,
      $firstName,
      $middleName,
      $lastName,
      $suffix,
      $email,
      $hashedPassword,
      $contactNumber,
      $role
    ]);

    return [
      'success' => true,
      'message' => ucfirst($role) . ' account created successfully',
      'data' => [
        'user_id' => $userId,
        'email' => $email,
        'temp_password' => $tempPassword
      ]
    ];
  } catch (Exception $e) {
    error_log("Create account error: " . $e->getMessage());
    return ['success' => false, 'message' => 'Failed to create account: ' . $e->getMessage()];
  }
}

function resetPassword($pdo, $userId)
{
  try {
    $newPassword = generateTempPassword();
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->execute([$hashedPassword, $userId]);

    return [
      'success' => true,
      'message' => 'Password reset successfully',
      'data' => ['temp_password' => $newPassword]
    ];
  } catch (Exception $e) {
    error_log("Reset password error: " . $e->getMessage());
    return ['success' => false, 'message' => 'Failed to reset password'];
  }
}

function archiveUser($pdo, $userId)
{
  try {
    $stmt = $pdo->prepare("UPDATE users SET status = 'archived' WHERE user_id = ?");
    $stmt->execute([$userId]);
    return ['success' => true, 'message' => 'User archived successfully'];
  } catch (Exception $e) {
    return ['success' => false, 'message' => 'Failed to archive user'];
  }
}

function unarchiveUser($pdo, $userId)
{
  try {
    $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
    $stmt->execute([$userId]);
    return ['success' => true, 'message' => 'User restored successfully'];
  } catch (Exception $e) {
    return ['success' => false, 'message' => 'Failed to restore user'];
  }
}

function deleteUser($pdo, $userId)
{
  try {
    $pdo->beginTransaction();
    $tables = [
      'course_assignments' => ['trainer_id', 'assigned_by'],
      'batch_assignments' => ['trainee_id', 'assigned_by'],
      'enrollments' => ['trainee_id', 'processed_by']
    ];
    foreach ($tables as $table => $columns) {
      foreach ($columns as $column) {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE $column = ?");
        $stmt->execute([$userId]);
      }
    }
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $pdo->commit();
    return ['success' => true, 'message' => 'User deleted successfully'];
  } catch (Exception $e) {
    $pdo->rollBack();
    error_log("Delete user error: " . $e->getMessage());
    return ['success' => false, 'message' => 'Failed to delete user'];
  }
}

function processEnrollment($pdo, $data)
{
  try {
    $enrollmentId = $data['enrollment_id'];
    $action = $data['action'];
    $remarks = $data['remarks'] ?? '';
    $processedBy = $_SESSION['user']['user_id'];
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    $stmt = $pdo->prepare("UPDATE enrollments SET status = ?, remarks = ?, processed_date = NOW(), processed_by = ? WHERE id = ?");
    $stmt->execute([$status, $remarks, $processedBy, $enrollmentId]);
    return ['success' => true, 'message' => "Enrollment {$action}d successfully"];
  } catch (Exception $e) {
    return ['success' => false, 'message' => 'Failed to process enrollment'];
  }
}

function generateEmail($firstName, $lastName)
{
  $cleanFirst = strtolower(preg_replace('/[^a-zA-Z]/', '', $firstName));
  $cleanLast = strtolower(preg_replace('/[^a-zA-Z]/', '', $lastName));
  return $cleanFirst . '.' . $cleanLast . '@bts.gov.ph';
}

function generateUserId($pdo, $role)
{
  $prefix = [
    'admin' => '1',
    'trainer' => '2',
    'trainee' => '3',
    'guest' => '4'
  ][$role] ?? '9';
  $stmt = $pdo->prepare("SELECT MAX(CAST(user_id AS UNSIGNED)) as max_id FROM users WHERE user_id LIKE ?");
  $stmt->execute([$prefix . '%']);
  $result = $stmt->fetch();
  $nextId = ($result['max_id']) ? $result['max_id'] + 1 : $prefix . '000000001';
  return str_pad($nextId, 10, '0', STR_PAD_LEFT);
}

function validatePhilippinePhoneNumber($number)
{
  $cleanNumber = preg_replace('/\D/', '', $number);
  if (preg_match('/^09\d{9}$/', $cleanNumber)) {
    return true;
  } elseif (preg_match('/^63\d{10}$/', $cleanNumber)) {
    return true;
  } elseif (preg_match('/^\d{10}$/', $cleanNumber) && substr($cleanNumber, 0, 2) === '09') {
    return true;
  }
  return false;
}

function checkDuplicateUser($pdo, $firstName, $lastName, $middleName = '', $contactNumber = '')
{
  try {
    $nameQuery = "SELECT COUNT(*) FROM users WHERE first_name = ? AND last_name = ? AND middle_name = ?";
    $nameStmt = $pdo->prepare($nameQuery);
    $nameStmt->execute([$firstName, $lastName, $middleName]);
    $nameCount = $nameStmt->fetchColumn();
    $contactCount = 0;
    if (!empty($contactNumber)) {
      $contactQuery = "SELECT COUNT(*) FROM users WHERE contact_number = ?";
      $contactStmt = $pdo->prepare($contactQuery);
      $contactStmt->execute([$contactNumber]);
      $contactCount = $contactStmt->fetchColumn();
    }
    return [
      'name_duplicate' => $nameCount > 0,
      'contact_duplicate' => $contactCount > 0
    ];
  } catch (Exception $e) {
    error_log("Duplicate check error: " . $e->getMessage());
    return ['name_duplicate' => false, 'contact_duplicate' => false];
  }
}

function generateTempPassword($length = 10)
{
  $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
  $password = '';
  for ($i = 0; $i < $length; $i++) {
    $password .= $chars[rand(0, strlen($chars) - 1)];
  }
  return $password;
}

function cleanupInactiveGuests($pdo)
{
  try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE role = 'guest' AND (last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL 6 MONTH)) AND status = 'active'");
    $stmt->execute();
    $deletedCount = $stmt->rowCount();
    if ($deletedCount > 0) {
      error_log("Cleaned up $deletedCount inactive guest accounts");
    }
  } catch (Exception $e) {
    error_log("Error cleaning up guests: " . $e->getMessage());
  }
}
?>