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
  
  // Tab switch functionality for links within content
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

  // Switch button functionality for enrolled/completed courses
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('switch-btn')) {
      const buttons = e.target.parentElement.querySelectorAll('.switch-btn');
      const switchInner = e.target.parentElement.querySelector('.switch-inner');
      const targetTab = e.target.getAttribute('data-tab');
      
      // Update active states
      buttons.forEach(btn => btn.classList.remove('active'));
      e.target.classList.add('active');
      
      // Move switch inner indicator
      if (switchInner) {
        const buttonIndex = Array.from(buttons).indexOf(e.target);
        const buttonWidth = 100 / buttons.length;
        switchInner.style.transform = `translateX(${buttonIndex * 100}%)`;
        switchInner.style.width = `${buttonWidth}%`;
      }
      
      // Show/hide content
      const container = e.target.closest('.course-box') || e.target.closest('#courseDetail');
      if (container) {
        const tabContents = container.querySelectorAll('.tab-inner');
        tabContents.forEach(content => content.classList.remove('active'));
        const targetContent = container.querySelector(`#${targetTab}`);
        if (targetContent) {
          targetContent.classList.add('active');
        }
      }
    }
  });
  document.addEventListener('click', function(e) {
    const card = e.target.closest('.batch-card.view-course-content-btn');
    if (card) {
      const name = card.getAttribute('data-course-name');
      const code = card.getAttribute('data-course-code');
      const hours = card.getAttribute('data-course-hours');
      const desc = card.getAttribute('data-course-description');
      const credited = card.getAttribute('data-credited-hours');
      document.getElementById('course-detail-title').textContent = name;
      document.getElementById('course-detail-code').textContent = 'Code: ' + code;
      document.getElementById('course-detail-hours').textContent = hours + ' hrs';
      const ch = document.getElementById('course-detail-credited-hours');
      if (ch) ch.textContent = 'Credited: ' + (credited || '0') + ' hrs';
      document.getElementById('course-detail-description').textContent = desc;
      const cd = document.getElementById('courseDetail');
      cd.classList.remove('hidden');
      cd.classList.add('active');
    }
  });
  const changeProfileBtn = document.getElementById('changeProfileBtn');
  const profileUpload = document.getElementById('profileUpload');
  const profilePreview = document.getElementById('profilePreview');
  if (changeProfileBtn && profileUpload) { changeProfileBtn.addEventListener('click', function() { profileUpload.click(); }); }
  if (profileUpload) { profileUpload.addEventListener('change', function(e) { const file = e.target.files[0]; if (file) { const reader = new FileReader(); reader.onload = function(ev) { if (profilePreview) profilePreview.src = ev.target.result; }; reader.readAsDataURL(file); } }); }
  
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
      
      // Add loading state for form submissions
      if (this.type === 'submit' || this.classList.contains('enroll-btn') || this.classList.contains('btn-cancel')) {
        const originalText = this.innerHTML;
        this.classList.add('btn-loading');
        this.disabled = true;
        
        // Simulate loading (remove this in production and use actual form submission)
        setTimeout(() => {
          this.classList.remove('btn-loading');
          this.disabled = false;
          this.innerHTML = originalText;
          
          // Add success state for successful operations
          if (this.classList.contains('enroll-btn') || this.classList.contains('btn-cancel')) {
            this.classList.add('btn-success-state');
            const successText = this.classList.contains('enroll-btn') ? 
              '<i class="fas fa-check"></i> Request Sent!' : 
              '<i class="fas fa-check"></i> Cancelled!';
            this.innerHTML = successText;
            
            setTimeout(() => {
              this.classList.remove('btn-success-state');
              this.innerHTML = originalText;
            }, 2000);
          }
        }, 1500);
      }
    });
  });
});
