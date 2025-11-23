<?php ?>
<div class="modal hidden" id="profileModal">
  <div class="modal-content">
    <span class="close-btn" id="closeProfileModal">&times;</span>
    <h2>Edit Profile</h2>
    <div class="profile-wrapper">
      <img id="profilePreview" src="<?php echo !empty($user['profile_picture']) ? '../uploads/profiles/' . htmlspecialchars($user['profile_picture']) : '../images/school.png'; ?>" alt="Profile">
      <button type="button" id="changeProfileBtn" class="change-profile-btn">Upload Profile</button>
      <input type="file" id="profileUpload" accept="image/*" style="display:none;">
    </div>
    <form class="modal-form" id="profileForm">
      <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
      <div><label>First Name</label><input type="text" id="firstName" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>"></div>
      <div><label>Last Name</label><input type="text" id="lastName" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>"></div>
      <div><label>Email</label><input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"><div class="validation-error hidden" id="emailError">Please enter a valid email address</div></div>
      <div><label>Contact Number</label><input type="text" id="contactNumber" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>"><div class="validation-error hidden" id="contactError">Please enter a valid contact number</div></div>
      
      <div class="verification-section">
        <h3>Account Verification</h3>
        <div class="verification-item">
          <div class="verification-status">
            <i class="fas fa-envelope"></i>
            <span>Email Verification</span>
            <span class="status-badge <?php echo ($user['email_verified'] ?? 0) ? 'verified' : 'unverified'; ?>">
              <?php echo ($user['email_verified'] ?? 0) ? 'Verified' : 'Unverified'; ?>
            </span>
          </div>
          <?php if (!($user['email_verified'] ?? 0)): ?>
            <button type="button" class="verify-btn" id="verifyEmailBtn">Verify Email</button>
          <?php endif; ?>
        </div>
        
        <div class="verification-item">
          <div class="verification-status">
            <i class="fas fa-id-card"></i>
            <span>Student ID</span>
            <span class="status-badge <?php echo !empty($user['student_id']) ? 'verified' : 'unverified'; ?>">
              <?php echo !empty($user['student_id']) ? 'Set' : 'Not Set'; ?>
            </span>
          </div>
          <div class="student-id-input" style="display: <?php echo empty($user['student_id']) ? 'block' : 'none'; ?>;">
            <input type="text" id="studentIdInput" placeholder="Enter your Student ID">
            <button type="button" class="verify-btn" id="setStudentIdBtn">Set Student ID</button>
          </div>
          <?php if (!empty($user['student_id'])): ?>
            <div class="student-id-display">
              <small>Current ID: <?php echo htmlspecialchars($user['student_id']); ?></small>
            </div>
          <?php endif; ?>
        </div>
        
        <div class="verification-item">
          <div class="verification-status">
            <i class="fas fa-phone"></i>
            <span>Phone Verification</span>
            <span class="status-badge <?php echo ($user['phone_verified'] ?? 0) ? 'verified' : 'unverified'; ?>">
              <?php echo ($user['phone_verified'] ?? 0) ? 'Verified' : 'Unverified'; ?>
            </span>
          </div>
          <?php if (!($user['phone_verified'] ?? 0) && !empty($user['contact_number'])): ?>
            <button type="button" class="verify-btn" id="verifyPhoneBtn">Verify Phone</button>
          <?php elseif (empty($user['contact_number'])): ?>
            <small class="text-muted">Add contact number first</small>
          <?php endif; ?>
        </div>
      </div>
      
      <div class="password-change-section"><h3>Change Password</h3><div><label>Old Password</label><input type="password" id="oldPassword" name="old_password"></div><div><label>New Password</label><input type="password" id="newPassword" name="new_password"></div><div><label>Confirm New Password</label><input type="password" id="confirmPassword" name="confirm_password"><div class="validation-error hidden" id="passwordError">Passwords do not match</div></div></div>
    </form>
    <div class="modal-buttons"><button type="button" class="delete-account-btn" id="openDeleteModal">Delete Account</button><div><button type="button" class="cancel-btn" id="cancelProfileChanges">Cancel</button><button type="button" class="primary-btn" id="saveProfileChanges">Save Changes</button></div></div>
  </div>
  </div>

<div class="modal hidden" id="deleteAccountModal"><div class="modal-content small-modal"><span class="close-btn" id="closeDeleteModal">&times;</span><h2>⚠️ Delete Account</h2><p>Are you sure you want to permanently delete your account? <br>This action cannot be undone.</p><div class="modal-buttons"><button class="cancel-btn" id="cancelDeleteBtn">Cancel</button><button type="button" class="delete-btn" id="confirmDeleteBtn">Delete</button></div></div></div>

<div id="enrollModal" class="modal hidden">
  <div class="modal-content">
    <span class="close-btn" id="closeEnrollModal">&times;</span>
    <h3>⚠️ Enrollment Confirmation</h3>
    <p>Are you sure you want to enroll in <strong id="enrollCourseName"></strong>?</p>
    
    <div id="verificationSection" class="verification-section hidden">
      <h4>Verification Required</h4>
      <p id="verificationMessage"></p>
      
      <div id="studentIdVerification" class="verification-field hidden">
        <label for="studentIdInput">Student ID:</label>
        <input type="text" id="studentIdInput" class="form-control" placeholder="Enter your Student ID">
        <div class="validation-error hidden" id="studentIdError">Please enter a valid Student ID</div>
      </div>
      
      <div id="emailVerification" class="verification-field hidden">
        <label for="emailVerificationInput">Email Address:</label>
        <input type="email" id="emailVerificationInput" class="form-control" placeholder="Enter your email address">
        <div class="validation-error hidden" id="emailVerificationError">Please verify your email address first</div>
      </div>
    </div>
    
    <div class="enrollment-info" id="enrollmentInfo">
      <p><strong>Important:</strong> By enrolling in this course, you agree to:</p>
      <ul>
        <li>Complete all required assignments and activities</li>
        <li>Follow the course schedule and deadlines</li>
        <li>Respect the learning environment and other participants</li>
        <li>Maintain academic integrity in all submissions</li>
      </ul>
    </div>
    
    <div class="modal-actions">
      <button id="cancelEnroll" class="cancel-btn">Cancel</button>
      <button id="confirmEnroll" class="confirm-btn">Yes, Enroll</button>
    </div>
  </div>
</div>

<div id="unenrollConfirmModal" class="modal hidden"><div class="modal-content small-modal"><span class="close-btn" id="closeUnenrollConfirmModal">&times;</span><h3>⚠️ Unenroll Confirmation</h3><p>Are you sure you want to unenroll from <strong id="unenrollConfirmCourseName"></strong>? Your progress in this course will be lost.</p><div class="modal-actions"><button id="cancelUnenrollConfirm" class="cancel-btn">Cancel</button><form id="unenrollForm" method="POST" action="../php/guest_unenroll.php" style="display:inline;"><input type="hidden" name="course_code" id="unenrollCourseCodeInput"><button id="confirmUnenroll" type="button" class="delete-btn">Yes, Unenroll</button></form></div></div></div>

<div class="modal hidden" id="activityModal"><div class="modal-content activity-modal-content"><div class="modal-header"><h2 class="modal-title" id="activityModalTitle">Activity Title</h2><button class="close-btn" id="closeActivityModal"><i class="fas fa-times"></i></button></div><div class="modal-body"><div class="activity-instructions"><div class="instructions-title"><i class="fas fa-info-circle"></i>Instructions</div><div class="instructions-content" id="activityInstructions"></div></div><div class="due-date"><i class="fas fa-calendar-alt"></i><span>Due: <strong id="activityDueDate">December 15, 2023</strong></span></div><div class="upload-section" id="uploadSection"><div class="upload-header"><i class="fas fa-upload"></i><span>Submit Your Work</span></div><div class="upload-area" id="uploadArea"><div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div><div class="upload-text">Click to upload your file</div><div class="upload-hint">Max file size: 10MB | Supported: PDF, DOC, DOCX, JPG, PNG</div><input type="file" id="activityFileInput" class="file-input" style="display:none;"></div><div class="file-preview hidden" id="activityFilePreview"></div><button class="submit-btn" id="activitySubmitBtn" disabled>Submit Assignment</button></div><div class="quiz-section hidden" id="quizSection"><div class="quiz-instructions"><p>This quiz contains 10 multiple-choice questions. You have 30 minutes to complete it.</p><button class="primary-btn" id="startQuizBtn">Start Quiz</button></div></div><div class="submission-success hidden" id="submissionSuccess"><i class="fas fa-check-circle"></i><span>Your assignment has been submitted successfully!</span></div></div></div></div>