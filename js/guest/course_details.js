function setupCourseDetailView() {
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('view-course-btn')) {
      const courseCard = e.target.closest('.course-card');
      if (courseCard) {
        const courseCode = courseCard.getAttribute('data-course');
        const courseName = courseCard.getAttribute('data-title');
        viewCourseDetails(courseCode, courseName);
      }
    }
  });
  const backToCourses = document.getElementById('backToCourses');
  const backToEnrolledCourses = document.getElementById('backToEnrolledCourses');
  if (backToCourses) { backToCourses.addEventListener('click', resetDetailViews); }
  if (backToEnrolledCourses) { backToEnrolledCourses.addEventListener('click', resetDetailViews); }
}

function viewCourseDetails(courseCode, courseName) {
  const enrolledContainer = document.getElementById('enrolledCoursesContainer');
  const courseDetailView = document.getElementById('courseDetail');
  const backButton = document.getElementById('backToEnrolledCourses');
  if (enrolledContainer) enrolledContainer.classList.add('hidden');
  if (courseDetailView) courseDetailView.classList.remove('hidden');
  if (backButton) backButton.classList.remove('hidden');
  const courseContentContainer = document.getElementById('courseDetailContent');
  if (!courseContentContainer) return;
  courseContentContainer.innerHTML = '<div>Loading course content...</div>';
  const unenrollBtn = document.getElementById('unenrollCourseBtn');
  if (unenrollBtn) { unenrollBtn.setAttribute('data-course-code', courseCode); unenrollBtn.setAttribute('data-course-name', courseName); }
  fetch('../guest/handlers/ajax_handlers.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `action=get_course_details&course_code=${encodeURIComponent(courseCode)}` })
    .then(response => response.json())
    .then(data => {
      if (data.error) { courseContentContainer.innerHTML = `<div class="error-message">Error: ${data.error}</div>`; return; }
      let contentHtml = `<h2>${data.course.course_name}</h2>`;
      contentHtml += `<p class="course-description-detail">${data.course.description}</p>`;
      const basicCompetencies = (data.competencies || []).filter(comp => comp.type === 'basic');
      if (basicCompetencies.length > 0) {
        contentHtml += '<h4>Basic Competencies</h4>';
        basicCompetencies.forEach(comp => {
          contentHtml += `<div class="competency-box">`;
          contentHtml += `<div class="competency-header"><h4>${comp.name}</h4></div>`;
          if (comp.topics && comp.topics.length > 0) {
            comp.topics.forEach(topic => {
              contentHtml += `<div class="topic-box">`;
              contentHtml += `<h5>${topic.name}</h5>`;
              if (topic.materials && topic.materials.length > 0) {
                contentHtml += `<div class="materials-list">`;
                topic.materials.forEach(mat => {
                  const fileLink = mat.file_path ? `../uploads/courses/${mat.file_path}` : '#';
                  contentHtml += `<div class="material-item material" data-material-id="${mat.id}"><div class="material-title"><a href="${fileLink}" target="_blank" rel="noopener noreferrer" ${mat.file_path ? 'download' : ''}>${mat.title || 'Material'}</a></div><div class="material-desc">${mat.description || ''}</div></div>`;
                });
                contentHtml += `</div>`;
              }
              if (topic.activities && topic.activities.length > 0) {
                contentHtml += `<div class="activities-list">`;
                topic.activities.forEach(act => {
                  contentHtml += `<div class="material-item activity" data-activity="${act.id}" data-type="${act.type}"><div class="material-title">${act.title}</div><div class="material-status not-submitted">Not submitted</div></div>`;
                });
                contentHtml += `</div>`;
              }
              contentHtml += `</div>`;
            });
          } else { contentHtml += '<p class="no-materials">No topics for this competency.</p>'; }
          contentHtml += `</div>`;
        });
      } else { contentHtml += '<p>No basic competency materials are available for this course.</p>'; }
      courseContentContainer.innerHTML = contentHtml;
    })
    .catch(() => { courseContentContainer.innerHTML = '<div class="error-message">Failed to load course content.</div>'; });
}

function resetDetailViews() {
  const enrolledContainer = document.getElementById('enrolledCoursesContainer');
  const courseDetailView = document.getElementById('courseDetail');
  const backButton = document.getElementById('backToEnrolledCourses');
  if (enrolledContainer) enrolledContainer.classList.remove('hidden');
  if (courseDetailView) courseDetailView.classList.add('hidden');
  if (backButton) backButton.classList.add('hidden');
}