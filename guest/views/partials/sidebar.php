<?php ?>
<aside class="sidebar">
  <div class="profile">
    <div class="user-card">
      <div class="user-card-header">
        <img src="<?php echo !empty($user['profile_picture']) ? '../uploads/profiles/' . htmlspecialchars($user['profile_picture']) : '../images/school.png'; ?>" alt="User Avatar" class="user-avatar">
        <button type="button" class="edit-profile-btn" id="editProfileBtn" title="Edit Profile"><i class="fas fa-edit"></i></button>
      </div>
      <h3 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
      <p class="user-role">Guest</p>
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
      <a href="#" class="tab-link <?php echo $currentTab === 'courses' ? 'active' : ''; ?>" data-tab="courses">
        <i class="fas fa-graduation-cap"></i>
        <span>Browse Courses</span>
      </a>
      <a href="#" class="tab-link <?php echo $currentTab === 'enrolled' ? 'active' : ''; ?>" data-tab="enrolled">
        <i class="fas fa-book"></i>
        <span>My Courses</span>
      </a>
    </div>
    
    <div class="nav-section">
      <h4 class="nav-title">Information</h4>
      <a href="#" class="tab-link <?php echo $currentTab === 'aboutUs' ? 'active' : ''; ?>" data-tab="aboutUs">
        <i class="fas fa-info-circle"></i>
        <span>About Us</span>
      </a>
    </div>
  </nav>
</aside>