<?php 
// Calculate statistics for the dashboard
$total_enrolled = is_array($enrolled_courses) ? count($enrolled_courses) : 0;
$total_available = is_array($offered_courses) ? count($offered_courses) : 0;
$completed_count = is_array($enrolled_courses) ? count(array_filter($enrolled_courses, function($course){
  return isset($course['status']) && $course['status'] === 'completed';
})) : 0;
?>
<div class="dashboard tab-inner active" id="dashboard">
  <h1 class="section-header">Dashboard Overview</h1>
  <div class="dashboard-cards">
    <div class="dashboard-card clickable-card" data-target="enrolled" data-subtab="active">
      <div class="number-circle"><?php echo $total_enrolled; ?></div>
      <div class="label-text">Enrolled Courses</div>
    </div>
    <div class="dashboard-card clickable-card" data-target="courses">
      <div class="number-circle"><?php echo $total_available; ?></div>
      <div class="label-text">Available Courses</div>
    </div>
    <div class="dashboard-card clickable-card" data-target="enrolled" data-subtab="completed">
      <div class="number-circle"><?php echo $completed_count; ?></div>
      <div class="label-text">Completed Courses</div>
    </div>
  </div>
  
  <?php if ($total_enrolled > 0): ?>
  <div class="progress-section">
    <h3 class="progress-header">Learning Progress</h3>
    <div class="progress-card">
      <div class="progress-info">
        <span class="progress-label">Active Courses</span>
        <span class="progress-percentage"><?php echo $total_enrolled; ?></span>
      </div>
      <div class="progress-stats">
        <span><?php echo $total_enrolled; ?> courses currently enrolled</span>
      </div>
    </div>
  </div>
  <?php endif; ?>
  
  <div class="welcome-section">
    <div class="welcome-card">
      <div class="welcome-header">
        <i class="fas fa-graduation-cap fa-2x"></i>
        <div>
          <h2>Welcome <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
          <p>Explore learning opportunities at Benguet Technical School</p>
        </div>
      </div>
    </div>
  </div>
</div>
