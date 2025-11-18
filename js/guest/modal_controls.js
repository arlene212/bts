function openModal(modalOrId) {
  const modal = typeof modalOrId === 'string' ? document.getElementById(modalOrId) : modalOrId;
  if (modal) { modal.classList.remove('hidden'); modal.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}

function closeModal(modalOrId) {
  const modal = typeof modalOrId === 'string' ? document.getElementById(modalOrId) : modalOrId;
  if (modal) { modal.style.display = 'none'; modal.classList.add('hidden'); document.body.style.overflow = ''; }
}

function setupGuestModals() {
  const closeButtons = document.querySelectorAll('.close-btn, .cancel-btn');
  closeButtons.forEach(btn => { btn.addEventListener('click', function() { const modal = this.closest('.modal'); if (modal) closeModal(modal); }); });
}