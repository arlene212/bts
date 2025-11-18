function activateTabFromUrl() {
  const params = new URLSearchParams(window.location.search);
  const tabId = params.get('current_tab') || window.currentTab || 'home';
  const tabLink = document.querySelector(`.tab-link[data-tab="${tabId}"]`);
  const tabContent = document.getElementById(tabId);
  if (tabLink && tabContent) {
    document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    tabLink.classList.add('active');
    tabContent.classList.add('active');
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
      document.querySelectorAll('.tab-link').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      this.classList.add('active');
      const targetTab = document.getElementById(tab);
      if (targetTab) targetTab.classList.add('active');
    });
  });
  const hamburger = document.getElementById('hamburger');
  const sidebar = document.querySelector('.sidebar');
  if (hamburger && sidebar) { hamburger.addEventListener('click', function() { sidebar.classList.toggle('active'); }); }
  document.addEventListener('click', function(e) {
    const card = e.target.closest('.course-card.view-course-content-btn');
    if (card) {
      const name = card.getAttribute('data-course-name');
      const code = card.getAttribute('data-course-code');
      const hours = card.getAttribute('data-course-hours');
      const desc = card.getAttribute('data-course-description');
      document.getElementById('course-detail-title').textContent = name;
      document.getElementById('course-detail-code').textContent = code;
      document.getElementById('course-detail-hours').textContent = hours + ' hrs';
      document.getElementById('course-detail-description').textContent = desc;
      document.getElementById('courseDetail').classList.remove('hidden');
    }
  });
  const changeProfileBtn = document.getElementById('changeProfileBtn');
  const profileUpload = document.getElementById('profileUpload');
  const profilePreview = document.getElementById('profilePreview');
  if (changeProfileBtn && profileUpload) { changeProfileBtn.addEventListener('click', function() { profileUpload.click(); }); }
  if (profileUpload) { profileUpload.addEventListener('change', function(e) { const file = e.target.files[0]; if (file) { const reader = new FileReader(); reader.onload = function(ev) { if (profilePreview) profilePreview.src = ev.target.result; }; reader.readAsDataURL(file); } }); }
});