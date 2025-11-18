function setupGlobalEventListeners() {
  const closeButtons = document.querySelectorAll('.close, .cancel-btn');
  const modals = document.querySelectorAll('.modal');
  closeButtons.forEach(button => {
    button.addEventListener('click', function() {
      const modal = this.closest('.modal');
      if (modal) {
        closeModal(modal.id);
      }
    });
  });
  modals.forEach(modal => {
    modal.addEventListener('click', function(e) {
      if (e.target === this) {
        closeModal(this.id);
      }
    });
  });
  const hamburger = document.getElementById('hamburger');
  const sidebarOverlay = document.getElementById('sidebarOverlay');
  if (hamburger) {
    hamburger.addEventListener('click', () => { document.body.classList.toggle('sidebar-open'); });
  }
  if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', () => { document.body.classList.remove('sidebar-open'); });
  }
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      const openModals = document.querySelectorAll('.modal:not(.hidden)');
      openModals.forEach(modal => { closeModal(modal.id); });
    }
  });
}

function setupTabNavigation() {
  const tabLinks = document.querySelectorAll('.tab-link');
  const tabContents = document.querySelectorAll('.tab-content');
  tabLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      tabLinks.forEach(l => l.classList.remove('active'));
      tabContents.forEach(c => c.classList.remove('active'));
      link.classList.add('active');
      const tabId = link.getAttribute('data-tab');
      try {
        const newUrl = window.location.pathname + '?current_tab=' + tabId;
        window.history.replaceState(null, '', newUrl);
      } catch (_) {}
      const targetContent = document.getElementById(tabId);
      if (targetContent) { targetContent.classList.add('active'); }
      if (window.innerWidth <= 768) { document.body.classList.remove('sidebar-open'); }
      if (tabId === 'trainers' || tabId === 'trainees') { setTimeout(() => { initializeSwitches(); }, 100); }
    });
  });
}

function activateTabFromUrl() {
  const params = new URLSearchParams(window.location.search);
  const tabId = params.get('current_tab') || window.location.hash.substring(1);
  if (tabId) {
    const tabLink = document.querySelector(`.tab-link[data-tab="${tabId}"]`);
    const tabContent = document.getElementById(tabId);
    if (tabLink && tabContent) {
      document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      tabLink.classList.add('active');
      tabContent.classList.add('active');
    }
  }
}

function setupDashboardCards() {
  const dashboardCards = document.querySelectorAll('.dashboard-card');
  const tabLinks = document.querySelectorAll('.tab-link');
  const tabContents = document.querySelectorAll('.tab-content');
  dashboardCards.forEach(card => {
    card.addEventListener('click', () => {
      const targetTab = card.getAttribute('data-target');
      const targetTabLink = document.querySelector(`.tab-link[data-tab="${targetTab}"]`);
      if (targetTabLink) {
        tabLinks.forEach(l => l.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));
        targetTabLink.classList.add('active');
        const targetContent = document.getElementById(targetTab);
        if (targetContent) { targetContent.classList.add('active'); }
        try {
          const newUrl = window.location.pathname + '?current_tab=' + targetTab;
          window.history.replaceState(null, '', newUrl);
        } catch (_) {}
        if (targetTab === 'trainers' || targetTab === 'trainees') { setTimeout(() => { initializeSwitches(); }, 100); }
      }
    });
  });
}