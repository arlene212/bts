<?php ?>
<aside class="sidebar">
  <div class="profile">
    <div class="user-card">
      <div class="user-card-header">
        <img src="<?php echo (!empty($adminProfile['profile_picture']) && $adminProfile['profile_picture'] !== 'default.png') ? '../uploads/profiles/' . $adminProfile['profile_picture'] . '?t=' . time() : '../images/school.png'; ?>" alt="User Avatar" class="user-avatar" id="userCardAvatar">
        <button class="edit-profile-btn" id="editProfileBtn" title="Edit Profile"><i class="fas fa-edit"></i></button>
      </div>
      <div class="user-info">
        <h2 class="user-name" id="userCardName"><?php echo htmlspecialchars($adminProfile['first_name'] . ' ' . $adminProfile['last_name']); ?></h2>
        <p class="user-email"><i class="fas fa-envelope"></i> <span id="userCardEmail"><?php echo htmlspecialchars($adminProfile['email']); ?></span></p>
        <p class="user-contact"><i class="fas fa-phone"></i> <span id="userCardContact"><?php echo htmlspecialchars($adminProfile['contact_number'] ?: 'Not set'); ?></span></p>
      </div>
    </div>
  </div>
  <nav class="nav">
    <a href="#" class="tab-link active" data-tab="home">
      <i class="fas fa-home"></i>
      <span>Home</span>
    </a>
    <a href="#" class="tab-link" data-tab="trainers">
      <i class="fas fa-chalkboard-teacher"></i>
      <span>Trainers</span>
    </a>
    <a href="#" class="tab-link" data-tab="trainees">
      <i class="fas fa-users"></i>
      <span>Trainees</span>
    </a>
    <a href="#" class="tab-link" data-tab="guests">
      <i class="fas fa-user-friends"></i>
      <span>Guests</span>
    </a>
    <a href="#" class="tab-link" data-tab="courses">
      <i class="fas fa-graduation-cap"></i>
      <span>Courses</span>
    </a>
    <a href="#" class="tab-link" data-tab="enrollments">
      <i class="fas fa-clipboard-list"></i>
      <span>Enrollments</span>
      <?php if ($pendingEnrollments > 0): ?>
        <span class="nav-badge"><?php echo $pendingEnrollments; ?></span>
      <?php endif; ?>
    </a>
  </nav>
</aside>