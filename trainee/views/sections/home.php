<?php ?>
<div class="dashboard tab-inner active" id="dashboard">
  <h1 class="section-header">Dashboard Overview</h1>
  <div class="dashboard-cards">
    <div class="dashboard-card clickable-card" data-target="mycourses">
      <div class="number-circle"><?php echo $total_courses; ?></div>
      <div class="label-text">My Courses</div>
    </div>
    <div class="dashboard-card clickable-card" data-target="mycourses">
      <div class="number-circle"><?php echo $total_hours; ?></div>
      <div class="label-text">Hours Enrolled</div>
    </div>
    <div class="dashboard-card clickable-card" data-target="mycourses">
      <div class="number-circle"><?php echo $total_activities; ?></div>
      <div class="label-text">Total Activities</div>
    </div>
    <div class="dashboard-card clickable-card" data-target="enrollment-requests">
      <div class="number-circle"><?php echo $pending_requests; ?></div>
      <div class="label-text">Pending Requests</div>
    </div>
  </div>
  
  <?php if ($total_courses > 0): ?>
  <div class="progress-section">
    <h3 class="progress-header">Learning Progress</h3>
    <div class="progress-card">
      <div class="progress-info">
        <span class="progress-label">Overall Completion</span>
        <span class="progress-percentage"><?php echo $progress; ?>%</span>
      </div>
      <div class="progress-bar">
        <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
      </div>
      <div class="progress-stats">
        <span><?php echo $total_submissions; ?> / <?php echo $total_activities; ?> activities completed</span>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>