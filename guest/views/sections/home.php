<?php 
// Calculate statistics for the dashboard
$total_enrolled = count($enrolled_courses);
$pending_requests = count(array_filter($enrollment_requests, function ($r) { return $r['status'] == 'pending'; }));
$total_available = count($offered_courses);
?>
<div class="dashboard tab-inner active" id="dashboard">
  <h1 class="section-header">Dashboard Overview</h1>
  <div class="dashboard-cards">
    <div class="dashboard-card clickable-card" data-target="enrolled">
      <div class="number-circle"><?php echo $total_enrolled; ?></div>
      <div class="label-text">Enrolled Courses</div>
    </div>
    <div class="dashboard-card clickable-card" data-target="courses">
      <div class="number-circle"><?php echo $total_available; ?></div>
      <div class="label-text">Available Courses</div>
    </div>
    <div class="dashboard-card clickable-card" data-target="enrolled">
      <div class="number-circle"><?php echo $pending_requests; ?></div>
      <div class="label-text">Pending Requests</div>
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