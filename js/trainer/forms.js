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
            alert('Profile updated successfully')
          } else { alert(d.message || 'Update failed'); }
        });
    });
  }
});
