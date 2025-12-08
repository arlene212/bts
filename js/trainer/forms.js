document.addEventListener('DOMContentLoaded', function() {
  const profileForm = document.getElementById('profileForm');
  if (profileForm) {
    profileForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(profileForm);
      const fileInput = document.getElementById('profileUpload');
      if (fileInput && fileInput.files[0]) { formData.append('profile_picture', fileInput.files[0]); }
      formData.append('action', 'update_profile');
      fetch('../trainer/handlers/ajax_handlers.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            const u = d.user || {};
            const name = [u.first_name, u.last_name].filter(Boolean).join(' ');
            const avatarUrl = u.profile_picture ? ('../uploads/profiles/' + u.profile_picture) : null;
            const nameEl = document.querySelector('.user-card .user-name');
            const avatarEl = document.querySelector('.user-card .user-avatar');
            const previewEl = document.getElementById('profilePreview');
            if (nameEl && name) nameEl.textContent = name;
            if (avatarUrl) {
              if (avatarEl) avatarEl.src = avatarUrl;
              if (previewEl) previewEl.src = avatarUrl;
            }
            closeModal('profileModal');
            alert('Profile updated successfully');
          } else { alert(d.message || 'Update failed'); }
        });
    });
  }
});
