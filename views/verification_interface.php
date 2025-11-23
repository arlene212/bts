<?php ?>
<section class="verification-section">
  <div class="verification-container">
    <h2>Account Verification</h2>
    <p class="verification-subtitle">Complete your account verification to access all features</p>
    
    <div class="verification-status">
      <div class="verification-card" id="emailVerificationCard">
        <div class="card-header">
          <div class="card-icon">
            <i class="fas fa-envelope"></i>
          </div>
          <div class="card-title">
            <h3>Email Verification</h3>
            <span class="status-badge" id="emailStatus">Pending</span>
          </div>
        </div>
        <div class="card-content">
          <p id="emailMessage">Verify your email address to secure your account</p>
          <div class="card-actions" id="emailActions">
            <button class="btn btn-primary" id="sendEmailVerification">Send Verification</button>
          </div>
          <div class="verification-form hidden" id="emailVerificationForm">
            <input type="text" id="emailVerificationCode" placeholder="Enter verification code" maxlength="6">
            <button class="btn btn-success" id="verifyEmailCode">Verify</button>
            <button class="btn btn-secondary" id="resendEmailCode">Resend Code</button>
          </div>
        </div>
      </div>
      
      <div class="verification-card" id="studentIdVerificationCard">
        <div class="card-header">
          <div class="card-icon">
            <i class="fas fa-id-card"></i>
          </div>
          <div class="card-title">
            <h3>Student ID Verification</h3>
            <span class="status-badge" id="studentIdStatus">Pending</span>
          </div>
        </div>
        <div class="card-content">
          <p id="studentIdMessage">Verify your Student ID to access course materials</p>
          <div class="card-actions" id="studentIdActions">
            <button class="btn btn-primary" id="startStudentIdVerification">Start Verification</button>
          </div>
          <div class="verification-form hidden" id="studentIdVerificationForm">
            <input type="text" id="studentIdInput" placeholder="Enter Student ID (e.g., BTS-2024-001)" maxlength="12">
            <input type="text" id="studentNameInput" placeholder="Full Name as per school records">
            <button class="btn btn-success" id="submitStudentId">Submit</button>
            <button class="btn btn-secondary" id="cancelStudentId">Cancel</button>
          </div>
        </div>
      </div>
    </div>
    
    <div class="verification-progress">
      <div class="progress-bar">
        <div class="progress-fill" id="progressFill"></div>
      </div>
      <p class="progress-text" id="progressText">0% Complete</p>
    </div>
    
    <div class="verification-benefits">
      <h3>Benefits of Verification</h3>
      <ul>
        <li><i class="fas fa-check"></i> Access to all course materials</li>
        <li><i class="fas fa-check"></i> Eligibility for certificates</li>
        <li><i class="fas fa-check"></i> Enhanced account security</li>
        <li><i class="fas fa-check"></i> Priority support</li>
        <li><i class="fas fa-check"></i> Access to premium features</li>
      </ul>
    </div>
  </div>
</section>

<style>
.verification-section {
  padding: 2rem;
  max-width: 800px;
  margin: 0 auto;
}

.verification-container h2 {
  text-align: center;
  color: var(--primary-700);
  margin-bottom: 0.5rem;
  font-size: var(--font-size-2xl);
}

.verification-subtitle {
  text-align: center;
  color: var(--secondary-600);
  margin-bottom: 2rem;
  font-size: var(--font-size-lg);
}

.verification-status {
  display: grid;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.verification-card {
  background: white;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  border: 1px solid var(--secondary-200);
  overflow: hidden;
  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.verification-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.card-header {
  display: flex;
  align-items: center;
  padding: 1.5rem;
  background: var(--secondary-50);
  border-bottom: 1px solid var(--secondary-200);
}

.card-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-lg);
  background: var(--primary-500);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  margin-right: 1rem;
}

.card-title h3 {
  margin: 0;
  color: var(--secondary-800);
  font-size: var(--font-size-lg);
  font-weight: 600;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: var(--radius-md);
  font-size: var(--font-size-xs);
  font-weight: 600;
  text-transform: uppercase;
  margin-top: 0.25rem;
}

.status-pending {
  background: var(--warning-100);
  color: var(--warning-700);
}

.status-verified {
  background: var(--success-100);
  color: var(--success-700);
}

.status-failed {
  background: var(--error-100);
  color: var(--error-700);
}

.card-content {
  padding: 1.5rem;
}

.card-content p {
  color: var(--secondary-600);
  margin-bottom: 1rem;
  line-height: 1.6;
}

.card-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.verification-form {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  align-items: flex-end;
  margin-top: 1rem;
}

.verification-form input {
  flex: 1;
  min-width: 200px;
  padding: 0.75rem 1rem;
  border: 1px solid var(--secondary-300);
  border-radius: var(--radius-md);
  font-size: var(--font-size-base);
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}

.verification-form input:focus {
  outline: none;
  border-color: var(--primary-400);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.verification-progress {
  background: white;
  border-radius: var(--radius-lg);
  padding: 1.5rem;
  box-shadow: var(--shadow-md);
  border: 1px solid var(--secondary-200);
  margin-bottom: 2rem;
}

.verification-progress h3 {
  margin-bottom: 1rem;
  color: var(--secondary-800);
  font-size: var(--font-size-lg);
}

.progress-bar {
  background: var(--secondary-200);
  border-radius: var(--radius-xl);
  height: 8px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--primary-500), var(--primary-600));
  border-radius: var(--radius-xl);
  transition: width var(--transition-normal);
  width: 0%;
}

.progress-text {
  text-align: center;
  color: var(--secondary-600);
  font-weight: 600;
  font-size: var(--font-size-sm);
}

.verification-benefits {
  background: var(--primary-50);
  border-radius: var(--radius-lg);
  padding: 1.5rem;
  border: 1px solid var(--primary-200);
}

.verification-benefits h3 {
  color: var(--primary-700);
  margin-bottom: 1rem;
  font-size: var(--font-size-lg);
}

.verification-benefits ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.verification-benefits li {
  display: flex;
  align-items: center;
  padding: 0.5rem 0;
  color: var(--primary-600);
  font-size: var(--font-size-sm);
}

.verification-benefits li i {
  color: var(--success-500);
  margin-right: 0.75rem;
  font-size: var(--font-size-base);
}

.hidden {
  display: none !important;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: var(--radius-md);
  font-weight: 600;
  text-decoration: none;
  display: inline-block;
  transition: all var(--transition-fast);
  cursor: pointer;
  font-size: var(--font-size-sm);
  text-align: center;
}

.btn-primary {
  background: var(--primary-500);
  color: white;
}

.btn-primary:hover {
  background: var(--primary-600);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-success {
  background: var(--success-500);
  color: white;
}

.btn-success:hover {
  background: #059669;
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-secondary {
  background: var(--secondary-200);
  color: var(--secondary-700);
}

.btn-secondary:hover {
  background: var(--secondary-300);
  transform: translateY(-1px);
}

@media (max-width: 768px) {
  .verification-section {
    padding: 1rem;
  }
  
  .card-header {
    padding: 1rem;
  }
  
  .card-content {
    padding: 1rem;
  }
  
  .verification-form {
    flex-direction: column;
  }
  
  .verification-form input {
    min-width: auto;
  }
  
  .card-actions {
    flex-direction: column;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  loadVerificationStatus();
  setupVerificationHandlers();
});

function loadVerificationStatus() {
  fetch('../php/get_verification_status.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        updateVerificationUI(data.status);
        updateProgressBar(data.status);
      } else {
        showNotification('Failed to load verification status', 'error');
      }
    })
    .catch(error => {
      console.error('Error loading verification status:', error);
      showNotification('Error loading verification status', 'error');
    });
}

function updateVerificationUI(status) {
  // Email verification status
  const emailStatus = document.getElementById('emailStatus');
  const emailMessage = document.getElementById('emailMessage');
  const emailActions = document.getElementById('emailActions');
  const emailVerificationForm = document.getElementById('emailVerificationForm');
  
  if (status.email_verified) {
    emailStatus.textContent = 'Verified';
    emailStatus.className = 'status-badge status-verified';
    emailMessage.textContent = 'Your email address has been verified successfully';
    emailActions.classList.add('hidden');
    emailVerificationForm.classList.add('hidden');
  } else {
    emailStatus.textContent = 'Pending';
    emailStatus.className = 'status-badge status-pending';
    emailMessage.textContent = 'Verify your email address to secure your account';
    emailActions.classList.remove('hidden');
    emailVerificationForm.classList.add('hidden');
  }
  
  // Student ID verification status
  const studentIdStatus = document.getElementById('studentIdStatus');
  const studentIdMessage = document.getElementById('studentIdMessage');
  const studentIdActions = document.getElementById('studentIdActions');
  const studentIdVerificationForm = document.getElementById('studentIdVerificationForm');
  
  if (status.student_id_verified) {
    studentIdStatus.textContent = 'Verified';
    studentIdStatus.className = 'status-badge status-verified';
    studentIdMessage.textContent = 'Your Student ID has been verified successfully';
    studentIdActions.classList.add('hidden');
    studentIdVerificationForm.classList.add('hidden');
  } else {
    studentIdStatus.textContent = 'Pending';
    studentIdStatus.className = 'status-badge status-pending';
    studentIdMessage.textContent = 'Verify your Student ID to access course materials';
    studentIdActions.classList.remove('hidden');
    studentIdVerificationForm.classList.add('hidden');
  }
}

function updateProgressBar(status) {
  let progress = 0;
  if (status.email_verified) progress += 50;
  if (status.student_id_verified) progress += 50;
  
  const progressFill = document.getElementById('progressFill');
  const progressText = document.getElementById('progressText');
  
  progressFill.style.width = progress + '%';
  progressText.textContent = progress + '% Complete';
}

function setupVerificationHandlers() {
  // Email verification handlers
  document.getElementById('sendEmailVerification')?.addEventListener('click', sendEmailVerification);
  document.getElementById('verifyEmailCode')?.addEventListener('click', verifyEmailCode);
  document.getElementById('resendEmailCode')?.addEventListener('click', resendEmailCode);
  
  // Student ID verification handlers
  document.getElementById('startStudentIdVerification')?.addEventListener('click', startStudentIdVerification);
  document.getElementById('submitStudentId')?.addEventListener('click', submitStudentId);
  document.getElementById('cancelStudentId')?.addEventListener('click', cancelStudentIdVerification);
}

function sendEmailVerification() {
  const button = document.getElementById('sendEmailVerification');
  button.disabled = true;
  button.textContent = 'Sending...';
  
  fetch('../php/send_email_verification.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'send_verification' })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showNotification(data.message, 'success');
      document.getElementById('emailActions').classList.add('hidden');
      document.getElementById('emailVerificationForm').classList.remove('hidden');
    } else {
      showNotification(data.message, 'error');
    }
  })
  .catch(error => {
    console.error('Error sending email verification:', error);
    showNotification('Failed to send verification email', 'error');
  })
  .finally(() => {
    button.disabled = false;
    button.textContent = 'Send Verification';
  });
}

function verifyEmailCode() {
  const code = document.getElementById('emailVerificationCode').value.trim();
  if (!code) {
    showNotification('Please enter the verification code', 'error');
    return;
  }
  
  fetch('../php/verify_email_code.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ verification_code: code })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showNotification(data.message, 'success');
      loadVerificationStatus(); // Refresh status
    } else {
      showNotification(data.message, 'error');
    }
  })
  .catch(error => {
    console.error('Error verifying email code:', error);
    showNotification('Failed to verify email code', 'error');
  });
}

function resendEmailCode() {
  sendEmailVerification(); // Same as initial send
}

function startStudentIdVerification() {
  document.getElementById('studentIdActions').classList.add('hidden');
  document.getElementById('studentIdVerificationForm').classList.remove('hidden');
}

function submitStudentId() {
  const studentId = document.getElementById('studentIdInput').value.trim();
  const studentName = document.getElementById('studentNameInput').value.trim();
  
  if (!studentId || !studentName) {
    showNotification('Please fill in all fields', 'error');
    return;
  }
  
  // Validate Student ID format
  if (!/^BTS-\d{4}-\d{3}$/.test(studentId)) {
    showNotification('Invalid Student ID format. Use format: BTS-YYYY-NNN', 'error');
    return;
  }
  
  const button = document.getElementById('submitStudentId');
  button.disabled = true;
  button.textContent = 'Submitting...';
  
  fetch('../php/verify_student_id.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      student_id: studentId,
      student_name: studentName
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showNotification(data.message, 'success');
      loadVerificationStatus(); // Refresh status
    } else {
      showNotification(data.message, 'error');
    }
  })
  .catch(error => {
    console.error('Error verifying student ID:', error);
    showNotification('Failed to verify student ID', 'error');
  })
  .finally(() => {
    button.disabled = false;
    button.textContent = 'Submit';
  });
}

function cancelStudentIdVerification() {
  document.getElementById('studentIdActions').classList.remove('hidden');
  document.getElementById('studentIdVerificationForm').classList.add('hidden');
  document.getElementById('studentIdInput').value = '';
  document.getElementById('studentNameInput').value = '';
}

function showNotification(message, type) {
  // Simple notification implementation
  console.log(`[${type.toUpperCase()}] ${message}`);
  // You can enhance this with a proper notification system
}
</script>