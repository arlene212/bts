<?php
require_once __DIR__ . '/../../../php/SessionManager.php';
require_once __DIR__ . '/../../../php/DatabaseConnection.php';
SessionManager::startSession();
SessionManager::requireRole('trainee');
$user = SessionManager::getCurrentUser();
$traineeId = $user['user_id'] ?? null;
$active_courses = [];
$completed_courses = [];
try {
  $db = (new DatabaseConnection())->getConnection();
  if ($traineeId) {
    $stmt = $db->prepare(
      "SELECT e.id, e.course_code, c.course_name,
         (
           SELECT COALESCE(SUM(comp.nominal_hours), 0)
           FROM competencies comp
           WHERE comp.status = 'active'
             AND comp.course_id = (SELECT id FROM courses WHERE course_code = e.course_code)
         ) AS hours,
         c.description, c.image
       FROM enrollments e
       JOIN courses c ON e.course_code = c.course_code
       WHERE e.trainee_id = ? AND e.status = 'approved'"
    );
    $stmt->execute([$traineeId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
      $creditStmt = $db->prepare(
        "SELECT COALESCE(SUM(hc.hours),0) FROM hour_credits hc WHERE hc.trainee_id = ? AND hc.course_code = ?"
      );
      $creditStmt->execute([$traineeId, $row['course_code']]);
      $row['credited_hours'] = (int)$creditStmt->fetchColumn();
      $totalStmt = $db->prepare(
        "SELECT COUNT(ta.id)
         FROM topic_activities ta
         JOIN course_topics ct ON ta.topic_id = ct.id
         WHERE ct.course_code = ?"
      );
      $totalStmt->execute([$row['course_code']]);
      $row['total_activities'] = (int)$totalStmt->fetchColumn();
      $completedStmt = $db->prepare(
        "SELECT COUNT(*)
         FROM activity_submissions s
         WHERE s.guest_id = ? AND s.activity_id IN (
           SELECT ta.id FROM topic_activities ta JOIN course_topics ct ON ta.topic_id = ct.id WHERE ct.course_code = ?
         )"
      );
      $completedStmt->execute([$traineeId, $row['course_code']]);
      $row['completed_activities'] = (int)$completedStmt->fetchColumn();
      $row['progress'] = $row['total_activities'] > 0 ? (int)round(($row['completed_activities'] / $row['total_activities']) * 100) : 0;
      if ($row['progress'] >= 100) { $completed_courses[] = $row; } else { $active_courses[] = $row; }
    }
  }
} catch (Throwable $e) {}
?>
<div class="mycourses tab-inner active" id="mycourses">
  <div class="course-box">
    <div class="news-switch-wrapper">
      <div class="switch-oval" id="courseSwitchContainer">
        <div id="myCourseSwitchInner" class="switch-inner"></div>
        <button type="button" class="switch-btn active" data-tab="enrolled-active-courses">Active</button>
        <button type="button" class="switch-btn" data-tab="enrolled-completed-courses">Completed</button>
      </div>
    </div>
    <div id="enrolled-active-courses" class="tab-inner active">
      <h2 class="section-header">Active Courses</h2>
      <div class="batch-list">
        <?php if (!empty($active_courses)): ?>
          <?php foreach ($active_courses as $course): ?>
            <div class="batch-card view-course-content-btn"
                 data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>"
                 data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>"
                 data-course-hours="<?php echo htmlspecialchars($course['hours']); ?>"
                 data-course-description="<?php echo htmlspecialchars($course['description']); ?>"
                 data-credited-hours="<?php echo htmlspecialchars($course['credited_hours'] ?? 0); ?>"
                 data-course-id="<?php echo htmlspecialchars($course['id']); ?>">
              <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/school.png'; ?>" alt="<?php echo htmlspecialchars($course['course_name']); ?>" class="course-img">
              <div class="batch-info">
                <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                <p class="competency-badge">Enrolled</p>
                <p>Code: <?php echo htmlspecialchars($course['course_code']); ?></p>
                <p>Total Hours: <?php echo htmlspecialchars($course['hours']); ?> hrs</p>
                <p>Credited: <?php echo htmlspecialchars($course['credited_hours'] ?? 0); ?> hrs</p>
                <p class="progress-indicator">Progress: <?php echo htmlspecialchars($course['progress']); ?>% (<?php echo $course['completed_activities']; ?>/<?php echo $course['total_activities']; ?>)</p>
                <?php if (!empty($course['description'])): ?>
                  <p class="course-description"><?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>...</p>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-courses">
            <p>You have no active courses.</p>
            <p>Browse <a href="#" class="tab-switch" data-tab="offered-courses">Offered Courses</a> to get started.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <div id="enrolled-completed-courses" class="tab-inner">
      <h2 class="section-header">Completed Courses</h2>
      <div class="batch-list">
        <?php if (!empty($completed_courses)): ?>
          <?php foreach ($completed_courses as $course): ?>
            <div class="batch-card view-course-content-btn"
                 data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>"
                 data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>"
                 data-course-hours="<?php echo htmlspecialchars($course['hours']); ?>"
                 data-course-description="<?php echo htmlspecialchars($course['description']); ?>"
                 data-credited-hours="<?php echo htmlspecialchars($course['credited_hours'] ?? 0); ?>"
                 data-course-id="<?php echo htmlspecialchars($course['id']); ?>">
              <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/school.png'; ?>" alt="<?php echo htmlspecialchars($course['course_name']); ?>" class="course-img">
              <div class="batch-info">
                <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                <p class="competency-badge">Completed</p>
                <p>Code: <?php echo htmlspecialchars($course['course_code']); ?></p>
                <p>Total Hours: <?php echo htmlspecialchars($course['hours']); ?> hrs</p>
                <p>Credited: <?php echo htmlspecialchars($course['credited_hours'] ?? 0); ?> hrs</p>
                <p class="progress-indicator completed">Status: <strong>Completed</strong> (<?php echo htmlspecialchars($course['progress']); ?>%)</p>
                <?php if (!empty($course['description'])): ?>
                  <p class="course-description"><?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>...</p>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-courses">
            <p>No completed courses yet.</p>
            <p>Keep learning to complete your enrolled courses.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<div id="courseDetail" class="course-detail hidden">
  <div class="detail-header">
    <button class="back-btn btn-ripple"><i class="fas fa-arrow-left"></i> Back to My Courses</button>
    <h2 id="course-detail-title">Course Title</h2>
  </div>
  <div class="course-card">
    <div class="course-meta">
      <span id="course-detail-code" class="chip"></span>
      <span class="chip chip-blue" id="course-detail-hours"></span>
      <span class="chip" id="course-detail-credited-hours"></span>
      <span class="chip" id="course-detail-basic-hours"></span>
      <span class="chip" id="course-detail-common-hours"></span>
      <span class="chip" id="course-detail-core-hours"></span>
    </div>
    <p id="course-detail-description"></p>
  </div>
  <div class="course-switch">
    <div class="switch-oval" id="courseDetailSwitchContainer">
      <div id="courseDetailSwitchInner" class="switch-inner"></div>
      <button type="button" class="switch-btn active" data-tab="modules-view">Modules</button>
      <button type="button" class="switch-btn" data-tab="activities-view">Activities</button>
    </div>
  </div>

  <div id="modules-pane" class="tab-inner active">
    <div class="list-header">
      <h3><i class="fas fa-book"></i> Modules</h3>
      <input type="text" id="modules-search" placeholder="Search modules...">
      <span class="chip" id="modules-count"></span>
    </div>
    <div id="modules-list"></div>
  </div>

  <div id="activities-pane" class="tab-inner">
    <div class="list-header">
      <h3><i class="fas fa-tasks"></i> Activities</h3>
      <input type="text" id="activities-search" placeholder="Search activities...">
      <span class="chip" id="activities-count"></span>
    </div>
    <div id="activities-list"></div>
  </div>
  <div id="competencies-card" class="competencies-card"></div>
</div>
  
