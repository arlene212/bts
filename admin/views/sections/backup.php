<?php
$backups = isset($backups) && is_array($backups) ? $backups : [];
?>
<section class="main-content tab-content <?php echo ($currentTab === 'backup' ? 'active' : ''); ?>" id="backup">
  <div class="tab-header">
    <h2>Backup & Recovery</h2>
    <p class="text-muted">Create, restore, and manage database backups. Only administrators can perform these operations.</p>
  </div>

  <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row g-4 mt-2">
    <!-- Create Backup Card -->
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title"><i class="fas fa-database text-primary"></i> Create Backup</h5>
          <p class="card-text text-muted">Generate a complete database backup of all system data including users, courses, enrollments, and settings.</p>
          <hr>
          <form method="POST" onsubmit="return confirm('Create a new backup now? This may take a few moments.');">
            <input type="hidden" name="current_tab" value="backup">
            <button type="submit" name="create_backup" class="btn btn-primary w-100 mb-3">
              <i class="fas fa-plus-circle"></i> Create New Backup
            </button>
          </form>
          <hr>
          <div class="alert alert-warning mb-0" role="alert">
            <small><i class="fas fa-info-circle"></i> <strong>Note:</strong> Backups are stored in the <code>/backups</code> directory</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Available Backups Card -->
    <div class="col-md-8">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title"><i class="fas fa-archive text-success"></i> Available Backups</h5>
          <p class="card-text text-muted">Manage existing backup files stored on the server.</p>
          <hr>
          <?php if (empty($backups)): ?>
            <div class="text-center py-4">
              <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
              <p class="text-muted">No backups found</p>
              <small>Create your first backup using the card on the left</small>
            </div>
          <?php else: ?>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
              <table class="table table-sm table-hover">
                <thead class="table-light sticky-top">
                  <tr>
                    <th>Filename</th>
                    <th>Size</th>
                    <th>Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($backups as $b): ?>
                    <tr>
                      <td class="small" title="<?php echo htmlspecialchars($b['name']); ?>">
                        <?php 
                        $displayName = $b['name'];
                        if (strlen($displayName) > 20) {
                          $displayName = substr($displayName, 0, 17) . '...';
                        }
                        echo htmlspecialchars($displayName); 
                        ?>
                      </td>
                      <td class="small"><?php echo number_format(($b['size'] ?? 0) / 1048576, 2); ?> MB</td>
                      <td class="small"><?php echo date('m/d/y H:i', $b['mtime']); ?></td>
                      <td>
                        <div class="btn-group btn-group-sm" role="group">
                          <a class="btn btn-outline-secondary" href="?download_backup=1&backup_file=<?php echo urlencode($b['name']); ?>" title="Download">
                            <i class="fas fa-download"></i>
                          </a>
                          <form method="POST" style="display:inline-block;" onsubmit="return confirm('⚠️ Restore from <?php echo htmlspecialchars($b['name']); ?>? This will overwrite all current data!');">
                            <input type="hidden" name="current_tab" value="backup">
                            <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" name="restore_backup" class="btn btn-outline-warning btn-sm" title="Restore">
                              <i class="fas fa-rotate-left"></i>
                            </button>
                          </form>
                          <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete backup: <?php echo htmlspecialchars($b['name']); ?>?');">
                            <input type="hidden" name="current_tab" value="backup">
                            <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" name="delete_backup" class="btn btn-outline-danger btn-sm" title="Delete">
                              <i class="fas fa-trash"></i>
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="mt-2">
              <small class="text-muted">
                <i class="fas fa-info-circle"></i> Total: <?php echo count($backups); ?> backup(s)
              </small>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Danger Zone -->
  <div class="row mt-4">
    <div class="col-12">
      <div class="card border-danger">
        <div class="card-header bg-danger text-white">
          <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Danger Zone</h5>
        </div>
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col-md-8">
              <h6>Purge Non-User Data</h6>
              <p class="text-muted mb-0">
                This will permanently delete all data from the database <strong>except user accounts</strong>. 
                This includes courses, enrollments, materials, assignments, announcements, and all other records.
                <strong class="text-danger">This action cannot be undone!</strong>
              </p>
            </div>
            <div class="col-md-4 text-end">
              <form method="POST" onsubmit="return confirm('⚠️ FINAL WARNING ⚠️\n\nThis will DELETE ALL DATA except users!\n\n• All courses will be removed\n• All enrollments will be deleted\n• All materials will be erased\n• All announcements will be cleared\n\nType DELETE in the prompt to confirm.') && prompt('Type DELETE to confirm:') === 'DELETE';">
                <input type="hidden" name="current_tab" value="backup">
                <button type="submit" name="purge_data" class="btn btn-danger">
                  <i class="fas fa-trash-alt"></i> Purge All Data
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Backup Information -->
  <div class="row mt-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title"><i class="fas fa-info-circle text-info"></i> Backup Guidelines</h5>
          <div class="row">
            <div class="col-md-6">
              <h6>Best Practices:</h6>
              <ul class="small">
                <li>Create regular backups before making major changes</li>
                <li>Store backups in multiple locations (local + external)</li>
                <li>Test backup restoration periodically</li>
                <li>Keep at least 3-5 recent backups</li>
              </ul>
            </div>
            <div class="col-md-6">
              <h6>What's Included:</h6>
              <ul class="small">
                <li>All user accounts and profiles</li>
                <li>Course data and materials</li>
                <li>Enrollment records</li>
                <li>Assignments and submissions</li>
                <li>System settings and configurations</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
.sticky-top {
  position: sticky;
  top: 0;
  z-index: 10;
}
</style>