function openEnrollModal(courseCode, courseName) {
  const enrollModal = document.getElementById('enrollModal');
  const enrollCourseName = document.getElementById('enrollCourseName');
  const confirmEnroll = document.getElementById('confirmEnroll');
  const cancelEnroll = document.getElementById('cancelEnroll');
  if (!enrollModal || !enrollCourseName || !confirmEnroll || !cancelEnroll) return;
  enrollCourseName.textContent = courseName;
  const newConfirmBtn = confirmEnroll.cloneNode(true);
  confirmEnroll.parentNode.replaceChild(newConfirmBtn, confirmEnroll);
  newConfirmBtn.onclick = function() { enrollRequest(courseCode, courseName); closeModal(enrollModal); };
  cancelEnroll.onclick = function() { closeModal(enrollModal); };
  enrollModal.classList.remove('hidden');
}

function enrollRequest(courseCode, courseName) {
  const enrollBtn = document.querySelector(`.enroll-btn[data-course-code="${courseCode}"]`) || document.querySelector(`.course-card[data-course="${courseCode}"] .enroll-btn`);
  if (enrollBtn) { enrollBtn.disabled = true; enrollBtn.textContent = 'Requesting...'; }
  const body = new URLSearchParams({ action: 'enroll_request', course_code: courseCode });
  fetch('../trainee/handlers/ajax_handlers.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Enrollment request sent');
        setTimeout(() => { window.location.href = `${window.location.pathname}?current_tab=enrollment-requests`; }, 800);
      } else {
        alert(data.message || 'Request failed');
        if (enrollBtn) { enrollBtn.disabled = false; enrollBtn.textContent = 'Request to Enroll'; }
      }
    })
    .catch(() => { alert('Network error'); if (enrollBtn) { enrollBtn.disabled = false; enrollBtn.textContent = 'Request to Enroll'; } });
}

document.addEventListener('click', function(e) {
  if (e.target.classList.contains('enroll-btn') && !e.target.disabled) {
    const courseCard = e.target.closest('.course-card');
    const courseCode = e.target.getAttribute('data-course-code') || courseCard.getAttribute('data-course');
    const courseName = e.target.getAttribute('data-course-name') || courseCard.getAttribute('data-title');
    openEnrollModal(courseCode, courseName);
  }
  if (e.target.classList.contains('btn-cancel') && !e.target.disabled) {
    const requestId = e.target.getAttribute('data-request-id');
    e.target.disabled = true;
    e.target.textContent = 'Canceling...';
    const body = new URLSearchParams({ action: 'cancel_request', request_id: requestId });
    fetch('../trainee/handlers/ajax_handlers.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
      .then(response => response.json())
      .then(data => { if (data.success) { alert('Request canceled'); window.location.reload(); } else { alert(data.message || 'Cancel failed'); e.target.disabled = false; e.target.textContent = 'Cancel'; } })
      .catch(() => { alert('Network error'); e.target.disabled = false; e.target.textContent = 'Cancel'; });
  }
});