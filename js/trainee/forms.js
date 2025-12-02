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
            const u = data.user || {};
            const nameEl = document.getElementById('userCardName');
            const avatarEl = document.getElementById('userCardAvatar');
            if (nameEl) nameEl.textContent = [u.first_name, u.last_name].filter(Boolean).join(' ');
            if (avatarEl && u.profile_picture) avatarEl.src = '../uploads/profiles/' + u.profile_picture + '?t=' + Date.now();
            alert('Profile updated successfully!');
            if (typeof closeModal === 'function') { closeModal('profileModal'); }
          } else { throw new Error(data.message || 'Failed to update profile'); }
        })
        .catch(err => { alert('Error updating profile: ' + err.message); })
        .finally(() => { saveBtn.disabled = false; saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes'; });
    });
  }
});
