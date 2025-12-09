<?php 
// Calculate enrolled courses statistics and split lists
$total_enrolled = is_array($enrolled_courses) ? count($enrolled_courses) : 0;
$completed_count = is_array($enrolled_courses) ? count(array_filter($enrolled_courses, function($course) { 
  return isset($course['status']) && $course['status'] == 'completed'; 
})) : 0;
$active_courses = is_array($enrolled_courses) ? array_filter($enrolled_courses, function($course) {
  return !isset($course['status']) || $course['status'] !== 'completed';
}) : [];
$completed_courses_list = is_array($enrolled_courses) ? array_filter($enrolled_courses, function($course) {
  return isset($course['status']) && $course['status'] === 'completed';
}) : [];
?>
<div class="tab-inner active" id="enrolledCoursesContainer">
  <h1 class="section-header">My Courses</h1>
  <p class="section-subheader">
    Enrolled: <strong><?php echo $total_enrolled; ?></strong> | Completed: <strong><?php echo $completed_count; ?></strong>
  </p>

  <div class="news-switch-wrapper">
    <div class="switch-oval" id="courseSwitchContainer" role="tablist" aria-label="My Courses Switch">
      <div class="switch-inner" id="myCourseSwitchInner"></div>
      <button type="button" class="switch-btn active" data-target="active" role="tab" aria-selected="true" aria-controls="enrolled-active-courses">Active</button>
      <button type="button" class="switch-btn" data-target="completed" role="tab" aria-selected="false" aria-controls="enrolled-completed-courses">Completed</button>
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
               data-course-hours="<?php echo htmlspecialchars($course['basic_hours'] ?? $course['hours']); ?>" 
               data-course-description="<?php echo htmlspecialchars($course['description']); ?>"
               data-credited-hours="<?php echo htmlspecialchars($course['credited_hours'] ?? 0); ?>"
               data-course-id="<?php echo htmlspecialchars($course['id'] ?? ''); ?>">
            <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/school.png'; ?>" 
                 alt="<?php echo htmlspecialchars($course['course_name']); ?>" 
                 class="course-img">
            <div class="batch-info">
              <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
              <p>Code: <?php echo htmlspecialchars($course['course_code']); ?></p>
              <p>Basic Hours: <?php echo htmlspecialchars($course['basic_hours'] ?? 0); ?> hrs</p>
              <p>Credited: <?php echo htmlspecialchars($course['credited_hours'] ?? 0); ?> hrs</p>
              <?php if (!empty($course['description'])): ?>
                <p class="course-description"><?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>...</p>
              <?php endif; ?>
            </div>
            <div class="batch-actions">
              <button class="btn btn-outline-danger unenroll-btn" title="Unenroll from this course"
                      data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>"
                      data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>">
                <i class="fas fa-times"></i> Unenroll
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-courses">
          <div class="no-courses-message">
            <i class="fas fa-book"></i>
            <h3>No Enrolled Courses</h3>
            <p>You are not enrolled in any courses yet.</p>
            <p>Check out our <a href="#" class="tab-switch" data-tab="courses">Available Courses</a> to get started!</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div id="enrolled-completed-courses" class="tab-inner">
    <h2 class="section-header">Completed Courses</h2>
    <div class="batch-list">
      <?php if (!empty($completed_courses_list)): ?>
        <?php foreach ($completed_courses_list as $course): ?>
          <div class="batch-card view-course-content-btn" 
               data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>" 
               data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>" 
               data-course-hours="<?php echo htmlspecialchars($course['basic_hours'] ?? $course['hours']); ?>" 
               data-course-description="<?php echo htmlspecialchars($course['description']); ?>"
               data-credited-hours="<?php echo htmlspecialchars($course['credited_hours'] ?? 0); ?>"
               data-course-id="<?php echo htmlspecialchars($course['id'] ?? ''); ?>">
            <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/school.png'; ?>" 
                 alt="<?php echo htmlspecialchars($course['course_name']); ?>" 
                 class="course-img">
            <div class="batch-info">
              <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
              <p>Code: <?php echo htmlspecialchars($course['course_code']); ?></p>
              <p>Basic Hours: <?php echo htmlspecialchars($course['basic_hours'] ?? 0); ?> hrs</p>
              <p>Credited: <?php echo htmlspecialchars($course['credited_hours'] ?? 0); ?> hrs</p>
              <span class="status-badge status-completed"><i class="fas fa-trophy"></i> Completed</span>
              <?php if (!empty($course['description'])): ?>
                <p class="course-description"><?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>...</p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-courses">
          <div class="no-courses-message">
            <i class="fas fa-trophy"></i>
            <h3>No Completed Courses</h3>
            <p>You haven't completed any courses yet.</p>
            <p>Keep learning to complete your enrolled courses!</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
  
  <div id="courseDetail" class="course-detail hidden">
    <div class="detail-header">
      <button id="backToEnrolledCourses" class="back-btn btn-ripple">
        <i class="fas fa-arrow-left"></i> Back to My Courses
      </button>
      <h2 id="course-detail-title">Course Title</h2>
      <div class="cert-actions" style="margin-left:auto; display:flex; gap:8px; align-items:center;">
        <button id="downloadCertificateBtn" class="btn btn-success" style="display:none;">
          <i class="fas fa-download"></i> Download Certificate
        </button>
        <span id="certificateInfo" class="text-muted" data-bs-toggle="tooltip" title="Checking eligibility..."></span>
      </div>
    </div>
    <div class="course-meta">
      <span id="course-detail-code"></span> | <span id="course-detail-hours"></span> | <span id="course-detail-credited-hours"></span>
    </div>
    <p id="course-detail-description"></p>
    <div class="course-content" id="courseDetailContent"></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const courseSwitchContainer = document.getElementById('courseSwitchContainer');
  if (!courseSwitchContainer) return;

  const switchButtons = courseSwitchContainer.querySelectorAll('.switch-btn');
  const switchInner = document.getElementById('myCourseSwitchInner');
  const activePane = document.getElementById('enrolled-active-courses');
  const completedPane = document.getElementById('enrolled-completed-courses');

  const buttonWidth = 100 / switchButtons.length;
  if (switchInner) {
    switchInner.style.width = `${buttonWidth}%`;
    switchInner.style.transform = 'translateX(0%)';
  }

  const setState = (target) => {
    switchButtons.forEach(btn => {
      const isActive = btn.getAttribute('data-target') === target;
      btn.classList.toggle('active', isActive);
      btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    const index = target === 'completed' ? 1 : 0;
    if (switchInner) {
      switchInner.style.transform = `translateX(${index * 100}%)`;
      switchInner.style.width = `${buttonWidth}%`;
    }

    if (activePane && completedPane) {
      activePane.classList.toggle('active', target === 'active');
      completedPane.classList.toggle('active', target === 'completed');
    }
  };

  switchButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const target = this.getAttribute('data-target');
      setState(target);
    });
    button.addEventListener('keydown', function(e) {
      const key = e.key;
      if (key === 'ArrowRight' || key === 'ArrowLeft') {
        e.preventDefault();
        const currentIndex = Array.from(switchButtons).indexOf(document.querySelector('.switch-btn.active'));
        const nextIndex = key === 'ArrowRight' ? Math.min(currentIndex + 1, switchButtons.length - 1) : Math.max(currentIndex - 1, 0);
        const nextTarget = switchButtons[nextIndex].getAttribute('data-target');
        switchButtons[nextIndex].focus();
        setState(nextTarget);
      }
    });
  });
});
</script>
