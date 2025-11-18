<?php ?>
<section class="main-content tab-content active" id="home">
  <div class="home-content-wrapper">
    <div class="home-main-content">
      <div class="dashboard tab-inner active" id="dashboard">
        <h2 class="dashboard-header">User Management</h2>
        <div class="dashboard-cards">
          <div class="dashboard-card" data-target="trainers"><div class="number-circle"><?php echo $totalTrainers; ?></div><div class="label-text">Total Trainers</div></div>
          <div class="dashboard-card" data-target="trainees"><div class="number-circle"><?php echo $totalTrainees; ?></div><div class="label-text">Total Trainees</div></div>
          <div class="dashboard-card" data-target="guests"><div class="number-circle"><?php echo $totalGuests; ?></div><div class="label-text">Guests</div></div>
          <div class="dashboard-card" data-target="courses"><div class="number-circle"><?php echo $totalCourses; ?></div><div class="label-text">Courses Offered</div></div>
          <div class="dashboard-card" data-target="enrollments"><div class="number-circle"><?php echo $pendingEnrollments; ?></div><div class="label-text">Enrollment Requests</div></div>
        </div>
      </div>
    </div>
    <aside class="announcements">
      <div class="announcements-header"><h2>Announcements</h2><button id="addAnnouncementBtn" class="add-announcement-btn">+</button></div>
      <div class="announcements-list">
        <?php if (!empty($announcements)): ?>
          <?php foreach ($announcements as $announcement): ?>
            <div class="announcement-item">
              <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
              <p><?php echo htmlspecialchars($announcement['content']); ?></p>
              <span class="announcement-date">Posted by <?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?> on <?php echo date('Y-m-d H:i', strtotime($announcement['date_posted'])); ?></span>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-announcements"><p>No announcements yet.</p></div>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</section>