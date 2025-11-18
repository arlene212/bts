function openModal(modal) { if (modal) { modal.classList.remove('hidden'); modal.style.display = 'flex'; document.body.style.overflow = 'hidden'; } }
function closeModal(modal) { if (modal) { modal.style.display = 'none'; modal.classList.add('hidden'); document.body.style.overflow = ''; } }

document.addEventListener('DOMContentLoaded', function() {
  const editProfileBtn = document.getElementById('editProfileBtn');
  const profileModal = document.getElementById('profileModal');
  const closeProfileModalBtn = document.getElementById('closeProfileModal');
  const cancelProfileChangesBtn = document.getElementById('cancelProfileChanges');
  const changeProfileBtn = document.getElementById('changeProfileBtn');
  const profileUpload = document.getElementById('profileUpload');
  const profilePreview = document.getElementById('profilePreview');
  if (editProfileBtn && profileModal) { editProfileBtn.addEventListener('click', function() { openModal(profileModal); }); }
  if (closeProfileModalBtn && profileModal) { closeProfileModalBtn.addEventListener('click', function() { closeModal(profileModal); }); }
  if (cancelProfileChangesBtn && profileModal) { cancelProfileChangesBtn.addEventListener('click', function() { closeModal(profileModal); }); }
  if (changeProfileBtn && profileUpload) { changeProfileBtn.addEventListener('click', function() { profileUpload.click(); }); }
  if (profileUpload) { profileUpload.addEventListener('change', function(e) { const file = e.target.files[0]; if (file) { const reader = new FileReader(); reader.onload = function(ev) { if (profilePreview) profilePreview.src = ev.target.result; }; reader.readAsDataURL(file); } }); }
});