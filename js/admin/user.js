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
      const checkedBatches = Array.from(document.querySelectorAll('#edit_trainer_batches_container input[type="checkbox"]:checked')).map(cb => cb.value);
      if (checkedBatches.length) { formData.append('trainer_batches', checkedBatches.join(', ')); }
      fetch('../php/update_user.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => { if (data.success) { alert(data.message); } else { alert('Error updating trainer: ' + data.message); } })
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
      if (typeof showConfirm === 'function') {
        showConfirm('Confirm Update', 'Save changes to this trainee?', () => {
          fetch('../php/update_user.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => { if (data.success) { showAlert('success', 'Trainee Updated', 'Changes saved successfully.'); } else { alert('Error updating trainee: ' + (data.message || 'Unknown error')); } })
            .catch(() => { alert('Network error while updating trainee'); });
        });
      } else {
        showConfirm('Confirm Update', 'Save changes to this trainee?', () => {
          fetch('../php/update_user.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => { if (data.success) { showAlert('success', 'Trainee Updated', 'Changes saved successfully.'); } else { alert('Error updating trainee: ' + (data.message || 'Unknown error')); } })
            .catch(() => { alert('Network error while updating trainee'); });
        });
      }
    });
  }
  document.querySelectorAll('.edit-trainer-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const userId = this.getAttribute('data-user-id');
      const userDataStr = this.getAttribute('data-user-data');
      const existingBatchesStr = this.getAttribute('data-trainer-batches') || '';
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
          .then(assignedCourses => {
            assignedCourses.forEach(course => {
              const option = coursesSelect.querySelector(`option[value="${course.course_code}"]`);
              if (option) { option.selected = true; }
            });
            const batchSet = new Set();
            assignedCourses.forEach(course => {
              if (course.batch_name) {
                String(course.batch_name).split(',').forEach(b => { const t = b.trim(); if (t) batchSet.add(t); });
              }
            });
            window.__trainerExistingBatches = Array.from(batchSet);
            populateTrainerBatchesFromSelection();
          })
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
            const statusSelect = document.getElementById('edit_trainee_enrollment_status');
            if (statusSelect) { statusSelect.value = enrollment.enrollment_status || 'active'; }
          })
          .catch(() => {});
        openModal('editTraineeModal');
      } catch (_) {}
    });
  });

  document.querySelectorAll('.reenroll-trainee-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const userId = this.getAttribute('data-user-id');
      const form = document.getElementById('editTraineeForm');
      if (!form) return;
      document.getElementById('editTraineeId').value = userId;
      // Reset fields for fresh reenrollment
      const courseSelect = document.getElementById('edit_trainee_course');
      const batchSelect = document.getElementById('edit_trainee_batch');
      const statusSelect = document.getElementById('edit_trainee_enrollment_status');
      if (courseSelect) courseSelect.value = '';
      if (batchSelect) batchSelect.innerHTML = '<option value="">Select Batch</option>';
      if (statusSelect) statusSelect.value = 'active';
      openModal('editTraineeModal');
    });
  });

  document.querySelectorAll('.undrop-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      const fd = new FormData(this);
      if (typeof showConfirm === 'function') {
        showConfirm('Confirm Undrop', 'Undrop this trainee and set status to Active?', () => {
          fetch('../php/update_user.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => { if (d.success) { showAlert('success', 'Undropped', 'Trainee status set to Active.'); } else { alert(d.message || 'Failed to undrop trainee'); } })
            .catch(() => { alert('Network error while undropping trainee'); });
        });
      } else {
        showConfirm('Confirm Undrop', 'Undrop this trainee and set status to Active?', () => {
          fetch('../php/update_user.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => { if (d.success) { showAlert('success', 'Undropped', 'Trainee status set to Active.'); } else { alert(d.message || 'Failed to undrop trainee'); } })
            .catch(() => { alert('Network error while undropping trainee'); });
        });
      }
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

function populateTrainerBatchesFromSelection() {
  const container = document.getElementById('edit_trainer_batches_container');
  const coursesSelect = document.getElementById('edit_trainer_courses');
  if (!container || !coursesSelect) return;
  const selected = Array.from(coursesSelect.selectedOptions).map(o => o.value);
  const primaryCourseCode = selected[0] || '';
  container.innerHTML = '';
  if (!primaryCourseCode) return;
  fetch('../admin/handlers/get_batches.php?course_code=' + primaryCourseCode)
    .then(response => response.json())
    .then(batches => {
      let count = 0;
      batches.forEach(batch => {
        const id = 'trainer_batch_' + batch.batch_name.replace(/\s+/g, '_');
        const label = document.createElement('label');
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.value = batch.batch_name;
        checkbox.id = id;
        const prechecked = Array.isArray(window.__trainerExistingBatches) && window.__trainerExistingBatches.includes(batch.batch_name);
        checkbox.checked = prechecked;
        checkbox.addEventListener('change', function() {
          const checked = container.querySelectorAll('input[type="checkbox"]:checked').length;
          container.querySelectorAll('input[type="checkbox"]').forEach(cb => { cb.disabled = checked >= 2 && !cb.checked; });
        });
        const text = document.createTextNode(batch.batch_name);
        label.appendChild(checkbox);
        label.appendChild(text);
        container.appendChild(label);
        count++;
      });
      const checkedAfter = container.querySelectorAll('input[type="checkbox"]:checked').length;
      if (checkedAfter > 2) {
        let kept = 0;
        container.querySelectorAll('input[type="checkbox"]').forEach(cb => {
          if (cb.checked) {
            kept++;
            if (kept > 2) cb.checked = false;
          }
        });
      }
      const finalChecked = container.querySelectorAll('input[type="checkbox"]:checked').length;
      container.querySelectorAll('input[type="checkbox"]').forEach(cb => { cb.disabled = finalChecked >= 2 && !cb.checked; });
    });
}

document.addEventListener('change', function(e) {
  if (e.target && e.target.id === 'edit_trainer_courses') { populateTrainerBatchesFromSelection(); }
});
