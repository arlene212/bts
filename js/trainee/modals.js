function openModal(modalOrId) { const modal = typeof modalOrId === 'string' ? document.getElementById(modalOrId) : modalOrId; if (modal) { modal.classList.remove('hidden'); modal.style.display = 'flex'; document.body.style.overflow = 'hidden'; } }
function closeModal(modalOrId) { const modal = typeof modalOrId === 'string' ? document.getElementById(modalOrId) : modalOrId; if (modal) { modal.style.display = 'none'; modal.classList.add('hidden'); document.body.style.overflow = ''; } }
document.addEventListener('DOMContentLoaded', function() {
  const editProfileBtn = document.getElementById('editProfileBtn');
  const profileModal = document.getElementById('profileModal');
  const closeProfileModalBtn = document.getElementById('closeProfileModal');
  const cancelProfileChangesBtn = document.getElementById('cancelProfileChanges');
  if (editProfileBtn && profileModal) { editProfileBtn.addEventListener('click', function() { openModal(profileModal); }); }
  if (closeProfileModalBtn && profileModal) { closeProfileModalBtn.addEventListener('click', function() { closeModal(profileModal); }); }
  if (cancelProfileChangesBtn && profileModal) { cancelProfileChangesBtn.addEventListener('click', function() { closeModal(profileModal); }); }
});
function showAlertModal(message) { const modal = document.createElement('div'); modal.className = 'modal'; modal.style.display = 'flex'; modal.innerHTML = '<div class="modal-content"><div class="modal-header"><h2>Notice</h2><span class="close">&times;</span></div><div class="modal-body"><p>' + String(message) + '</p></div><div class="modal-footer"><button class="cancel-btn">OK</button></div></div>'; document.body.appendChild(modal); const closeBtn = modal.querySelector('.close'); const okBtn = modal.querySelector('.cancel-btn'); const close = function() { modal.style.opacity = '0'; setTimeout(function(){ modal.remove(); }, 200); }; if (closeBtn) closeBtn.addEventListener('click', close); if (okBtn) okBtn.addEventListener('click', close); modal.addEventListener('click', function(e){ if (e.target === modal) close(); }); }
window.alert = function(message) { showAlertModal(message); };
