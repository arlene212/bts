function openModal(modal) { if (modal) { modal.classList.remove('hidden'); modal.style.display = 'flex'; document.body.style.overflow = 'hidden'; } }
function closeModal(modal) { if (modal) { modal.style.display = 'none'; modal.classList.add('hidden'); document.body.style.overflow = ''; } }
document.addEventListener('DOMContentLoaded', function() {
  const editProfileBtn = document.getElementById('editProfileBtn');
  const profileModal = document.getElementById('profileModal');
  const closeProfileModalBtn = document.getElementById('closeProfileModal');
  const cancelProfileChangesBtn = document.getElementById('cancelProfileChanges');
  if (editProfileBtn && profileModal) { editProfileBtn.addEventListener('click', function() { openModal(profileModal); }); }
  if (closeProfileModalBtn && profileModal) { closeProfileModalBtn.addEventListener('click', function() { closeModal(profileModal); }); }
  if (cancelProfileChangesBtn && profileModal) { cancelProfileChangesBtn.addEventListener('click', function() { closeModal(profileModal); }); }
});