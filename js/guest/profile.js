function validateProfileForm() {
  let isValid = true;
  document.querySelectorAll('.validation-error').forEach(error => { error.classList.add('hidden'); });
  const email = document.getElementById('email');
  const emailError = document.getElementById('emailError');
  if (email && emailError) { const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; if (!emailRegex.test(email.value)) { emailError.classList.remove('hidden'); isValid = false; } }
  const newPassword = document.getElementById('newPassword');
  const confirmPassword = document.getElementById('confirmPassword');
  const passwordError = document.getElementById('passwordError');
  if (newPassword && confirmPassword && passwordError) { if (newPassword.value !== confirmPassword.value) { passwordError.classList.remove('hidden'); isValid = false; } }
  const contactNumber = document.getElementById('contactNumber');
  const contactError = document.getElementById('contactError');
  if (contactNumber && contactError && contactNumber.value.trim() !== '') {
    const phoneRegex = /^(09\d{9}|\+639\d{9})$/;
    if (!phoneRegex.test(contactNumber.value.trim())) {
      contactError.classList.remove('hidden');
      isValid = false;
    }
  }
  return isValid;
}

function saveProfileData() {
  const form = document.getElementById('profileForm');
  const formData = new FormData(form);
  const profilePictureFile = document.getElementById('profileUpload').files[0];
  if (profilePictureFile) { formData.append('profile_picture', profilePictureFile); }
  const saveBtn = document.getElementById('saveProfileChanges');
  saveBtn.disabled = true; saveBtn.textContent = 'Saving...';
  formData.append('action', 'update_profile');
  fetch('../guest/handlers/ajax_handlers.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showNotification(data.message, 'success');
        if (data.user) {
          const nameEl = document.querySelector('.user-name');
          if (nameEl && data.user.first_name && data.user.last_name) {
            nameEl.textContent = `${data.user.first_name} ${data.user.last_name}`;
          }
          const avatarEl = document.querySelector('.user-avatar');
          if (avatarEl) {
            if (data.user.profile_picture) {
              avatarEl.src = `../uploads/profiles/${data.user.profile_picture}`;
            }
          }
          const previewEl = document.getElementById('profilePreview');
          if (previewEl && data.user.profile_picture) {
            previewEl.src = `../uploads/profiles/${data.user.profile_picture}`;
          }
        }
      } else { showNotification(data.message, 'error'); }
    })
    .catch(() => { showNotification('An error occurred while updating your profile.', 'error'); })
    .finally(() => { saveBtn.disabled = false; saveBtn.textContent = 'Save Changes'; });
}

function setupProfileModal() {
  const openDeleteModal = document.getElementById('openDeleteModal');
  const deleteAccountModal = document.getElementById('deleteAccountModal');
  const closeDeleteModalBtn = document.getElementById('closeDeleteModal');
  const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
  const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  const editProfileBtn = document.getElementById('editProfileBtn');
  const profileModal = document.getElementById('profileModal');
  const closeProfileModalBtn = document.getElementById('closeProfileModal');
  const cancelProfileChangesBtn = document.getElementById('cancelProfileChanges');
  const saveProfileChangesBtn = document.getElementById('saveProfileChanges');
  if (editProfileBtn) editProfileBtn.addEventListener('click', () => { openModal(profileModal); const firstInput = document.getElementById('firstName'); if (firstInput) { setTimeout(() => firstInput.focus(), 50); } });
  if (closeProfileModalBtn) closeProfileModalBtn.addEventListener('click', () => closeModal(profileModal));
  if (cancelProfileChangesBtn) cancelProfileChangesBtn.addEventListener('click', () => closeModal(profileModal));
  if (saveProfileChangesBtn) { saveProfileChangesBtn.addEventListener('click', function() { if (validateProfileForm()) { saveProfileData(); } }); }
  if (openDeleteModal && deleteAccountModal) openDeleteModal.addEventListener('click', () => openModal(deleteAccountModal));
  if (closeDeleteModalBtn && deleteAccountModal) closeDeleteModalBtn.addEventListener('click', () => closeModal(deleteAccountModal));
  if (cancelDeleteBtn && deleteAccountModal) cancelDeleteBtn.addEventListener('click', () => closeModal(deleteAccountModal));
  if (confirmDeleteBtn) { confirmDeleteBtn.addEventListener('click', function() { showNotification('Account deletion feature would be implemented with backend integration', 'info'); closeModal(deleteAccountModal); }); }
  const profileUpload = document.getElementById('profileUpload');
  const changeProfileBtn = document.getElementById('changeProfileBtn');
  const profilePreview = document.getElementById('profilePreview');
  if (changeProfileBtn && profileUpload) { changeProfileBtn.addEventListener('click', function() { profileUpload.click(); }); }
  if (profileUpload) { profileUpload.addEventListener('change', function(e) { const file = e.target.files[0]; if (file) { const reader = new FileReader(); reader.onload = function(ev) { if (profilePreview) { profilePreview.src = ev.target.result; } }; reader.readAsDataURL(file); } }); }
}
