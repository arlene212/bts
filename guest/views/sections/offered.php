<?php ?>
  <div class="tab-inner active" id="offered-courses">
  <div id="courseGrid" class="dashboard-cards">
    <?php if (!empty($offered_courses)): ?>
      <?php foreach ($offered_courses as $course): ?>
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
        <div class="dashboard-card course-card card" style="width: 18rem; height: 20rem;" data-course="<?php echo htmlspecialchars($course['course_code']); ?>" data-title="<?php echo htmlspecialchars($course['course_name']); ?>">
          <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/course-placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($course['course_name']); ?>" class="course-img">
          <div class="label-text"><?php echo htmlspecialchars($course['course_name']); ?></div>
          <div class="sub-text"><?php echo htmlspecialchars($course['course_code']); ?> | <?php echo htmlspecialchars($course['hours']); ?> hours</div>
          <div class="course-description"><?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>...</div>
          <?php if ($is_enrolled): ?>
            <button class="btn btn-outline-danger unenroll-btn" data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>" data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>">
              <i class="fas fa-times"></i> Unenroll
            </button>
          <?php elseif ($is_pending): ?>
            <span class="status-badge status-pending">
              <i class="fas fa-clock"></i> Request Pending
            </span>
          <?php else: ?>
            <button class="btn btn-primary enroll-btn btn-ripple" data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>" data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>">
              <i class="fas fa-plus"></i> Enroll
            </button>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="no-courses"><p>No courses available at the moment.</p></div>
    <?php endif; ?>
  </div>
</div>