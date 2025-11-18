<?php ?>
<section class="tab-content <?php echo $currentTab === 'home' ? 'active' : ''; ?>" id="home">
  <div class="dashboard tab-inner active" id="dashboard">
    <div class="dashboard-header">Quick Overview</div>
    <div class="dashboard-cards">
      <div class="dashboard-card" data-target="mycourses"><div class="number-circle"><?php echo $total_courses; ?></div><div class="label-text">My Courses</div></div>
      <div class="dashboard-card" data-target="mycourses"><div class="number-circle"><?php echo $total_hours; ?></div><div class="label-text">Hours Enrolled</div></div>
      <div class="dashboard-card" data-target="mycourses"><div class="number-circle"><?php echo $total_activities; ?></div><div class="label-text">Activities</div></div>
      <div class="dashboard-card" data-target="offered-courses"><div class="number-circle"><?php echo $pending_requests; ?></div><div class="label-text">Pending Requests</div></div>
    </div>
  </div>
</section>