function activateTabFromUrl() {
  const params = new URLSearchParams(window.location.search);
  const tabId = params.get('current_tab') || window.currentTab || 'home';
  const tabLink = document.querySelector(`.tab-link[data-tab="${tabId}"]`);
  const tabContent = document.getElementById(`content-${tabId}`);
  if (tabLink && tabContent) {
    document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
    document.querySelectorAll('.content-area').forEach(c => c.classList.remove('active'));
    tabLink.classList.add('active');
    tabContent.classList.add('active');
  }
}

function setupTabs() {
  const tabLinks = document.querySelectorAll('.tab-link');
  const tabContents = document.querySelectorAll('.content-area');
  tabLinks.forEach(link => {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      const tab = this.dataset.tab;
      const newUrl = window.location.pathname + '?current_tab=' + tab;
      window.history.replaceState(null, '', newUrl);
      tabLinks.forEach(t => t.classList.remove('active'));
      tabContents.forEach(c => c.classList.remove('active'));
      this.classList.add('active');
      const targetTab = document.getElementById(`content-${tab}`);
      if (targetTab) targetTab.classList.add('active');
    });
  });
}

function setupHamburgerAndNotif() {
  const hamburger = document.getElementById('hamburger');
  const sidebar = document.querySelector('.sidebar');
  if (hamburger && sidebar) {
    hamburger.addEventListener('click', function() { sidebar.classList.toggle('active'); });
  }
  const notifIcon = document.getElementById('notifIcon');
  const notifDropdown = document.getElementById('notifDropdown');
  if (notifIcon && notifDropdown) {
    notifIcon.addEventListener('click', function(e) { e.stopPropagation(); notifDropdown.classList.toggle('hidden'); });
    document.addEventListener('click', function(e) { if (!notifIcon.contains(e.target) && !notifDropdown.contains(e.target)) { notifDropdown.classList.add('hidden'); } });
  }
}