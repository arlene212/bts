<?php ?>
<div class="dashboard tab-inner active" id="dashboard">
  <h1 class="section-header">Dashboard Overview</h1>
  <div class="dashboard-cards">
    <div class="dashboard-card clickable-card" data-target="courses">
      <div class="number-circle"><?php echo count($courses); ?></div>
      <div class="label-text">My Courses</div>
    </div>
    <div class="dashboard-card clickable-card" data-target="trainees">
      <div class="number-circle"><?php echo $total_trainees; ?></div>
      <div class="label-text">Trainees</div>
    </div>
    <div class="dashboard-card clickable-card" data-target="guests">
      <div class="number-circle"><?php echo count($guests); ?></div>
      <div class="label-text">Guests</div>
    </div>
    <div class="dashboard-card clickable-card" data-target="requests">
      <div class="number-circle"><?php echo $total_requests; ?></div>
      <div class="label-text">Enrollment Requests</div>
    </div>
  </div>
</div>