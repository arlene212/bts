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
function closeAlertModal() { document.getElementById('alertModal').style.display = 'none'; window.location.reload(); }
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
function showRemarks(title, label, placeholder, onConfirm) {
  const modal = document.getElementById('remarksModal');
  const titleEl = document.getElementById('remarksTitle');
  const labelEl = document.getElementById('remarksLabel');
  const inputEl = document.getElementById('remarksInput');
  const confirmBtn = document.getElementById('remarksConfirmBtn');
  titleEl.textContent = title || 'Add Remarks';
  if (labelEl) labelEl.textContent = label || 'Enter remarks (optional):';
  if (inputEl) { inputEl.value = ''; inputEl.placeholder = placeholder || ''; }
  if (inputEl) { try { inputEl.focus(); inputEl.selectionStart = inputEl.value.length; inputEl.selectionEnd = inputEl.value.length; } catch (_) {} }
  const newConfirmBtn = confirmBtn.cloneNode(true);
  confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
  newConfirmBtn.addEventListener('click', () => { const val = inputEl ? inputEl.value : ''; closeRemarksModal(); if (typeof onConfirm === 'function') { onConfirm(val); } });
  modal.style.display = 'block';
}
function closeRemarksModal() { document.getElementById('remarksModal').style.display = 'none'; }
window.onclick = function(event) {
  const alertModal = document.getElementById('alertModal');
  const confirmModal = document.getElementById('confirmModal');
  const remarksModal = document.getElementById('remarksModal');
  if (event.target === alertModal) { closeAlertModal(); }
  if (event.target === confirmModal) { closeConfirmModal(); }
  if (event.target === remarksModal) { closeRemarksModal(); }
}
window.alert = function(message) { showAlert('info', 'Notice', message); };
