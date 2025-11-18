<?php ?>
<section class="tab-content <?php echo $currentTab === 'offered-courses' ? 'active' : ''; ?>" id="offered-courses">
  <h2 class="section-header">Available Courses</h2>
  <div class="course-box">
    <div class="course-list">
      <?php if (!empty($offered_courses)): ?>
        <?php foreach ($offered_courses as $course): ?>
          <div class="dashboard-card course-card card" data-course="<?php echo htmlspecialchars($course['course_code']); ?>" data-title="<?php echo htmlspecialchars($course['course_name']); ?>">
            <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/course-placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($course['course_name']); ?>" class="course-img">
            <div class="label-text"><?php echo htmlspecialchars($course['course_name']); ?></div>
            <div class="sub-text"><?php echo htmlspecialchars($course['course_code']); ?> | <?php echo htmlspecialchars($course['hours']); ?> hours</div>
            <div class="course-description"><?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>...</div>
            <?php
              $is_enrolled = false;
              $is_pending = false;
              foreach ($enrollment_requests as $request) {
                if ($request['course_code'] == $course['course_code']) {
                  if ($request['status'] == 'approved') { $is_enrolled = true; }
                  elseif ($request['status'] == 'pending') { $is_pending = true; }
                }
              }
            ?>
            <?php if ($is_enrolled): ?>
              <button class="enroll-btn enrolled btn btn-secondary" disabled>Already Enrolled</button>
            <?php elseif ($is_pending): ?>
              <button class="enroll-btn pending btn btn-warning" disabled>Request Pending</button>
            <?php else: ?>
              <button type="button" class="enroll-btn btn btn-primary" data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>" data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>">Request to Enroll</button>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-courses"><p>No courses available at the moment.</p></div>
      <?php endif; ?>
    </div>
  </div>
</section>