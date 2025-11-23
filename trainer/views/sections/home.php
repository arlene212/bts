<?php 
// Calculate additional statistics for better dashboard display
$total_courses = count($courses);
$active_courses = count(array_filter($courses, function($course) { 
  return isset($course['status']) && $course['status'] === 'active'; 
}));
$pending_requests = $total_requests;
$total_guests = count($guests);
?>
<div class="dashboard tab-inner active" id="dashboard">
  <h1 class="section-header">Dashboard Overview</h1>
  
  <!-- Enhanced Dashboard Cards with Statistics -->
  <div class="dashboard-cards">
    <div class="dashboard-card clickable-card" data-target="courses">
      <div class="number-circle"><?php echo $total_courses; ?></div>
      <div class="label-text">My Courses</div>
      <div class="sub-text"><?php echo $active_courses; ?> active courses</div>
    </div>
    <div class="dashboard-card clickable-card" data-target="trainees">
      <div class="number-circle"><?php echo $total_trainees; ?></div>
      <div class="label-text">Trainees</div>
      <div class="sub-text">Total enrolled students</div>
    </div>
    <div class="dashboard-card clickable-card" data-target="guests">
      <div class="number-circle"><?php echo $total_guests; ?></div>
      <div class="label-text">Guests</div>
      <div class="sub-text">Guest learners</div>
    </div>
    <div class="dashboard-card clickable-card" data-target="requests">
      <div class="number-circle"><?php echo $pending_requests; ?></div>
      <div class="label-text">Enrollment Requests</div>
      <div class="sub-text">Pending approvals</div>
    </div>
  </div>
  
  <!-- Welcome Section -->
  <div class="welcome-section">
    <div class="welcome-card">
      <div class="welcome-header">
        <i class="fas fa-chalkboard-teacher fa-2x"></i>
        <div>
          <h2>Welcome <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
          <p>Manage your courses and track student progress</p>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Quick Actions Section -->
  <?php if ($total_courses > 0): ?>
  <div class="quick-actions-section">
    <h3 class="quick-actions-header">Quick Actions</h3>
    <div class="quick-actions-grid">
      <div class="quick-action-card" data-target="courses">
        <i class="fas fa-book"></i>
        <span>View Courses</span>
      </div>
      <div class="quick-action-card" data-target="trainees">
        <i class="fas fa-users"></i>
        <span>Manage Trainees</span>
      </div>
      <div class="quick-action-card" data-target="quizzes">
        <i class="fas fa-question-circle"></i>
        <span>Create Quiz</span>
      </div>
      <div class="quick-action-card" data-target="grades">
        <i class="fas fa-chart-bar"></i>
        <span>View Grades</span>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>