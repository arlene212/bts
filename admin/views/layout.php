<?php ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial=1.0">
  <title>Benguet Technical School-eLMS</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/v4-shims.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/admin.css">
  <link rel="stylesheet" href="../css/utils.css">
  <link rel="icon" type="image/png" href="../images/school.png">
  <style>
    .alert-modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); animation: fadeIn 0.3s; }
    .alert-modal-content { background-color: #fff; margin: 15% auto; padding: 0; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3); animation: slideDown 0.3s; }
    .alert-modal-header { padding: 20px; border-bottom: 1px solid #e5e5e5; display: flex; align-items: center; gap: 15px; }
    .alert-modal-icon { font-size: 24px; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .alert-modal-icon.success { background-color: #d4edda; color: #28a745; }
    .alert-modal-icon.error { background-color: #f8d7da; color: #dc3545; }
    .alert-modal-icon.warning { background-color: #fff3cd; color: #ffc107; }
    .alert-modal-icon.info { background-color: #d1ecf1; color: #17a2b8; }
    .alert-modal-title { margin: 0; font-size: 18px; font-weight: 600; }
    .alert-modal-body { padding: 20px; }
    .alert-modal-footer { padding: 15px 20px; border-top: 1px solid #e5e5e5; display: flex; justify-content: flex-end; gap: 10px; }
    .alert-modal-btn { padding: 8px 20px; border: none; border-radius: 4px; font-size: 14px; cursor: pointer; transition: all 0.3s; }
    .alert-modal-btn-primary { background-color: #007bff; color: white; }
    .alert-modal-btn-primary:hover { background-color: #0056b3; }
    .alert-modal-btn-secondary { background-color: #6c757d; color: white; }
    .alert-modal-btn-secondary:hover { background-color: #545b62; }
    .alert-modal-btn-danger { background-color: #dc3545; color: white; }
    .alert-modal-btn-danger:hover { background-color: #c82333; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
  </style>
</head>
<body>
  <div class="container">
    <aside class="sidebar">
      <div class="profile">
        <div class="user-card">
          <div class="user-card-header">
            <img src="<?php echo (!empty($adminProfile['profile_picture']) && $adminProfile['profile_picture'] !== 'default.png') ? '../uploads/profiles/' . $adminProfile['profile_picture'] . '?t=' . time() : '../images/school.png'; ?>" alt="User Avatar" class="user-avatar" id="userCardAvatar">
            <button class="edit-profile-btn" id="editProfileBtn" title="Edit Profile"><i class="fas fa-edit"></i></button>
          </div>
          <div class="user-info">
            <h2 class="user-name" id="userCardName"><?php echo htmlspecialchars($adminProfile['first_name'] . ' ' . $adminProfile['last_name']); ?></h2>
            <p class="user-email"><i class="fas fa-envelope"></i> <span id="userCardEmail"><?php echo htmlspecialchars($adminProfile['email']); ?></span></p>
            <p class="user-contact"><i class="fas fa-phone"></i> <span id="userCardContact"><?php echo htmlspecialchars($adminProfile['contact_number'] ?: 'Not set'); ?></span></p>
          </div>
        </div>
      </div>
      <nav class="nav">
        <a href="#" class="tab-link active" data-tab="home">Home</a>
        <a href="#" class="tab-link" data-tab="trainers">Trainers</a>
        <a href="#" class="tab-link" data-tab="trainees">Trainees</a>
        <a href="#" class="tab-link" data-tab="guests">Guests</a>
        <a href="#" class="tab-link" data-tab="courses">Courses</a>
        <a href="#" class="tab-link" data-tab="enrollments">Enrollments</a>
      </nav>
    </aside>
    <main class="main">
      <header class="topbar">
        <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
        <div class="user-info">
          <div class="notif-wrapper">
            <i class="fa-solid fa-bell notif-icon" id="notifIcon"></i>
            <div class="notif-dropdown hidden" id="notifDropdown"><p>No new notifications</p></div>
          </div>
          <a href="../php/logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
      </header>
      <div class="sidebar-overlay" id="sidebarOverlay"></div>
      <div class="content">
        <section class="main-content tab-content active" id="home">
          <div class="home-content-wrapper">
            <div class="home-main-content">
              <div class="dashboard tab-inner active" id="dashboard">
                <h2 class="dashboard-header">User Management</h2>
                <?php if (isset($_SESSION['success_message'])): ?>
                  <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error_message'])): ?>
                  <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
                <?php endif; ?>
                <div class="dashboard-cards">
                  <div class="dashboard-card" data-target="trainers"><div class="number-circle"><?php echo $totalTrainers; ?></div><div class="label-text">Total Trainers</div></div>
                  <div class="dashboard-card" data-target="trainees"><div class="number-circle"><?php echo $totalTrainees; ?></div><div class="label-text">Total Trainees</div></div>
                  <div class="dashboard-card" data-target="guests"><div class="number-circle"><?php echo $totalGuests; ?></div><div class="label-text">Guests</div></div>
                  <div class="dashboard-card" data-target="courses"><div class="number-circle"><?php echo $totalCourses; ?></div><div class="label-text">Courses Offered</div></div>
                  <div class="dashboard-card" data-target="enrollments"><div class="number-circle"><?php echo $pendingEnrollments; ?></div><div class="label-text">Enrollment Requests</div></div>
                </div>
              </div>
            </div>
            <aside class="announcements">
              <div class="announcements-header"><h2>Announcements</h2><button id="addAnnouncementBtn" class="add-announcement-btn">+</button></div>
              <div class="announcements-list">
                <?php if (!empty($announcements)): ?>
                  <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-item">
                      <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                      <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                      <span class="announcement-date">Posted by <?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?> on <?php echo date('Y-m-d H:i', strtotime($announcement['date_posted'])); ?></span>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="no-announcements"><p>No announcements yet.</p></div>
                <?php endif; ?>
              </div>
            </aside>
          </div>
        </section>
        <?php include __DIR__ . '/sections/trainees.php'; ?>
        <?php include __DIR__ . '/sections/guests.php'; ?>
        <?php include __DIR__ . '/sections/courses.php'; ?>
        <?php include __DIR__ . '/sections/enrollments.php'; ?>

        <section class="main-content tab-content" id="trainers">
          <div class="tab-header"><h2>Trainer Management</h2><div class="tab-actions"><button class="create-btn" id="createTrainerBtn">+ Create Trainer</button></div></div>
          <div class="search-container">
            <div class="search-input-group">
              <i class="fas fa-search search-icon"></i>
              <input type="text" id="trainerSearchInput" class="search-input" placeholder="Search trainers by name, ID, or email..." value="<?php echo htmlspecialchars($trainerSearch); ?>">
              <div class="search-loading hidden" id="trainerSearchLoading"><i class="fas fa-spinner fa-spin"></i></div>
              <?php if (!empty($trainerSearch)): ?><a href="?trainer_page=1" class="clear-search" id="trainerClearSearch">Clear</a><?php endif; ?>
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
              <table class="trainer-table"><thead><tr><th>User ID</th><th>User Name</th><th>Email</th><th>Contact Number</th><th>Assigned Courses</th><th>Date Created</th><th>Actions</th></tr></thead><tbody>
                <?php if (empty($activeTrainers)): ?>
                  <tr><td colspan="7" class="no-data">No trainers found</td></tr>
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
                          if (!empty($assignedCourses)) { echo implode(', ', array_map(function ($ca) { return $ca['course_code']; }, $assignedCourses)); } else { echo 'No courses assigned'; }
                        ?>
                      </td>
                      <td><?php echo date('Y-m-d', strtotime($trainer['date_created'])); ?></td>
                      <td class="table-actions">
                        <button class="action-btn edit edit-trainer-btn" title="Edit" data-user-id="<?php echo $trainer['user_id']; ?>" data-user-data='<?php echo htmlspecialchars(json_encode($trainer), ENT_QUOTES, 'UTF-8'); ?>'><i class="fas fa-edit"></i><span class="btn-text">Edit</span></button>
                        <button class="action-btn reset reset-password-btn" title="Reset Password" data-user-id="<?php echo $trainer['user_id']; ?>" data-user-name="<?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?>"><i class="fas fa-key"></i><span class="btn-text">Reset</span></button>
                        <form method="POST" onsubmit="return false;" class="archive-form"><input type="hidden" name="user_id" value="<?php echo $trainer['user_id']; ?>"><input type="hidden" name="current_tab" value="trainers"><button type="submit" class="action-btn archive" title="Archive"><i class="fas fa-archive"></i><span class="btn-text">Archive</span></button></form>
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
              <table class="trainer-table"><thead><tr><th>User ID</th><th>User Name</th><th>Email</th><th>Contact Number</th><th>Assigned Courses</th><th>Date Created</th><th>Actions</th></tr></thead><tbody>
                <?php if (empty($archivedTrainers)): ?>
                  <tr><td colspan="7" class="no-data">No archived trainers found</td></tr>
                <?php else: foreach ($archivedTrainers as $trainer): ?>
                  <tr data-status="archived">
                    <td><?php echo htmlspecialchars($trainer['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($trainer['last_name'] . ', ' . $trainer['first_name'] . ($trainer['middle_name'] ? ' ' . $trainer['middle_name'] . '.' : '')); ?></td>
                    <td><?php echo htmlspecialchars($trainer['email']); ?></td>
                    <td><?php echo htmlspecialchars($trainer['contact_number']); ?></td>
                    <td>
                      <?php
                        $assignedCourses = array_filter($courseAssignments, function ($ca) use ($trainer) { return $ca['trainer_id'] == $trainer['user_id']; });
                        if (!empty($assignedCourses)) { echo implode(', ', array_map(function ($ca) { return $ca['course_code']; }, $assignedCourses)); } else { echo 'No courses assigned'; }
                      ?>
                    </td>
                    <td><?php echo date('Y-m-d', strtotime($trainer['date_created'])); ?></td>
                    <td class="table-actions">
                      <form method="POST" onsubmit="return true;" class="unarchive-form"><input type="hidden" name="user_id" value="<?php echo $trainer['user_id']; ?>"><input type="hidden" name="current_tab" value="trainers"><button type="submit" name="unarchive_user" class="action-btn unarchive" title="Restore"><i class="fas fa-box-open"></i><span class="btn-text">Restore</span></button></form>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody></table>
            </div>
          </div>
        </section>

      </div>
    </main>
  </div>

  <?php include __DIR__ . '/partials/modal.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    window.initialTrainerSearch = '<?php echo addslashes($trainerSearch ?? ""); ?>';
    window.initialTraineeSearch = '<?php echo addslashes($traineeSearch ?? ""); ?>';
    window.initialGuestSearch = '<?php echo addslashes($guestSearch ?? ""); ?>';
    window.initialEnrollmentSearch = '<?php echo addslashes($enrollmentSearch ?? ""); ?>';
  </script>
  <script src="../js/admin/core.js"></script>
  <script src="../js/admin/modal_controls.js"></script>
  <script src="../js/admin/navigation.js"></script>
  <script src="../js/admin/switches.js"></script>
  <script src="../js/admin/main.js"></script>
  <script src="../js/admin/search.js"></script>
  <script src="../js/admin/modals.js"></script>
  <script src="../js/admin/ajax.js"></script>
  <script src="../js/admin/forms.js"></script>
  <script src="../js/admin/competencies.js"></script>
  <script src="../js/admin/course_edit.js"></script>
  <script src="../js/admin/course_details.js"></script>
  <script src="../js/admin/enrollments.js"></script>
  <script src="../js/admin/user.js"></script>
  <script src="../js/admin/batches.js"></script>
</body>
</html>