<?php ?>
<section class="main-content tab-content <?php echo $currentTab === 'home' ? 'active' : ''; ?>" id="home">
  <div class="dashboard tab-inner active" id="dashboard">
    <div class="slogan-box">
      <div class="slogan-text">
        <div class="welcome-header">
          <i class="fas fa-graduation-cap fa-3x"></i>
          <div>
            <h2>Welcome <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
            <p>See opportunities for better future with BTS.</p>
            <p><strong>Enrolled Courses:</strong> <?php echo count($enrolled_courses); ?> | <strong>Pending Requests:</strong> <?php echo count(array_filter($enrollment_requests, function ($r) { return $r['status'] == 'pending'; })); ?></p>
          </div>
        </div>
      </div>
      <div class="slideshow-container">
        <div class="slider">
          <div class="slide active" style="background-image: url('../images/bts3.jpg');"></div>
          <div class="slide" style="background-image: url('../images/bts1.jpg');"></div>
          <div class="slide" style="background-image: url('../images/bts2.jpg');"></div>
          <div class="slide" style="background-image: url('../images/bts4.jpg');"></div>
          <div class="slide" style="background-image: url('../images/bts5.jpg');"></div>
        </div>
        <div class="slideshow-text"></div>
      </div>
    </div>
  </div>
</section>