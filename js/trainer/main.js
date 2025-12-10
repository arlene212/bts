function switchToTab(tab) {
  const newUrl = window.location.pathname + '?current_tab=' + tab;
  window.history.replaceState(null, '', newUrl);
  document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
  document.querySelectorAll('.content-area').forEach(c => c.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
  const overlay = document.getElementById('course-detail');
  if (overlay) overlay.classList.add('hidden');
  document.body.classList.remove('modal-open');
  const tabLink = document.querySelector(`.tab-link[data-tab="${tab}"]`);
  const contentArea = document.getElementById(`content-${tab}`);
  if (tabLink) tabLink.classList.add('active');
  if (contentArea) {
    contentArea.classList.add('active');
    const tabContent = contentArea.querySelector('.tab-content');
    if (tabContent) tabContent.classList.add('active');
  }
  if (tab === 'courses') {
    const enrolled = document.getElementById('enrolled');
    const overlay2 = document.getElementById('course-detail');
    if (overlay2) overlay2.classList.add('hidden');
    if (enrolled) enrolled.classList.remove('hidden');
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
  const sidebarBackdrop = document.getElementById('sidebarBackdrop');
  if (hamburger && sidebar) {
    hamburger.addEventListener('click', function () {
      const willOpen = !sidebar.classList.contains('open');
      sidebar.classList.toggle('open');
      if (sidebarBackdrop) sidebarBackdrop.classList.toggle('active', willOpen);
      document.body.classList.toggle('no-scroll', willOpen);
      if (hamburger) hamburger.setAttribute('aria-expanded', String(willOpen));
    });
  }

  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('open');
    if (sidebarBackdrop) sidebarBackdrop.classList.remove('active');
    document.body.classList.remove('no-scroll');
    if (hamburger) hamburger.setAttribute('aria-expanded', 'false');
  }

  if (sidebarBackdrop) {
    sidebarBackdrop.addEventListener('click', closeSidebar);
    sidebarBackdrop.addEventListener('touchstart', function(){ closeSidebar(); }, { passive: true });
    sidebarBackdrop.addEventListener('pointerdown', function(){ closeSidebar(); });
  }

  // Close on ESC
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
      closeSidebar();
    }
  });

  function outsideHandler(e) {
    const isOpen = sidebar && sidebar.classList.contains('open');
    const backdropActive = sidebarBackdrop && sidebarBackdrop.classList.contains('active');
    if (isOpen && (window.innerWidth <= 1024 || backdropActive)) {
      const clickedInsideSidebar = e.target.closest('.sidebar');
      const clickedHamburger = e.target.closest('#hamburger');
      if (!clickedInsideSidebar && !clickedHamburger) {
        closeSidebar();
      }
    }
  }
  document.addEventListener('click', outsideHandler, true);
  document.addEventListener('touchstart', outsideHandler, { passive: true });
  document.addEventListener('pointerdown', outsideHandler);

  // Ensure state resets on resize to desktop
  window.addEventListener('resize', function () {
    if (window.innerWidth > 1024) {
      if (sidebar) sidebar.classList.remove('open');
      if (sidebarBackdrop) sidebarBackdrop.classList.remove('active');
      document.body.classList.remove('no-scroll');
      if (hamburger) hamburger.setAttribute('aria-expanded', 'false');
    }
  });

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

  // Course card functionality is handled in course_details.js
});
