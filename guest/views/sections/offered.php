<?php 
// Calculate enrollment statistics for better display
$total_offered = count($offered_courses);
$enrolled_count = count(array_filter($enrollment_requests, function($r) { return $r['status'] == 'approved'; }));
$pending_count = count(array_filter($enrollment_requests, function($r) { return $r['status'] == 'pending'; }));
?>
<div class="tab-inner active" id="offered-courses">
  <h1 class="section-header">Available Courses</h1>
  
  <?php if (!empty($offered_courses)): ?>
    <div class="batch-list">
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
            <?php if ($is_enrolled): ?>
              <button class="btn btn-outline-danger unenroll-btn" 
                      data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>" 
                      data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>">
                <i class="fas fa-times"></i> Unenroll
              </button>
            <?php elseif ($is_pending): ?>
              <span class="status-badge status-pending">
                <i class="fas fa-clock"></i> Request Pending
              </span>
            <?php else: ?>
              <button class="btn btn-primary enroll-btn btn-ripple" 
                      data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>" 
                      data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>">
                <i class="fas fa-plus"></i> Enroll Now
              </button>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="no-courses">
      <div class="no-courses-message">
        <i class="fas fa-book-open"></i>
        <h3>No Courses Available</h3>
        <p>No courses are currently being offered. Please check back later.</p>
      </div>
    </div>
  <?php endif; ?>
</div>