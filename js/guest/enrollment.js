function openEnrollModal(courseCode, courseName) {
  const enrollModal = document.getElementById('enrollModal');
  const enrollCourseName = document.getElementById('enrollCourseName');
  const confirmEnroll = document.getElementById('confirmEnroll');
  const cancelEnroll = document.getElementById('cancelEnroll');
  const verificationSection = document.getElementById('verificationSection');
  const batchSection = document.getElementById('batchSelectSection');
  
  if (!enrollModal || !enrollCourseName || !confirmEnroll || !cancelEnroll) return;
  
  enrollCourseName.textContent = courseName;
  
  // Check if verification is required for this course
  checkCourseVerification(courseCode).then(verificationData => {
    if (verificationData.require_verification) {
      verificationSection.classList.remove('hidden');
      setupVerificationFields(verificationData.verification_type);
    } else {
      verificationSection.classList.add('hidden');
    }
  });

  // Load batches for selected course and populate select
  loadBatchesForCourse(courseCode);
  
  const newConfirmBtn = confirmEnroll.cloneNode(true);
  confirmEnroll.parentNode.replaceChild(newConfirmBtn, confirmEnroll);
  newConfirmBtn.onclick = function() { 
    if (validateEnrollment()) {
      newConfirmBtn.disabled = true;
      newConfirmBtn.classList.add('btn-loading');
      // Keep modal open to show loading; close after request completes
      enrollInCourse(courseCode, courseName)
        .finally(function(){
          newConfirmBtn.disabled = false;
          newConfirmBtn.classList.remove('btn-loading');
          closeModal(enrollModal);
        }); 
    }
  };
  cancelEnroll.onclick = function() { closeModal(enrollModal); };
  openModal(enrollModal);
}

function checkCourseVerification(courseCode) {
  return fetch('../php/check_course_verification.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `course_code=${encodeURIComponent(courseCode)}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      return data;
    } else {
      return { require_verification: false };
    }
  })
  .catch(() => {
    return { require_verification: false };
  });
}

function setupVerificationFields(verificationType) {
  const studentIdVerification = document.getElementById('studentIdVerification');
  const emailVerification = document.getElementById('emailVerification');
  const verificationMessage = document.getElementById('verificationMessage');
  
  // Hide all verification fields first
  studentIdVerification.classList.add('hidden');
  emailVerification.classList.add('hidden');
  
  if (verificationType === 'student_id') {
    studentIdVerification.classList.remove('hidden');
    verificationMessage.textContent = 'This course requires Student ID verification. Please enter your Student ID to continue.';
  } else if (verificationType === 'email') {
    emailVerification.classList.remove('hidden');
    verificationMessage.textContent = 'This course requires email verification. Please enter your verified email address.';
  }
}

function validateEnrollment() {
  const verificationSection = document.getElementById('verificationSection');
  const batchSelect = document.getElementById('batchSelect');
  const batchError = document.getElementById('batchSelectError');
  if (batchSelect) {
    if (!batchSelect.value) {
      if (batchError) batchError.classList.remove('hidden');
      batchSelect.focus();
      return false;
    } else if (batchError) {
      batchError.classList.add('hidden');
    }
  }
  if (verificationSection.classList.contains('hidden')) {
    return true;
  }
  
  const studentIdVerification = document.getElementById('studentIdVerification');
  const emailVerification = document.getElementById('emailVerification');
  
  if (!studentIdVerification.classList.contains('hidden')) {
    const studentIdInput = document.getElementById('studentIdInput');
    const studentIdError = document.getElementById('studentIdError');
    
    if (!studentIdInput.value.trim()) {
      studentIdError.classList.remove('hidden');
      studentIdInput.focus();
      return false;
    }
    studentIdError.classList.add('hidden');
  }
  
  if (!emailVerification.classList.contains('hidden')) {
    const emailVerificationInput = document.getElementById('emailVerificationInput');
    const emailVerificationError = document.getElementById('emailVerificationError');
    
    if (!emailVerificationInput.value.trim()) {
      emailVerificationError.classList.remove('hidden');
      emailVerificationInput.focus();
      return false;
    }
    emailVerificationError.classList.add('hidden');
  }
  
  return true;
}

function loadBatchesForCourse(courseCode) {
  const batchSelect = document.getElementById('batchSelect');
  if (!batchSelect) return;
  fetch('../php/get_batches.php?course_code=' + encodeURIComponent(courseCode))
    .then(r => r.json())
    .then(rows => {
      while (batchSelect.firstChild) batchSelect.removeChild(batchSelect.firstChild);
      const list = Array.isArray(rows) ? rows : [];
      if (list.length > 0) {
        const def = document.createElement('option'); def.value = ''; def.textContent = 'Select a batch'; batchSelect.appendChild(def);
        list.forEach(r => { const opt = document.createElement('option'); opt.value = r.batch_name; opt.textContent = r.batch_name; batchSelect.appendChild(opt); });
      } else {
        const opt = document.createElement('option'); opt.value = ''; opt.textContent = 'No batches available'; batchSelect.appendChild(opt);
      }
    })
    .catch(() => {
      while (batchSelect.firstChild) batchSelect.removeChild(batchSelect.firstChild);
      const err = document.createElement('option'); err.value = ''; err.textContent = 'No batches available'; batchSelect.appendChild(err);
    });
}

function openUnenrollModal(courseCode, courseName) {
  document.getElementById('unenrollConfirmCourseName').textContent = courseName;
  const confirmUnenroll = document.getElementById('confirmUnenroll');
  confirmUnenroll.onclick = function() { unenrollFromCourse(courseCode, this); closeModal(document.getElementById('unenrollConfirmModal')); };
  openModal(document.getElementById('unenrollConfirmModal'));
}

function enrollInCourse(courseCode, courseName) {
  const enrollBtn = document.querySelector(`.enroll-btn[data-course-code="${courseCode}"]`) || document.querySelector(`.course-card[data-course="${courseCode}"] .enroll-btn`) || document.querySelector(`.batch-card[data-course-code="${courseCode}"] .enroll-btn`);
  if (enrollBtn) { enrollBtn.disabled = true; enrollBtn.textContent = 'Enrolling...'; }
  
  const formData = new FormData();
  formData.append('course_code', courseCode);
  formData.append('action', 'enroll');
  const batchSelect = document.getElementById('batchSelect');
  if (batchSelect && batchSelect.value) {
    formData.append('batch_name', batchSelect.value);
  }
  
  // Add verification data if required
  const verificationSection = document.getElementById('verificationSection');
  if (verificationSection && !verificationSection.classList.contains('hidden')) {
    const studentIdInput = document.getElementById('studentIdInput');
    const emailVerificationInput = document.getElementById('emailVerificationInput');
    
    if (studentIdInput && !document.getElementById('studentIdVerification').classList.contains('hidden')) {
      formData.append('student_id', studentIdInput.value.trim());
    }
    
    if (emailVerificationInput && !document.getElementById('emailVerification').classList.contains('hidden')) {
      formData.append('email_verification', emailVerificationInput.value.trim());
    }
  }
  
  fetch('../guest/handlers/ajax_handlers.php', { method: 'POST', body: formData })
    .then(response => { if (!response.ok) throw new Error('Network response was not ok'); return response.json(); })
    .then(data => {
      if (data.success) {
        showNotification(data.message, 'success');
        const courseCard = document.querySelector(`.course-card[data-course="${courseCode}"]`) || document.querySelector(`.batch-card[data-course-code="${courseCode}"]`);
        if (courseCard) {
          const actions = courseCard.querySelector('.batch-actions');
          if (actions) {
            actions.innerHTML = `
              <button class="btn btn-outline-danger unenroll-btn" 
                      data-course-code="${courseCode}" 
                      data-course-name="${courseName}">
                <i class="fas fa-times"></i> Unenroll
              </button>
            `;
          }
        }
        const newUrl = window.location.pathname + '?current_tab=enrolled';
        window.location.assign(newUrl);
        // Removed auto redirect to avoid interrupting user actions; stay on current view
      } else {
        showNotification(data.message || 'Enrollment failed. Please try again.', 'error');
        if (enrollBtn) { 
          enrollBtn.disabled = false; 
          enrollBtn.textContent = 'Enroll'; 
        }
      }
    })
    .catch(() => { 
      showNotification('An error occurred during enrollment. Please try again.', 'error'); 
      if (enrollBtn) { 
        enrollBtn.disabled = false; 
        enrollBtn.textContent = 'Enroll'; 
      } 
    });
}

function unenrollFromCourse(courseCode, button) {
  button.disabled = true; button.textContent = 'Unenrolling...';
  fetch('../guest/handlers/ajax_handlers.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `action=unenroll&course_code=${encodeURIComponent(courseCode)}` })
    .then(response => response.json())
    .then(data => { if (data.success) { showNotification(data.message, 'success'); const newUrl = window.location.pathname + '?current_tab=enrolled'; window.location.assign(newUrl); } else { showNotification(data.message, 'error'); button.disabled = false; button.textContent = 'Yes, Unenroll'; } });
}

// Initialize enrollment buttons when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - setting up enrollment buttons');
    setupEnrollmentButtons();
  });
} else {
  console.log('DOM already loaded - setting up enrollment buttons immediately');
  setupEnrollmentButtons();
}

function setupEnrollmentButtons() {
  console.log('Setting up enrollment button listeners...');
  document.addEventListener('click', function(e) {
    const viewCard = e.target.closest('.view-course-content-btn');
    if (viewCard && !e.target.classList.contains('enroll-btn') && !e.target.classList.contains('unenroll-btn')) {
      return;
    }
    
    if (e.target.classList.contains('enroll-btn') && !e.target.disabled) {
      console.log('✅ GUEST ENROLL BUTTON CLICKED!');
      const courseCard = e.target.closest('.course-card') || e.target.closest('.batch-card');
      if (courseCard) {
        const courseCode = courseCard.getAttribute('data-course') || courseCard.getAttribute('data-course-code');
        const courseName = courseCard.getAttribute('data-title') || courseCard.getAttribute('data-course-name');
        console.log('Extracted course data - Code:', courseCode, 'Name:', courseName);
        if (courseCode && courseName) {
          openEnrollModal(courseCode, courseName);
        }
      }
    }
    if (e.target.classList.contains('unenroll-btn') && !e.target.disabled) {
      let courseCode = e.target.getAttribute('data-course-code');
      let courseName = e.target.getAttribute('data-course-name');
      if (!courseCode) { 
        const courseCard = e.target.closest('.course-card') || e.target.closest('.batch-card'); 
        if (courseCard) {
          courseCode = courseCard.getAttribute('data-course') || courseCard.getAttribute('data-course-code'); 
          courseName = courseCard.getAttribute('data-title') || courseCard.getAttribute('data-course-name'); 
        }
      }
      if (courseCode) {
        openUnenrollModal(courseCode, courseName);
      }
    }
  });
}
