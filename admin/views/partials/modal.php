<?php ?>
<div id="alertModal" class="alert-modal">
  <div class="alert-modal-content">
    <div class="alert-modal-header">
      <div class="alert-modal-icon" id="alertIcon"><i class="fas fa-info-circle"></i></div>
      <h3 class="alert-modal-title" id="alertTitle">Alert</h3>
    </div>
    <div class="alert-modal-body" id="alertBody"></div>
    <div class="alert-modal-footer" id="alertFooter">
      <button class="alert-modal-btn alert-modal-btn-primary" onclick="closeAlertModal()">OK</button>
    </div>
  </div>
  </div>

<div id="confirmModal" class="alert-modal">
  <div class="alert-modal-content">
    <div class="alert-modal-header">
      <div class="alert-modal-icon warning"><i class="fas fa-exclamation-triangle"></i></div>
      <h3 class="alert-modal-title" id="confirmTitle">Confirm Action</h3>
    </div>
    <div class="alert-modal-body" id="confirmBody"></div>
    <div class="alert-modal-footer">
      <button class="alert-modal-btn alert-modal-btn-secondary" onclick="closeConfirmModal()">Cancel</button>
      <button class="alert-modal-btn alert-modal-btn-danger" id="confirmBtn">Confirm</button>
    </div>
  </div>
</div>

<div class="modal hidden" id="addAnnouncementModal">
  <div class="modal-content">
    <div class="modal-header"><h2>Add New Announcement</h2><span class="close">&times;</span></div>
    <form method="POST" id="announcementForm">
      <input type="hidden" name="current_tab" value="home">
      <div class="modal-body">
        <div class="form-group"><label for="announcement_title">Title:</label><input type="text" id="announcement_title" name="announcement_title" required></div>
        <div class="form-group"><label for="announcement_content">Content:</label><textarea id="announcement_content" name="announcement_content" rows="4" required></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="cancel-btn">Cancel</button><button type="submit" name="add_announcement" class="submit-btn">Post Announcement</button></div>
    </form>
  </div>
</div>

<div class="modal hidden" id="createTrainerModal">
  <div class="modal-content">
    <div class="modal-header"><h2>Create Trainer Account</h2><span class="close">&times;</span></div>
    <form method="POST" id="createTrainerForm">
      <input type="hidden" name="current_tab" value="trainers">
      <div class="modal-body">
        <div class="form-row"><div class="form-group"><label for="trainer_first_name">First Name:</label><input type="text" id="trainer_first_name" name="trainer_first_name" required></div><div class="form-group"><label for="trainer_middle_name">Middle Name:</label><input type="text" id="trainer_middle_name" name="trainer_middle_name"></div></div>
        <div class="form-row"><div class="form-group"><label for="trainer_last_name">Last Name:</label><input type="text" id="trainer_last_name" name="trainer_last_name" required></div><div class="form-group"><label for="trainer_suffix">Suffix:</label><input type="text" id="trainer_suffix" name="trainer_suffix"></div></div>
        <div class="form-group"><label for="trainer_number">Contact Number:</label><input type="tel" id="trainer_number" name="trainer_number" pattern="^(09\d{9}|\+639\d{9})$" title="Format: 09XXXXXXXXX or +639XXXXXXXXX" required></div>
        <div class="form-group"><label for="trainer_courses">Assign Courses (Multiple Selection):</label><select id="trainer_courses" name="trainer_courses[]" multiple style="height: 120px;"><?php foreach ($courses as $course): ?><option value="<?php echo $course['course_code']; ?>"><?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?></option><?php endforeach; ?></select><small>Hold Ctrl/Cmd to select multiple courses</small></div>
      </div>
      <div class="modal-footer"><button type="button" class="cancel-btn">Cancel</button><button type="submit" name="create_trainer" class="submit-btn">Create Trainer</button></div>
    </form>
  </div>
</div>

<div class="modal hidden" id="createTraineeModal">
  <div class="modal-content">
    <div class="modal-header"><h2>Create Trainee Account</h2><span class="close">&times;</span></div>
    <form method="POST" id="createTraineeForm">
      <input type="hidden" name="current_tab" value="trainees">
      <div class="modal-body">
        <div class="form-row"><div class="form-group"><label for="trainee_first_name">First Name:</label><input type="text" id="trainee_first_name" name="trainee_first_name" required></div><div class="form-group"><label for="trainee_middle_name">Middle Name:</label><input type="text" id="trainee_middle_name" name="trainee_middle_name"></div></div>
        <div class="form-row"><div class="form-group"><label for="trainee_last_name">Last Name:</label><input type="text" id="trainee_last_name" name="trainee_last_name" required></div><div class="form-group"><label for="trainee_suffix">Suffix:</label><input type="text" id="trainee_suffix" name="trainee_suffix"></div></div>
        <div class="form-group"><label for="trainee_number">Contact Number:</label><input type="tel" id="trainee_number" name="trainee_number" pattern="^(09\d{9}|\+639\d{9})$" title="Format: 09XXXXXXXXX or +639XXXXXXXXX" required></div>
        <div class="form-group"><label for="trainee_course">Enroll in Course (Optional):</label><select id="trainee_course" name="trainee_course"><option value="">Select a course</option><?php foreach ($courses as $course): ?><option value="<?php echo $course['course_code']; ?>"><?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label for="trainee_batch">Assign to Batch (Optional):</label><select id="trainee_batch" name="trainee_batch"><option value="">Select a batch</option></select></div>
      </div>
      <div class="modal-footer"><button type="button" class="cancel-btn">Cancel</button><button type="submit" name="create_trainee" class="submit-btn">Create Trainee</button></div>
    </form>
  </div>
</div>

<div class="modal hidden" id="addCourseModal">
  <div class="modal-content">
    <div class="modal-header"><h2>Add New Course</h2><span class="close">&times;</span></div>
    <form method="POST" enctype="multipart/form-data" id="addCourseForm">
      <input type="hidden" name="current_tab" value="courses">
      <div class="modal-body">
        <div class="form-group">
          <div class="form-group"><label for="course_name">Course Name:</label><input type="text" id="course_name" name="course_name" required></div>
          <div class="form-group"><label for="course_code">Course Code:</label><input type="text" id="course_code" name="course_code" required></div>
          <div class="form-group"><label for="course_hours">Course Hours:</label><input type="number" id="course_hours" name="course_hours" required min="1"></div>
          <div class="form-group"><label for="course_image">Course Image:</label><input type="file" id="course_image" name="course_image" accept="image/*"></div>
          <div class="form-group form-group-full"><label for="course_description">Description:</label><textarea id="course_description" name="course_description" rows="3"></textarea></div>
          <div class="form-group form-group-full"><label for="course_learning_outcomes">Learning Outcomes:</label><textarea id="course_learning_outcomes" name="course_learning_outcomes" rows="4" placeholder="Enter learning outcomes for this course (what students will be able to do after completing this course)"></textarea></div>
          <div class="form-row">
            <div class="form-group">
              <label for="course_status">Course Status:</label>
              <select id="course_status" name="course_status" class="form-control">
                <option value="draft">Draft</option>
                <option value="published" selected>Published</option>
                <option value="archived">Archived</option>
              </select>
            </div>
            <div class="form-group">
              <label for="allow_preview">Allow Preview:</label>
              <select id="allow_preview" name="allow_preview" class="form-control">
                <option value="0">No</option>
                <option value="1">Yes</option>
              </select>
            </div>
          </div>
          <div class="form-group form-group-full" id="preview_content_group" style="display:none;">
            <label for="course_preview_content">Preview Content:</label>
            <textarea id="course_preview_content" name="course_preview_content" rows="3" placeholder="Content to show in course preview"></textarea>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="require_verification">Require Verification:</label>
              <select id="require_verification" name="require_verification" class="form-control">
                <option value="0">No</option>
                <option value="1">Yes</option>
              </select>
              <small class="form-text">Require student ID or email verification for enrollment</small>
            </div>
            <div class="form-group">
              <label for="verification_type">Verification Type:</label>
              <select id="verification_type" name="verification_type" class="form-control">
                <option value="email">Email Verification</option>
                <option value="student_id">Student ID</option>
                <option value="phone">Phone Verification</option>
              </select>
              <small class="form-text">Type of verification required for enrollment</small>
            </div>
          </div>
        </div>
        <div class="competencies-section">
          <h3>Course Competencies</h3>
          <div class="competency-group" id="basicCompetenciesGroup"><div class="competency-header"><h4>Basic Competencies</h4><button type="button" class="add-competency-btn" data-type="basic">+ Add Another</button></div><div class="competency-fields"><div class="competency-field-group"><div class="form-group"><label>Name:</label><input type="text" name="basic_competency[]" placeholder="Enter basic competency name"></div><div class="form-group"><label>Description:</label><textarea name="basic_competency_desc[]" rows="2" placeholder="Enter description (optional)"></textarea></div></div></div></div>
          <div class="competency-group" id="commonCompetenciesGroup"><div class="competency-header"><h4>Common Competencies</h4><button type="button" class="add-competency-btn" data-type="common">+ Add Another</button></div><div class="competency-fields"><div class="competency-field-group"><div class="form-group"><label>Name:</label><input type="text" name="common_competency[]" placeholder="Enter common competency name"></div><div class="form-group"><label>Description:</label><textarea name="common_competency_desc[]" rows="2" placeholder="Enter description (optional)"></textarea></div></div></div></div>
          <div class="competency-group" id="coreCompetenciesGroup"><div class="competency-header"><h4>Core Competencies</h4><button type="button" class="add-competency-btn" data-type="core">+ Add Another</button></div><div class="competency-fields"><div class="competency-field-group"><div class="form-group"><label>Name:</label><input type="text" name="core_competency[]" placeholder="Enter core competency name"></div><div class="form-group"><label>Description:</label><textarea name="core_competency_desc[]" rows="2" placeholder="Enter description (optional)"></textarea></div></div></div></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="cancel-btn">Cancel</button><button type="submit" name="add_course" class="submit-btn">Add Course</button></div>
    </form>
  </div>
</div>

<div class="modal hidden" id="addBatchModal">
  <div class="modal-content"><div class="modal-header"><h2>Add Course Batch</h2><span class="close">&times;</span></div><form method="POST" id="addBatchForm"><input type="hidden" name="current_tab" value="courses"><div class="modal-body"><div class="form-group"><label for="batch_course_code">Course:</label><select id="batch_course_code" name="batch_course_code" required><option value="">Select a course</option><?php foreach ($courses as $course): ?><option value="<?php echo $course['course_code']; ?>"><?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?></option><?php endforeach; ?></select></div><div class="form-group"><label for="batch_name">Batch Name:</label><input type="text" id="batch_name" name="batch_name" required></div><div class="form-group"><label for="batch_description">Description:</label><textarea id="batch_description" name="batch_description" rows="3"></textarea></div></div><div class="modal-footer"><button type="button" class="cancel-btn">Cancel</button><button type="submit" name="add_course_batch" class="submit-btn">Add Batch</button></div></form></div>
</div>

<div class="modal hidden" id="editCourseModal">
  <div class="modal-content modal-content-large">
    <div class="modal-header">
      <div class="modal-header-content">
        <div class="modal-icon"><i class="fas fa-edit"></i></div>
        <div class="modal-title-section">
          <h2>Edit Course</h2>
          <p class="modal-subtitle">Update course details and competencies</p>
        </div>
      </div>
      <span class="close">&times;</span>
    </div>
    <form method="POST" enctype="multipart/form-data" id="editCourseForm">
      <input type="hidden" name="edit_course" value="1">
      <input type="hidden" id="edit_course_code" name="course_code">
      <input type="hidden" name="current_tab" value="courses">
      <div class="modal-body">
        <div class="form-section">
          <div class="section-header">
            <h4><i class="fas fa-info-circle"></i> Basic Information</h4>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="edit_course_name">Course Name <span class="required">*</span></label>
              <input type="text" id="edit_course_name" name="course_name" required class="form-control">
              <small class="form-text">Enter the full name of the course</small>
            </div>
            <div class="form-group">
              <label for="edit_course_code_display">Course Code</label>
              <input type="text" id="edit_course_code_display" readonly class="form-control readonly-input">
              <small class="form-text text-muted">Course code cannot be changed</small>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="edit_course_hours">Course Hours <span class="required">*</span></label>
              <input type="number" id="edit_course_hours" name="course_hours" required min="1" class="form-control">
              <small class="form-text">Total duration in hours</small>
            </div>
            <div class="form-group">
              <label for="edit_course_image">Course Image</label>
              <div class="file-input-wrapper">
                <input type="file" id="edit_course_image" name="course_image" accept="image/*" class="form-control">
                <label for="edit_course_image" class="file-input-label">
                  <i class="fas fa-upload"></i> Choose Image
                </label>
              </div>
              <small class="form-text text-muted">Leave blank to keep current image</small>
            </div>
          </div>
          <div class="form-group">
            <label for="edit_course_description">Course Description</label>
            <textarea id="edit_course_description" name="course_description" rows="4" class="form-control" placeholder="Enter course description..."></textarea>
            <small class="form-text">Provide a detailed description of the course</small>
          </div>
          <div class="form-group">
            <label for="edit_course_learning_outcomes">Learning Outcomes</label>
            <textarea id="edit_course_learning_outcomes" name="course_learning_outcomes" rows="4" class="form-control" placeholder="What students will be able to do after completing this course..."></textarea>
            <small class="form-text">List the learning outcomes students will achieve</small>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="edit_course_status">Course Status</label>
              <select id="edit_course_status" name="course_status" class="form-control">
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="archived">Archived</option>
              </select>
              <small class="form-text">Control course visibility and availability</small>
            </div>
            <div class="form-group">
              <label for="edit_allow_preview">Allow Preview</label>
              <select id="edit_allow_preview" name="allow_preview" class="form-control">
                <option value="0">No</option>
                <option value="1">Yes</option>
              </select>
              <small class="form-text">Allow users to preview course content</small>
            </div>
          </div>
          <div class="form-group" id="edit_preview_content_group" style="display:none;">
            <label for="edit_course_preview_content">Preview Content</label>
            <textarea id="edit_course_preview_content" name="course_preview_content" rows="3" class="form-control" placeholder="Content to show in course preview"></textarea>
            <small class="form-text">Content shown when preview is enabled</small>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="edit_require_verification">Require Verification:</label>
              <select id="edit_require_verification" name="require_verification" class="form-control">
                <option value="0">No</option>
                <option value="1">Yes</option>
              </select>
              <small class="form-text">Require student ID or email verification for enrollment</small>
            </div>
            <div class="form-group">
              <label for="edit_verification_type">Verification Type:</label>
              <select id="edit_verification_type" name="verification_type" class="form-control">
                <option value="email">Email Verification</option>
                <option value="student_id">Student ID</option>
                <option value="phone">Phone Verification</option>
              </select>
              <small class="form-text">Type of verification required for enrollment</small>
            </div>
          </div>
        </div>

        <div class="form-section">
          <div class="section-header">
            <h4><i class="fas fa-tasks"></i> Course Competencies</h4>
            <button type="button" id="add_new_competency_btn" class="add-competency-btn">
              <i class="fas fa-plus"></i> Add Competency
            </button>
          </div>
          <div id="edit_competencies_container" class="competencies-grid"></div>
          <div class="competency-help-text">
            <small class="text-muted">
              <i class="fas fa-info-circle"></i> Add competencies that students will achieve upon completing this course
            </small>
          </div>
        </div>

        <div class="image-preview-section" id="current_image_preview"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="cancel-btn">
          <i class="fas fa-times"></i> Cancel
        </button>
        <button type="submit" name="edit_course" class="submit-btn">
          <i class="fas fa-save"></i> Update Course
        </button>
      </div>
    </form>
  </div>
</div>

<div class="modal hidden" id="resetPasswordModal">
  <div class="modal-content"><div class="modal-header"><h2>Reset Password</h2><span class="close">&times;</span></div><form method="POST" id="resetPasswordForm"><input type="hidden" name="current_tab" value="<?php echo $currentTab; ?>"><div class="modal-body"><p>Are you sure you want to reset the password for <strong id="resetUserName"></strong>?</p><p>A new temporary password will be generated and shown to you.</p><input type="hidden" name="user_id" id="resetUserId"></div><div class="modal-footer"><button type="button" class="cancel-btn">Cancel</button><button type="submit" name="reset_password" class="submit-btn">Reset Password</button></div></form></div>
</div>

<div class="modal hidden" id="editTrainerModal">
  <div class="modal-content"><div class="modal-header"><h2>Edit Trainer Account</h2><span class="close">&times;</span></div><form id="editTrainerForm"><input type="hidden" name="user_id" id="editTrainerId"><input type="hidden" name="current_tab" value="trainers"><div class="modal-body"><div class="form-group"><label for="edit_trainer_first_name">First Name:</label><input type="text" id="edit_trainer_first_name" name="first_name" required></div><div class="form-group"><label for="edit_trainer_last_name">Last Name:</label><input type="text" id="edit_trainer_last_name" name="last_name" required></div><div class="form-group"><label for="edit_trainer_email">Email:</label><input type="email" id="edit_trainer_email" name="email" required readonly style="background-color: #f5f5f5;"><small class="email-preview-note">Email will automatically update based on name changes</small></div><div class="form-group"><label for="edit_trainer_contact">Contact Number:</label><input type="tel" id="edit_trainer_contact" name="contact_number" pattern="^(09\d{9}|\+639\d{9})$" title="Format: 09XXXXXXXXX or +639XXXXXXXXX"></div><div class="form-group"><label for="edit_trainer_courses">Assigned Courses:</label><select id="edit_trainer_courses" name="trainer_courses[]" multiple style="height: 120px;"><?php foreach ($courses as $course): ?><option value="<?php echo $course['course_code']; ?>"><?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?></option><?php endforeach; ?></select><small>Hold Ctrl/Cmd to select multiple courses</small></div><div class="form-group" style="width: max-content;"><label>Assign Batches (max 2):</label><div id="edit_trainer_batches_container" class="batch-checkboxes"></div><small class="field-note">Select up to two batches for the selected course</small></div></div><div class="modal-footer"><button type="button" class="cancel-btn">Cancel</button><button type="submit" name="edit_trainer_courses" class="submit-btn">Update Trainer</button></div></form></div>
</div>

  <div class="modal hidden" id="editTraineeModal">
  <div class="modal-content"><div class="modal-header"><h2>Edit Trainee Account</h2><span class="close">&times;</span></div><form id="editTraineeForm"><input type="hidden" name="user_id" id="editTraineeId"><input type="hidden" name="current_tab" value="trainees"><div class="modal-body"><div class="form-group"><label for="edit_trainee_first_name">First Name:</label><input type="text" id="edit_trainee_first_name" name="first_name" required></div><div class="form-group"><label for="edit_trainee_last_name">Last Name:</label><input type="text" id="edit_trainee_last_name" name="last_name" required></div><div class="form-group"><label for="edit_trainee_email">Email:</label><input type="email" id="edit_trainee_email" name="email" required readonly style="background-color: #f5f5f5;"><small class="email-preview-note">Email will automatically update based on name changes</small></div><div class="form-group"><label for="edit_trainee_contact">Contact Number:</label><input type="tel" id="edit_trainee_contact" name="contact_number" pattern="^(09\d{9}|\+639\d{9})$" title="Format: 09XXXXXXXXX or +639XXXXXXXXX"></div><div class="form-group"><label for="edit_trainee_course">Course Enrollment:</label><select id="edit_trainee_course" name="course_code"><option value="">Select Course</option><?php foreach ($courses as $course): ?><option value="<?php echo $course['course_code']; ?>"><?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?></option><?php endforeach; ?></select></div><div class="form-group"><label for="edit_trainee_batch">Batch Assignment:</label><select id="edit_trainee_batch" name="batch_name"><option value="">Select Batch</option></select></div><div class="form-group"><label for="edit_trainee_enrollment_status">Enrollment Status:</label><select id="edit_trainee_enrollment_status" name="enrollment_status"><option value="active">Active</option><option value="dropped">Dropped</option><option value="graduated">Graduated</option></select><small class="field-note">Setting Dropped or Graduated will be visible to the assigned trainer</small></div></div><div class="modal-footer"><button type="button" class="cancel-btn">Cancel</button><button type="submit" name="edit_trainee_enrollment" class="submit-btn">Update Trainee</button></div></form></div>
  </div>

<div class="modal hidden" id="viewCourseDetailModal">
  <div class="modal-content large"><div class="modal-header"><h2 id="viewCourseDetailTitle">Course Details</h2><span class="close">&times;</span></div><div class="modal-body" id="viewCourseDetailBody"></div><div class="modal-footer"><button type="button" class="cancel-btn">Close</button></div></div>
</div>

<div class="modal hidden" id="editProfileModal">
  <div class="modal-content"><div class="modal-header"><h2>Edit Profile</h2><span class="close">&times;</span></div><form method="POST" enctype="multipart/form-data" id="editProfileForm"><input type="hidden" name="current_tab" value="home"><div class="modal-body"><div class="profile-picture-section"><div class="current-profile-picture"><img src="<?php echo (!empty($adminProfile['profile_picture']) && $adminProfile['profile_picture'] !== 'default.png') ? '../uploads/profiles/' . $adminProfile['profile_picture'] . '?t=' . time() : '../images/school.png'; ?>" alt="Current Profile Picture" id="currentProfilePicture"></div><div class="form-group"><label for="profile_picture">Change Profile Picture:</label><input type="file" id="profile_picture" name="profile_picture" accept="image/*"><small>Max file size: 2MB. Supported formats: JPG, PNG, GIF</small></div></div><div class="form-row"><div class="form-group"><label for="first_name">First Name:</label><input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($adminProfile['first_name']); ?>" required></div><div class="form-group"><label for="middle_name">Middle Name:</label><input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($adminProfile['middle_name'] ?? ''); ?>"></div></div><div class="form-row"><div class="form-group"><label for="last_name">Last Name:</label><input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($adminProfile['last_name']); ?>" required></div><div class="form-group"><label for="suffix">Suffix:</label><input type="text" id="suffix" name="suffix" value="<?php echo htmlspecialchars($adminProfile['suffix'] ?? ''); ?>"></div></div><div class="form-group"><label for="contact_number">Contact Number:</label><input type="tel" id="contact_number" name="contact_number" pattern="^(09\d{9}|\+639\d{9})$" title="Format: 09XXXXXXXXX or +639XXXXXXXXX" value="<?php echo htmlspecialchars($adminProfile['contact_number'] ?? ''); ?>"></div><div class="form-group"><label for="email">Email (read-only):</label><input type="email" id="email" name="email" value="<?php echo htmlspecialchars($adminProfile['email']); ?>" readonly></div></div><div class="modal-footer"><button type="button" class="cancel-btn">Cancel</button><button type="submit" name="update_profile" class="submit-btn">Save Changes</button></div></form></div>
</div>

<div class="modal hidden" id="accountConfirmationModal">
  <div class="modal-content"><div class="modal-header"><h2 id="accountConfirmationTitle">Account Creation Confirmation</h2><span class="close">&times;</span></div><div class="modal-body"><div id="accountConfirmationContent"></div><div id="duplicateWarnings" class="warning-box hidden"></div></div><div class="modal-footer"><button class="cancel-btn">Cancel</button><button id="confirmAccountBtn" class="confirm-btn">Confirm Creation</button></div></div>
</div>

<div class="modal hidden" id="enrollGuestModal">
  <div class="modal-content"><div class="modal-header"><h2>Enroll Guest in Course</h2><span class="close">&times;</span></div><form method="POST" id="enrollGuestForm"><input type="hidden" name="current_tab" value="guests"><div class="modal-body"><input type="hidden" name="guest_id" id="enrollGuestId"><p>Enrolling: <strong id="enrollGuestName"></strong></p><div class="form-group"><label for="enroll_course_code">Select Course:</label><select id="enroll_course_code" name="enroll_course_code" required><option value="">Select a course</option><?php foreach ($courses as $course): ?><option value="<?php echo $course['course_code']; ?>"><?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?></option><?php endforeach; ?></select></div></div><div class="modal-footer"><button type="button" class="cancel-btn">Cancel</button><button type="submit" name="guest_enroll" class="submit-btn">Enroll Guest</button></div></form></div>
</div>

<div id="resetPasswordConfirmationModal" class="modal hidden">
  <div class="modal-content">
    <div class="modal-header"><h2>Password Reset Successful</h2><span class="close">&times;</span></div>
    <div class="modal-body">
      <div class="confirmation-details">
        <h3>New Temporary Password:</h3>
        <div id="resetPasswordConfirmationContent"></div>
        <div class="confirmation-note"><p><em>Please save this password as it will not be shown again.</em></p></div>
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="submit-btn" onclick="closeModal('resetPasswordConfirmationModal')">OK</button></div>
  </div>
</div>