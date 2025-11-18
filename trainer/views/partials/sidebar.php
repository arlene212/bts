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
    <a href="#" class="tab-link <?php echo $currentTab === 'home' ? 'active' : ''; ?>" data-tab="home">Home</a>
    <a href="#" class="tab-link <?php echo $currentTab === 'mycourses' ? 'active' : ''; ?>" data-tab="mycourses">My Courses</a>
    <a href="#" class="tab-link <?php echo $currentTab === 'trainees' ? 'active' : ''; ?>" data-tab="trainees">Trainees</a>
    <a href="#" class="tab-link <?php echo $currentTab === 'guests' ? 'active' : ''; ?>" data-tab="guests">Guests</a>
    <a href="#" class="tab-link <?php echo $currentTab === 'requests' ? 'active' : ''; ?>" data-tab="requests">Enrollment Request</a>
  </nav>
</aside>