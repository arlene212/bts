<?php ?>
<aside class="sidebar">
  <div class="profile">
    <div class="user-card">
      <div class="user-card-header">
        <img src="<?php echo !empty($user['profile_picture']) ? '../uploads/profiles/' . htmlspecialchars($user['profile_picture']) : '../images/school.png'; ?>" alt="User Avatar" class="user-avatar">
        <button type="button" class="edit-profile-btn" id="editProfileBtn" title="Edit Profile"><i class="fas fa-edit"></i></button>
      </div>
      <h3 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
      <p class="user-role">Trainee</p>
      <p class="user-id"><?php echo htmlspecialchars($user['user_id']); ?></p>
    </div>
  </div>
  
  <nav class="nav">
    <div class="nav-section">
      <h4 class="nav-title">Dashboard</h4>
      <a href="#" class="tab-link <?php echo $currentTab === 'home' ? 'active' : ''; ?>" data-tab="home">
        <i class="fas fa-home"></i>
        <span>Overview</span>
      </a>
    </div>
    
    <div class="nav-section">
      <h4 class="nav-title">Learning</h4>
      <a href="#" class="tab-link <?php echo $currentTab === 'mycourses' ? 'active' : ''; ?>" data-tab="mycourses">
        <i class="fas fa-book"></i>
        <span>My Courses</span>
      </a>
      <a href="#" class="tab-link <?php echo $currentTab === 'offered-courses' ? 'active' : ''; ?>" data-tab="offered-courses">
        <i class="fas fa-graduation-cap"></i>
        <span>Browse Courses</span>
      </a>
    </div>
    
    <div class="nav-section">
      <h4 class="nav-title">Requests</h4>
      <a href="#" class="tab-link <?php echo $currentTab === 'enrollment-requests' ? 'active' : ''; ?>" data-tab="enrollment-requests">
        <i class="fas fa-clipboard-list"></i>
        <span>My Requests</span>
        <?php if ($pending_requests > 0): ?>
          <span class="nav-badge"><?php echo $pending_requests; ?></span>
        <?php endif; ?>
      </a>
    </div>
  </nav>
</aside>