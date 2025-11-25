let currentOpenModal = null;

function openModal(modal) {
  if (currentOpenModal) { closeModal(currentOpenModal); }
  modal.classList.remove('hidden');
  currentOpenModal = modal;

  document.body.classList.add('modal-open');
  document.body.classList.add('modal-open');
}

function closeModal(modal) {
  modal.classList.add('hidden');
  currentOpenModal = null;
}

function showAlertModal(message) {
  const modal = document.createElement('div');
  modal.className = 'modal';
  modal.style.display = 'flex';
  modal.innerHTML = '<div class="modal-content"><div class="modal-header"><h2>Notice</h2><span class="close">&times;</span></div><div class="modal-body"><p>' + String(message) + '</p></div><div class="modal-footer"><button class="cancel-btn">OK</button></div></div>';
  document.body.appendChild(modal);
  const closeBtn = modal.querySelector('.close');
  const okBtn = modal.querySelector('.cancel-btn');
  const close = function() { modal.style.opacity = '0'; setTimeout(function(){ modal.remove(); }, 200); };
  if (closeBtn) closeBtn.addEventListener('click', close);
  if (okBtn) okBtn.addEventListener('click', close);
  modal.addEventListener('click', function(e){ if (e.target === modal) close(); });


window.alert = function(message) { showAlertModal(message); };
  document.body.classList.remove('modal-open');
}

function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.className = `notification ${type}`;
  notification.style.cssText = 'position: fixed; top: 20px; right: 20px; padding: 12px 20px; border-radius: 4px; color: white; z-index: 10000; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);';
  notification.style.background = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6';
  notification.innerHTML = `<div style="display: flex; align-items: center; gap: 8px;"><i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i><span>${message}</span></div>`;
  document.body.appendChild(notification);
  setTimeout(() => { notification.remove(); }, 3000);
}
