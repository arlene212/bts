<?php
require_once __DIR__ . '/../../php/SessionManager.php';
require_once __DIR__ . '/../../php/DatabaseConnection.php';
require_once __DIR__ . '/../../php/config.php';
require_once __DIR__ . '/../../php/ErrorHandler.php';

SessionManager::startSession();
SessionManager::requireRole('admin');

$errHandler = ErrorHandler::getInstance();

function backup_log($event, $details = []) {
  $logFile = __DIR__ . '/../../logs/backup_reset.log';
  $dir = dirname($logFile);
  if (!is_dir($dir)) { mkdir($dir, 0755, true); }
  $entry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'event' => $event,
    'user' => $_SESSION['user']['user_id'] ?? 'unknown',
    'details' => $details
  ];
  file_put_contents($logFile, json_encode($entry) . "\n", FILE_APPEND | LOCK_EX);
}

function get_backup_dir() {
  return realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR . 'backups';
}
function ensure_backup_dir() {
  $dir = get_backup_dir();
  if (!is_dir($dir)) { mkdir($dir, 0755, true); }
  $ht = $dir . DIRECTORY_SEPARATOR . '.htaccess';
  if (!file_exists($ht)) {
    file_put_contents($ht, "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
  }
  return $dir;
}
function find_mysqldump() {
  $candidates = ['C:\\xampp\\mysql\\bin\\mysqldump.exe', 'mysqldump'];
  foreach ($candidates as $c) { if ($c === 'mysqldump' || file_exists($c)) { return $c; } }
  return null;
}
function find_mysql() {
  $candidates = ['C:\\xampp\\mysql\\bin\\mysql.exe', 'mysql'];
  foreach ($candidates as $c) { if ($c === 'mysql' || file_exists($c)) { return $c; } }
  return null;
}
function list_sql_backups() {
  $dir = ensure_backup_dir();
  $files = glob($dir . DIRECTORY_SEPARATOR . '*_full_backup.sql');
  $items = [];
  foreach ($files as $f) { $rf = realpath($f); if ($rf) { $items[] = ['path' => $rf, 'mtime' => filemtime($rf) ?: 0]; } }
  usort($items, function($a, $b){ return $b['mtime'] <=> $a['mtime']; });
  return $items;
}
function latest_sql_backup() { $items = list_sql_backups(); return empty($items) ? null : $items[0]['path']; }
function apply_retention_policy() {
  $items = list_sql_backups(); $now = time(); $maxCount = 20; $maxDays = 30; $toDelete = [];
  foreach ($items as $idx => $it) { if ($idx >= $maxCount) { $toDelete[] = $it['path']; } elseif (($now - $it['mtime']) > ($maxDays*86400)) { $toDelete[] = $it['path']; } }
  foreach ($toDelete as $f) { @unlink($f); }
}
function verify_sql_dump($path, &$report) {
  if (!file_exists($path)) { $report = 'Backup file not found'; return false; }
  if (filesize($path) <= 0) { $report = 'Backup file empty'; return false; }
  $fh = @fopen($path, 'rb'); if (!$fh) { $report = 'Unable to open backup'; return false; }
  $hasCreate = false; $linesChecked = 0; while (!feof($fh) && $linesChecked < 500) { $line = fgets($fh, 8192); if ($line === false) { break; } $linesChecked++; if (stripos($line, 'CREATE TABLE') !== false) { $hasCreate = true; break; } }
  fclose($fh); if (!$hasCreate) { $report = 'CREATE TABLE not detected'; return false; }
  $report = 'SQL dump verified'; return true;
}
function backup_full(&$message, &$trace = null) {
  $dir = ensure_backup_dir(); $timestamp = date('Y-m-d_H-i-s'); $sqlPath = $dir . DIRECTORY_SEPARATOR . $timestamp . '_full_backup.sql';
  $mysqldump = find_mysqldump(); if (!$mysqldump) { $message = 'mysqldump not found'; backup_log('backup_failed', ['reason' => $message]); return [false, null]; }
  $cmd = '"' . $mysqldump . '"' . ' --host=' . escapeshellarg(DB_HOST) . ' --user=' . escapeshellarg(DB_USER) . ' --password=' . escapeshellarg(DB_PASS) . ' --default-character-set=utf8mb4 ' . ' --routines --triggers --events --single-transaction ' . ' --complete-insert --extended-insert ' . ' --hex-blob --add-drop-table --set-charset ' . ' --result-file=' . escapeshellarg($sqlPath) . ' ' . escapeshellarg(DB_NAME);
  $exit = null; $stderr = ''; $started = microtime(true);
  $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
  if (is_resource($proc)) { $stdout = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]); foreach ($pipes as $p) { if (is_resource($p)) fclose($p); } $exit = proc_close($proc); }
  $duration = round((microtime(true)-$started),2); if (is_array($trace)) { $trace[] = ['stage'=>'backup_dump','exit'=>$exit,'duration_s'=>$duration]; }
  if ($exit !== 0 || !file_exists($sqlPath)) { $message = 'Database dump failed'; backup_log('backup_failed', ['reason'=>$message,'stderr'=>$stderr]); return [false, null]; }
  $vr = ''; if (!verify_sql_dump($sqlPath, $vr)) { $message = 'Backup verification failed: ' . $vr; backup_log('backup_failed', ['reason'=>$message]); @unlink($sqlPath); return [false, null]; }
  apply_retention_policy(); $message = 'Backup created: ' . basename($sqlPath); backup_log('backup_success', ['file'=>basename($sqlPath)]); return [true, $sqlPath];
}
function reset_full(&$message, &$trace = null) {
  try { $db = new DatabaseConnection(); $pdo = $db->getConnection(); $pdo->exec('SET FOREIGN_KEY_CHECKS=0'); $stmt = $pdo->query('SHOW TABLES'); $tables = $stmt->fetchAll(PDO::FETCH_COLUMN); foreach ($tables as $t) { if (strtolower($t) === 'users') { continue; } $pdo->exec('TRUNCATE TABLE `' . str_replace('`','``',$t) . '`'); } $pdo->exec('SET FOREIGN_KEY_CHECKS=1'); $message = 'System reset completed'; backup_log('reset_success', []); if (is_array($trace)) { $trace[] = ['stage'=>'reset_done','ok'=>true]; } return true; } catch (Exception $e) { $message = 'Reset failed: ' . $e->getMessage(); backup_log('reset_failed', ['error'=>$e->getMessage()]); if (is_array($trace)) { $trace[] = ['stage'=>'reset_error','error'=>$e->getMessage()]; } return false; }
}
function restore_latest_sql(&$message, &$trace = null) {
  $latest = latest_sql_backup(); if (!$latest) { $message = 'No backups found'; backup_log('restore_failed', ['reason'=>$message]); if (is_array($trace)) { $trace[] = ['stage'=>'no_backups']; } return false; }
  $vr=''; if (!verify_sql_dump($latest,$vr)) { $message = 'Backup verification failed: ' . $vr; backup_log('restore_failed', ['reason'=>$message]); if (is_array($trace)) { $trace[] = ['stage'=>'verify_failed','reason'=>$vr]; } return false; }
  $mysql = find_mysql(); if (!$mysql) { $message = 'mysql client not found'; backup_log('restore_failed', ['reason'=>$message]); if (is_array($trace)) { $trace[] = ['stage'=>'mysql_not_found']; } return false; }
  $cmd = '"' . $mysql . '"' . ' --host=' . escapeshellarg(DB_HOST) . ' --user=' . escapeshellarg(DB_USER) . ' --password=' . escapeshellarg(DB_PASS) . ' ' . escapeshellarg(DB_NAME);
  $proc = proc_open($cmd, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes); if (!is_resource($proc)) { $message = 'Failed to start restore process'; backup_log('restore_failed', ['reason'=>$message]); if (is_array($trace)) { $trace[] = ['stage'=>'proc_open_failed']; } return false; }
  $fh = fopen($latest,'rb'); if ($fh) { while (!feof($fh)) { fwrite($pipes[0], fread($fh,8192)); } fclose($fh); } fclose($pipes[0]); $stdout = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); $exit = proc_close($proc);
  if (is_array($trace)) { $trace[] = ['stage'=>'mysql_exit','exit'=>$exit,'stderr_len'=>strlen($stderr??'')]; }
  if ($exit !== 0) { $message = 'Restore failed'; backup_log('restore_failed', ['stderr'=>$stderr??'']); return false; }
  try { $db = new DatabaseConnection(); $pdo = $db->getConnection(); $stmt = $pdo->query('SHOW TABLES'); $tables = $stmt->fetchAll(PDO::FETCH_COLUMN); if (empty($tables)) { $message = 'Integrity check failed: no tables'; backup_log('restore_failed', ['reason'=>$message]); return false; } $checked=0; $counts=[]; foreach($tables as $t){ $cntStmt=$pdo->query('SELECT COUNT(*) FROM `'.str_replace('`','``',$t).'`'); $counts[$t]=(int)$cntStmt->fetchColumn(); if(++$checked>=5){break;} } backup_log('restore_success', ['file'=>basename($latest),'sample_counts'=>$counts]); } catch (Exception $e) {}
  $message = 'System restored from latest backup: ' . basename($latest); if (is_array($trace)) { $trace[] = ['stage'=>'restore_done','ok'=>true]; } return true;
}

header('Content-Type: application/json');
$action = $_POST['action'] ?? '';
$token = $_POST['csrf_token'] ?? '';
if (!$errHandler->validateCSRFToken($token)) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Security check failed (CSRF)']); exit; }
if ($action === 'backup_and_reset') {
  $trace=[]; $msgBk=''; $okBk=false; $bkPath=null; $trace[]=['stage'=>'start_backup','ts'=>date('c')];
  list($okBk,$bkPath) = backup_full($msgBk,$trace);
  if (!$okBk) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>$msgBk,'trace'=>$trace]); exit; }
  // Verify admin password before proceeding to reset
  $adminPassword = $_POST['admin_password'] ?? '';
  if (trim($adminPassword) === '') { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Admin password is required to proceed with reset','trace'=>$trace]); exit; }
  try {
    $db = new DatabaseConnection();
    $pdo = $db->getConnection();
    $userId = $_SESSION['user']['user_id'] ?? '';
    $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ? AND role = "admin" AND status = "active"');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !password_verify($adminPassword, $row['password'])) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Invalid admin password','trace'=>$trace]); exit; }
  } catch (Exception $e) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Password verification failed','trace'=>$trace]); exit; }
  $trace[]=['stage'=>'start_reset','ts'=>date('c')]; $msgRs=''; $okRs = reset_full($msgRs,$trace); $type = $okRs ? 'warning' : 'danger'; $finalMsg = $okRs ? ('Backup created and system reset: ' . basename($bkPath)) : $msgRs; echo json_encode(['ok'=>$okRs,'type'=>$type,'message'=>$finalMsg,'trace'=>$trace]); exit;
} elseif ($action === 'restore_latest') {
  $trace=[]; $trace[]=['stage'=>'start_restore','ts'=>date('c')];
  $adminPassword = $_POST['admin_password'] ?? '';
  if (trim($adminPassword) === '') { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Admin password is required to proceed','trace'=>$trace]); exit; }
  try {
    $db = new DatabaseConnection();
    $pdo = $db->getConnection();
    $userId = $_SESSION['user']['user_id'] ?? '';
    $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ? AND role = "admin" AND status = "active"');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !password_verify($adminPassword, $row['password'])) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Invalid admin password','trace'=>$trace]); exit; }
  } catch (Exception $e) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Password verification failed','trace'=>$trace]); exit; }
  $msg=''; $ok = restore_latest_sql($msg,$trace); $type = $ok ? 'success':'danger'; echo json_encode(['ok'=>$ok,'type'=>$type,'message'=>$msg,'trace'=>$trace]); exit;
} elseif ($action === 'backup_only') {
  $trace=[]; $trace[]=['stage'=>'start_backup_only','ts'=>date('c')];
  $adminPassword = $_POST['admin_password'] ?? '';
  if (trim($adminPassword) === '') { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Admin password is required to proceed','trace'=>$trace]); exit; }
  try {
    $db = new DatabaseConnection();
    $pdo = $db->getConnection();
    $userId = $_SESSION['user']['user_id'] ?? '';
    $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ? AND role = "admin" AND status = "active"');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !password_verify($adminPassword, $row['password'])) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Invalid admin password','trace'=>$trace]); exit; }
  } catch (Exception $e) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Password verification failed','trace'=>$trace]); exit; }
  $msgBk=''; $okBk=false; $bkPath=null;
  list($okBk,$bkPath) = backup_full($msgBk,$trace);
  $type = $okBk ? 'success' : 'danger';
  echo json_encode(['ok'=>$okBk,'type'=>$type,'message'=>$msgBk,'file'=>($bkPath?basename($bkPath):null),'trace'=>$trace]); exit;
} elseif ($action === 'list_backups') {
  $items = list_sql_backups();
  $dir = ensure_backup_dir();
  $backups = [];
  foreach ($items as $it) {
    $path = $it['path'];
    if (strpos($path, $dir) !== 0) { continue; }
    $backups[] = [
      'file' => basename($path),
      'mtime' => $it['mtime'],
      'size' => @filesize($path) ?: 0
    ];
  }
  echo json_encode(['ok'=>true,'type'=>'info','backups'=>$backups]); exit;
} elseif ($action === 'restore_backup') {
  $file = $_POST['file'] ?? '';
  $dir = ensure_backup_dir();
  $safe = basename($file);
  if (!$safe || !preg_match('/_full_backup\.sql$/', $safe)) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Invalid file']); exit; }
  $path = realpath($dir . DIRECTORY_SEPARATOR . $safe);
  if (!$path || strpos($path, $dir) !== 0 || !file_exists($path)) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Backup not found']); exit; }
  $adminPassword = $_POST['admin_password'] ?? '';
  if (trim($adminPassword) === '') { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Admin password is required to proceed']); exit; }
  try {
    $db = new DatabaseConnection();
    $pdo = $db->getConnection();
    $userId = $_SESSION['user']['user_id'] ?? '';
    $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ? AND role = "admin" AND status = "active"');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !password_verify($adminPassword, $row['password'])) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Invalid admin password']); exit; }
  } catch (Exception $e) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Password verification failed']); exit; }
  $vr=''; if (!verify_sql_dump($path,$vr)) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Backup verification failed: '.$vr]); exit; }
  $mysql = find_mysql(); if (!$mysql) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'mysql client not found']); exit; }
  $cmd = '"' . $mysql . '"' . ' --host=' . escapeshellarg(DB_HOST) . ' --user=' . escapeshellarg(DB_USER) . ' --password=' . escapeshellarg(DB_PASS) . ' ' . escapeshellarg(DB_NAME);
  $proc = proc_open($cmd, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes);
  if (!is_resource($proc)) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Failed to start restore process']); exit; }
  $fh = fopen($path,'rb'); if ($fh) { while (!feof($fh)) { fwrite($pipes[0], fread($fh,8192)); } fclose($fh); }
  fclose($pipes[0]); $stdout = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); $exit = proc_close($proc);
  if ($exit !== 0) { backup_log('restore_failed', ['file'=>$safe,'stderr'=>$stderr??'']); echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Restore failed']); exit; }
  backup_log('restore_success', ['file'=>$safe]);
  echo json_encode(['ok'=>true,'type'=>'success','message'=>'System restored from backup: '.$safe]); exit;
} elseif ($action === 'delete_backup') {
  $file = $_POST['file'] ?? '';
  $dir = ensure_backup_dir();
  $safe = basename($file);
  if (!$safe || !preg_match('/_full_backup\.sql$/', $safe)) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Invalid file']); exit; }
  $path = realpath($dir . DIRECTORY_SEPARATOR . $safe);
  if (!$path || strpos($path, $dir) !== 0 || !file_exists($path)) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Backup not found']); exit; }
  $adminPassword = $_POST['admin_password'] ?? '';
  if (trim($adminPassword) === '') { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Admin password is required to proceed']); exit; }
  try {
    $db = new DatabaseConnection();
    $pdo = $db->getConnection();
    $userId = $_SESSION['user']['user_id'] ?? '';
    $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ? AND role = "admin" AND status = "active"');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !password_verify($adminPassword, $row['password'])) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Invalid admin password']); exit; }
  } catch (Exception $e) { echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Password verification failed']); exit; }
  if (@unlink($path)) { backup_log('backup_deleted', ['file'=>$safe]); echo json_encode(['ok'=>true,'type'=>'warning','message'=>'Backup deleted: '.$safe]); exit; }
  echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Delete failed']); exit;
}
echo json_encode(['ok'=>false,'type'=>'danger','message'=>'Invalid action']);
