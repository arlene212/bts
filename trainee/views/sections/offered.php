<?php ?>
<section class="tab-content <?php echo $currentTab === 'offered-courses' ? 'active' : ''; ?>" id="offered-courses">
  <h1 class="section-header">Available Courses</h1>
  <div class="course-box">
    <div class="batch-list">
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
          <div class="batch-card course-card" 
               data-course="<?php echo htmlspecialchars($course['course_code']); ?>" 
               data-title="<?php echo htmlspecialchars($course['course_name']); ?>"
               data-course-id="<?php echo htmlspecialchars($course['id']); ?>">
            <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/course-placeholder.jpg'; ?>" 
                 alt="<?php echo htmlspecialchars($course['course_name']); ?>" 
                 class="course-img">
            <div class="batch-info">
              <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
              <p class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></p>
              <p class="course-hours"><?php echo htmlspecialchars($course['hours']); ?> hours</p>
              <?php if (!empty($course['description'])): ?>
                <p class="course-description"><?php echo htmlspecialchars(substr($course['description'], 0, 120)); ?>...</p>
              <?php endif; ?>
              <div class="course-actions">
                <?php if ($is_enrolled): ?>
                  <span class="status-badge status-approved">
                    <i class="fas fa-check"></i> Already Enrolled
                  </span>
                <?php elseif ($is_pending): ?>
                  <span class="status-badge status-pending">
                    <i class="fas fa-clock"></i> Request Pending
                  </span>
                <?php else: ?>
                  <button type="button" class="btn btn-primary enroll-btn btn-ripple" 
                          data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>" 
                          data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>">
                    <i class="fas fa-plus"></i> Request to Enroll
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-courses">
          <p>No courses available at the moment.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>