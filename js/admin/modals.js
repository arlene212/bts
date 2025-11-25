function showAlert(type, title, message) {
  const modal = document.getElementById('alertModal');
  const icon = document.getElementById('alertIcon');
  const titleEl = document.getElementById('alertTitle');
  const body = document.getElementById('alertBody');
  icon.className = 'alert-modal-icon ' + type;
  const icons = { success: 'fa-check-circle', error: 'fa-times-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
  icon.querySelector('i').className = 'fas ' + icons[type];
  titleEl.textContent = title;
  body.innerHTML = message;
  modal.style.display = 'block';
}
function closeAlertModal() { document.getElementById('alertModal').style.display = 'none'; }
function showConfirm(title, message, onConfirm) {
  const modal = document.getElementById('confirmModal');
  const titleEl = document.getElementById('confirmTitle');
  const body = document.getElementById('confirmBody');
  const confirmBtn = document.getElementById('confirmBtn');
  titleEl.textContent = title;
  body.innerHTML = message;
  const newConfirmBtn = confirmBtn.cloneNode(true);
  confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
  newConfirmBtn.addEventListener('click', () => { closeConfirmModal(); if (typeof onConfirm === 'function') { onConfirm(); } });
  modal.style.display = 'block';
}
function closeConfirmModal() { document.getElementById('confirmModal').style.display = 'none'; }
window.onclick = function(event) {
  const alertModal = document.getElementById('alertModal');
  const confirmModal = document.getElementById('confirmModal');
  if (event.target === alertModal) { closeAlertModal(); }
  if (event.target === confirmModal) { closeConfirmModal(); }
}
window.alert = function(message) { showAlert('info', 'Notice', message); };
