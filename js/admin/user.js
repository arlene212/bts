function setupUserManagement() {
  // Reset password handled in ajax.js via showConfirm + ajaxRequest
  const editProfileForm = document.getElementById('editProfileForm');
  if (editProfileForm) {
    editProfileForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const form = this;
      const formData = new FormData(form);
      const fileInput = form.querySelector('#profile_picture');
      const submitBtn = form.querySelector('.submit-btn');
      const originalText = submitBtn ? submitBtn.textContent : '';
      if (fileInput && fileInput.files[0]) { formData.append('profile_picture', fileInput.files[0]); }
      if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Saving...'; }
      fetch('../php/update_profile.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            showAlert('success', 'Profile Updated', 'Your profile has been updated successfully.');
            setTimeout(() => { window.location.reload(); }, 800);
          } else {
            showAlert('error', 'Update Failed', d.message || 'An error occurred while updating your profile.');
          }
        })
        .catch(() => { showAlert('error', 'Update Failed', 'Network error occurred.'); })
        .finally(() => { if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = originalText; } });
    });
  }
  const editTrainerForm = document.getElementById('editTrainerForm');
  if (editTrainerForm) {
    editTrainerForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const form = this;
      const formData = new FormData(form);
      formData.append('user_role', 'trainer');
      fetch('../php/update_user.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => { if (data.success) { alert(data.message); window.location.reload(); } else { alert('Error updating trainer: ' + data.message); } })
        .catch(() => {});
    });
  }
  const editTraineeForm = document.getElementById('editTraineeForm');
  if (editTraineeForm) {
    editTraineeForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const form = this;
      const formData = new FormData(form);
      formData.append('user_role', 'trainee');
      fetch('../php/update_user.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => { if (data.success) { alert(data.message); window.location.reload(); } else { alert('Error updating trainee: ' + data.message); } })
        .catch(() => {});
    });
  }
  document.querySelectorAll('.edit-trainer-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const userId = this.getAttribute('data-user-id');
      const userDataStr = this.getAttribute('data-user-data');
      try {
        const userData = JSON.parse(userDataStr);
        document.getElementById('editTrainerId').value = userId;
        document.getElementById('edit_trainer_first_name').value = userData.first_name || '';
        document.getElementById('edit_trainer_last_name').value = userData.last_name || '';
        document.getElementById('edit_trainer_email').value = userData.email || '';
        document.getElementById('edit_trainer_contact').value = userData.contact_number || '';
        const coursesSelect = document.getElementById('edit_trainer_courses');
        Array.from(coursesSelect.options).forEach(option => option.selected = false);
        fetch(`../php/get_trainer_courses.php?trainer_id=${userId}`)
          .then(response => response.json())
          .then(assignedCourses => { assignedCourses.forEach(course => { const option = coursesSelect.querySelector(`option[value="${course.course_code}"]`); if (option) { option.selected = true; } }); })
          .catch(() => {});
        openModal('editTrainerModal');
      } catch (_) {}
    });
  });
  const editTrainerFirstName = document.getElementById('edit_trainer_first_name');
  const editTrainerLastName = document.getElementById('edit_trainer_last_name');
  const editTrainerEmail = document.getElementById('edit_trainer_email');
  if (editTrainerFirstName && editTrainerLastName && editTrainerEmail) {
    const updateTrainerEmail = () => {
      const firstName = editTrainerFirstName.value.trim().replace(/[^a-zA-Z]/g, '').toLowerCase();
      const lastName = editTrainerLastName.value.trim().replace(/[^a-zA-Z]/g, '').toLowerCase();
      if (firstName && lastName) { editTrainerEmail.value = `${lastName}.${firstName}@bts.gov.ph`; }
    };
    editTrainerFirstName.addEventListener('input', updateTrainerEmail);
    editTrainerLastName.addEventListener('input', updateTrainerEmail);
  }
  const editTraineeLastName = document.getElementById('edit_trainee_last_name');
  const editTraineeEmail = document.getElementById('edit_trainee_email');
  const editTraineeIdInput = document.getElementById('editTraineeId');
  document.querySelectorAll('.edit-trainee-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const userId = this.getAttribute('data-user-id');
      const userDataStr = this.getAttribute('data-user-data');
      try {
        const userData = JSON.parse(userDataStr);
        document.getElementById('editTraineeId').value = userId;
        document.getElementById('edit_trainee_first_name').value = userData.first_name || '';
        document.getElementById('edit_trainee_last_name').value = userData.last_name || '';
        document.getElementById('edit_trainee_email').value = userData.email || '';
        document.getElementById('edit_trainee_contact').value = userData.contact_number || '';
        const courseSelect = document.getElementById('edit_trainee_course');
        const batchSelect = document.getElementById('edit_trainee_batch');
        fetch(`../php/get_trainee_enrollments.php?trainee_id=${userId}`)
          .then(response => response.json())
          .then(enrollment => {
            courseSelect.value = enrollment.course_code || '';
            const event = new Event('change');
            courseSelect.dispatchEvent(event);
            setTimeout(() => { batchSelect.value = enrollment.batch_name || ''; }, 300);
          })
          .catch(() => {});
        openModal('editTraineeModal');
      } catch (_) {}
    });
  });
  if (editTraineeLastName && editTraineeEmail && editTraineeIdInput) {
    const updateTraineeEmail = () => {
      const lastName = editTraineeLastName.value.trim().replace(/[^a-zA-Z]/g, '').toLowerCase();
      const userId = editTraineeIdInput.value;
      if (lastName && userId) { editTraineeEmail.value = `${lastName}.${userId}@bts.gov.ph`; }
    };
    editTraineeLastName.addEventListener('input', updateTraineeEmail);
    new MutationObserver(updateTraineeEmail).observe(editTraineeIdInput, { attributes: true, childList: true, subtree: true });
  }
  const editTraineeCourseSelect = document.getElementById('edit_trainee_course');
  const editTraineeBatchSelect = document.getElementById('edit_trainee_batch');
  if (editTraineeCourseSelect && editTraineeBatchSelect) {
    editTraineeCourseSelect.addEventListener('change', function() {
      const courseCode = this.value;
      editTraineeBatchSelect.innerHTML = '<option value="">Select Batch</option>';
      if (courseCode) {
        fetch('../admin/handlers/get_batches.php?course_code=' + courseCode)
          .then(response => response.json())
          .then(batches => { batches.forEach(batch => { const option = new Option(batch.batch_name, batch.batch_name); editTraineeBatchSelect.add(option); }); });
      }
    });
  }
}

function setupGuestEnrollment() {
  document.querySelectorAll('.enroll-guest-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const userId = this.getAttribute('data-user-id');
      const userName = this.getAttribute('data-user-name');
      const enrollGuestId = document.getElementById('enrollGuestId');
      const enrollGuestName = document.getElementById('enrollGuestName');
      if (enrollGuestId && enrollGuestName) {
        enrollGuestId.value = userId;
        enrollGuestName.textContent = userName;
        openModal('enrollGuestModal');
      }
    });
  });
}