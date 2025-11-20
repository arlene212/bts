<?php
if (!isset($courseAssignments) || !is_array($courseAssignments)) { $courseAssignments = []; }
?>
<section class="main-content tab-content" id="trainers">
  <div class="tab-header"><h2>Trainer Management</h2><div class="tab-actions"><button class="create-btn" id="createTrainerBtn">+ Create Trainer</button></div></div>
  <div class="search-container">
    <div class="input-group">
      <span class="input-group-text"><i class="fas fa-search"></i></span>
      <input type="text" id="trainerSearchInput" class="form-control" placeholder="Search trainers by name, ID, or email..." value="<?php echo htmlspecialchars($trainerSearch); ?>">
      <div class="search-loading hidden" id="trainerSearchLoading"><i class="fas fa-spinner fa-spin"></i></div>
      <?php if (!empty($trainerSearch)): ?><a href="?trainer_page=1" class="btn btn-link" id="trainerClearSearch">Clear</a><?php endif; ?>
    </div>
    <div class="search-results-dropdown" id="trainerSearchResults"></div>
    <div class="search-results-info" id="trainerSearchResultsInfo">
      <?php if (!empty($trainerSearch)): ?>
        Showing <?php echo count($activeTrainers); ?> of <?php echo $totalActiveTrainers; ?> active trainers matching "<?php echo htmlspecialchars($trainerSearch); ?>"
      <?php endif; ?>
    </div>
  </div>
  <div class="switch-oval" style="margin-bottom: 20px;"><div class="switch-inner" id="trainerSwitchInner"></div><button class="switch-btn active" data-tab="activeTrainers">Active (<?php echo $totalActiveTrainers; ?>)</button><button class="switch-btn" data-tab="archivedTrainers">Archived (<?php echo $totalArchivedTrainers; ?>)</button></div>
  <div class="tab-inner active" id="activeTrainers" data-tab-content>
    <h3>Active Trainers (<?php echo $activeTrainersCount; ?>)</h3>
    <div class="table-container">
      <table class="table table-striped table-hover trainer-table"><thead><tr><th>User ID</th><th>User Name</th><th>Email</th><th>Contact Number</th><th>Assigned Courses</th><th>Assigned Batches</th><th>Date Created</th><th>Actions</th></tr></thead><tbody>
        <?php if (empty($activeTrainers)): ?>
          <tr><td colspan="8" class="no-data">No trainers found</td></tr>
        <?php else: ?>
          <?php foreach ($activeTrainers as $trainer): ?>
            <tr>
              <td><?php echo htmlspecialchars($trainer['user_id']); ?></td>
              <td><?php echo htmlspecialchars($trainer['last_name'] . ', ' . $trainer['first_name'] . ($trainer['middle_name'] ? ' ' . $trainer['middle_name'] . '.' : '')); ?></td>
              <td><?php echo htmlspecialchars($trainer['email']); ?></td>
              <td><?php echo htmlspecialchars($trainer['contact_number']); ?></td>
              <td>
                <?php
                  $assignedCourses = array_filter($courseAssignments, function ($ca) use ($trainer) { return $ca['trainer_id'] == $trainer['user_id']; });
                  if (empty($assignedCourses)) { echo 'No courses assigned'; } else {
                    $courseCodes = array_values(array_unique(array_map(function ($ca) { return (string)$ca['course_code']; }, $assignedCourses)));
                    echo implode(', ', array_map('htmlspecialchars', $courseCodes));
                  }
                ?>
              </td>
              <td>
                <?php
                  try {
                    if (!isset($pdo) || !($pdo instanceof PDO)) { 
                      require_once __DIR__ . '/../../php/DatabaseConnection.php'; 
                      $database = new DatabaseConnection(); 
                      $pdo = $database->getConnection(); 
                    }
                    
                    // Get batches directly from course_batches table using trainer_id
                    $stmt = $pdo->prepare("SELECT DISTINCT TRIM(batch_name) AS batch_name FROM course_batches WHERE trainer_id = ? AND batch_name IS NOT NULL AND batch_name != '' ORDER BY created_at DESC");
                    $stmt->execute([$trainer['user_id']]);
                    $batches = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (!empty($batches)) {
                      echo htmlspecialchars(implode(', ', $batches));
                    } else {
                      echo 'None';
                    }
                  } catch (Exception $e) { 
                    echo 'None'; 
                  }
                ?>
              </td>
              
              <td><?php echo date('Y-m-d', strtotime($trainer['date_created'])); ?></td>
              <td class="table-actions">
                <?php
                  $trainerAssignedBatchesAttr = '';
                  try {
                    if (!isset($pdo) || !($pdo instanceof PDO)) { 
                      require_once __DIR__ . '/../../php/DatabaseConnection.php'; 
                      $database = new DatabaseConnection(); 
                      $pdo = $database->getConnection(); 
                    }
                    
                    // Get batches directly from course_batches table using trainer_id
                    $stmt = $pdo->prepare("SELECT DISTINCT TRIM(batch_name) AS batch_name FROM course_batches WHERE trainer_id = ? AND batch_name IS NOT NULL AND batch_name != ''");
                    $stmt->execute([$trainer['user_id']]);
                    $assignedBatches = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (!empty($assignedBatches)) { 
                      $trainerAssignedBatchesAttr = implode('|', array_unique($assignedBatches)); 
                    }
                  } catch (Exception $e) {}
                ?>
                <button class="action-btn edit edit-trainer-btn btn btn-sm btn-outline-primary" title="Edit" data-user-id="<?php echo $trainer['user_id']; ?>" data-user-data='<?php echo htmlspecialchars(json_encode($trainer), ENT_QUOTES, 'UTF-8'); ?>' data-trainer-batches="<?php echo htmlspecialchars($trainerAssignedBatchesAttr, ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-edit"></i><span class="btn-text">Edit</span></button>
                <button class="action-btn reset reset-password-btn btn btn-sm btn-warning" title="Reset Password" data-user-id="<?php echo $trainer['user_id']; ?>" data-user-name="<?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name'], ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-key"></i><span class="btn-text">Reset</span></button>
                <form method="POST" onsubmit="return false;" class="archive-form"><input type="hidden" name="user_id" value="<?php echo $trainer['user_id']; ?>"><input type="hidden" name="current_tab" value="trainers"><button type="submit" class="action-btn archive btn btn-sm btn-outline-secondary" title="Archive"><i class="fas fa-archive"></i><span class="btn-text">Archive</span></button></form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody></table>
    </div>
    <?php if ($totalActiveTrainerPages > 1): ?>
      <div class="pagination">
        <?php if ($trainerPage > 1): ?>
          <a href="?trainer_page=1&trainer_search=<?php echo urlencode($trainerSearch); ?>" class="page-link first">First</a>
          <a href="?trainer_page=<?php echo $trainerPage - 1; ?>&trainer_search=<?php echo urlencode($trainerSearch); ?>" class="page-link prev">Previous</a>
        <?php endif; ?>
        <?php for ($i = max(1, $trainerPage - 2); $i <= min($totalActiveTrainerPages, $trainerPage + 2); $i++): ?>
          <a href="?trainer_page=<?php echo $i; ?>&trainer_search=<?php echo urlencode($trainerSearch); ?>" class="page-link <?php echo $i == $trainerPage ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($trainerPage < $totalActiveTrainerPages): ?>
          <a href="?trainer_page=<?php echo $trainerPage + 1; ?>&trainer_search=<?php echo urlencode($trainerSearch); ?>" class="page-link next">Next</a>
          <a href="?trainer_page=<?php echo $totalActiveTrainerPages; ?>&trainer_search=<?php echo urlencode($trainerSearch); ?>" class="page-link last">Last</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="tab-inner" id="archivedTrainers" data-tab-content>
    <h3>Archived Trainers (<?php echo $archivedTrainersCount; ?>)</h3>
    <div class="table-container">
      <table class="table table-striped table-hover trainer-table"><thead><tr><th>User ID</th><th>User Name</th><th>Email</th><th>Contact Number</th><th>Assigned Courses</th><th>Assigned Batches</th><th>Date Created</th><th>Actions</th></tr></thead><tbody>
        <?php if (empty($archivedTrainers)): ?>
          <tr><td colspan="8" class="no-data">No archived trainers found</td></tr>
        <?php else: foreach ($archivedTrainers as $trainer): ?>
          <tr data-status="archived">
            <td><?php echo htmlspecialchars($trainer['user_id']); ?></td>
            <td><?php echo htmlspecialchars($trainer['last_name'] . ', ' . $trainer['first_name'] . ($trainer['middle_name'] ? ' ' . $trainer['middle_name'] . '.' : '')); ?></td>
            <td><?php echo htmlspecialchars($trainer['email']); ?></td>
            <td><?php echo htmlspecialchars($trainer['contact_number']); ?></td>
            <td>
              <?php
                $assignedCourses = array_filter($courseAssignments, function ($ca) use ($trainer) { return $ca['trainer_id'] == $trainer['user_id']; });
                if (empty($assignedCourses)) { echo 'No courses assigned'; } else {
                  $courseCodes = array_values(array_unique(array_map(function ($ca) { return (string)$ca['course_code']; }, $assignedCourses)));
                  try {
                    if (!isset($pdo) || !($pdo instanceof PDO)) { require_once __DIR__ . '/../../php/DatabaseConnection.php'; $database = new DatabaseConnection(); $pdo = $database->getConnection(); }
                    $byCourse = [];
                    $hasCBTrainerCol = false;
                    $chk1 = $pdo->query("SHOW COLUMNS FROM course_batches LIKE 'trainer_id'");
                    if ($chk1 && $chk1->rowCount() > 0) { $hasCBTrainerCol = true; }
                    if ($hasCBTrainerCol) {
                      $bs = $pdo->prepare("SELECT course_code, batch_name FROM course_batches WHERE trainer_id = ?");
                      $bs->execute([$trainer['user_id']]);
                      foreach ($bs->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $cc = (string)$row['course_code']; $bn = trim((string)$row['batch_name']);
                        if ($bn !== '') { $byCourse[$cc] = isset($byCourse[$cc]) ? array_unique(array_merge($byCourse[$cc], [$bn])) : [$bn]; }
                      }
                    } else {
                      $hasCACol = false;
                      $chk2 = $pdo->query("SHOW COLUMNS FROM course_assignments LIKE 'batch_name'");
                      if ($chk2 && $chk2->rowCount() > 0) { $hasCACol = true; }
                      if ($hasCACol) {
                        $bs = $pdo->prepare("SELECT course_code, batch_name FROM course_assignments WHERE trainer_id = ? AND batch_name IS NOT NULL AND batch_name != ''");
                        $bs->execute([$trainer['user_id']]);
                        foreach ($bs->fetchAll(PDO::FETCH_ASSOC) as $row) {
                          $cc = (string)$row['course_code']; $bText = (string)$row['batch_name'];
                          $bList = array_filter(array_map('trim', explode(',', $bText)));
                          if (!empty($bList)) { $byCourse[$cc] = isset($byCourse[$cc]) ? array_unique(array_merge($byCourse[$cc], $bList)) : $bList; }
                        }
                      }
                    }
                    $items = [];
                    foreach ($courseCodes as $cc) {
                      if (!empty($byCourse[$cc])) { foreach ($byCourse[$cc] as $bn) { $items[] = htmlspecialchars($cc) . ' - ' . htmlspecialchars($bn); } }
                      else { $items[] = htmlspecialchars($cc); }
                    }
                    echo implode(', ', $items);
                  } catch (Exception $__) {
                    echo implode(', ', array_map(function ($cc) { return htmlspecialchars($cc); }, $courseCodes));
                  }
                }
              ?>
            </td>
            <td>
              <?php
                try {
                  if (!isset($pdo) || !($pdo instanceof PDO)) { 
                    require_once __DIR__ . '/../../php/DatabaseConnection.php'; 
                    $database = new DatabaseConnection(); 
                    $pdo = $database->getConnection(); 
                  }
                  
                  // Get batches directly from course_batches table using trainer_id
                  $stmt = $pdo->prepare("SELECT DISTINCT TRIM(batch_name) AS batch_name FROM course_batches WHERE trainer_id = ? AND batch_name IS NOT NULL AND batch_name != '' ORDER BY created_at DESC");
                  $stmt->execute([$trainer['user_id']]);
                  $batches = $stmt->fetchAll(PDO::FETCH_COLUMN);
                  
                  if (!empty($batches)) {
                    echo htmlspecialchars(implode(', ', $batches));
                  } else {
                    echo 'None';
                  }
                } catch (Exception $e) { 
                  echo 'None'; 
                }
              ?>
            </td>
            
            <td><?php echo date('Y-m-d', strtotime($trainer['date_created'])); ?></td>
            <td class="table-actions">
              <form method="POST" onsubmit="return true;" class="unarchive-form"><input type="hidden" name="user_id" value="<?php echo $trainer['user_id']; ?>"><input type="hidden" name="current_tab" value="trainers"><button type="submit" name="unarchive_user" class="action-btn unarchive btn btn-sm btn-success" title="Restore"><i class="fas fa-box-open"></i><span class="btn-text">Restore</span></button></form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody></table>
    </div>
  </div>
</section>