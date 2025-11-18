let currentOpenModal = null;

function openModal(modal) {
  if (currentOpenModal) { closeModal(currentOpenModal); }
  modal.classList.remove('hidden');
  currentOpenModal = modal;
  document.body.classList.add('modal-open');
}

function closeModal(modal) {
  modal.classList.add('hidden');
  currentOpenModal = null;
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