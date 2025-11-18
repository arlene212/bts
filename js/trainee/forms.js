document.addEventListener('DOMContentLoaded', function() {
  const profileForm = document.getElementById('profileForm');
  const saveBtn = document.getElementById('saveProfileChanges');
  if (profileForm && saveBtn) {
    profileForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(profileForm);
      const fileInput = document.getElementById('profileUpload');
      if (fileInput && fileInput.files[0]) { formData.append('profile_picture', fileInput.files[0]); }
      saveBtn.disabled = true; saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
      formData.append('action', 'update_profile');
      fetch('../trainee/handlers/ajax_handlers.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Profile updated successfully!');
            setTimeout(() => { window.location.reload(); }, 800);
          } else { throw new Error(data.message || 'Failed to update profile'); }
        })
        .catch(err => { alert('Error updating profile: ' + err.message); })
        .finally(() => { saveBtn.disabled = false; saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes'; });
    });
  }
});