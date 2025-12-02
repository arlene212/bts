document.addEventListener("DOMContentLoaded", () => {
    console.log('Landing page initializing...');
    
    // Modern modal functionality with error handling
    const modalTriggers = document.querySelectorAll('[data-modal-trigger]');
    const modalCloses = document.querySelectorAll('.modal-close');
    const modals = document.querySelectorAll('.modal');
    
    // Open modal functions with error handling
    function openModal(modalId) {
        try {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
                
                // Focus management for accessibility
                const firstFocusable = modal.querySelector('input, button, a, [tabindex]:not([tabindex="-1"])');
                if (firstFocusable) {
                    setTimeout(() => firstFocusable.focus(), 100);
                }
                
                // Announce to screen readers
                const modalTitle = modal.querySelector('.modal-title');
                if (modalTitle) {
                    modalTitle.setAttribute('aria-live', 'polite');
                }
            }
        } catch (error) {
            console.error('Error opening modal:', error);
        }
    }
    
    // Close modal functions
    function closeModal(modalId) {
        try {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
                
                // Return focus to trigger element
                const trigger = document.querySelector(`[data-modal-trigger="${modalId}"]`);
                if (trigger) {
                    trigger.focus();
                }
            }
        } catch (error) {
            console.error('Error closing modal:', error);
        }
    }
    
    // Modal trigger event listeners
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const modalId = trigger.getAttribute('data-modal-trigger');
            if (modalId) {
                openModal(modalId);
            }
        });
        
        // Keyboard accessibility
        trigger.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const modalId = trigger.getAttribute('data-modal-trigger');
                if (modalId) {
                    openModal(modalId);
                }
            }
        });
    });
    
    // Modal close event listeners
    modalCloses.forEach(closeBtn => {
        closeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const modalId = closeBtn.getAttribute('data-modal');
            if (modalId) {
                closeModal(modalId);
            }
        });
    });
    
    // Close modal when clicking outside
    modals.forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                const modalId = modal.id;
                closeModal(modalId);
            }
        });
        
        // Keyboard navigation for modals
        modal.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                const modalId = modal.id;
                closeModal(modalId);
            }
        });
    });
    
    // Specific modal triggers for landing page with fallback
    const openLoginBtn = document.getElementById('openLogin');
    const openRegisterBtn = document.getElementById('openRegister');
    const openLoginFromRegisterBtn = document.getElementById('openLoginFromRegister');
    
    if (openLoginBtn) {
        openLoginBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openModal('loginModal');
        });
    }
    
    if (openRegisterBtn) {
        openRegisterBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openModal('registerModal');
        });
    }
    
    if (openLoginFromRegisterBtn) {
        openLoginFromRegisterBtn.addEventListener('click', (e) => {
            e.preventDefault();
            closeModal('registerModal');
            setTimeout(() => openModal('loginModal'), 150);
        });
    }

    // Enhanced Slideshow with error handling
    let slideIndex = 0;
    const slides = document.querySelectorAll(".slide");
    let slideInterval;
    
    if (slides.length > 0) {
        const showSlides = () => {
            try {
                slides.forEach(slide => slide.classList.remove("active"));
                slideIndex++;
                if (slideIndex > slides.length) { slideIndex = 1; }
                if (slides[slideIndex - 1]) {
                    slides[slideIndex - 1].classList.add("active");
                }
            } catch (error) {
                console.error('Error in slideshow:', error);
                clearInterval(slideInterval);
            }
        };
        
        // Start slideshow
        slideInterval = setInterval(showSlides, 5000);
        
        // Pause slideshow on hover for better UX
        const heroSection = document.querySelector('.hero');
        if (heroSection) {
            heroSection.addEventListener('mouseenter', () => {
                clearInterval(slideInterval);
            });
            
            heroSection.addEventListener('mouseleave', () => {
                slideInterval = setInterval(showSlides, 5000);
            });
        }
    }
    
    // Enhanced form validation and interaction
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        const passwordInput = document.getElementById('regPassword');
        const confirmPasswordInput = document.getElementById('regConfirmPassword');
        const emailInput = document.getElementById('regEmail');
        const contactInput = document.getElementById('regContact');
        const submitButton = registerForm.querySelector('button[type="submit"]');
        
        // Password strength checker with visual feedback
        function checkPasswordStrength(password) {
            let strength = 0;
            const patterns = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password),
                special: /[*\-_.]/.test(password)
            };
            
            Object.values(patterns).forEach(valid => {
                if (valid) strength++;
            });
            
            return { strength, patterns };
        }
        
        function updatePasswordStrength() {
            const password = passwordInput.value;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            const { strength } = checkPasswordStrength(password);
            
            if (password.length === 0) {
                strengthFill.style.width = '0%';
                strengthText.textContent = '';
                strengthFill.className = 'strength-fill';
                strengthText.className = 'strength-text';
                return;
            }
            
            const strengthLevels = [
                { class: 'weak', text: 'Weak', width: '20%' },
                { class: 'fair', text: 'Fair', width: '40%' },
                { class: 'good', text: 'Good', width: '60%' },
                { class: 'strong', text: 'Strong', width: '80%' },
                { class: 'strong', text: 'Very Strong', width: '100%' }
            ];
            
            const level = strengthLevels[Math.min(strength - 1, 4)];
            strengthFill.className = `strength-fill ${level.class}`;
            strengthText.className = `strength-text ${level.class}`;
            strengthText.textContent = level.text;
            strengthFill.style.width = level.width;
        }
        
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const passwordMatch = document.getElementById('passwordMatch');
            
            if (confirmPassword.length === 0) {
                passwordMatch.textContent = '';
                passwordMatch.className = 'form-validation';
                return true; // Empty is valid for progressive validation
            }
            
            if (password === confirmPassword) {
                passwordMatch.textContent = '✓ Passwords match';
                passwordMatch.className = 'form-validation success';
                return true;
            } else {
                passwordMatch.textContent = '✗ Passwords do not match';
                passwordMatch.className = 'form-validation error';
                return false;
            }
        }
        
        function validateEmail() {
            const email = emailInput.value;
            const emailValidation = document.getElementById('emailValidation');
            const emailPattern = /^[a-z0-9._%+-]+@(?:bts\.gov\.ph|gmail\.com)$/;
            
            if (email.length === 0) {
                emailValidation.textContent = '';
                emailValidation.className = 'form-validation';
                return true; // Empty is valid for progressive validation
            }
            
            if (emailPattern.test(email)) {
                emailValidation.textContent = '✓ Valid email format';
                emailValidation.className = 'form-validation success';
                return true;
            } else {
                emailValidation.textContent = '✗ Email must be from @bts.gov.ph or @gmail.com';
                emailValidation.className = 'form-validation error';
                return false;
            }
        }
        
        function validateContact() {
            const contact = contactInput.value;
            const contactValidation = document.getElementById('contactValidation');
            const contactPattern = /^(09\d{9}|\+639\d{9})$/;
            
            if (contact.length === 0) {
                contactValidation.textContent = '';
                contactValidation.className = 'form-validation';
                return true; // Empty is valid for progressive validation
            }
            
            if (contactPattern.test(contact)) {
                contactValidation.textContent = '✓ Valid contact number';
                contactValidation.className = 'form-validation success';
                return true;
            } else {
                contactValidation.textContent = '✗ Format: 09xxxxxxxxx or +639xxxxxxxxx';
                contactValidation.className = 'form-validation error';
                return false;
            }
        }
        
        // Add event listeners with debouncing
        let validationTimeout;
        
        function debounceValidation(validationFunc, delay = 300) {
            clearTimeout(validationTimeout);
            validationTimeout = setTimeout(validationFunc, delay);
        }
        
        passwordInput.addEventListener('input', () => debounceValidation(updatePasswordStrength));
        confirmPasswordInput.addEventListener('input', () => debounceValidation(checkPasswordMatch));
        emailInput.addEventListener('input', () => debounceValidation(validateEmail));
        contactInput.addEventListener('input', () => debounceValidation(validateContact));
        
        // Button state manager to prevent permanent disability
        function updateButtonState() {
            if (!submitButton) return;
            
            // Always enable button - let validation handle submission
            submitButton.disabled = false;
            submitButton.classList.remove('btn-loading');
            submitButton.innerHTML = '<i class="fas fa-user-plus" aria-hidden="true"></i> Create Account';
            
            // Ensure proper ARIA attributes
            submitButton.setAttribute('aria-label', 'Create your account');
            submitButton.setAttribute('role', 'button');
        }
        
        // Form submission with comprehensive validation
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default submission
            
            const requiredFields = registerForm.querySelectorAll('[required]');
            let isValid = true;
            let firstErrorField = null;
            
            // Reset all error states
            requiredFields.forEach(field => field.classList.remove('error'));
            
            // Validate required fields
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    if (!firstErrorField) firstErrorField = field;
                }
            });
            
            // Validate specific fields (only if they have values)
            if (emailInput.value.trim() && !validateEmail()) isValid = false;
            if (contactInput.value.trim() && !validateContact()) isValid = false;
            if (passwordInput.value.trim() && confirmPasswordInput.value.trim() && !checkPasswordMatch()) isValid = false;
            
            // Check password strength (only if password is entered)
            if (passwordInput.value.trim()) {
                const { strength } = checkPasswordStrength(passwordInput.value);
                if (strength < 3) {
                    isValid = false;
                    passwordInput.classList.add('error');
                    if (!firstErrorField) firstErrorField = passwordInput;
                }
            }
            
            if (!isValid) {
                // Focus first error field
                if (firstErrorField) {
                    firstErrorField.focus();
                    firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                // Reset button state
                updateButtonState();
                return;
            }
            
            // Show loading state
            if (submitButton) {
                submitButton.classList.add('btn-loading');
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
            }
            
            // Submit form after validation
            setTimeout(() => {
                registerForm.submit();
            }, 500);
        });
        
        // Ensure button is always enabled on form interaction
        registerForm.addEventListener('input', updateButtonState);
        registerForm.addEventListener('change', updateButtonState);
        registerForm.addEventListener('focus', updateButtonState, true);
        
        // Enhanced keyboard accessibility for the button
        if (submitButton) {
            submitButton.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (!this.disabled) {
                        this.click();
                    }
                }
            });
            
            // Focus management
            submitButton.addEventListener('focus', function() {
                this.classList.add('focus-visible');
            });
            
            submitButton.addEventListener('blur', function() {
                this.classList.remove('focus-visible');
            });
        }
        
        // Initial button state
        updateButtonState();
    }
    
    // Forgot Password Modal Functionality
    const forgotPasswordLink = document.getElementById('forgotPassword');
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    const backToLoginLink = document.getElementById('backToLogin');
    const openLoginFromRegister = document.getElementById('openLoginFromRegister');
    
    // Forgot password link click handler
    if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener('click', (e) => {
            e.preventDefault();
            closeModal('loginModal'); // Close login modal if open
            setTimeout(() => openModal('forgotPasswordModal'), 300);
        });
    }
    
    // Back to login link handler
    if (backToLoginLink) {
        backToLoginLink.addEventListener('click', (e) => {
            e.preventDefault();
            closeModal('forgotPasswordModal');
            setTimeout(() => openModal('loginModal'), 300);
        });
    }
    
    // Open login from register modal
    if (openLoginFromRegister) {
        openLoginFromRegister.addEventListener('click', (e) => {
            e.preventDefault();
            closeModal('registerModal');
            setTimeout(() => openModal('loginModal'), 300);
        });
    }
    
    // Forgot password form submission
    if (forgotPasswordForm) {
        const forgotSubmitButton = forgotPasswordForm.querySelector('button[type="submit"]');
        const forgotEmailInput = document.getElementById('forgotEmail');
        const forgotEmailValidation = document.getElementById('forgotEmailValidation');
        const forgotNewPasswordInput = document.getElementById('forgotNewPassword');
        const forgotConfirmPasswordInput = document.getElementById('forgotConfirmPassword');
        const forgotNewPasswordValidation = document.getElementById('forgotNewPasswordValidation');
        const forgotConfirmPasswordValidation = document.getElementById('forgotConfirmPasswordValidation');
        const forgotPasswordStrength = document.getElementById('forgotPasswordStrength');
        const sendGuestResetOtpBtn = document.getElementById('sendGuestResetOtp');
        const forgotOtpStatus = document.getElementById('forgotOtpStatus');
        const forgotOtpInput = document.getElementById('forgotOtpCode');
        const forgotOtpValidation = document.getElementById('forgotOtpValidation');
        
        // Email validation for forgot password
        function validateForgotEmail() {
            const email = forgotEmailInput.value;
            const emailPattern = /^[a-z0-9._%+-]+@(?:bts\.gov\.ph|gmail\.com)$/;
            
            if (email.length === 0) {
                forgotEmailValidation.textContent = 'Email is required';
                forgotEmailValidation.className = 'form-validation error';
                return false;
            }
            
            if (emailPattern.test(email)) {
                forgotEmailValidation.textContent = '✓ Valid email format';
                forgotEmailValidation.className = 'form-validation success';
                return true;
            } else {
                forgotEmailValidation.textContent = '✗ Email must be from @bts.gov.ph or @gmail.com';
                forgotEmailValidation.className = 'form-validation error';
                return false;
            }
        }
        
        // Password strength indicator for forgot password
        function updateForgotPasswordStrength(password) {
            if (!forgotPasswordStrength) return;
            
            const strengthBar = forgotPasswordStrength.querySelector('.password-strength-bar');
            const strengthText = forgotPasswordStrength.querySelector('.password-strength-text');
            
            if (!strengthBar || !strengthText) return;
            
            const strength = calculatePasswordStrength(password);
            
            strengthBar.style.width = strength.percentage + '%';
            strengthBar.className = 'password-strength-bar ' + strength.class;
            strengthText.textContent = strength.text;
            strengthText.className = 'password-strength-text ' + strength.class;
        }

        function validateForgotOtp() {
            const code = (forgotOtpInput?.value || '').trim();
            if (!forgotOtpValidation) return true;
            if (code.length === 0) {
                forgotOtpValidation.textContent = 'OTP code is required';
                forgotOtpValidation.className = 'form-validation error';
                return false;
            }
            if (!/^\d{6}$/.test(code)) {
                forgotOtpValidation.textContent = 'OTP must be a 6-digit number';
                forgotOtpValidation.className = 'form-validation error';
                return false;
            }
            forgotOtpValidation.textContent = '✓ OTP format valid';
            forgotOtpValidation.className = 'form-validation success';
            return true;
        }

        if (sendGuestResetOtpBtn) {
            sendGuestResetOtpBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                const emailValid = validateForgotEmail();
                if (!emailValid) {
                    forgotEmailInput.focus();
                    return;
                }
                if (forgotOtpStatus) {
                    forgotOtpStatus.textContent = '';
                    forgotOtpStatus.className = 'form-validation';
                }
                try {
                    sendGuestResetOtpBtn.disabled = true;
                    sendGuestResetOtpBtn.classList.add('btn-loading');
                    sendGuestResetOtpBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                    const form = new FormData();
                    form.append('email', forgotEmailInput.value.trim());
                    const res = await fetch('php/send_guest_reset_otp.php', { method: 'POST', body: form });
                    const data = await res.json();
                    if (data.success) {
                        if (forgotOtpStatus) {
                            forgotOtpStatus.textContent = data.message || 'OTP sent.';
                            forgotOtpStatus.className = 'form-validation success';
                        }
                        if (forgotOtpInput) forgotOtpInput.focus();
                    } else {
                        if (forgotOtpStatus) {
                            forgotOtpStatus.textContent = data.message || 'Failed to send OTP';
                            forgotOtpStatus.className = 'form-validation error';
                        }
                    }
                } catch (err) {
                    console.error('OTP send error:', err);
                    if (forgotOtpStatus) {
                        forgotOtpStatus.textContent = 'An error occurred while sending OTP';
                        forgotOtpStatus.className = 'form-validation error';
                    }
                } finally {
                    sendGuestResetOtpBtn.disabled = false;
                    sendGuestResetOtpBtn.classList.remove('btn-loading');
                    sendGuestResetOtpBtn.innerHTML = 'Send OTP';
                }
            });
        }

        // New password validation for forgot password
        function validateForgotNewPassword() {
            const password = forgotNewPasswordInput.value;
            
            if (password.length === 0) {
                forgotNewPasswordValidation.textContent = 'New password is required';
                forgotNewPasswordValidation.className = 'form-validation error';
                return false;
            }
            
            if (password.length < 8) {
                forgotNewPasswordValidation.textContent = 'Password must be at least 8 characters long';
                forgotNewPasswordValidation.className = 'form-validation error';
                return false;
            }
            
            forgotNewPasswordValidation.textContent = '✓ Password meets requirements';
            forgotNewPasswordValidation.className = 'form-validation success';
            return true;
        }
        
        // Confirm password validation for forgot password
        function validateForgotConfirmPassword() {
            const password = forgotNewPasswordInput.value;
            const confirmPassword = forgotConfirmPasswordInput.value;
            
            if (confirmPassword.length === 0) {
                forgotConfirmPasswordValidation.textContent = 'Please confirm your new password';
                forgotConfirmPasswordValidation.className = 'form-validation error';
                return false;
            }
            
            if (password !== confirmPassword) {
                forgotConfirmPasswordValidation.textContent = 'Passwords do not match';
                forgotConfirmPasswordValidation.className = 'form-validation error';
                return false;
            }
            
            forgotConfirmPasswordValidation.textContent = '✓ Passwords match';
            forgotConfirmPasswordValidation.className = 'form-validation success';
            return true;
        }
        
        // Event listeners
        forgotEmailInput.addEventListener('input', () => debounceValidation(validateForgotEmail));
        forgotNewPasswordInput.addEventListener('input', function() {
            updateForgotPasswordStrength(this.value);
            debounceValidation(validateForgotNewPassword);
            if (forgotConfirmPasswordInput.value) {
                validateForgotConfirmPassword();
            }
        });
        forgotConfirmPasswordInput.addEventListener('input', () => debounceValidation(validateForgotConfirmPassword));
        if (forgotOtpInput) {
            forgotOtpInput.addEventListener('input', () => debounceValidation(validateForgotOtp));
        }
        
        // Forgot password form submission
        forgotPasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate all fields
            const isEmailValid = validateForgotEmail();
            const isNewPasswordValid = validateForgotNewPassword();
            const isConfirmPasswordValid = validateForgotConfirmPassword();
            const isOtpValid = validateForgotOtp();
            
            if (!isEmailValid) {
                forgotEmailInput.focus();
                return;
            }
            
            if (!isNewPasswordValid) {
                forgotNewPasswordInput.focus();
                return;
            }
            
            if (!isConfirmPasswordValid) {
                forgotConfirmPasswordInput.focus();
                return;
            }

            if (!isOtpValid) {
                if (forgotOtpInput) forgotOtpInput.focus();
                return;
            }
            
            // Show loading state
            if (forgotSubmitButton) {
                forgotSubmitButton.classList.add('btn-loading');
                forgotSubmitButton.disabled = true;
                forgotSubmitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';
            }
            
            // Create form data for AJAX submission
            const formData = new FormData(forgotPasswordForm);
            
            // Submit the form via AJAX
            fetch('php/simple_password_reset.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Password has been reset successfully! You can now log in with your new password.');
                    closeModal('forgotPasswordModal');
                    openModal('loginModal'); // Redirect to login modal
                } else {
                    alert(data.message || 'Failed to reset password. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while resetting your password. Please try again.');
            })
            .finally(() => {
                // Reset button state
                if (forgotSubmitButton) {
                    forgotSubmitButton.classList.remove('btn-loading');
                    forgotSubmitButton.disabled = false;
                    forgotSubmitButton.innerHTML = '<i class="fas fa-key"></i> Reset Password';
                }
            });
        });
    }
    
    // Smooth scrolling for navigation links with offset
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            // Skip empty hash or just "#"
            if (!href || href === '#' || href.length <= 1) return;
            
            const target = document.querySelector(href);
            if (target) {
                const headerOffset = 80;
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Add scroll effect to header with performance optimization
    let scrollTimeout;
    window.addEventListener('scroll', () => {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            const header = document.querySelector('.site-header');
            if (header) {
                if (window.scrollY > 100) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            }
        }, 10);
    });
    
    // Performance optimization: Intersection Observer for animations
    if ('IntersectionObserver' in window) {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);
        
        // Observe elements for animation
        document.querySelectorAll('.course-card, .mv-card, .timeline-item, .stat-card').forEach(el => {
            observer.observe(el);
        });
    }
});
