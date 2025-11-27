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
  console.log('Course Code:', courseCode);
  console.log('Course Name:', courseName);
  
  if (!courseCode) {
    console.error('ERROR: No course code!');
    alert('Error: Course code is missing.');
    return;
  }
  
  const enrollBtn = document.querySelector(`.enroll-btn[data-course-code="${courseCode}"]`) || 
                     document.querySelector(`.course-card[data-course="${courseCode}"] .enroll-btn`);
  
  if (enrollBtn) {
    enrollBtn.disabled = true;
    enrollBtn.textContent = 'Requesting...';
  }
  
  // Create form data
  const params = new URLSearchParams();
  params.append('course_code', courseCode);
  
  console.log('📤 Sending to: ../php/enhanced_enrollment.php');
  console.log('📦 Data:', {course_code: courseCode});
  
  fetch('../php/enhanced_enrollment.php', {
    method: 'POST',
    body: params,
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'Accept': 'application/json'
    }
  })
  .then(response => {
    console.log('📥 Response status:', response.status);
    return response.text();
  })
  .then(text => {
    console.log('📄 Raw response:', text);
    try {
      const data = JSON.parse(text);
      console.log('✅ Parsed JSON:', data);
      
      if (data.success) {
        console.log('🎉 SUCCESS!');
        
        if (enrollBtn) {
          const badge = document.createElement('span');
          badge.className = 'status-badge status-pending';
          badge.innerHTML = '<i class="fas fa-clock"></i> Request Pending';
          enrollBtn.parentNode.replaceChild(badge, enrollBtn);
        }
        
        alert(data.message || 'Enrollment request sent!');
        
        setTimeout(() => location.reload(), 1000);
      } else {
        console.error('❌ Failed:', data.message);
        alert(data.message || 'Request failed');
        
        if (enrollBtn) {
          enrollBtn.disabled = false;
          enrollBtn.textContent = 'Request to Enroll';
        }
      }
    } catch (e) {
      console.error('❌ JSON parse error:', e);
      console.error('Response was:', text);
      alert('Server error - check console');
      
      if (enrollBtn) {
        enrollBtn.disabled = false;
        enrollBtn.textContent = 'Request to Enroll';
      }
    }
  })
  .catch(error => {
    console.error('❌ Network error:', error);
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
  // Only check for cancel button, since enroll buttons are handled directly
  if (e.target.classList.contains('btn-cancel') && !e.target.disabled) {
    console.log('Cancel button clicked');
    const requestId = e.target.getAttribute('data-request-id');
    e.target.disabled = true;
    e.target.textContent = 'Canceling...';
    
    const body = new URLSearchParams({ 
      action: 'cancel_request', 
      request_id: requestId 
    });
    
    fetch('../trainee/handlers/ajax_handlers.php', { 
      method: 'POST', 
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, 
      body 
    })
      .then(response => response.json())
      .then(data => { 
        if (data.success) { 
          alert('Request canceled'); 
          location.reload();
        } else { 
          alert(data.message || 'Cancel failed'); 
          e.target.disabled = false; 
          e.target.textContent = 'Cancel'; 
        } 
      })
      .catch(() => { 
        alert('Network error'); 
        e.target.disabled = false; 
        e.target.textContent = 'Cancel'; 
      });
  }
});

// Add this at the END of your ajax.js file

console.log('=== SETTING UP ENROLL BUTTONS ===');

// Wait for DOM to be ready
setTimeout(function() {
  const enrollButtons = document.querySelectorAll('.enroll-btn');
  console.log('Found enroll buttons:', enrollButtons.length);
  
  if (enrollButtons.length === 0) {
    console.warn('⚠️ No enroll buttons found! Check if you are on the correct tab.');
  }
  
  enrollButtons.forEach(function(button, index) {
    console.log(`Setting up button ${index + 1}:`, {
      courseCode: button.getAttribute('data-course-code'),
      courseName: button.getAttribute('data-course-name')
    });
    
    // Add click handler directly to button
    button.onclick = function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      console.log('✅✅✅ BUTTON CLICKED!');
      
      const courseCode = this.getAttribute('data-course-code');
      const courseName = this.getAttribute('data-course-name');
      
      console.log('Opening modal for:', courseCode, courseName);
      
      if (!courseCode) {
        alert('Error: Course code not found');
        return false;
      }
      
      openEnrollModal(courseCode, courseName);
      return false;
    };
  });
  
  console.log('✅ Button setup complete');
}, 500); // Give the page 500ms to load

// Also set up when tab changes
document.addEventListener('click', function(e) {
  // Check if clicking on a tab
  if (e.target.closest('[data-tab]') || e.target.closest('.nav-item')) {
    console.log('Tab clicked, will reinitialize buttons...');
    setTimeout(function() {
      const enrollButtons = document.querySelectorAll('.enroll-btn');
      console.log('Reinitializing', enrollButtons.length, 'buttons');
      enrollButtons.forEach(function(button) {
        if (!button.onclick) {
          button.onclick = function(e) {
            e.preventDefault();
            const courseCode = this.getAttribute('data-course-code');
            const courseName = this.getAttribute('data-course-name');
            openEnrollModal(courseCode, courseName);
            return false;
          };
        }
      });
    }, 300);
  }
});