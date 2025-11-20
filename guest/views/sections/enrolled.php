<?php 
// Calculate enrolled courses statistics
$total_enrolled = count($enrolled_courses);
$completed_courses = count(array_filter($enrolled_courses, function($course) { 
  return isset($course['status']) && $course['status'] == 'completed'; 
}));
?>
<div class="tab-inner active" id="enrolled-courses">
  <h1 class="section-header">My Courses</h1>
  
  <div class="news-switch-wrapper">
    <div class="switch-oval">
      <div class="switch-inner" id="enrolledSwitchInner"></div>
      <button class="switch-btn active" data-tab="enrolled-active">Active</button>
      <button class="switch-btn" data-tab="enrolled-completed">Completed</button>
    </div>
  </div>
  
  <div id="enrolled-active" class="tab-inner active">
    <h2 class="section-subheader">Active Courses</h2>
    <div class="batch-list">
      <?php if (!empty($enrolled_courses)): ?>
        <?php foreach ($enrolled_courses as $course): ?>
          <div class="batch-card enrolled-course" 
               data-course="<?php echo htmlspecialchars($course['course_code']); ?>" 
               data-title="<?php echo htmlspecialchars($course['course_name']); ?>"
               data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>"
               data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>"
               data-course-hours="<?php echo htmlspecialchars($course['hours']); ?>"
               data-course-description="<?php echo htmlspecialchars($course['description']); ?>">
            <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/course-placeholder.jpg'; ?>" 
                 alt="<?php echo htmlspecialchars($course['course_name']); ?>" 
                 class="course-img">
            <div class="batch-info">
              <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
              <p><?php echo htmlspecialchars($course['course_code']); ?> | <?php echo htmlspecialchars($course['hours']); ?> Hours</p>
              <?php if (!empty($course['description'])): ?>
                <p class="course-description"><?php echo htmlspecialchars(substr($course['description'], 0, 120)); ?>...</p>
              <?php endif; ?>
            </div>
            <div class="batch-actions">
              <button class="btn btn-primary view-course-btn btn-ripple">
                <i class="fas fa-eye"></i> View Course
              </button>
              <button id="unenrollCourseBtn" class="btn btn-outline-danger unenroll-btn" title="Unenroll from this course">
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
  
  <div id="enrolled-completed" class="tab-inner">
    <h2 class="section-subheader">Completed Courses</h2>
    <div class="batch-list">
      <div class="no-courses">
        <div class="no-courses-message">
          <i class="fas fa-trophy"></i>
          <h3>No Completed Courses</h3>
          <p>You haven't completed any courses yet.</p>
          <p>Keep learning to complete your enrolled courses!</p>
        </div>
      </div>
    </div>
  </div>
  
  <div id="courseDetail" class="course-detail hidden">
    <div class="detail-header">
      <button class="back-btn btn-ripple">
        <i class="fas fa-arrow-left"></i> Back to My Courses
      </button>
      <h2 id="course-detail-title">Course Title</h2>
    </div>
    <div class="course-meta">
      <span id="course-detail-code"></span> | <span id="course-detail-hours"></span>
    </div>
    <p id="course-detail-description"></p>
    <div class="course-content" id="courseDetailContent"></div>
  </div>
</div>