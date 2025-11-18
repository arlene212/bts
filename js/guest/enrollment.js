function openEnrollModal(courseCode, courseName) {
  const enrollModal = document.getElementById('enrollModal');
  const enrollCourseName = document.getElementById('enrollCourseName');
  const confirmEnroll = document.getElementById('confirmEnroll');
  const cancelEnroll = document.getElementById('cancelEnroll');
  if (!enrollModal || !enrollCourseName || !confirmEnroll || !cancelEnroll) return;
  enrollCourseName.textContent = courseName;
  const newConfirmBtn = confirmEnroll.cloneNode(true);
  confirmEnroll.parentNode.replaceChild(newConfirmBtn, confirmEnroll);
  newConfirmBtn.onclick = function() { enrollInCourse(courseCode, courseName); closeModal(enrollModal); };
  cancelEnroll.onclick = function() { closeModal(enrollModal); };
  openModal(enrollModal);
}

function openUnenrollModal(courseCode, courseName) {
  document.getElementById('unenrollConfirmCourseName').textContent = courseName;
  const confirmUnenroll = document.getElementById('confirmUnenroll');
  confirmUnenroll.onclick = function() { unenrollFromCourse(courseCode, this); closeModal(document.getElementById('unenrollConfirmModal')); };
  openModal(document.getElementById('unenrollConfirmModal'));
}

function enrollInCourse(courseCode, courseName) {
  const enrollBtn = document.querySelector(`.enroll-btn[data-course-code="${courseCode}"]`) || document.querySelector(`.course-card[data-course="${courseCode}"] .enroll-btn`);
  if (enrollBtn) { enrollBtn.disabled = true; enrollBtn.textContent = 'Enrolling...'; }
  const formData = new FormData();
  formData.append('course_code', courseCode);
  formData.append('action', 'enroll');
  fetch('../guest/handlers/ajax_handlers.php', { method: 'POST', body: formData })
    .then(response => { if (!response.ok) throw new Error('Network response was not ok'); return response.json(); })
    .then(data => {
      if (data.success) {
        showNotification(data.message, 'success');
        const courseCard = document.querySelector(`.course-card[data-course="${courseCode}"]`);
        if (courseCard) {
          const oldBtn = courseCard.querySelector('.enroll-btn');
          if (oldBtn) { const newBtn = document.createElement('button'); newBtn.className = 'unenroll-btn'; newBtn.setAttribute('data-course-code', courseCode); newBtn.setAttribute('data-course-name', courseName); newBtn.textContent = 'Unenroll'; oldBtn.parentNode.replaceChild(newBtn, oldBtn); }
          const pendingEl = courseCard.querySelector('.enrollment-status.pending'); if (pendingEl) pendingEl.remove();
        }
        setTimeout(() => { window.location.href = `${window.location.pathname}?current_tab=enrolled`; }, 1200);
      } else {
        showNotification(data.message || 'Enrollment failed. Please try again.', 'error');
        if (enrollBtn) { enrollBtn.disabled = false; enrollBtn.textContent = 'Enroll'; enrollBtn.classList.remove('pending'); }
      }
    })
    .catch(() => { showNotification('An error occurred during enrollment. Please try again.', 'error'); if (enrollBtn) { enrollBtn.disabled = false; enrollBtn.textContent = 'Enroll'; enrollBtn.classList.remove('pending'); } });
}

function unenrollFromCourse(courseCode, button) {
  button.disabled = true; button.textContent = 'Unenrolling...';
  fetch('../guest/handlers/ajax_handlers.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `action=unenroll&course_code=${encodeURIComponent(courseCode)}` })
    .then(response => response.json())
    .then(data => { if (data.success) { showNotification(data.message, 'success'); setTimeout(() => window.location.href = `${window.location.pathname}?current_tab=enrolled`, 1500); } else { showNotification(data.message, 'error'); button.disabled = false; button.textContent = 'Yes, Unenroll'; } });
}

function setupEnrollmentButtons() {
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('enroll-btn') && !e.target.disabled) {
      const courseCard = e.target.closest('.course-card');
      const courseCode = courseCard.getAttribute('data-course');
      const courseName = courseCard.getAttribute('data-title');
      openEnrollModal(courseCode, courseName);
    }
    if (e.target.classList.contains('unenroll-btn') && !e.target.disabled) {
      let courseCode = e.target.getAttribute('data-course-code');
      let courseName = e.target.getAttribute('data-course-name');
      if (!courseCode) { const courseCard = e.target.closest('.course-card'); courseCode = courseCard.getAttribute('data-course'); courseName = courseCard.getAttribute('data-title'); }
      openUnenrollModal(courseCode, courseName);
    }
  });
}