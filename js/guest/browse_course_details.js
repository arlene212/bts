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
    const courseName = card.getAttribute('data-course-name') || '';
    const courseHours = card.getAttribute('data-course-hours') || '';
    titleEl.textContent = courseName;
    codeEl.textContent = courseCode;
    hoursEl.textContent = courseHours ? (courseHours + ' hrs') : '';
    if (offeredSection) offeredSection.classList.remove('active');
    listContainer.style.display = 'none';
    overlay.classList.remove('hidden');
    overlay.style.display = 'block';
    contentEl.innerHTML = '<div>Loading course content...</div>';
    fetch('../guest/handlers/ajax_handlers.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=get_course_details&course_code=' + encodeURIComponent(courseCode)
    })
    .then(function(r){ return r.json(); })
    .then(function(data){
      if (data.error) { contentEl.innerHTML = '<div class="error-message">Error: ' + data.error + '</div>'; return; }
      var comps = (data.competencies || []).filter(function(c){ return c.type === 'basic'; });
      var html = '';
      if (data.course && data.course.description) {
        html += '<p class="course-description-detail">' + data.course.description + '</p>';
      }
      if (comps.length > 0) {
        html += '<h4>Basic Competencies</h4>';
        comps.forEach(function(comp){
          html += '<div class="competency-box">';
          html += '<div class="competency-header"><h4>' + comp.name + '</h4></div>';
          if (comp.topics && comp.topics.length > 0) {
            comp.topics.forEach(function(topic){
              html += '<div class="topic-box">';
              html += '<h5>' + (topic.name || '') + '</h5>';
              if (topic.materials && topic.materials.length > 0) {
                html += '<div class="materials-list">';
                topic.materials.forEach(function(mat){
                  var fileLink = mat.file_path ? ('../uploads/courses/' + mat.file_path) : '#';
                  html += '<div class="material-item material"><div class="material-title">';
                  html += '<a href="' + fileLink + '" target="_blank" rel="noopener noreferrer"' + (mat.file_path ? ' download' : '') + '>' + (mat.title || 'Material') + '</a>';
                  html += '</div><div class="material-desc">' + (mat.description || '') + '</div></div>';
                });
                html += '</div>';
              }
              if (topic.activities && topic.activities.length > 0) {
                html += '<div class="activities-list">';
                topic.activities.forEach(function(act){
                  html += '<div class="material-item activity"><div class="material-title">' + (act.title || '') + '</div>';
                  html += '<div class="material-status not-submitted">Not submitted</div></div>';
                });
                html += '</div>';
              }
              html += '</div>';
            });
          } else {
            html += '<p class="no-materials">No topics for this competency.</p>';
          }
          html += '</div>';
        });
      } else {
        html += '<p>No basic competency materials are available for this course.</p>';
      }
      contentEl.innerHTML = html;
    })
    .catch(function(){ contentEl.innerHTML = '<div class="error-message">Failed to load course content.</div>'; });
  });
  backBtn.addEventListener('click', function(){
    overlay.classList.add('hidden');
    overlay.style.display = 'none';
    if (offeredSection) offeredSection.classList.add('active');
    listContainer.style.display = 'grid';
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function(){ setupBrowseCourseDetailView(); });
} else {
  setupBrowseCourseDetailView();
}
