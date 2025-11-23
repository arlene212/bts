// User Verification Management
document.addEventListener('DOMContentLoaded', function() {
    setupVerificationHandlers();
});

function setupVerificationHandlers() {
    // Email verification
    const verifyEmailBtn = document.getElementById('verifyEmailBtn');
    if (verifyEmailBtn) {
        verifyEmailBtn.addEventListener('click', handleEmailVerification);
    }
    
    // Student ID verification
    const setStudentIdBtn = document.getElementById('setStudentIdBtn');
    if (setStudentIdBtn) {
        setStudentIdBtn.addEventListener('click', handleStudentIdVerification);
    }
    
    // Phone verification
    const verifyPhoneBtn = document.getElementById('verifyPhoneBtn');
    if (verifyPhoneBtn) {
        verifyPhoneBtn.addEventListener('click', handlePhoneVerification);
    }
}

function handleEmailVerification() {
    const button = document.getElementById('verifyEmailBtn');
    const originalText = button.textContent;
    
    button.disabled = true;
    button.textContent = 'Sending...';
    
    fetch('../php/verification_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=send_email_verification'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            // Show verification input if needed
            showEmailVerificationInput(data.debug_url);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Email verification error:', error);
        showNotification('Failed to send verification email. Please try again.', 'error');
    })
    .finally(() => {
        button.disabled = false;
        button.textContent = originalText;
    });
}

function showEmailVerificationInput(debugUrl) {
    // Create verification input section
    const verificationSection = document.querySelector('.verification-section');
    const emailVerificationItem = verificationSection.querySelector('.verification-item');
    
    // Remove existing verification input if any
    const existingInput = emailVerificationItem.querySelector('.email-verification-input');
    if (existingInput) {
        existingInput.remove();
    }
    
    // Create new verification input
    const verificationInput = document.createElement('div');
    verificationInput.className = 'email-verification-input';
    verificationInput.innerHTML = `
        <div class="verification-code-input">
            <input type="text" id="emailVerificationCode" placeholder="Enter verification code" maxlength="6">
            <button type="button" class="verify-btn" id="confirmEmailVerification">Verify</button>
        </div>
        ${debugUrl ? `<small class="text-muted">Debug: ${debugUrl}</small>` : ''}
    `;
    
    emailVerificationItem.appendChild(verificationInput);
    
    // Add event listener for confirmation
    document.getElementById('confirmEmailVerification').addEventListener('click', function() {
        const code = document.getElementById('emailVerificationCode').value.trim();
        if (!code) {
            showNotification('Please enter the verification code.', 'error');
            return;
        }
        confirmEmailVerification(code);
    });
}

function confirmEmailVerification(code) {
    fetch('../php/verification_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=verify_email&token=${encodeURIComponent(code)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            // Update UI
            updateVerificationUI('email', 'verified');
            // Remove verification input
            const verificationInput = document.querySelector('.email-verification-input');
            if (verificationInput) {
                verificationInput.remove();
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Email verification confirmation error:', error);
        showNotification('Failed to verify email. Please try again.', 'error');
    });
}

function handleStudentIdVerification() {
    const studentIdInput = document.getElementById('studentIdInput');
    const studentId = studentIdInput.value.trim();
    
    if (!studentId) {
        showNotification('Please enter your Student ID.', 'error');
        studentIdInput.focus();
        return;
    }
    
    const button = document.getElementById('setStudentIdBtn');
    const originalText = button.textContent;
    
    button.disabled = true;
    button.textContent = 'Setting...';
    
    fetch('../php/verification_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update_student_id&student_id=${encodeURIComponent(studentId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            updateVerificationUI('student_id', 'verified');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Student ID update error:', error);
        showNotification('Failed to update Student ID. Please try again.', 'error');
    })
    .finally(() => {
        button.disabled = false;
        button.textContent = originalText;
    });
}

function handlePhoneVerification() {
    const contactNumber = document.getElementById('contactNumber').value.trim();
    
    if (!contactNumber) {
        showNotification('Please add your contact number first.', 'error');
        return;
    }
    
    // Validate phone format
    const phoneRegex = /^(09\d{9}|\+639\d{9})$/;
    if (!phoneRegex.test(contactNumber)) {
        showNotification('Please enter a valid phone number (09XXXXXXXXX or +639XXXXXXXXX).', 'error');
        return;
    }
    
    const button = document.getElementById('verifyPhoneBtn');
    const originalText = button.textContent;
    
    button.disabled = true;
    button.textContent = 'Sending...';
    
    fetch('../php/verification_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=send_phone_verification&phone=${encodeURIComponent(contactNumber)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            showPhoneVerificationInput(data.debug_code);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Phone verification error:', error);
        showNotification('Failed to send verification code. Please try again.', 'error');
    })
    .finally(() => {
        button.disabled = false;
        button.textContent = originalText;
    });
}

function showPhoneVerificationInput(debugCode) {
    // Create verification input section
    const verificationSection = document.querySelector('.verification-section');
    const phoneVerificationItem = verificationSection.querySelectorAll('.verification-item')[2]; // Third item (phone)
    
    // Remove existing verification input if any
    const existingInput = phoneVerificationItem.querySelector('.phone-verification-input');
    if (existingInput) {
        existingInput.remove();
    }
    
    // Create new verification input
    const verificationInput = document.createElement('div');
    verificationInput.className = 'phone-verification-input';
    verificationInput.innerHTML = `
        <div class="verification-code-input">
            <input type="text" id="phoneVerificationCode" placeholder="Enter 6-digit code" maxlength="6">
            <button type="button" class="verify-btn" id="confirmPhoneVerification">Verify</button>
        </div>
        ${debugCode ? `<small class="text-muted">Debug: ${debugCode}</small>` : ''}
    `;
    
    phoneVerificationItem.appendChild(verificationInput);
    
    // Add event listener for confirmation
    document.getElementById('confirmPhoneVerification').addEventListener('click', function() {
        const code = document.getElementById('phoneVerificationCode').value.trim();
        if (!code) {
            showNotification('Please enter the verification code.', 'error');
            return;
        }
        confirmPhoneVerification(code);
    });
}

function confirmPhoneVerification(code) {
    fetch('../php/verification_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=verify_phone&token=${encodeURIComponent(code)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            updateVerificationUI('phone', 'verified');
            // Remove verification input
            const verificationInput = document.querySelector('.phone-verification-input');
            if (verificationInput) {
                verificationInput.remove();
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Phone verification confirmation error:', error);
        showNotification('Failed to verify phone. Please try again.', 'error');
    });
}

function updateVerificationUI(type, status) {
    const verificationSection = document.querySelector('.verification-section');
    
    switch (type) {
        case 'email':
            const emailBadge = verificationSection.querySelector('.verification-item .status-badge');
            if (emailBadge) {
                emailBadge.className = 'status-badge verified';
                emailBadge.textContent = 'Verified';
            }
            const emailButton = document.getElementById('verifyEmailBtn');
            if (emailButton) {
                emailButton.style.display = 'none';
            }
            break;
            
        case 'student_id':
            const studentIdBadge = verificationSection.querySelectorAll('.verification-item .status-badge')[1];
            if (studentIdBadge) {
                studentIdBadge.className = 'status-badge verified';
                studentIdBadge.textContent = 'Set';
            }
            const studentIdInput = document.querySelector('.student-id-input');
            if (studentIdInput) {
                studentIdInput.style.display = 'none';
            }
            // Add display of current ID
            const studentIdItem = verificationSection.querySelectorAll('.verification-item')[1];
            const currentId = document.getElementById('studentIdInput').value.trim();
            const displayDiv = document.createElement('div');
            displayDiv.className = 'student-id-display';
            displayDiv.innerHTML = `<small>Current ID: ${currentId}</small>`;
            studentIdItem.appendChild(displayDiv);
            break;
            
        case 'phone':
            const phoneBadge = verificationSection.querySelectorAll('.verification-item .status-badge')[2];
            if (phoneBadge) {
                phoneBadge.className = 'status-badge verified';
                phoneBadge.textContent = 'Verified';
            }
            const phoneButton = document.getElementById('verifyPhoneBtn');
            if (phoneButton) {
                phoneButton.style.display = 'none';
            }
            break;
    }
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Add notification styles if not already present
if (!document.querySelector('#notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            max-width: 400px;
            z-index: 1000;
            animation: slideInRight 0.3s ease;
        }
        
        .notification-success {
            border-left: 4px solid #10b981;
            color: #065f46;
        }
        
        .notification-error {
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }
        
        .notification-info {
            border-left: 4px solid #3b82f6;
            color: #1d4ed8;
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: color 0.2s;
        }
        
        .notification-close:hover {
            color: #374151;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
}