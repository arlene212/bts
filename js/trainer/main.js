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
  
  const hamburger = document.getElementById('hamburger');
  const sidebar = document.querySelector('.sidebar');
  if (hamburger && sidebar) { hamburger.addEventListener('click', function() { sidebar.classList.toggle('active'); }); }
  
  // Tab switch functionality
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('tab-switch')) {
      e.preventDefault();
      const tab = e.target.getAttribute('data-tab');
      if (tab) {
        const tabLink = document.querySelector(`.tab-link[data-tab="${tab}"]`);
        if (tabLink) {
          tabLink.click();
        }
      }
    }
  });
  
  // Dashboard card click functionality
  document.addEventListener('click', function(e) {
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
    button.addEventListener('click', function(e) {
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
  document.addEventListener('click', function(e) {
    const card = e.target.closest('.batch-card');
    if (card) {
      document.getElementById('course-detail-title').textContent = card.getAttribute('data-course');
      document.getElementById('course-code').textContent = card.getAttribute('data-code');
      document.getElementById('course-hours').textContent = card.getAttribute('data-hours') + ' Hours';
      document.getElementById('course-detail').classList.remove('hidden');
    }
  });
});