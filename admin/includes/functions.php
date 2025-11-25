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

function getBackupDir()
{
  return realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR . 'backups';
}

function ensureBackupDir()
{
  $dir = getBackupDir();
  if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
  }
  return $dir;
}

function listBackups()
{
  $dir = ensureBackupDir();
  $list = [];
  $entries = @scandir($dir) ?: [];
  foreach ($entries as $entry) {
    if ($entry === '.' || $entry === '..') continue;
    $file = $dir . DIRECTORY_SEPARATOR . $entry;
    if (is_file($file) && preg_match('/\.sql$/i', $entry)) {
      $list[] = [
        'name' => $entry,
        'path' => $file,
        'size' => @filesize($file) ?: 0,
        'mtime' => @filemtime($file) ?: 0
      ];
    }
  }
  usort($list, function ($a, $b) { return ($b['mtime'] <=> $a['mtime']); });
  return $list;
}

function createBackup()
{
  require_once __DIR__ . '/../../php/config.php';
  $dir = ensureBackupDir();
  $filename = DB_NAME . '_' . date('Ymd_His') . '.sql';
  $backupPath = $dir . DIRECTORY_SEPARATOR . $filename;

  $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
  if (!file_exists($mysqldump)) { $mysqldump = 'mysqldump'; }

  $cmd = '"' . $mysqldump . '"'
    . ' --host=' . escapeshellarg(DB_HOST)
    . ' --user=' . escapeshellarg(DB_USER)
    . ' --password=' . escapeshellarg(DB_PASS)
    . ' --routines --triggers --events --single-transaction --hex-blob'
    . ' --databases ' . escapeshellarg(DB_NAME)
    . ' --result-file=' . escapeshellarg($backupPath);

  $output = [];
  $exitCode = 0;
  exec($cmd, $output, $exitCode);

  if ($exitCode === 0 && file_exists($backupPath) && filesize($backupPath) > 0) {
    return ['success' => true, 'message' => 'Backup created successfully', 'file' => $filename];
  } else {
    return ['success' => false, 'message' => 'Backup failed', 'details' => implode("\n", $output)];
  }
}

function restoreBackup($fileBaseName)
{
  require_once __DIR__ . '/../../php/config.php';
  $dir = ensureBackupDir();
  $filePath = realpath($dir . DIRECTORY_SEPARATOR . $fileBaseName);
  if (!$filePath || strpos($filePath, $dir) !== 0 || !preg_match('/\.sql$/i', $fileBaseName)) {
    return ['success' => false, 'message' => 'Invalid backup file'];
  }

  $mysql = 'C:\\xampp\\mysql\\bin\\mysql.exe';
  if (!file_exists($mysql)) { $mysql = 'mysql'; }

  @set_time_limit(0);
  $cmd = '"' . $mysql . '"'
    . ' --host=' . escapeshellarg(DB_HOST)
    . ' --user=' . escapeshellarg(DB_USER)
    . ' --password=' . escapeshellarg(DB_PASS)
    . ' --default-character-set=utf8mb4 '
    . escapeshellarg(DB_NAME);

  $descriptorspec = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
  ];
  $pipes = [];
  $process = proc_open($cmd, $descriptorspec, $pipes);
  if (!is_resource($process)) {
    return ['success' => false, 'message' => 'Restore failed: could not start mysql process'];
  }

  $fh = fopen($filePath, 'rb');
  if ($fh) {
    stream_copy_to_stream($fh, $pipes[0]);
    fclose($fh);
  }
  fclose($pipes[0]);
  $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
  $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
  $exitCode = proc_close($process);
  $ok = ($exitCode === 0);
  return ['success' => $ok, 'message' => $ok ? 'Restore completed' : 'Restore failed', 'details' => trim($stdout . "\n" . $stderr)];
}

function restoreBackupFromAbsolute($absolutePath)
{
  require_once __DIR__ . '/../../php/config.php';
  $filePath = realpath($absolutePath);
  if (!$filePath || !preg_match('/\.sql$/i', $filePath) || !file_exists($filePath)) {
    return ['success' => false, 'message' => 'Invalid backup file path'];
  }
  $mysql = 'C:\\xampp\\mysql\\bin\\mysql.exe';
  if (!file_exists($mysql)) { $mysql = 'mysql'; }
  @set_time_limit(0);
  $cmd = '"' . $mysql . '"'
    . ' --host=' . escapeshellarg(DB_HOST)
    . ' --user=' . escapeshellarg(DB_USER)
    . ' --password=' . escapeshellarg(DB_PASS)
    . ' --default-character-set=utf8mb4 '
    . escapeshellarg(DB_NAME);
  $descriptorspec = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
  $pipes = [];
  $process = proc_open($cmd, $descriptorspec, $pipes);
  if (!is_resource($process)) { return ['success' => false, 'message' => 'Restore failed: could not start mysql process']; }
  $fh = fopen($filePath, 'rb');
  if ($fh) { stream_copy_to_stream($fh, $pipes[0]); fclose($fh); }
  fclose($pipes[0]);
  $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
  $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
  $exitCode = proc_close($process);
  $ok = ($exitCode === 0);
  return ['success' => $ok, 'message' => $ok ? 'Restore completed' : 'Restore failed', 'details' => trim($stdout . "\n" . $stderr)];
}

function deleteBackup($fileBaseName)
{
  $dir = ensureBackupDir();
  $filePath = realpath($dir . DIRECTORY_SEPARATOR . $fileBaseName);
  if (!$filePath || strpos($filePath, $dir) !== 0 || !preg_match('/\.sql$/i', $fileBaseName)) {
    return ['success' => false, 'message' => 'Invalid backup file'];
  }
  if (unlink($filePath)) {
    return ['success' => true, 'message' => 'Backup deleted'];
  }
  return ['success' => false, 'message' => 'Failed to delete backup'];
}

function purgeDatabaseDataExceptUsers($pdo)
{
  require_once __DIR__ . '/../../php/config.php';
  try {
    $pdo->beginTransaction();
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $stmt = $pdo->prepare('SELECT TABLE_NAME FROM information_schema.tables WHERE TABLE_SCHEMA = ?');
    $stmt->execute([DB_NAME]);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $purged = 0;
    foreach ($tables as $table) {
      if (strtolower($table) === 'users') { continue; }
      $pdo->exec('TRUNCATE TABLE `' . str_replace('`','', $table) . '`');
      $purged++;
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    $pdo->commit();
    return ['success' => true, 'message' => 'Purged ' . $purged . ' tables'];
  } catch (Exception $e) {
    $pdo->rollBack();
    return ['success' => false, 'message' => 'Purge failed: ' . $e->getMessage()];
  }
}

function autoArchiveEndedBatches($pdo)
{
  try {
    $endedBatches = $pdo->query("SELECT course_code, batch_name, trainer_id, start_date, end_date FROM course_batches WHERE end_date IS NOT NULL AND end_date < CURDATE()")->fetchAll();
    foreach ($endedBatches as $cb) {
      // Mark batch archived if column exists
      try {
        $colCheck = $pdo->query("SHOW COLUMNS FROM course_batches LIKE 'status'");
        if ($colCheck && $colCheck->rowCount() > 0) {
          $upd = $pdo->prepare("UPDATE course_batches SET status = 'archived' WHERE course_code = ? AND batch_name = ?");
          $upd->execute([$cb['course_code'], $cb['batch_name']]);
        }
      } catch (Exception $__) {}

      // Write archive status rows to batch_assignment_status table (trainer and trainees)
      recordBatchStatus($pdo, $cb['course_code'], $cb['batch_name'], ($cb['trainer_id'] ?? null), null, 'archived', ($cb['start_date'] ?? null), ($cb['end_date'] ?? null));
      try {
        $traineesStmt = $pdo->prepare("SELECT trainee_id FROM batch_assignments WHERE course_code = ? AND batch_name = ?");
        $traineesStmt->execute([$cb['course_code'], $cb['batch_name']]);
        $trainees = $traineesStmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($trainees as $tid) {
          recordBatchStatus($pdo, $cb['course_code'], $cb['batch_name'], null, $tid, 'archived', ($cb['start_date'] ?? null), ($cb['end_date'] ?? null));
        }
      } catch (Exception $__) {}
    }
  } catch (Exception $e) {}
}

function hasTable($pdo, $table)
{
  try {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
  } catch (Exception $__) { return false; }
}

function recordBatchStatus($pdo, $courseCode, $batchName, $trainerId, $traineeId, $status, $startDate = null, $endDate = null)
{
  if (!hasTable($pdo, 'batch_assignment_status')) { return false; }
  try {
    $stmt = $pdo->prepare("INSERT INTO batch_assignment_status (course_code, batch_name, trainer_id, trainee_id, status, start_date, end_date, assigned_at, archived_at) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, NULL)");
    if ($status === 'archived') {
      $stmt = $pdo->prepare("INSERT INTO batch_assignment_status (course_code, batch_name, trainer_id, trainee_id, status, start_date, end_date, assigned_at, archived_at) VALUES (?, ?, ?, ?, 'archived', ?, ?, NULL, CURRENT_TIMESTAMP)");
    }
    $stmt->execute([$courseCode, $batchName, $trainerId, $traineeId, $status, $startDate, $endDate]);
    return true;
  } catch (Exception $__) { return false; }
}
?>
