document.addEventListener('DOMContentLoaded', function () {
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
    const card = e.target.closest('.batch-card');
    if (card) {
      document.getElementById('course-detail-title').textContent = card.getAttribute('data-course');
      document.getElementById('course-code').textContent = card.getAttribute('data-code');
      document.getElementById('course-hours').textContent = card.getAttribute('data-hours') + ' Hours';
      document.getElementById('course-detail').classList.remove('hidden');
    }
  });
});