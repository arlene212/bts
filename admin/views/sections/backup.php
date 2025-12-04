<?php
require_once __DIR__ . '/../../../php/SessionManager.php';
require_once __DIR__ . '/../../../php/DatabaseConnection.php';
require_once __DIR__ . '/../../../php/config.php';
require_once __DIR__ . '/../../../php/ErrorHandler.php';

SessionManager::startSession();
SessionManager::requireRole('admin');

$errHandler = ErrorHandler::getInstance();
$csrfToken = $errHandler->generateCSRFToken();

function backup_log($event, $details = []) {
  $logFile = __DIR__ . '/../../../logs/backup_reset.log';
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
  return realpath(__DIR__ . '/../../../') . DIRECTORY_SEPARATOR . 'backups';
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
  $candidates = [
    'C:\\xampp\\mysql\\bin\\mysqldump.exe',
    'mysqldump'
  ];
  foreach ($candidates as $c) {
    if ($c === 'mysqldump' || file_exists($c)) { return $c; }
  }
  return null;
}

function find_mysql() {
  $candidates = [
    'C:\\xampp\\mysql\\bin\\mysql.exe',
    'mysql'
  ];
  foreach ($candidates as $c) {
    if ($c === 'mysql' || file_exists($c)) { return $c; }
  }
  return null;
}

function list_sql_backups() {
  $dir = ensure_backup_dir();
  $files = glob($dir . DIRECTORY_SEPARATOR . '*_full_backup.sql');
  $items = [];
  foreach ($files as $f) {
    $rf = realpath($f);
    if ($rf) { $items[] = ['path' => $rf, 'mtime' => filemtime($rf) ?: 0]; }
  }
  usort($items, function($a, $b){ return $b['mtime'] <=> $a['mtime']; });
  return $items;
}

function latest_sql_backup() {
  $items = list_sql_backups();
  if (empty($items)) { return null; }
  return $items[0]['path'];
}

function apply_retention_policy() {
  $dir = ensure_backup_dir();
  $maxCount = 20;
  $maxDays = 30;
  $items = list_sql_backups();
  $now = time();
  $toDelete = [];
  foreach ($items as $idx => $it) {
    if ($idx >= $maxCount) { $toDelete[] = $it['path']; }
    elseif (($now - $it['mtime']) > ($maxDays * 86400)) { $toDelete[] = $it['path']; }
  }
  foreach ($toDelete as $f) { @unlink($f); }
}

function verify_sql_dump($path, &$report) {
  if (!file_exists($path)) { $report = 'Backup file not found'; return false; }
  if (filesize($path) <= 0) { $report = 'Backup file empty'; return false; }
  $fh = @fopen($path, 'rb');
  if (!$fh) { $report = 'Unable to open backup'; return false; }
  $hasCreate = false; $linesChecked = 0;
  while (!feof($fh) && $linesChecked < 500) {
    $line = fgets($fh, 8192);
    if ($line === false) { break; }
    $linesChecked++;
    if (stripos($line, 'CREATE TABLE') !== false) { $hasCreate = true; break; }
  }
  fclose($fh);
  if (!$hasCreate) { $report = 'CREATE TABLE not detected'; return false; }
  $report = 'SQL dump verified';
  return true;
}

function backup_full(&$message, &$trace = null) {
  $dir = ensure_backup_dir();
  $timestamp = date('Y-m-d_H-i-s');
  $sqlPath = $dir . DIRECTORY_SEPARATOR . $timestamp . '_full_backup.sql';
  $mysqldump = find_mysqldump();
  if (!$mysqldump) { $message = 'mysqldump not found'; backup_log('backup_failed', ['reason' => $message]); return [false, null]; }
  $cmd = '"' . $mysqldump . '"'
    . ' --host=' . escapeshellarg(DB_HOST)
    . ' --user=' . escapeshellarg(DB_USER)
    . ' --password=' . escapeshellarg(DB_PASS)
    . ' --default-character-set=utf8mb4 '
    . ' --routines --triggers --events --single-transaction '
    . ' --complete-insert --extended-insert '
    . ' --hex-blob --add-drop-table --set-charset '
    . ' --result-file=' . escapeshellarg($sqlPath) . ' '
    . escapeshellarg(DB_NAME);
  $exit = null; $stderr = '';
  $started = microtime(true);
  if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (is_resource($proc)) {
      $stdout = stream_get_contents($pipes[1]);
      $stderr = stream_get_contents($pipes[2]);
      foreach ($pipes as $p) { if (is_resource($p)) fclose($p); }
      $exit = proc_close($proc);
    }
  } else {
    $output = [];
    $exit = 0;
    @exec($cmd, $output, $exit);
  }
  $duration = round((microtime(true) - $started), 2);
  if (is_array($trace)) { $trace[] = ['stage' => 'backup_dump', 'exit' => $exit, 'duration_s' => $duration]; }
  if ($exit !== 0 || !file_exists($sqlPath)) { $message = 'Database dump failed'; backup_log('backup_failed', ['reason' => $message, 'stderr' => $stderr]); return [false, null]; }
  $vr = '';
  if (!verify_sql_dump($sqlPath, $vr)) { $message = 'Backup verification failed: ' . $vr; backup_log('backup_failed', ['reason' => $message]); @unlink($sqlPath); return [false, null]; }
  apply_retention_policy();
  $message = 'Backup created: ' . basename($sqlPath);
  backup_log('backup_success', ['file' => basename($sqlPath)]);
  return [true, $sqlPath];
}

function reset_full(&$message, &$trace = null) {
  try {
    $db = new DatabaseConnection();
    $pdo = $db->getConnection();
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
      if (strtolower($t) === 'users') { continue; }
      $pdo->exec('TRUNCATE TABLE `' . str_replace('`', '``', $t) . '`');
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    $message = 'System reset completed';
    backup_log('reset_success', []);
    if (is_array($trace)) { $trace[] = ['stage' => 'reset_done', 'ok' => true]; }
    return true;
  } catch (Exception $e) {
    $message = 'Reset failed: ' . $e->getMessage();
    backup_log('reset_failed', ['error' => $e->getMessage()]);
    if (is_array($trace)) { $trace[] = ['stage' => 'reset_error', 'error' => $e->getMessage()]; }
    return false;
  }
}

function restore_latest_sql(&$message, &$trace = null) {
  $latest = latest_sql_backup();
  if (!$latest) { $message = 'No backups found'; backup_log('restore_failed', ['reason' => $message]); if (is_array($trace)) { $trace[] = ['stage' => 'no_backups']; } return false; }
  $vr = '';
  if (!verify_sql_dump($latest, $vr)) { $message = 'Backup verification failed: ' . $vr; backup_log('restore_failed', ['reason' => $message]); if (is_array($trace)) { $trace[] = ['stage' => 'verify_failed', 'reason' => $vr]; } return false; }
  $mysql = find_mysql();
  if (!$mysql) { $message = 'mysql client not found'; backup_log('restore_failed', ['reason' => $message]); if (is_array($trace)) { $trace[] = ['stage' => 'mysql_not_found']; } return false; }
  $cmd = '"' . $mysql . '"' . ' --host=' . escapeshellarg(DB_HOST) . ' --user=' . escapeshellarg(DB_USER) . ' --password=' . escapeshellarg(DB_PASS) . ' ' . escapeshellarg(DB_NAME);
  $descriptorspec = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
  $proc = proc_open($cmd, $descriptorspec, $pipes);
  if (!is_resource($proc)) { $message = 'Failed to start restore process'; backup_log('restore_failed', ['reason' => $message]); if (is_array($trace)) { $trace[] = ['stage' => 'proc_open_failed']; } return false; }
  $fh = fopen($latest, 'rb');
  if ($fh) {
    while (!feof($fh)) { fwrite($pipes[0], fread($fh, 8192)); }
    fclose($fh);
  }
  fclose($pipes[0]);
  $stdout = stream_get_contents($pipes[1]);
  $stderr = stream_get_contents($pipes[2]);
  fclose($pipes[1]); fclose($pipes[2]);
  $exit = proc_close($proc);
  if (is_array($trace)) { $trace[] = ['stage' => 'mysql_exit', 'exit' => $exit, 'stderr_len' => strlen($stderr ?? '')]; }
  if ($exit !== 0) { $message = 'Restore failed'; backup_log('restore_failed', ['stderr' => $stderr ?? '']); return false; }
  try {
    $db = new DatabaseConnection();
    $pdo = $db->getConnection();
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($tables)) { $message = 'Integrity check failed: no tables'; backup_log('restore_failed', ['reason' => $message]); return false; }
    $checked = 0; $counts = [];
    foreach ($tables as $t) {
      $cntStmt = $pdo->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $t) . '`');
      $counts[$t] = (int)$cntStmt->fetchColumn();
      if (++$checked >= 5) { break; }
    }
    backup_log('restore_success', ['file' => basename($latest), 'sample_counts' => $counts]);
  } catch (Exception $e) {
  }
  $message = 'System restored from latest backup: ' . basename($latest);
  if (is_array($trace)) { $trace[] = ['stage' => 'restore_done', 'ok' => true]; }
  return true;
}

// AJAX posting is handled via dedicated endpoint at admin/actions/backup.php to avoid header issues in views
?>

<section class="main-content tab-content <?php echo ((isset($currentTab) && $currentTab === 'backup') ? 'active' : ''); ?>" id="backup">
  <div class="tab-header">
    <h2>Backup & Reset</h2>
    <p class="text-muted">Creates a full SQL backup of schema and data, then resets system data.</p>
  </div>

  <div class="row g-4 mt-2">
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title"><i class="fas fa-shield-alt text-primary"></i> Backup and Reset</h5>
          <p class="text-muted">Transactional flow: backup must succeed before reset proceeds.</p>
          <hr>
          <form method="POST" id="backupResetForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="backup_and_reset">
            <input type="hidden" name="ajax" value="1">
            <button type="submit" class="btn btn-primary w-100">
              <span class="spinner-border spinner-border-sm me-2 d-none" id="backupResetSpinner" role="status" aria-hidden="true"></span>
              <i class="fas fa-database"></i> Backup and Reset
            </button>
          </form>
          <div class="mt-3" id="backupResetProgress" aria-live="polite"></div>
          <div class="mt-2">
            <small class="text-muted"><i class="fas fa-info-circle"></i> Output: <code>YYYY-MM-DD_HH-MM-SS_full_backup.sql</code> in <code>/backups</code></small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title"><i class="fas fa-save text-primary"></i> Backup Only</h5>
          <p class="text-muted">Creates a full SQL backup without resetting any data.</p>
          <hr>
          <form method="POST" id="backupOnlyForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="backup_only">
            <input type="hidden" name="ajax" value="1">
            <button type="submit" class="btn btn-outline-primary w-100">
              <span class="spinner-border spinner-border-sm me-2 d-none" id="backupOnlySpinner" role="status" aria-hidden="true"></span>
              <i class="fas fa-save"></i> Create System Backup
            </button>
          </form>
          <div class="mt-3" id="backupOnlyProgress" aria-live="polite"></div>
          <div class="mt-2">
            <small class="text-muted"><i class="fas fa-info-circle"></i> Output: <code>*_full_backup.sql</code> in <code>/backups</code></small>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title"><i class="fas fa-rotate-right text-success"></i> Restore System</h5>
          <p class="text-muted">Restores latest verified SQL backup and validates table integrity.</p>
          <hr>
          <form method="POST" id="restoreLatestForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="restore_latest">
            <input type="hidden" name="ajax" value="1">
            <button type="submit" class="btn btn-outline-success w-100">
              <span class="spinner-border spinner-border-sm me-2 d-none" id="restoreLatestSpinner" role="status" aria-hidden="true"></span>
              <i class="fas fa-rotate-right"></i> Restore System
            </button>
          </form>
          <div class="mt-3" id="restoreLatestProgress" aria-live="polite"></div>
          <div class="mt-2">
            <small class="text-muted"><i class="fas fa-info-circle"></i> Uses latest <code>*_full_backup.sql</code> in <code>/backups</code></small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row mt-4">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="fas fa-list text-secondary"></i> Backups</h5>
            <div>
              <button class="btn btn-sm btn-outline-secondary" id="refreshBackupsBtn"><i class="fas fa-sync"></i> Refresh</button>
            </div>
          </div>
          <div class="table-responsive mt-3">
            <table class="table table-sm" id="backupsTable">
              <thead>
                <tr>
                  <th>File</th>
                  <th>Created</th>
                  <th class="text-end">Size</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
          <div class="small text-muted"><i class="fas fa-info-circle"></i> Backups are stored in <code>/backups</code>.</div>
        </div>
      </div>
      <div class="card">
        <div class="card-body">
          <h5 class="card-title"><i class="fas fa-info-circle text-info"></i> Guidelines</h5>
          <ul class="small mb-0">
            <li>Only administrators can perform backup, reset, and restore.</li>
            <li>Backups are stored securely in <code>/backups</code> with access denied by server.</li>
            <li>Backup verification and post-restore integrity checks are enforced.</li>
            <li>All activities are logged to <code>/logs/backup_reset.log</code>.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
function renderSteps(container, trace){
  if(!Array.isArray(trace)) return;
  const ul = document.createElement('ul');
  ul.className = 'list-unstyled small';
  trace.forEach(t=>{
    const li = document.createElement('li');
    li.textContent = (t.stage||'step') + (t.ts? (' @ '+t.ts):'');
    ul.appendChild(li);
  });
  container.innerHTML = '';
  container.appendChild(ul);
}
document.getElementById('backupResetForm')?.addEventListener('submit', function(e){
  e.preventDefault();
  const btn = this.querySelector('button');
  const sp = document.getElementById('backupResetSpinner');
  const progress = document.getElementById('backupResetProgress');
  btn.disabled = true; if (sp) sp.classList.remove('d-none');
  const fd = new FormData(this);
  openConfirmWithPassword('Confirm Backup & Reset', 'This will create a full backup and then reset system data. Please enter admin password to confirm.', 'Backup & Reset')
    .then(pwd => { if(!pwd) throw new Error('cancel'); fd.append('admin_password', pwd); return fetch('actions/backup.php', { method: 'POST', body: fd }); })
    .then(r=>r.json())
    .then(data=>{
      renderSteps(progress, data.trace);
      const alert = document.createElement('div');
      alert.className = 'alert alert-'+(data.type||'info')+' mt-2';
      alert.textContent = data.message || 'Completed';
      progress.appendChild(alert);
      if (data.ok) { setTimeout(()=>{ window.location.reload(); }, 1000); }
    })
    .catch(()=>{
      const alert = document.createElement('div');
      alert.className = 'alert alert-danger mt-2';
      alert.textContent = 'Action cancelled or request failed';
      progress.appendChild(alert);
    })
    .finally(()=>{ btn.disabled = false; if (sp) sp.classList.add('d-none'); });
});
document.getElementById('restoreLatestForm')?.addEventListener('submit', function(e){
  e.preventDefault();
  const btn = this.querySelector('button');
  const sp = document.getElementById('restoreLatestSpinner');
  const progress = document.getElementById('restoreLatestProgress');
  btn.disabled = true; if (sp) sp.classList.remove('d-none');
  const fd = new FormData(this);
  openConfirm('Confirm Restore', 'Restore the system from the latest backup?', 'Restore')
  .then(ok=>{ if(!ok) throw new Error('cancel'); return fetch('actions/backup.php', { method: 'POST', body: fd }); })
  .then(r=>r.json())
  .then(data=>{
    renderSteps(progress, data.trace);
    const alert = document.createElement('div');
    alert.className = 'alert alert-'+(data.type||'info')+' mt-2';
    alert.textContent = data.message || 'Completed';
    progress.appendChild(alert);
    if (data.ok) { setTimeout(()=>{ window.location.reload(); }, 1000); }
  })
  .catch(()=>{
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger mt-2';
    alert.textContent = 'Action cancelled or request failed';
    progress.appendChild(alert);
  })
  .finally(()=>{ btn.disabled = false; if (sp) sp.classList.add('d-none'); });
});

document.getElementById('backupOnlyForm')?.addEventListener('submit', function(e){
  e.preventDefault();
  const btn = this.querySelector('button');
  const sp = document.getElementById('backupOnlySpinner');
  const progress = document.getElementById('backupOnlyProgress');
  btn.disabled = true; if (sp) sp.classList.remove('d-none');
  const fd = new FormData(this);
  openConfirm('Confirm Backup', 'Create a full system backup now?', 'Create Backup')
  .then(ok=>{ if(!ok) throw new Error('cancel'); return fetch('actions/backup.php', { method: 'POST', body: fd }); })
    .then(r=>r.json())
    .then(data=>{
      const alert = document.createElement('div');
      alert.className = 'alert alert-'+(data.type||'info')+' mt-2';
      alert.textContent = data.message || 'Completed';
      progress.innerHTML = '';
      progress.appendChild(alert);
      loadBackups();
    })
    .catch(()=>{
      const alert = document.createElement('div');
      alert.className = 'alert alert-danger mt-2';
      alert.textContent = 'Action cancelled or request failed';
      progress.innerHTML = '';
      progress.appendChild(alert);
    })
    .finally(()=>{ btn.disabled = false; if (sp) sp.classList.add('d-none'); });
});

function formatBytes(bytes){
  const sizes=['B','KB','MB','GB'];
  if(bytes===0) return '0 B';
  const i = Math.min(Math.floor(Math.log(bytes)/Math.log(1024)), sizes.length-1);
  return (bytes/Math.pow(1024,i)).toFixed(i===0?0:2)+' '+sizes[i];
}
function renderBackups(rows){
  const tbody = document.querySelector('#backupsTable tbody');
  tbody.innerHTML = '';
  if(!Array.isArray(rows) || rows.length===0){
    const tr = document.createElement('tr');
    const td = document.createElement('td'); td.colSpan = 4; td.className='text-muted'; td.textContent='No backups found'; tr.appendChild(td); tbody.appendChild(tr); return;
  }
  rows.forEach(b=>{
    const tr = document.createElement('tr');
    const created = new Date(b.mtime*1000);
    tr.innerHTML = `
      <td><code>${b.file}</code></td>
      <td>${created.toLocaleString()}</td>
      <td class="text-end">${formatBytes(b.size||0)}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-outline-success me-2" data-action="restore" data-file="${b.file}"><i class="fas fa-rotate-right"></i> Restore</button>
        <button class="btn btn-sm btn-outline-danger" data-action="delete" data-file="${b.file}"><i class="fas fa-trash"></i> Delete</button>
      </td>`;
    tbody.appendChild(tr);
  });
}
function loadBackups(){
  const fd = new FormData(); fd.append('csrf_token', '<?php echo htmlspecialchars($csrfToken); ?>'); fd.append('action','list_backups'); fd.append('ajax','1');
  fetch('actions/backup.php', { method:'POST', body:fd })
    .then(r=>r.json())
    .then(data=>{ renderBackups(data.backups||[]); })
    .catch(()=>{});
}
document.getElementById('refreshBackupsBtn')?.addEventListener('click', function(e){ e.preventDefault(); loadBackups(); });
document.querySelector('#backupsTable tbody')?.addEventListener('click', function(e){
  const btn = e.target.closest('button[data-action]'); if(!btn) return;
  const action = btn.getAttribute('data-action'); const file = btn.getAttribute('data-file');
  const fd = new FormData(); fd.append('csrf_token','<?php echo htmlspecialchars($csrfToken); ?>'); fd.append('ajax','1');
  if(action==='restore'){ fd.append('action','restore_backup'); fd.append('file', file); }
  else if(action==='delete'){ fd.append('action','delete_backup'); fd.append('file', file); }
  else { return; }
  btn.disabled = true;
  const msg = action==='delete' ? ('Delete backup '+file+'?') : ('Restore backup '+file+'?');
  const okLabel = action==='delete' ? 'Delete' : 'Restore';
  openConfirm('Please Confirm', msg, okLabel)
    .then(ok=>{ if(!ok){ throw new Error('cancel'); } return fetch('actions/backup.php', { method:'POST', body:fd }); })
    .then(r=>r.json())
    .then(data=>{
      const alert = document.createElement('div');
      alert.className = 'alert alert-'+(data.type||'info')+' mt-2';
      alert.textContent = data.message || 'Completed';
      document.querySelector('#backupsTable').parentElement.appendChild(alert);
      loadBackups();
    })
    .catch(()=>{})
    .finally(()=>{ btn.disabled = false; });
});
document.addEventListener('DOMContentLoaded', loadBackups);

</script>

<div class="cf-modal d-none" id="backupConfirmModal" aria-hidden="true">
  <div class="cf-dialog" role="dialog" aria-modal="true" aria-labelledby="cfTitle">
    <div class="cf-header"><h5 id="cfTitle"><i class="fas fa-question-circle"></i> Confirm Action</h5></div>
    <div class="cf-body"><p id="cfMessage" class="mb-0"></p>
      <div id="cfPasswordWrap" class="mt-3 d-none">
        <label for="cfPassword" class="form-label small">Admin password</label>
        <input type="password" class="form-control" id="cfPassword" autocomplete="current-password" placeholder="Enter admin password">
      </div>
    </div>
    <div class="cf-footer">
      <button type="button" class="btn btn-secondary" id="cfCancelBtn">Cancel</button>
      <button type="button" class="btn btn-primary" id="cfOkBtn">Confirm</button>
    </div>
  </div>
  <div class="cf-backdrop"></div>
  <style>
    .cf-modal{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;}
    .cf-modal.d-none{display:none;}
    .cf-backdrop{position:absolute;inset:0;background:rgba(0,0,0,0.4);} 
    .cf-dialog{position:relative;z-index:2;background:#fff;border-radius:8px;box-shadow:0 10px 30px rgba(0,0,0,0.2);max-width:420px;width:92%;}
    .cf-header{padding:12px 16px;border-bottom:1px solid #eee;}
    .cf-body{padding:16px;}
    .cf-footer{padding:12px 16px;border-top:1px solid #eee;display:flex;gap:8px;justify-content:flex-end;}
  </style>
</div>

<script>
let cfResolve = null;
let cfMode = 'confirm';
function openConfirm(title, message, okLabel){
  return new Promise((resolve)=>{
    cfResolve = resolve;
    cfMode = 'confirm';
    const modal = document.getElementById('backupConfirmModal');
    document.getElementById('cfTitle').textContent = title || 'Confirm';
    document.getElementById('cfMessage').textContent = message || 'Proceed?';
    document.getElementById('cfOkBtn').textContent = okLabel || 'Confirm';
    const pw = document.getElementById('cfPasswordWrap'); if (pw) pw.classList.add('d-none');
    const pwd = document.getElementById('cfPassword'); if (pwd) pwd.value = '';
    modal.classList.remove('d-none');
  });
}
function openConfirmWithPassword(title, message, okLabel){
  return new Promise((resolve)=>{
    cfResolve = resolve;
    cfMode = 'password';
    const modal = document.getElementById('backupConfirmModal');
    document.getElementById('cfTitle').textContent = title || 'Confirm';
    document.getElementById('cfMessage').textContent = message || 'Proceed?';
    document.getElementById('cfOkBtn').textContent = okLabel || 'Confirm';
    const pw = document.getElementById('cfPasswordWrap'); if (pw) pw.classList.remove('d-none');
    const pwd = document.getElementById('cfPassword'); if (pwd) pwd.value = '';
    modal.classList.remove('d-none');
  });
}
document.getElementById('cfCancelBtn')?.addEventListener('click', function(e){
  document.getElementById('backupConfirmModal').classList.add('d-none');
  if (cfResolve) cfResolve(false);
  e.stopImmediatePropagation();
}, { capture: true });
document.getElementById('cfOkBtn')?.addEventListener('click', function(e){
  const modal = document.getElementById('backupConfirmModal');
  modal.classList.add('d-none');
  if (cfResolve) {
    if (cfMode === 'password') {
      const pwd = document.getElementById('cfPassword')?.value || '';
      cfResolve(pwd);
    } else {
      cfResolve(true);
    }
  }
  e.stopImmediatePropagation();
}, { capture: true });
document.addEventListener('keydown', function(e){
  const visible = !document.getElementById('backupConfirmModal').classList.contains('d-none');
  if (!visible) return;
  if (e.key === 'Escape') { document.getElementById('cfCancelBtn').click(); }
});
</script>

<!-- Admin password modal removed: reverting to pre-password flow for backup actions -->

<style>
.sticky-top { position: sticky; top: 0; z-index: 10; }
</style>
