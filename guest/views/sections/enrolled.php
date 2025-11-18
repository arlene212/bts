<?php ?>
<section class="main-content tab-content <?php echo $currentTab === 'enrolled' ? 'active' : ''; ?>" id="enrolled">
  <button id="backToEnrolledCourses" class="back-btn hidden" aria-label="Back"><i class="fa-solid fa-arrow-left"></i></button>
  <h2>Enrolled Courses</h2>
  <div id="enrolledCoursesContainer" class="dashboard-cards card" style="width: 18rem; height: 20rem;">
    <?php if (!empty($enrolled_courses)): ?>
      <?php foreach ($enrolled_courses as $course): ?>
        <div class="dashboard-card course-card enrolled-course" data-course="<?php echo htmlspecialchars($course['course_code']); ?>" data-title="<?php echo htmlspecialchars($course['course_name']); ?>">
          <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/course-placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($course['course_name']); ?>" class="course-img">
          <div class="label-text"><?php echo htmlspecialchars($course['course_name']); ?></div>
          <div class="sub-text"><?php echo htmlspecialchars($course['course_code']); ?> | <?php echo htmlspecialchars($course['hours']); ?> hours</div>
          <button class="btn btn-primary view-course-btn">View Course</button>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="no-courses-message">You are not enrolled in any courses.</p>
    <?php endif; ?>
  </div>
  <div id="courseDetail" class="course-detail hidden">
    <div class="course-detail-header"><button id="unenrollCourseBtn" class="unenroll-btn" title="Unenroll from this course">Unenroll</button></div>
    <div class="course-content" id="courseDetailContent"></div>
  </div>
</section>