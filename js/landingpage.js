document.addEventListener("DOMContentLoaded", () => {
    // Modal handling
    const loginModal = document.getElementById("loginModal");
    const registerModal = document.getElementById("registerModal");
    const openLoginBtn = document.getElementById("openLogin");
    const openRegisterBtn = document.getElementById("openRegister");
    const openLoginFromRegisterBtn = document.getElementById("openLoginFromRegister");
    const closeButtons = document.querySelectorAll(".close, .close-register");

    function openModal(modal) {
        if (modal) modal.style.display = "flex";
    }

    function closeModal(modal) {
        if (modal) modal.style.display = "none";
    }

    if (openLoginBtn) openLoginBtn.addEventListener("click", () => openModal(loginModal));
    if (openRegisterBtn) openRegisterBtn.addEventListener("click", () => {
        closeModal(loginModal);
        openModal(registerModal);
    });
    if (openLoginFromRegisterBtn) openLoginFromRegisterBtn.addEventListener("click", (e) => {
        e.preventDefault();
        closeModal(registerModal);
        openModal(loginModal);
    });

    closeButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            closeModal(loginModal);
            closeModal(registerModal);
        });
    });

    window.addEventListener("click", (event) => {
        if (event.target === loginModal) closeModal(loginModal);
        if (event.target === registerModal) closeModal(registerModal);
    });

    // Slideshow
    let slideIndex = 0;
    const slides = document.querySelectorAll(".slide");
    if (slides.length > 0) {
        const showSlides = () => {
            slides.forEach(slide => slide.classList.remove("active"));
            slideIndex++;
            if (slideIndex > slides.length) { slideIndex = 1; }
            slides[slideIndex - 1].classList.add("active");
            setTimeout(showSlides, 5000); // Change image every 5 seconds
        };
        showSlides();
    }

    // "See More" button for About Us
    const seeMoreBtn = document.getElementById("seeMoreBtn");
    const aboutBox = document.querySelector(".about-box");
    if (seeMoreBtn && aboutBox) {
        seeMoreBtn.addEventListener("click", () => {
            aboutBox.classList.toggle("expanded");
            if (aboutBox.classList.contains("expanded")) {
                seeMoreBtn.textContent = "See Less...";
            } else {
                seeMoreBtn.textContent = "See More...";
            }
        });
    }

    // --- Password Visibility Toggle ---
    const togglePasswordIcons = document.querySelectorAll('.toggle-password');
    togglePasswordIcons.forEach(icon => {
        icon.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);

            if (passwordInput) {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                }
            }
        });
    });

    // --- Registration Password Matching Validation ---
    const regPassword = document.getElementById('regPassword');
    const regConfirmPassword = document.getElementById('regConfirmPassword');
    const registerForm = document.getElementById('registerForm');
    const contactInput = document.querySelector('input[name="contact"]');

    function validatePasswords() {
        if (regPassword.value !== regConfirmPassword.value) {
            // Add a visual indicator that passwords don't match
            regConfirmPassword.style.borderColor = 'red';
            return false;
        } else {
            // Reset the visual indicator
            regConfirmPassword.style.borderColor = '';
            return true;
        }
    }

    function validatePhoneNumber() {
        if (!contactInput) return true; // If field doesn't exist, skip validation

        const phone = contactInput.value.trim();
        // Regex for 09xxxxxxxxx or +639xxxxxxxxx
        const phoneRegex = /^(09\d{9}|\+639\d{9})$/;

        if (!phoneRegex.test(phone)) {
            contactInput.style.borderColor = 'red';
            return false;
        } else {
            contactInput.style.borderColor = '';
            return true;
        }
    }

    if (regPassword && regConfirmPassword) {
        // Validate as the user types
        regConfirmPassword.addEventListener('input', validatePasswords);
        regPassword.addEventListener('input', validatePasswords);
    }

    if (contactInput) {
        contactInput.addEventListener('input', validatePhoneNumber);
    }

    if (registerForm) {
        registerForm.addEventListener('submit', function(event) {
            const passwordsValid = validatePasswords();
            const phoneValid = validatePhoneNumber();

            if (!passwordsValid || !phoneValid) {
                event.preventDefault(); // Stop form submission
                
                // Find existing error message div or create one
                let errorDiv = this.querySelector('.error-message');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    // Insert it at the top of the form step
                    this.querySelector('.form-step').prepend(errorDiv);
                }
                
                if (!passwordsValid) {
                    errorDiv.textContent = "Passwords do not match!";
                } else if (!phoneValid) {
                    errorDiv.textContent = "Please enter a valid phone number (e.g., 09xxxxxxxxx or +639xxxxxxxxx).";
                }
                errorDiv.style.display = 'block';
                
                // Scroll to the top of the modal content to make the error visible
                const modalContent = this.closest('.modal-content');
                if (modalContent) {
                    modalContent.scrollTop = 0;
                }
            }
        });
    }
});