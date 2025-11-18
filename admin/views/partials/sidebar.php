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
    <a href="#" class="tab-link active" data-tab="home">Home</a>
    <a href="#" class="tab-link" data-tab="trainers">Trainers</a>
    <a href="#" class="tab-link" data-tab="trainees">Trainees</a>
    <a href="#" class="tab-link" data-tab="guests">Guests</a>
    <a href="#" class="tab-link" data-tab="courses">Courses</a>
    <a href="#" class="tab-link" data-tab="enrollments">Enrollments</a>
  </nav>
</aside>