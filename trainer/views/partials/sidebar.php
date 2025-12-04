<?php ?>
<aside class="sidebar">
  <div class="profile">
    <div class="user-card">
      <div class="user-card-header">
        <img src="<?php echo !empty($trainer_info['profile_picture']) ? '../uploads/profiles/' . htmlspecialchars($trainer_info['profile_picture']) : '../images/school.png'; ?>" alt="User Avatar" class="user-avatar">
        <button class="edit-profile-btn" id="editProfileBtn" title="Edit Profile"><i class="fas fa-edit"></i></button>
      </div>
      <h3 class="user-name"><?php echo htmlspecialchars($trainer_info['first_name'] . ' ' . $trainer_info['last_name']); ?></h3>
      <p class="user-role">Trainer</p>
      <p class="user-id"><?php echo htmlspecialchars($trainer_info['user_id']); ?></p>
    </div>
  </div>
  <nav class="nav">
    <div class="nav-section">
      <h4 class="nav-title">Dashboard</h4>
      <a href="?current_tab=home" class="tab-link <?php echo $currentTab === 'home' ? 'active' : ''; ?>" data-tab="home">
        <i class="fas fa-home"></i>
        <span>Overview</span>
      </a>
    </div>
    
    <div class="nav-section">
      <h4 class="nav-title">Teaching</h4>
      <a href="?current_tab=courses" class="tab-link <?php echo $currentTab === 'courses' ? 'active' : ''; ?>" data-tab="courses">
        <i class="fas fa-graduation-cap"></i>
        <span>My Courses</span>
      </a>
      <a href="?current_tab=trainees" class="tab-link <?php echo $currentTab === 'trainees' ? 'active' : ''; ?>" data-tab="trainees">
        <i class="fas fa-users"></i>
        <span>My Trainees</span>
      </a>
      <a href="?current_tab=quizzes" class="tab-link <?php echo $currentTab === 'quizzes' ? 'active' : ''; ?>" data-tab="quizzes">
        <i class="fas fa-question-circle"></i>
        <span>Activities/ Exams</span>
      </a>
      <a href="?current_tab=grades" class="tab-link <?php echo $currentTab === 'grades' ? 'active' : ''; ?>" data-tab="grades">
        <i class="fas fa-chart-bar"></i>
        <span>Grades</span>
      </a>
      <a href="?current_tab=gradebook" class="tab-link <?php echo $currentTab === 'gradebook' ? 'active' : ''; ?>" data-tab="gradebook">
        <i class="fas fa-book"></i>
        <span>Grade Book</span>
      </a>
    </div>
    
    <div class="nav-section">
      <h4 class="nav-title">Management</h4>
      <a href="?current_tab=requests" class="tab-link <?php echo $currentTab === 'requests' ? 'active' : ''; ?>" data-tab="requests">
        <i class="fas fa-clipboard-list"></i>
        <span>Enrollment Requests</span>
        <?php if ($total_requests > 0): ?>
          <span class="nav-badge"><?php echo $total_requests; ?></span>
        <?php endif; ?>
      </a>
      <a href="?current_tab=guests" class="tab-link <?php echo $currentTab === 'guests' ? 'active' : ''; ?>" data-tab="guests">
        <i class="fas fa-user-friends"></i>
        <span>Guest Management</span>
      </a>
    </div>
  </nav>
</aside>
