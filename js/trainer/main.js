function switchToTab(tab) {
  const newUrl = window.location.pathname + '?current_tab=' + tab;
  window.history.replaceState(null, '', newUrl);
  document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
  document.querySelectorAll('.content-area').forEach(c => c.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
  const tabLink = document.querySelector(`.tab-link[data-tab="${tab}"]`);
  const contentArea = document.getElementById(`content-${tab}`);
  if (tabLink) tabLink.classList.add('active');
  if (contentArea) {
    contentArea.classList.add('active');
    const tabContent = contentArea.querySelector('.tab-content');
    if (tabContent) tabContent.classList.add('active');
  }
  const sidebar = document.querySelector('.sidebar');
  if (sidebar && window.innerWidth <= 768) sidebar.classList.remove('open');
}

function activateTabFromUrl() {
  const params = new URLSearchParams(window.location.search);
  const tabId = params.get('current_tab') || window.currentTab || 'home';
  switchToTab(tabId);
}

document.addEventListener('DOMContentLoaded', function () {
  activateTabFromUrl();

  // Single delegated click handler for ALL tab navigation
  document.addEventListener('click', function (e) {
    const tabLink = e.target.closest('.tab-link');
    if (tabLink) {
      e.preventDefault();
      const tab = tabLink.dataset.tab;
      switchToTab(tab);
    }
  });

  document.querySelectorAll('.tab-link').forEach(link => {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      const tab = this.dataset.tab;
      if (tab) switchToTab(tab);
    });
  });

  // Hamburger toggle
  const hamburger = document.getElementById('hamburger');
  const sidebar = document.querySelector('.sidebar');
  if (hamburger && sidebar) {
    hamburger.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
  }

  // Dashboard card click functionality
  document.addEventListener('click', function (e) {
    const card = e.target.closest('.clickable-card');
    if (card) {
      const target = card.getAttribute('data-target');
      if (target) {
        const tabLink = document.querySelector(`.tab-link[data-tab="${target}"]`);
        if (tabLink) {
          tabLink.click();
        }
      }
    }
  });

  // Enhanced button interactions
  document.querySelectorAll('.btn').forEach(button => {
    // Add ripple effect on click
    button.addEventListener('click', function (e) {
      if (this.classList.contains('btn-ripple')) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');
        this.appendChild(ripple);
        setTimeout(() => {
          ripple.remove();
        }, 600);
      }
    });
  });

  // Course card functionality
  document.addEventListener('click', function (e) {
    const card = e.target.closest('.batch-card');
    if (card) {
      const titleEl = document.getElementById('course-detail-title');
      const codeEl = document.getElementById('course-code');
      const hoursEl = document.getElementById('course-hours');
      const detailEl = document.getElementById('course-detail');
      if (titleEl) titleEl.textContent = card.getAttribute('data-course');
      if (codeEl) codeEl.textContent = card.getAttribute('data-code');
      if (hoursEl) hoursEl.textContent = card.getAttribute('data-hours') + ' Hours';
      if (detailEl) detailEl.classList.remove('hidden');
    }
  });
});