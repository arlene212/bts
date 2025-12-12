function activateTabFromUrl() {
  const params = new URLSearchParams(window.location.search);
  const tabId = params.get('current_tab') || window.currentTab || 'home';
  const tabLink = document.querySelector(`.tab-link[data-tab="${tabId}"]`);
  const contentArea = document.getElementById(`content-${tabId}`);
  
  if (tabLink && contentArea) {
    // Remove active class from all tab links and content areas
    document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
    document.querySelectorAll('.content-area').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    
    // Add active class to current tab and content
    tabLink.classList.add('active');
    contentArea.classList.add('active');
    
    // Also activate the inner tab content
    const tabContent = contentArea.querySelector('.tab-content');
    if (tabContent) {
      tabContent.classList.add('active');
    }
  }
}

document.addEventListener('DOMContentLoaded', function () {
  activateTabFromUrl();
  
  document.querySelectorAll('.tab-link').forEach(link => {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      const tab = this.dataset.tab;
      const newUrl = window.location.pathname + '?current_tab=' + tab;
      window.history.replaceState(null, '', newUrl);
      
      // Remove active class from all tab links and content areas
      document.querySelectorAll('.tab-link').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.content-area').forEach(c => c.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      
      // Add active class to current tab and content
      this.classList.add('active');
      const targetContentArea = document.getElementById(`content-${tab}`);
      if (targetContentArea) {
        targetContentArea.classList.add('active');
        // Also activate the inner tab content
        const tabContent = targetContentArea.querySelector('.tab-content');
        if (tabContent) {
          tabContent.classList.add('active');
        }
      }
    });
  });
  
  setupHamburgerAndNotif();
  setupGuestModals();
  setupEnrollmentButtons();
  setupActivityBindings();
  setupCourseDetailView();
  if (typeof setupBrowseCourseDetailView === 'function') { setupBrowseCourseDetailView(); }
  setupProfileModal();
  setupEnrolledTabs();
  setupClickableCards();

  try {
    const params = new URLSearchParams(window.location.search);
    const openCode = params.get('open_course');
    if (openCode) {
      const tabLink = document.querySelector('.tab-link[data-tab="enrolled"]');
      if (tabLink) tabLink.click();
      setTimeout(function(){ if (window.openCourseByCode) window.openCourseByCode(openCode); }, 50);
    }
  } catch (e) {}
});

function setupEnrolledTabs() {
  const enrolledSwitchInner = document.getElementById('enrolledSwitchInner');
  const enrolledTabs = document.querySelectorAll('[data-tab="enrolled-active"], [data-tab="enrolled-completed"]');
  const enrolledActiveContent = document.getElementById('enrolled-active');
  const enrolledCompletedContent = document.getElementById('enrolled-completed');
  
  if (!enrolledSwitchInner || enrolledTabs.length === 0) return;
  
  function updateEnrolledSwitchPosition(activeTab) {
    if (activeTab === 'enrolled-active') {
      enrolledSwitchInner.style.left = '2px';
    } else if (activeTab === 'enrolled-completed') {
      enrolledSwitchInner.style.left = 'calc(50% - 2px)';
    }
  }
  
  function showEnrolledTab(tabName) {
    // Hide all enrolled tab contents
    if (enrolledActiveContent) enrolledActiveContent.classList.remove('active');
    if (enrolledCompletedContent) enrolledCompletedContent.classList.remove('active');
    
    // Remove active class from all enrolled tab buttons
    enrolledTabs.forEach(tab => tab.classList.remove('active'));
    
    // Show selected tab content
    if (tabName === 'enrolled-active' && enrolledActiveContent) {
      enrolledActiveContent.classList.add('active');
      document.querySelector('[data-tab="enrolled-active"]').classList.add('active');
    } else if (tabName === 'enrolled-completed' && enrolledCompletedContent) {
      enrolledCompletedContent.classList.add('active');
      document.querySelector('[data-tab="enrolled-completed"]').classList.add('active');
    }
    
    updateEnrolledSwitchPosition(tabName);
  }
  
  // Add click handlers to enrolled tab buttons
  enrolledTabs.forEach(tab => {
    tab.addEventListener('click', function(e) {
      e.preventDefault();
      const tabName = this.getAttribute('data-tab');
      showEnrolledTab(tabName);
    });
  });
  
  // Initialize with active tab
  const activeEnrolledTab = document.querySelector('[data-tab="enrolled-active"].active') ? 'enrolled-active' : 'enrolled-completed';
  showEnrolledTab(activeEnrolledTab);
}

function setupClickableCards() {
  const cards = document.querySelectorAll('.clickable-card');
  if (!cards || cards.length === 0) return;
  cards.forEach(card => {
    card.addEventListener('click', function() {
      const targetTab = this.getAttribute('data-target') || 'home';
      const subtab = this.getAttribute('data-subtab');
      const tabLink = document.querySelector(`.tab-link[data-tab="${targetTab}"]`);
      if (tabLink) {
        tabLink.click();
      }
      if (targetTab === 'enrolled' && subtab) {
        const btn = document.querySelector(`.switch-btn[data-target="${subtab}"]`);
        if (btn) btn.click();
      }
    });
  });
}
