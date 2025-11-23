<?php
if (!isset($pdo) || !($pdo instanceof PDO)) {
  require_once __DIR__ . '/../../php/DatabaseConnection.php';
  $database = new DatabaseConnection();
  $pdo = $database->getConnection();
}

$logs = [];
try {
  $queries = [
    "SELECT a.user_id, u.last_name, u.first_name, u.middle_name, a.action AS action, a.timestamp AS timestamp FROM activities a LEFT JOIN users u ON a.user_id = u.user_id",
    "SELECT ta.created_by AS user_id, u.last_name, u.first_name, u.middle_name, 'Activity Upload - Trainee' AS action, ta.created_at AS timestamp FROM topic_activities ta LEFT JOIN users u ON ta.created_by = u.user_id",
    "SELECT tm.uploaded_by AS user_id, u.last_name, u.first_name, u.middle_name, 'Module Upload' AS action, tm.uploaded_at AS timestamp FROM topic_materials tm LEFT JOIN users u ON tm.uploaded_by = u.user_id",
    "SELECT s.trainee_id AS user_id, u.last_name, u.first_name, u.middle_name, 'Trainee Submission' AS action, s.submitted_at AS timestamp FROM submissions s LEFT JOIN users u ON s.trainee_id = u.user_id",
    "SELECT s.graded_by AS user_id, u.last_name, u.first_name, u.middle_name, 'Grading' AS action, s.graded_at AS timestamp FROM submissions s LEFT JOIN users u ON s.graded_by = u.user_id WHERE s.graded_at IS NOT NULL",
    "SELECT asub.guest_id AS user_id, u.last_name, u.first_name, u.middle_name, 'Guest Submission' AS action, asub.submitted_at AS timestamp FROM activity_submissions asub LEFT JOIN users u ON asub.guest_id = u.user_id",
    "SELECT asub.graded_by AS user_id, u.last_name, u.first_name, u.middle_name, 'Grading' AS action, asub.graded_at AS timestamp FROM activity_submissions asub LEFT JOIN users u ON asub.graded_by = u.user_id WHERE asub.graded_at IS NOT NULL",
    "SELECT ca.assigned_by AS user_id, u.last_name, u.first_name, u.middle_name, 'Assign Trainer' AS action, ca.date_assigned AS timestamp FROM course_assignments ca LEFT JOIN users u ON ca.assigned_by = u.user_id",
    "SELECT ba.assigned_by AS user_id, u.last_name, u.first_name, u.middle_name, 'Assign Batch' AS action, ba.date_assigned AS timestamp FROM batch_assignments ba LEFT JOIN users u ON ba.assigned_by = u.user_id",
    "SELECT cb.created_by AS user_id, u.last_name, u.first_name, u.middle_name, 'Create Batch' AS action, cb.created_at AS timestamp FROM course_batches cb LEFT JOIN users u ON cb.created_by = u.user_id",
    "SELECT e.trainee_id AS user_id, u.last_name, u.first_name, u.middle_name, 'Request Enrollment' AS action, e.date_requested AS timestamp FROM enrollments e LEFT JOIN users u ON e.trainee_id = u.user_id",
    "SELECT e.processed_by AS user_id, u.last_name, u.first_name, u.middle_name, 'Approved Enrollment' AS action, e.processed_date AS timestamp FROM enrollments e LEFT JOIN users u ON e.processed_by = u.user_id WHERE e.status = 'approved' AND e.processed_date IS NOT NULL AND e.processed_by IS NOT NULL",
    "SELECT ct.added_by AS user_id, u.last_name, u.first_name, u.middle_name, 'Create Learning Objectives' AS action, ct.created_at AS timestamp FROM course_topics ct LEFT JOIN users u ON ct.added_by = u.user_id",
    "SELECT '1000000001' AS user_id, u.last_name, u.first_name, u.middle_name, 'Create Course' AS action, c.date_created AS timestamp FROM courses c LEFT JOIN users u ON u.user_id = '1000000001'",
    "SELECT a.posted_by AS user_id, u.last_name, u.first_name, u.middle_name, 'Create Announcement' AS action, a.date_posted AS timestamp FROM announcements a LEFT JOIN users u ON a.posted_by = u.user_id",
    "SELECT u.user_id AS user_id, u.last_name, u.first_name, u.middle_name, 'Create User' AS action, u.date_created AS timestamp FROM users u",
    "SELECT u.user_id AS user_id, u.last_name, u.first_name, u.middle_name, 'Last Login' AS action, u.last_login AS timestamp FROM users u WHERE u.last_login IS NOT NULL"
  ];
  foreach ($queries as $sql) {
    try {
      $st = $pdo->prepare($sql);
      $st->execute();
      $rows = $st->fetchAll();
      if (is_array($rows) && !empty($rows)) { $logs = array_merge($logs, $rows); }
    } catch (Exception $e) {
      error_log('User logs query failed: ' . $e->getMessage());
      continue;
    }
  }
  usort($logs, function($a, $b) {
    $ta = isset($a['timestamp']) ? strtotime($a['timestamp']) : 0;
    $tb = isset($b['timestamp']) ? strtotime($b['timestamp']) : 0;
    if ($ta == $tb) return 0;
    return ($ta < $tb) ? 1 : -1;
  });
  $logsPerPage = 30;
  $totalLogs = count($logs);
  $logsPage = max(1, intval($_GET['user_logs_page'] ?? 1));
  $totalLogPages = min(30, max(1, (int)ceil($totalLogs / $logsPerPage)));
  if ($logsPage > $totalLogPages) { $logsPage = $totalLogPages; }
  $logsOffset = ($logsPage - 1) * $logsPerPage;
  $pagedLogs = array_slice($logs, $logsOffset, $logsPerPage);
} catch (Exception $e) {
  $logs = [];
}
?>
<section class="main-content tab-content <?php echo ($currentTab === 'user_logs' ? 'active' : ''); ?>" id="user_logs">
  <div class="tab-header">
    <h2>User Activity Logs</h2>
  </div>
  <div class="table-container">
    <table class="table table-striped table-hover logs-table">
      <thead>
        <tr>
          <th>User ID</th>
          <th>Name</th>
          <th>Activity Type</th>
          <th>Date Made</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pagedLogs)): ?>
          <tr>
            <td colspan="4" class="no-data">No activity logs found</td>
          </tr>
        <?php else: ?>
          <?php foreach ($pagedLogs as $row): ?>
            <tr>
              <td><?php echo htmlspecialchars($row['user_id']); ?></td>
              <td>
                <?php
                  $ln = isset($row['last_name']) ? trim((string)$row['last_name']) : '';
                  $fn = isset($row['first_name']) ? trim((string)$row['first_name']) : '';
                  $mn = isset($row['middle_name']) ? trim((string)$row['middle_name']) : '';
                  $name = '';
                  if ($ln !== '' || $fn !== '') {
                    $name = $ln !== '' ? $ln : '';
                    if ($fn !== '') { $name = ($name !== '' ? $name . ', ' : '') . $fn; }
                    if ($mn !== '') { $name .= ' ' . $mn . '.'; }
                  } else {
                    $name = $row['user_id'];
                  }
                  echo htmlspecialchars($name);
                ?>
              </td>
              <td><?php echo htmlspecialchars($row['action']); ?></td>
              <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($row['timestamp']))); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalLogs > 0 && $totalLogPages >= 1): ?>
    <div class="pagination">
      <?php if ($logsPage > 1): ?>
        <a href="?current_tab=user_logs&user_logs_page=1" class="page-link first">First</a>
        <a href="?current_tab=user_logs&user_logs_page=<?php echo $logsPage - 1; ?>" class="page-link prev">Previous</a>
      <?php endif; ?>
      <?php for ($i = max(1, $logsPage - 2); $i <= min($totalLogPages, $logsPage + 2); $i++): ?>
        <a href="?current_tab=user_logs&user_logs_page=<?php echo $i; ?>" class="page-link <?php echo $i == $logsPage ? 'active' : ''; ?>"><?php echo $i; ?></a>
      <?php endfor; ?>
      <?php if ($logsPage < $totalLogPages): ?>
        <a href="?current_tab=user_logs&user_logs_page=<?php echo $logsPage + 1; ?>" class="page-link next">Next</a>
        <a href="?current_tab=user_logs&user_logs_page=<?php echo $totalLogPages; ?>" class="page-link last">Last</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>
<?php if (empty($logs)): ?>
<div class="text-center" style="padding:10px; color:#666; font-size:0.9rem;">
  <?php
    try {
      $tables = [
        'activities' => "SELECT COUNT(*) AS c FROM activities",
        'activity_submissions' => "SELECT COUNT(*) AS c FROM activity_submissions",
        'announcements' => "SELECT COUNT(*) AS c FROM announcements",
        'batch_assignments' => "SELECT COUNT(*) AS c FROM batch_assignments",
        'course_assignments' => "SELECT COUNT(*) AS c FROM course_assignments",
        'course_batches' => "SELECT COUNT(*) AS c FROM course_batches",
        'enrollments' => "SELECT COUNT(*) AS c FROM enrollments",
        'submissions' => "SELECT COUNT(*) AS c FROM submissions",
        'topic_activities' => "SELECT COUNT(*) AS c FROM topic_activities",
        'topic_materials' => "SELECT COUNT(*) AS c FROM topic_materials",
        'users' => "SELECT COUNT(*) AS c FROM users"
      ];
      foreach ($tables as $name => $sql) {
        $st = $pdo->prepare($sql);
        $st->execute();
        $cnt = (int)($st->fetchColumn());
        echo htmlspecialchars($name) . ": " . $cnt . " ";
      }
    } catch (Exception $e) {}
  ?>
</div>
<?php endif; ?>