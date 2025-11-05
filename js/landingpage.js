document.addEventListener("DOMContentLoaded", () => {
    // Slideshow functionality
    let currentSlide = 0;
    const slides = document.querySelectorAll('.slide');
    const totalSlides = slides.length;

    function showSlide(n) {
        slides.forEach(slide => slide.classList.remove('active'));
        currentSlide = (n + totalSlides) % totalSlides;
        slides[currentSlide].classList.add('active');
    }

    function nextSlide() {
        showSlide(currentSlide + 1);
    }

    // Auto-advance slides every 5 seconds
    if (slides.length > 0) {
        setInterval(nextSlide, 5000);
        console.log("Slideshow loaded with", totalSlides, "slides");
    }

    // Modal functionality
    const loginModal = document.getElementById("loginModal");
    const registerModal = document.getElementById("registerModal");
    const openLogin = document.getElementById("openLogin");
    const openRegister = document.getElementById("openRegister");
    const openLoginFromRegister = document.getElementById("openLoginFromRegister");
    const closeLogin = document.querySelector("#loginModal .close");
    const closeRegister = document.querySelector("#registerModal .close-register");

    // Open Login Modal
    if (openLogin && loginModal) {
        openLogin.addEventListener("click", (e) => {
            e.preventDefault();
            loginModal.style.display = "flex";
            registerModal.style.display = "none";
        });
    }

    // Open Register Modal
    if (openRegister && registerModal) {
        openRegister.addEventListener("click", (e) => {
            e.preventDefault();
            registerModal.style.display = "flex";
            loginModal.style.display = "none";
        });
    }

    // Switch to Login from Register
    if (openLoginFromRegister) {
        openLoginFromRegister.addEventListener("click", (e) => {
            e.preventDefault();
            loginModal.style.display = "flex";
            registerModal.style.display = "none";
        });
    }

    // Close Modals
    if (closeLogin) {
        closeLogin.addEventListener("click", () => {
            loginModal.style.display = "none";
        });
    }

    if (closeRegister) {
        closeRegister.addEventListener("click", () => {
            registerModal.style.display = "none";
        });
    }

    // Close modal when clicking outside
    window.addEventListener("click", (e) => {
        if (e.target === loginModal) {
            loginModal.style.display = "none";
        }
        if (e.target === registerModal) {
            registerModal.style.display = "none";
        }
    });

    // Toggle password visibility
    document.querySelectorAll(".toggle-password").forEach(icon => {
        icon.addEventListener("click", () => {
            const target = document.getElementById(icon.getAttribute("data-target"));
            const isPassword = target.getAttribute("type") === "password";
            target.setAttribute("type", isPassword ? "text" : "password");
            icon.classList.toggle("fa-eye");
            icon.classList.toggle("fa-eye-slash");
        });
    });

    // See More/Less functionality
    const seeMoreBtn = document.getElementById("seeMoreBtn");
    const aboutBox = document.querySelector(".about-box");
    const aboutContent = document.getElementById("aboutContent");

    if (seeMoreBtn && aboutBox && aboutContent) {
        seeMoreBtn.addEventListener("click", () => {
            aboutBox.classList.toggle("expanded");
            
            if (aboutBox.classList.contains("expanded")) {
                seeMoreBtn.textContent = "See Less";
                aboutContent.style.maxHeight = aboutContent.scrollHeight + "px";
            } else {
                seeMoreBtn.textContent = "See More...";
                aboutContent.style.maxHeight = "180px";
            }
        });
    }
    // Add this to your existing JavaScript
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            // Clear error messages when closing modal by clicking outside
            const errorMessages = modal.querySelectorAll('.error-message, .success-message');
            errorMessages.forEach(msg => msg.style.display = 'none');
        }
    });
});

// Also clear errors when opening modals
if (openLogin && loginModal) {
    openLogin.addEventListener("click", (e) => {
        e.preventDefault();
        loginModal.style.display = "flex";
        registerModal.style.display = "none";
        
        // Clear any visible error messages
        const errors = loginModal.querySelectorAll('.error-message, .success-message');
        errors.forEach(msg => msg.style.display = 'none');
    });
}

if (openRegister && registerModal) {
    openRegister.addEventListener("click", (e) => {
        e.preventDefault();
        registerModal.style.display = "flex";
        loginModal.style.display = "none";
        
        // Clear any visible error messages
        const errors = registerModal.querySelectorAll('.error-message, .success-message');
        errors.forEach(msg => msg.style.display = 'none');
    });
}
});