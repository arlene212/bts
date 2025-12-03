<?php
if (!isset($approvedEnrollments) || !is_array($approvedEnrollments)) { $approvedEnrollments = []; }
if (!isset($batchAssignments) || !is_array($batchAssignments)) { $batchAssignments = []; }
if (!isset($pdo) || !($pdo instanceof PDO)) { require_once __DIR__ . '/../../php/DatabaseConnection.php'; $database = new DatabaseConnection(); $pdo = $database->getConnection(); }
$droppedStmt = $pdo->prepare("SELECT user_id, first_name, last_name, middle_name, email, contact_number, date_created FROM users WHERE role = 'trainee' AND status = 'dropped' ORDER BY last_name, first_name");
$droppedStmt->execute();
$droppedTrainees = $droppedStmt->fetchAll(PDO::FETCH_ASSOC);
$graduatedStmt = $pdo->prepare("SELECT user_id, first_name, last_name, middle_name, email, contact_number, date_created FROM users WHERE role = 'trainee' AND status = 'graduated' ORDER BY last_name, first_name");
$graduatedStmt->execute();
$graduatedTrainees = $graduatedStmt->fetchAll(PDO::FETCH_ASSOC);
$droppedTraineesCount = is_array($droppedTrainees) ? count($droppedTrainees) : 0;
$graduatedTraineesCount = is_array($graduatedTrainees) ? count($graduatedTrainees) : 0;
?>
<section class="main-content tab-content <?php echo ($currentTab === 'trainees' ? 'active' : ''); ?>" id="trainees">
  <div class="tab-header"><h2>Trainee Management</h2><div class="tab-actions"><button class="create-btn" id="createTraineeBtn">+ Create Trainee</button></div></div>
  <div class="search-container">
    <div class="input-group">
      <span class="input-group-text"><i class="fas fa-search"></i></span>
      <input type="text" id="traineeSearchInput" class="form-control" placeholder="Search trainees by name, ID, or email..." value="<?php echo htmlspecialchars($traineeSearch); ?>">
      <div class="search-loading hidden" id="traineeSearchLoading"><i class="fas fa-spinner fa-spin"></i></div>
      <?php if (!empty($traineeSearch)): ?><a href="?trainee_page=1" class="btn btn-link" id="traineeClearSearch">Clear</a><?php endif; ?>
    </div>
    <div class="search-results-dropdown" id="traineeSearchResults"></div>
    <div class="search-results-info" id="traineeSearchResultsInfo">
      <?php if (!empty($traineeSearch)): ?>
        Showing <?php echo count($activeTrainees); ?> of <?php echo $totalTrainees; ?> active trainees matching "<?php echo htmlspecialchars($traineeSearch); ?>"
      <?php endif; ?>
    </div>
  </div>
  <div class="switch-oval" style="margin-bottom: 20px;"><div class="switch-inner" id="traineeSwitchInner"></div><button class="switch-btn active" data-tab="activeTrainees">Active (<?php echo $totalTrainees; ?>)</button><button class="switch-btn" data-tab="droppedTrainees">Dropped (<?php echo $droppedTraineesCount; ?>)</button><button class="switch-btn" data-tab="graduatedTrainees">Graduated (<?php echo $graduatedTraineesCount; ?>)</button><button class="switch-btn" data-tab="archivedTrainees">Archived (<?php echo $totalArchivedTrainees; ?>)</button></div>
  <div class="tab-inner active" id="activeTrainees" data-tab-content>
    <h3>Active Trainees (<?php echo $totalTrainees; ?>)</h3>
    <div class="table-container">
      <table class="table table-striped table-hover trainee-table"><thead><tr><th>User ID</th><th>User Name</th><th>Email</th><th>Contact Number</th><th>Enrolled Courses</th><th>Batch</th><th>Date Created</th><th>Actions</th></tr></thead><tbody>
        <?php if (empty($activeTrainees)): ?>
          <tr><td colspan="8" class="no-data">No active trainees found</td></tr>
        <?php else: ?>
          <?php foreach ($activeTrainees as $trainee): ?>
            <tr>
              <td><?php echo htmlspecialchars($trainee['user_id']); ?></td>
              <td><?php echo htmlspecialchars($trainee['last_name'] . ', ' . $trainee['first_name'] . ($trainee['middle_name'] ? ' ' . $trainee['middle_name'] . '.' : '')); ?></td>
              <td><?php echo htmlspecialchars($trainee['email']); ?></td>
              <td><?php echo htmlspecialchars($trainee['contact_number']); ?></td>
              <td>
                <?php
                $enrolledCourses = array_filter($approvedEnrollments, function ($e) use ($trainee) { return $e['trainee_id'] == $trainee['user_id']; });
                if (!empty($enrolledCourses)) { echo implode(', ', array_map(function ($e) { return $e['course_code']; }, $enrolledCourses)); } else { echo 'No courses enrolled'; }
                ?>
              </td>
              <td>
                <?php
                $traineeBatches = array_filter($batchAssignments, function ($ba) use ($trainee) { return $ba['trainee_id'] == $trainee['user_id']; });
                if (!empty($traineeBatches)) { echo implode(', ', array_map(function ($ba) { return $ba['batch_name']; }, $traineeBatches)); } else { echo 'No batch assigned'; }
                ?>
              </td>
              <td><?php echo date('Y-m-d', strtotime($trainee['date_created'])); ?></td>
              <td class="table-actions">
                <button class="action-btn edit edit-trainee-btn btn btn-sm btn-outline-primary" title="Edit" data-user-id="<?php echo $trainee['user_id']; ?>" data-user-data='<?php echo htmlspecialchars(json_encode($trainee), ENT_QUOTES, 'UTF-8'); ?>'><i class="fas fa-edit"></i><span class="btn-text">Edit</span></button>
                <button class="action-btn reset reset-password-btn btn btn-sm btn-warning" title="Reset Password" data-user-id="<?php echo $trainee['user_id']; ?>" data-user-name="<?php echo htmlspecialchars($trainee['first_name'] . ' ' . $trainee['last_name'], ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-key"></i><span class="btn-text">Reset</span></button>
                <form method="POST" onsubmit="return false;" class="archive-form"><input type="hidden" name="user_id" value="<?php echo $trainee['user_id']; ?>"><input type="hidden" name="current_tab" value="trainees"><button type="submit" name="archive_user" class="action-btn archive btn btn-sm btn-outline-secondary" title="Archive"><i class="fas fa-archive"></i><span class="btn-text">Archive</span></button></form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody></table>
    </div>
    <?php if ($totalActiveTraineePages > 1): ?>
      <div class="pagination">
        <?php if ($traineePage > 1): ?>
          <a href="?trainee_page=1&trainee_search=<?php echo urlencode($traineeSearch); ?>" class="page-link first">First</a>
          <a href="?trainee_page=<?php echo $traineePage - 1; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>" class="page-link prev">Previous</a>
        <?php endif; ?>
        <?php for ($i = max(1, $traineePage - 2); $i <= min($totalActiveTraineePages, $traineePage + 2); $i++): ?>
          <a href="?trainee_page=<?php echo $i; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>" class="page-link <?php echo $i == $traineePage ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($traineePage < $totalActiveTraineePages): ?>
          <a href="?trainee_page=<?php echo $traineePage + 1; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>" class="page-link next">Next</a>
          <a href="?trainee_page=<?php echo $totalActiveTraineePages; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>" class="page-link last">Last</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="tab-inner" id="archivedTrainees" data-tab-content>
    <h3>Archived Trainees (<?php echo $archivedTraineesCount; ?>)</h3>
    <div class="table-container">
      <table class="table table-striped table-hover trainee-table"><thead><tr><th>User ID</th><th>User Name</th><th>Email</th><th>Contact Number</th><th>Enrolled Courses</th><th>Batch</th><th>Date Created</th><th>Actions</th></tr></thead><tbody>
        <?php if (empty($archivedTrainees)): ?>
          <tr><td colspan="8" class="no-data">No archived trainees found</td></tr>
        <?php else: ?>
          <?php foreach ($archivedTrainees as $trainee): ?>
            <tr data-status="archived">
              <td><?php echo htmlspecialchars($trainee['user_id']); ?></td>
              <td><?php echo htmlspecialchars($trainee['last_name'] . ', ' . $trainee['first_name'] . ($trainee['middle_name'] ? ' ' . $trainee['middle_name'] . '.' : '')); ?></td>
              <td><?php echo htmlspecialchars($trainee['email']); ?></td>
              <td><?php echo htmlspecialchars($trainee['contact_number']); ?></td>
              <td>
                <?php
                $enrolledCourses = array_filter($approvedEnrollments, function ($e) use ($trainee) { return $e['trainee_id'] == $trainee['user_id']; });
                if (!empty($enrolledCourses)) { echo implode(', ', array_map(function ($e) { return $e['course_code']; }, $enrolledCourses)); } else { echo 'No courses enrolled'; }
                ?>
              </td>
              <td>
                <?php
                $traineeBatches = array_filter($batchAssignments, function ($ba) use ($trainee) { return $ba['trainee_id'] == $trainee['user_id']; });
                if (!empty($traineeBatches)) { echo implode(', ', array_map(function ($ba) { return $ba['batch_name']; }, $traineeBatches)); } else { echo 'No batch assigned'; }
                ?>
              </td>
              <td><?php echo date('Y-m-d', strtotime($trainee['date_created'])); ?></td>
              <td class="table-actions">
                <form method="POST" onsubmit="return false;" class="unarchive-form"><input type="hidden" name="user_id" value="<?php echo $trainee['user_id']; ?>"><input type="hidden" name="current_tab" value="trainees"><button type="submit" name="unarchive_user" class="action-btn unarchive btn btn-sm btn-success" title="Restore"><i class="fas fa-box-open"></i><span class="btn-text">Restore</span></button></form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody></table>
    </div>
    <?php if ($totalArchivedTraineePages > 1): ?>
      <div class="pagination">
        <?php if ($archivedTraineePage > 1): ?>
          <a href="?archived_trainee_page=1&trainee_search=<?php echo urlencode($traineeSearch); ?>" class="page-link first">First</a>
          <a href="?archived_trainee_page=<?php echo $archivedTraineePage - 1; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>" class="page-link prev">Previous</a>
        <?php endif; ?>
        <?php for ($i = max(1, $archivedTraineePage - 2); $i <= min($totalArchivedTraineePages, $archivedTraineePage + 2); $i++): ?>
          <a href="?archived_trainee_page=<?php echo $i; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>" class="page-link <?php echo $i == $archivedTraineePage ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($archivedTraineePage < $totalArchivedTraineePages): ?>
          <a href="?archived_trainee_page=<?php echo $archivedTraineePage + 1; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>" class="page-link next">Next</a>
          <a href="?archived_trainee_page=<?php echo $totalArchivedTraineePages; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>" class="page-link last">Last</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="tab-inner" id="droppedTrainees" data-tab-content>
    <h3>Dropped Trainees (<?php echo $droppedTraineesCount; ?>)</h3>
    <div class="table-container">
      <table class="table table-striped table-hover trainee-table"><thead><tr><th>User ID</th><th>User Name</th><th>Email</th><th>Contact Number</th><th>Enrolled Courses</th><th>Batch</th><th>Date Created</th><th>Actions</th></tr></thead><tbody>
        <?php if (empty($droppedTrainees)): ?>
          <tr><td colspan="7" class="no-data">No dropped trainees found</td></tr>
        <?php else: ?>
          <?php foreach ($droppedTrainees as $trainee): ?>
            <tr data-status="dropped">
              <td><?php echo htmlspecialchars($trainee['user_id']); ?></td>
              <td><?php echo htmlspecialchars($trainee['last_name'] . ', ' . $trainee['first_name'] . ($trainee['middle_name'] ? ' ' . $trainee['middle_name'] . '.' : '')); ?></td>
              <td><?php echo htmlspecialchars($trainee['email']); ?></td>
              <td><?php echo htmlspecialchars($trainee['contact_number']); ?></td>
              <td>
                <?php
                $enrolledCourses = array_filter($approvedEnrollments, function ($e) use ($trainee) { return $e['trainee_id'] == $trainee['user_id']; });
                if (!empty($enrolledCourses)) { echo implode(', ', array_map(function ($e) { return $e['course_code']; }, $enrolledCourses)); } else { echo 'No courses enrolled'; }
                ?>
              </td>
              <td>
                <?php
                $traineeBatches = array_filter($batchAssignments, function ($ba) use ($trainee) { return $ba['trainee_id'] == $trainee['user_id']; });
                if (!empty($traineeBatches)) { echo implode(', ', array_map(function ($ba) { return $ba['batch_name']; }, $traineeBatches)); } else { echo 'No batch assigned'; }
                ?>
              </td>
              <td><?php echo date('Y-m-d', strtotime($trainee['date_created'])); ?></td>
              <td class="table-actions">
                <form method="POST" class="undrop-form">
                  <input type="hidden" name="user_id" value="<?php echo $trainee['user_id']; ?>">
                  <input type="hidden" name="user_role" value="trainee">
                  <input type="hidden" name="enrollment_status" value="active">
                  <input type="hidden" name="current_tab" value="trainees">
                  <button type="submit" formaction="../php/update_user.php" class="action-btn btn btn-sm btn-success" title="Undrop">
                    <i class="fas fa-user-check"></i><span class="btn-text">Undrop</span>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody></table>
    </div>
  </div>

  <div class="tab-inner" id="graduatedTrainees" data-tab-content>
    <h3>Graduated Trainees (<?php echo $graduatedTraineesCount; ?>)</h3>
    <div class="table-container">
      <table class="table table-striped table-hover trainee-table"><thead><tr><th>User ID</th><th>User Name</th><th>Email</th><th>Contact Number</th><th>Enrolled Courses</th><th>Batch</th><th>Date Created</th><th>Actions</th></tr></thead><tbody>
        <?php if (empty($graduatedTrainees)): ?>
          <tr><td colspan="7" class="no-data">No graduated trainees found</td></tr>
        <?php else: ?>
          <?php foreach ($graduatedTrainees as $trainee): ?>
            <tr data-status="graduated">
              <td><?php echo htmlspecialchars($trainee['user_id']); ?></td>
              <td><?php echo htmlspecialchars($trainee['last_name'] . ', ' . $trainee['first_name'] . ($trainee['middle_name'] ? ' ' . $trainee['middle_name'] . '.' : '')); ?></td>
              <td><?php echo htmlspecialchars($trainee['email']); ?></td>
              <td><?php echo htmlspecialchars($trainee['contact_number']); ?></td>
              <td>
                <?php
                $enrolledCourses = array_filter($approvedEnrollments, function ($e) use ($trainee) { return $e['trainee_id'] == $trainee['user_id']; });
                if (!empty($enrolledCourses)) { echo implode(', ', array_map(function ($e) { return $e['course_code']; }, $enrolledCourses)); } else { echo 'No courses enrolled'; }
                ?>
              </td>
              <td>
                <?php
                $traineeBatches = array_filter($batchAssignments, function ($ba) use ($trainee) { return $ba['trainee_id'] == $trainee['user_id']; });
                if (!empty($traineeBatches)) { echo implode(', ', array_map(function ($ba) { return $ba['batch_name']; }, $traineeBatches)); } else { echo 'No batch assigned'; }
                ?>
              </td>
              <td><?php echo date('Y-m-d', strtotime($trainee['date_created'])); ?></td>
              <td class="table-actions">
                <button class="action-btn btn btn-sm btn-primary reenroll-trainee-btn" title="Re-enroll" data-user-id="<?php echo $trainee['user_id']; ?>">
                  <i class="fas fa-user-plus"></i><span class="btn-text">Re-enroll</span>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody></table>
    </div>
  </div>
</section>
