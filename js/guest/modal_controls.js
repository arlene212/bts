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

  // Close when clicking overlay (outside content)
  document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
      const content = this.querySelector('.modal-content');
      if (content && !content.contains(e.target)) {
        closeModal(this);
      }
    });
  });

  // ESC closes the topmost open modal
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      const openModals = Array.from(document.querySelectorAll('.modal')).filter(m => m.style.display === 'flex' && !m.classList.contains('hidden'));
      const top = openModals.pop();
      if (top) closeModal(top);
    }
  });
}
