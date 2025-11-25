function openEnrollModal(courseCode, courseName) {
  console.log('=== OPENING ENROLLMENT MODAL ===');
  console.log('Opening enrollment modal for:', courseCode, courseName);
  const enrollModal = document.getElementById('enrollModal');
  const enrollCourseName = document.getElementById('enrollCourseName');
  const confirmEnroll = document.getElementById('confirmEnroll');
  const cancelEnroll = document.getElementById('cancelEnroll');
  console.log('Modal elements:', { enrollModal, enrollCourseName, confirmEnroll, cancelEnroll });
  if (!enrollModal || !enrollCourseName || !confirmEnroll || !cancelEnroll) {
    console.error('❌ Missing modal elements - cannot proceed');
    alert('Error: Enrollment modal not found. Please refresh the page.');
    return;
  }
  console.log('✅ All modal elements found, proceeding...');
  enrollCourseName.textContent = courseName;
  const newConfirmBtn = confirmEnroll.cloneNode(true);
  confirmEnroll.parentNode.replaceChild(newConfirmBtn, confirmEnroll);
  newConfirmBtn.onclick = function() { enrollRequest(courseCode, courseName); closeModal(enrollModal); };
  cancelEnroll.onclick = function() { closeModal(enrollModal); };
  enrollModal.classList.remove('hidden');
}

function enrollRequest(courseCode, courseName) {
  console.log('=== ENROLLMENT REQUEST STARTED ===');
  console.log('Starting enrollment request for:', courseCode, courseName);
  
  // Debug: Check if we're in the right context
  console.log('Current page:', window.location.href);
  console.log('User role:', document.body.getAttribute('data-user-role') || 'not set');
  
  const enrollBtn = document.querySelector(`.enroll-btn[data-course-code="${courseCode}"]`) || document.querySelector(`.course-card[data-course="${courseCode}"] .enroll-btn`);
  console.log('Found enrollment button:', enrollBtn);
  
  if (!enrollBtn) {
    console.error('ENROLLMENT BUTTON NOT FOUND!');
    alert('Error: Enrollment button not found. Please refresh the page and try again.');
    return;
  }
  
  if (enrollBtn) { 
    enrollBtn.disabled = true; 
    enrollBtn.textContent = 'Requesting...'; 
    console.log('Button disabled and text changed');
  }
  // Use URLSearchParams instead of FormData for better compatibility
  const params = new URLSearchParams();
  params.append('course_code', courseCode);
  console.log('Sending enrollment request to PHP with course_code:', courseCode);
  console.log('URLSearchParams contents:');
  for (let pair of params.entries()) {
    console.log(pair[0] + ': ' + pair[1]);
  }
  fetch('../php/enhanced_enrollment.php', { 
    method: 'POST', 
    body: params,
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/x-www-form-urlencoded'
    }
  })
    .then(response => {
      console.log('Enrollment response received:', response);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      console.log('Enrollment data received:', data);
      if (data.success) {
        if (enrollBtn) {
          const requestedBadge = document.createElement('span');
          requestedBadge.className = 'status-badge status-pending';
          requestedBadge.innerHTML = '<i class="fas fa-clock"></i> Requested';
          enrollBtn.parentNode.replaceChild(requestedBadge, enrollBtn);
        }
      } else {
        alert(data.message || 'Request failed');
        if (enrollBtn) { enrollBtn.disabled = false; enrollBtn.textContent = 'Request to Enroll'; }
      }
    })
    .catch((error) => { 
      console.error('Enrollment error:', error);
      alert('Network error: ' + error.message); 
      if (enrollBtn) { 
        enrollBtn.disabled = false; 
        enrollBtn.textContent = 'Request to Enroll'; 
      }
    });
}

// Debug function to test enrollment directly
window.testEnrollment = function(courseCode, courseName) {
  console.log('=== MANUAL ENROLLMENT TEST ===');
  console.log('Testing enrollment with courseCode:', courseCode, 'courseName:', courseName);
  
  fetch('../php/enhanced_enrollment.php', { 
    method: 'POST', 
    body: new URLSearchParams({
      'course_code': courseCode,
      'test': 'true'
    }),
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/x-www-form-urlencoded'
    }
  })
  .then(response => {
    console.log('Test response:', response);
    return response.json();
  })
  .then(data => {
    console.log('Test result:', data);
    return data;
  })
  .catch(error => {
    console.error('Test error:', error);
    return error;
  });
};

// Debug function to check system status
window.checkEnrollmentStatus = function() {
  console.log('=== CHECKING ENROLLMENT STATUS ===');
  fetch('../debug_enrollment.php')
  .then(response => response.json())
  .then(data => {
    console.log('System status:', data);
    return data;
  })
  .catch(error => {
    console.error('Status check error:', error);
    return error;
  });
};

// Initialize enrollment functionality when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() {
    console.log('Trainee DOM Content Loaded - enrollment listeners ready');
  });
} else {
  console.log('Trainee DOM already loaded - enrollment listeners ready');
}

document.addEventListener('click', function(e) {
  console.log('Document click detected, checking for enroll-btn class...');
  console.log('Clicked element:', e.target);
  console.log('Element classes:', e.target.classList);
  
  if (e.target.classList.contains('enroll-btn') && !e.target.disabled) {
    console.log('✅ ENROLL BUTTON CLICKED!');
    console.log('Enroll button clicked:', e.target);
    const courseCard = e.target.closest('.course-card');
    console.log('Course card found:', courseCard);
    const courseCode = e.target.getAttribute('data-course-code') || courseCard.getAttribute('data-course');
    const courseName = e.target.getAttribute('data-course-name') || courseCard.getAttribute('data-title');
    console.log('Extracted course data - Code:', courseCode, 'Name:', courseName);
    openEnrollModal(courseCode, courseName);
  } else {
    console.log('❌ Not an enroll button or button is disabled');
  }
  if (e.target.classList.contains('btn-cancel') && !e.target.disabled) {
    const requestId = e.target.getAttribute('data-request-id');
    e.target.disabled = true;
    e.target.textContent = 'Canceling...';
    const body = new URLSearchParams({ action: 'cancel_request', request_id: requestId });
    fetch('../trainee/handlers/ajax_handlers.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
      .then(response => response.json())
      .then(data => { if (data.success) { alert('Request canceled'); } else { alert(data.message || 'Cancel failed'); e.target.disabled = false; e.target.textContent = 'Cancel'; } })
      .catch(() => { alert('Network error'); e.target.disabled = false; e.target.textContent = 'Cancel'; });
  }
});
