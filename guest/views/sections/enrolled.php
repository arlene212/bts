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
    <div class="switch-oval" id="courseSwitchContainer">
      <div class="switch-inner" id="myCourseSwitchInner"></div>
      <button type="button" class="switch-btn active" data-target="active">Active</button>
      <button type="button" class="switch-btn" data-target="completed">Completed</button>
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
               data-course-id="<?php echo htmlspecialchars($course['id'] ?? ''); ?>">
            <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/school.png'; ?>" 
                 alt="<?php echo htmlspecialchars($course['course_name']); ?>" 
                 class="course-img">
            <div class="batch-info">
              <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
              <p>Code: <?php echo htmlspecialchars($course['course_code']); ?></p>
              <p>Hours: <?php echo htmlspecialchars($course['hours']); ?> hrs</p>
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
               data-course-hours="<?php echo htmlspecialchars($course['hours']); ?>" 
               data-course-description="<?php echo htmlspecialchars($course['description']); ?>"
               data-credited-hours="<?php echo htmlspecialchars($course['credited_hours'] ?? 0); ?>"
               data-course-id="<?php echo htmlspecialchars($course['id'] ?? ''); ?>">
            <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/school.png'; ?>" 
                 alt="<?php echo htmlspecialchars($course['course_name']); ?>" 
                 class="course-img">
            <div class="batch-info">
              <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
              <p>Code: <?php echo htmlspecialchars($course['course_code']); ?></p>
              <p>Hours: <?php echo htmlspecialchars($course['hours']); ?> hrs</p>
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
    </div>
    <div class="course-meta">
      <span id="course-detail-code"></span> | <span id="course-detail-hours"></span> | <span id="course-detail-credited-hours"></span>
    </div>
    <div class="course-content" id="courseDetailContent"></div>
  </div>
</div>

<script>
// Match trainee UI switch behavior
document.addEventListener('DOMContentLoaded', function() {
  const courseSwitchContainer = document.getElementById('courseSwitchContainer');
  if (!courseSwitchContainer) return;

  const switchButtons = courseSwitchContainer.querySelectorAll('.switch-btn');
  const switchInner = document.getElementById('myCourseSwitchInner');
  const activeCoursesContent = document.getElementById('enrolled-active-courses');
  const completedCoursesContent = document.getElementById('enrolled-completed-courses');

  if (switchInner) {
    switchInner.style.transform = 'translateX(0%)';
    switchInner.style.width = '50%';
  }

  switchButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();

      const target = this.getAttribute('data-target');

      switchButtons.forEach(btn => btn.classList.remove('active'));
      this.classList.add('active');

      const buttonIndex = Array.from(switchButtons).indexOf(this);
      const buttonWidth = 100 / switchButtons.length;
      if (switchInner) {
        switchInner.style.transform = `translateX(${buttonIndex * 100}%)`;
        switchInner.style.width = `${buttonWidth}%`;
      }

      if (target === 'active') {
        if (activeCoursesContent) {
          activeCoursesContent.classList.add('active');
          activeCoursesContent.style.display = 'block';
        }
        if (completedCoursesContent) {
          completedCoursesContent.classList.remove('active');
          completedCoursesContent.style.display = 'none';
        }
      } else {
        if (activeCoursesContent) {
          activeCoursesContent.classList.remove('active');
          activeCoursesContent.style.display = 'none';
        }
        if (completedCoursesContent) {
          completedCoursesContent.classList.add('active');
          completedCoursesContent.style.display = 'block';
        }
      }
    });
  });
});
</script>
