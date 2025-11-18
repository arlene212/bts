function initializeModalButtons() {
  const modalButtons = { 'createTrainerBtn': 'createTrainerModal', 'createTraineeBtn': 'createTraineeModal', 'addCourseBtn': 'addCourseModal', 'addBatchBtn': 'addBatchModal', 'addAnnouncementBtn': 'addAnnouncementModal', 'editProfileBtn': 'editProfileModal' };
  Object.keys(modalButtons).forEach(buttonId => {
    const button = document.getElementById(buttonId);
    const modalId = modalButtons[buttonId];
    if (button) {
      const newButton = button.cloneNode(true);
      button.parentNode.replaceChild(newButton, button);
      newButton.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); openModal(modalId); });
    }
  });
}

function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    modal.style.opacity = '0';
    document.body.style.overflow = 'hidden';
    setTimeout(() => { modal.style.opacity = '1'; modal.style.transition = 'opacity 0.3s ease'; }, 10);
    const firstInput = modal.querySelector('input, textarea, select');
    if (firstInput) { setTimeout(() => firstInput.focus(), 300); }
    if (typeof dispatchModalOpened === 'function') { dispatchModalOpened(modalId); } else { document.dispatchEvent(new CustomEvent('modalOpened', { detail: { modalId } })); }
  }
}

function dispatchModalOpened(modalId) { document.dispatchEvent(new CustomEvent('modalOpened', { detail: { modalId } })); }

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.opacity = '0';
    setTimeout(() => { modal.style.display = 'none'; modal.classList.add('hidden'); document.body.style.overflow = 'auto'; const form = modal.querySelector('form'); if (form) form.reset(); }, 300);
  }
}

function updateConfirmationWithCredentials(type, credentials) {
  if (typeof showAccountCreationSuccess === 'function') { showAccountCreationSuccess(type, credentials); } else { document.dispatchEvent(new CustomEvent('accountCredentials', { detail: { type, credentials } })); }
}

function showAccountConfirmation(type, form) {
  const formData = new FormData(form);
  let firstName, middleName, lastName, suffix, contactNumber, courseInfo, batchInfo;
  if (type === 'trainer') {
    firstName = formData.get('trainer_first_name'); middleName = formData.get('trainer_middle_name') || ''; lastName = formData.get('trainer_last_name'); suffix = formData.get('trainer_suffix') || ''; contactNumber = formData.get('trainer_number');
    const courseSelect = form.querySelector('#trainer_courses');
    const selectedCourses = Array.from(courseSelect.selectedOptions).map(opt => opt.textContent.split(' (')[0]);
    courseInfo = selectedCourses.length > 0 ? selectedCourses.join(', ') : 'None'; batchInfo = 'N/A';
  } else if (type === 'trainee') {
    firstName = formData.get('trainee_first_name'); middleName = formData.get('trainee_middle_name') || ''; lastName = formData.get('trainee_last_name'); suffix = formData.get('trainee_suffix') || ''; contactNumber = formData.get('trainee_number');
    const courseCode = formData.get('trainee_course'); const batchName = formData.get('trainee_batch');
    const courseSelect = form.querySelector('#trainee_course'); const selectedCourse = courseSelect.selectedOptions[0];
    courseInfo = selectedCourse ? selectedCourse.textContent.split(' (')[0] : 'Not assigned'; batchInfo = batchName || 'Not assigned';
  }
  const expectedInfo = generateExpectedAccountInfo(type, firstName, lastName);
  const content = document.getElementById('accountConfirmationContent');
  content.innerHTML = `
    <div class="confirmation-details"><h3>Please review the ${type} details:</h3>
      <div class="confirmation-section"><h4>Personal Information</h4><div class="confirmation-item"><strong>Full Name:</strong> ${firstName} ${middleName} ${lastName} ${suffix}</div><div class="confirmation-item"><strong>Contact Number:</strong> ${contactNumber}</div></div>
      <div class="confirmation-section"><h4>Account Information</h4><div class="confirmation-item"><strong>Expected User ID:</strong> ${expectedInfo.userId}</div><div class="confirmation-item"><strong>Expected Email:</strong> ${expectedInfo.email}</div></div>
      <div class="confirmation-section"><h4>${type === 'trainer' ? 'Course Assignment' : 'Enrollment Information'}</h4><div class="confirmation-item"><strong>${type === 'trainer' ? 'Assigned Courses' : 'Course'}:</strong> ${courseInfo}</div>${type === 'trainee' ? `<div class="confirmation-item"><strong>Batch:</strong> ${batchInfo}</div>` : ''}</div>
      <div class="confirmation-note"><p><em>Click "Confirm Creation" to generate the account with auto-generated credentials.</em></p></div>
      <div id="accountCredentialsSection" style="display: none; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #28a745;"></div>
    </div>`;
  document.getElementById('accountConfirmationTitle').textContent = `Confirm ${type.charAt(0).toUpperCase() + type.slice(1)} Creation`;
  const confirmBtn = document.getElementById('confirmAccountBtn');
  confirmBtn.textContent = 'Confirm Creation';
  confirmBtn.onclick = function() { submitAccountForm(type, form); };
  openModal('accountConfirmationModal');
}

function generateExpectedAccountInfo(type, firstName, lastName) {
  const datePart = new Date().toLocaleDateString('en-US', { month: '2-digit', day: '2-digit' }).replace(/\//g, '');
  const randomPart = Math.random().toString().substr(2, 3);
  let userId, email;
  if (type === 'trainer') {
    userId = '2' + datePart + randomPart;
    const cleanLastName = lastName.replace(/[^a-zA-Z]/g, '').toLowerCase();
    const cleanFirstName = firstName.replace(/[^a-zA-Z]/g, '').toLowerCase();
    email = `${cleanLastName}.${cleanFirstName}@bts.gov.ph`;
  } else {
    userId = '3' + datePart + randomPart;
    const cleanLastName = lastName.replace(/[^a-zA-Z]/g, '').toLowerCase();
    email = `${cleanLastName}.${userId}@bts.gov.ph`;
  }
  return { userId, email };
}

function submitAccountForm(type, form) {
  const formData = new FormData(form);
  const url = type === 'trainer' ? '../php/create_trainer.php' : '../php/create_trainee.php';
  const confirmBtn = document.getElementById('confirmAccountBtn');
  const originalText = confirmBtn.textContent;
  confirmBtn.textContent = 'Creating...';
  confirmBtn.disabled = true;
  fetch(url, { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => { if (data.success) { showAccountCreationSuccess(type, data.credentials); } else { alert(`Error creating ${type}: ` + data.message); closeModal('accountConfirmationModal'); } })
    .catch(() => { alert(`Error creating ${type}. Please try again.`); closeModal('accountConfirmationModal'); })
    .finally(() => { confirmBtn.textContent = originalText; confirmBtn.disabled = false; });
}

function showAccountCreationSuccess(type, credentials) {
  const credentialsSection = document.getElementById('accountCredentialsSection');
  const confirmBtn = document.getElementById('confirmAccountBtn');
  credentialsSection.style.display = 'block';
  credentialsSection.innerHTML = `
    <div class="success-message" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;"><h4 style="margin: 0 0 10px 0;">✅ ${type.charAt(0).toUpperCase() + type.slice(1)} Created Successfully!</h4><p style="margin: 0;">Account has been created with the following credentials:</p></div>
    <div class="credentials-display"><div class="credential-item"><strong>User ID:</strong> <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 1.1em;">${credentials.user_id}</span></div><div class="credential-item"><strong>Email:</strong> <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-family: monospace;">${credentials.email}</span></div><div class="credential-item"><strong>Temporary Password:</strong> <span style="background: #fff3cd; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 1.1em; font-weight: bold;">${credentials.temp_password}</span></div></div>
    <div class="confirmation-note" style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 5px; border-left: 4px solid #007bff;"><p style="margin: 0;"><strong>⚠️ Important:</strong> Please save these credentials as they will not be shown again.</p></div>`;
  confirmBtn.textContent = 'Done';
  confirmBtn.onclick = function() { closeModal('accountConfirmationModal'); const targetTab = type === 'trainer' ? 'trainers' : 'trainees'; setTimeout(() => window.location.href = window.location.pathname + `?current_tab=${targetTab}`, 300); };
  credentialsSection.scrollIntoView({ behavior: 'smooth' });
}