function setupBrowseCourseDetailView() {
  const offeredSection = document.getElementById('offered-courses');
  const listContainer = offeredSection ? offeredSection.querySelector('.batch-list') : null;
  const overlay = document.getElementById('browseCourseDetail');
  const backBtn = document.getElementById('backToBrowseCourses');
  const titleEl = document.getElementById('browse-course-detail-title');
  const codeEl = document.getElementById('browse-course-detail-code');
  const hoursEl = document.getElementById('browse-course-detail-hours');
  const contentEl = document.getElementById('browseCourseDetailContent');
  if (!listContainer || !overlay || !backBtn || !titleEl || !codeEl || !hoursEl || !contentEl) return;
  listContainer.addEventListener('click', function(e) {
    const actions = e.target.closest('.batch-actions');
    if (actions) return;
    const card = e.target.closest('.batch-card.course-card');
    if (!card) return;
    const courseCode = card.getAttribute('data-course-code') || '';
    if (!courseCode) return;
    const url = window.location.pathname + '?current_tab=enrolled&open_course=' + encodeURIComponent(courseCode);
    window.location.assign(url);
  });
  backBtn.addEventListener('click', function(){
    const url = window.location.pathname + '?current_tab=enrolled';
    window.location.assign(url);
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function(){ setupBrowseCourseDetailView(); });
} else {
  setupBrowseCourseDetailView();
}
