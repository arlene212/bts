function setupEnrollmentActions() {
  const enrollmentTable = document.getElementById('enrollments');
  if (!enrollmentTable) return;
  enrollmentTable.addEventListener('click', function(e) {
    const button = e.target.closest('.action-btn');
    if (button) {
      const action = button.dataset.action;
      const enrollmentId = button.closest('.enrollment-actions').dataset.enrollmentId;
      const isReject = action === 'reject';
      const title = isReject ? 'Reject Enrollment' : 'Approve Enrollment';
      const label = isReject ? 'Please provide a reason for rejection (optional):' : 'Optional remarks for approval:';
      const placeholder = isReject ? 'Reason for rejection (optional)' : 'Remarks (optional)';
      if (typeof showRemarks === 'function') {
        showRemarks(title, label, placeholder, (val) => { processEnrollment(enrollmentId, action, val || '', button); });
      } else {
        processEnrollment(enrollmentId, action, '', button);
      }
    }
  });
}

function processEnrollment(enrollmentId, action, remarks, button) {
  const formData = new FormData();
  formData.append('enrollment_id', enrollmentId);
  formData.append('action', action);
  formData.append('remarks', remarks);
  button.disabled = true;
  button.textContent = 'Processing...';
  fetch('../php/process_enrollment.php', { method: 'POST', body: formData })
    .then(handleApiError)
    .then(data => {
      if (data.success) {
        alert(data.message);
        button.closest('tr').remove();
      } else {
        alert('Error: ' + data.message);
        button.disabled = false;
        button.textContent = action.charAt(0).toUpperCase() + action.slice(1);
      }
    })
    .catch(() => {
      alert('An unexpected error occurred.');
      button.disabled = false;
      button.textContent = action.charAt(0).toUpperCase() + action.slice(1);
    });
}
