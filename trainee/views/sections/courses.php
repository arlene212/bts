<?php ?>
<section class="tab-content <?php echo $currentTab === 'mycourses' ? 'active' : ''; ?>" id="mycourses">
  <div class="course-box">
    <div class="news-switch-wrapper"><div class="switch-oval"><div class="switch-inner" id="myCourseSwitchInner"></div><button class="switch-btn active" data-tab="enrolled">Enrolled</button><button class="switch-btn" data-tab="completed">Completed</button></div></div>
    <div id="enrolled" class="tab-inner active">
      <h2 class="section-header">Enrolled Courses</h2>
      <div class="course-list">
        <?php if (!empty($enrolled_courses)): ?>
          <?php foreach ($enrolled_courses as $course): ?>
            <div class="course-card view-course-content-btn card" data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>" data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>" data-course-hours="<?php echo htmlspecialchars($course['hours']); ?>" data-course-description="<?php echo htmlspecialchars($course['description']); ?>">
              <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : 'https://via.placeholder.com/250x140'; ?>" alt="Course Image">
              <div class="course-info"><h3><?php echo htmlspecialchars($course['course_name']); ?></h3><p>Code: <?php echo htmlspecialchars($course['course_code']); ?></p><p>Hours: <?php echo htmlspecialchars($course['hours']); ?> hrs</p></div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-courses"><p>You are not enrolled in any courses yet.</p><p>Check out our <a href="#" class="tab-switch" data-tab="offered-courses">Offered Courses</a> to get started!</p></div>
        <?php endif; ?>
      </div>
    </div>
    <div id="completed" class="tab-inner"><h2 class="section-header">Completed Courses</h2><div class="course-list"><div class="no-courses"><p>No completed courses yet.</p><p>Keep learning to complete your enrolled courses!</p></div></div></div>
  </div>
  <div id="courseDetail" class="course-detail hidden">
    <div class="detail-header"><button class="back-btn"><i class="fas fa-arrow-left"></i> Back to My Courses</button><h2 id="course-detail-title">Course Title</h2></div>
    <div class="course-meta"><span id="course-detail-code"></span> | <span id="course-detail-hours"></span></div>
    <p id="course-detail-description"></p>
    <div class="news-switch-wrapper course-switch"><div class="switch-oval"><div class="switch-inner"></div><button class="switch-btn active" data-tab="modules">Modules</button><button class="switch-btn" data-tab="activities">Activities</button></div></div>
    <div id="modules-view" class="tab-inner active"><div id="competencies-list"></div></div>
    <div id="activities-view" class="tab-inner"></div>
  </div>
</section>