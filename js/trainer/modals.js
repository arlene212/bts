function __resolveModal(m) { return typeof m === 'string' ? document.getElementById(m) : m; }
function openModal(modalArg) { const modal = __resolveModal(modalArg); if (modal) { modal.classList.remove('hidden'); modal.style.display = 'flex'; } }
function closeModal(modalArg) { const modal = __resolveModal(modalArg); if (modal) { modal.style.display = 'none'; modal.classList.add('hidden'); } }

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
function showAlertModal(message) { const modal = document.createElement('div'); modal.className = 'modal'; modal.style.display = 'flex'; modal.innerHTML = '<div class="modal-content"><div class="modal-header"><h2>Notice</h2><span class="close">&times;</span></div><div class="modal-body"><p>' + String(message) + '</p></div><div class="modal-footer"><button class="cancel-btn">OK</button></div></div>'; document.body.appendChild(modal); const closeBtn = modal.querySelector('.close'); const okBtn = modal.querySelector('.cancel-btn'); const close = function() { modal.style.opacity = '0'; setTimeout(function(){ modal.remove(); }, 200); }; if (closeBtn) closeBtn.addEventListener('click', close); if (okBtn) okBtn.addEventListener('click', close); modal.addEventListener('click', function(e){ if (e.target === modal) close(); }); }
window.alert = function(message) { showAlertModal(message); };
