let currentOpenModal = null;

// Delete Account Modal Elements
const openDeleteModal = document.getElementById('openDeleteModal');
const deleteAccountModal = document.getElementById('deleteAccountModal');
const closeDeleteModal = document.getElementById('closeDeleteModal');
const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
const editProfileBtn = document.getElementById('editProfileBtn');
const profileModal = document.getElementById('profileModal');
const closeProfileModalBtn = document.getElementById('closeProfileModal');
const cancelProfileChangesBtn = document.getElementById('cancelProfileChanges');
const saveProfileChangesBtn = document.getElementById('saveProfileChanges');

editProfileBtn.addEventListener('click', () => openModal(profileModal));
closeProfileModalBtn.addEventListener('click', () => closeModal(profileModal));
cancelProfileChangesBtn.addEventListener('click', () => closeModal(profileModal));

saveProfileChangesBtn.addEventListener('click', function() {
    if (validateProfileForm()) {
        saveProfileData();
    }
});

// ===== DELETE ACCOUNT CONFIRMATION =====
confirmDeleteBtn.addEventListener('click', function() {
    showNotification('Account deletion feature would be implemented with backend integration', 'info');
    closeCurrentModal();
    
    // Simulate logout after account deletion
    setTimeout(() => {
        window.location.href = '../landingpage.php';
    }, 2000);
});

// ===== ENROLLMENT FUNCTIONALITY =====
document.addEventListener('DOMContentLoaded', function() {
    // Activate tab from URL on page load
    activateTabFromUrl();

    // Profile Image Upload
    const profileUpload = document.getElementById('profileUpload');
    const changeProfileBtn = document.getElementById('changeProfileBtn');
    const profilePreview = document.getElementById('profilePreview');

    if (changeProfileBtn && profileUpload) {
        changeProfileBtn.addEventListener('click', function() {
            profileUpload.click();
        });
    }

    if (profileUpload) {
        profileUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (profilePreview) {
                        profilePreview.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Add event listeners to enroll buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('enroll-btn') && !e.target.disabled) {
            const courseCard = e.target.closest('.course-card');
            const courseCode = courseCard.getAttribute('data-course');
            const courseName = courseCard.getAttribute('data-title');
            
            openEnrollModal(courseCode, courseName);
        }

        // Handle unenroll button clicks
        if (e.target.classList.contains('unenroll-btn') && !e.target.disabled) {
            let courseCode = e.target.getAttribute('data-course-code');
            let courseName = e.target.getAttribute('data-course-name');

            if (!courseCode) { // Fallback for other unenroll buttons
                const courseCard = e.target.closest('.course-card');
                courseCode = courseCard.getAttribute('data-course');
                courseName = courseCard.getAttribute('data-title');
            }

            openUnenrollModal(courseCode, courseName);
        }
    });

    // Tab functionality
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    tabLinks.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove active class from all tabs and contents
            tabLinks.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            // Add active class to clicked tab
            this.classList.add('active');

            // Show corresponding content
            const tabId = this.getAttribute('data-tab');
            const targetTab = document.getElementById(tabId);
            if (targetTab) {
                targetTab.classList.add('active');
            }
        });
    });
});

function activateTabFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const tabId = params.get('current_tab');

    if (tabId) {
        const tabLink = document.querySelector(`.tab-link[data-tab="${tabId}"]`);
        const tabContent = document.getElementById(tabId);

        if (tabLink && tabContent) {
            // Remove active class from all tabs and contents
            document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

            // Add active class to the target tab and content
            tabLink.classList.add('active');
            tabContent.classList.add('active');
        }
    }
}

function openEnrollModal(courseCode, courseName) {
    document.getElementById('enrollCourseName').textContent = courseName; // This line is correct for the enroll modal
    
    // Set up confirmation handler
    confirmEnroll.onclick = function() {
        enrollInCourse(courseCode, courseName);
        closeModal(enrollModal);
    };
    
    openModal(enrollModal);
}

function openUnenrollModal(courseCode, courseName) {
    document.getElementById('unenrollConfirmCourseName').textContent = courseName;
    
    // Set up confirmation handler
    confirmUnenroll.onclick = function() {
        unenrollFromCourse(courseCode, this);
        closeModal(document.getElementById('unenrollConfirmModal'));
    };
    
    openModal(document.getElementById('unenrollConfirmModal'));
}

function unenrollFromCourse(courseCode, button) {
    button.disabled = true;
    button.textContent = 'Unenrolling...';

    fetch('../php/guest_unenroll.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `course_code=${encodeURIComponent(courseCode)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            // Reload and stay on the current tab
            setTimeout(() => window.location.href = `${window.location.pathname}?current_tab=enrolled`, 1500);
        } else {
            showNotification(data.message, 'error');
            button.disabled = false;
            button.textContent = 'Yes, Unenroll';
        }
    });
}

// ===== ACTIVITY MODAL FUNCTIONALITY =====
document.addEventListener('click', function(e) {
    const materialItem = e.target.closest('.material-item.activity');
    if (materialItem) {
        const activityId = materialItem.getAttribute('data-activity');
        const activityType = materialItem.getAttribute('data-type');
        const activityTitle = materialItem.querySelector('.material-title').textContent;
        
        openActivityModal(activityId, activityType, activityTitle);
    }
});

function openActivityModal(activityId, activityType, activityTitle) {
    currentActivity = { id: activityId, type: activityType, title: activityTitle };
    
    // Set modal content based on activity type
    document.getElementById('activityModalTitle').textContent = activityTitle;
    
    // Set instructions based on activity type
    const instructions = getActivityInstructions(activityType, activityTitle);
    document.getElementById('activityInstructions').innerHTML = instructions;
    
    // Set due date
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 7);
    document.getElementById('activityDueDate').textContent = dueDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    // Show/hide sections based on activity type
    const uploadSection = document.getElementById('uploadSection');
    const quizSection = document.getElementById('quizSection');
    
    if (activityType === 'quiz') {
        uploadSection.classList.add('hidden');
        quizSection.classList.remove('hidden');
    } else {
        uploadSection.classList.remove('hidden');
        quizSection.classList.add('hidden');
    }
    
    // Reset file upload
    resetFileUpload();
    
    openModal(activityModal);
}

// Activity Submission
activitySubmitBtn.addEventListener('click', function() {
    if (selectedFile) {
        // Simulate file upload
        showNotification('Assignment submitted successfully!', 'success');
        
        // Update activity status
        updateActivityStatus(currentActivity.id, 'submitted');
        
        // Show success message
        document.getElementById('submissionSuccess').classList.remove('hidden');
        activitySubmitBtn.disabled = true;
        
        // Close modal after delay
        setTimeout(() => {
            closeModal(activityModal);
        }, 2000);
    }
});

// Quiz Start
startQuizBtn.addEventListener('click', function() {
    showNotification('Quiz functionality would be implemented with full backend integration', 'info');
    closeModal(activityModal);
});

// File Upload Handling
const uploadArea = document.getElementById('uploadArea');
const activityFileInput = document.getElementById('activityFileInput');
const activityFilePreview = document.getElementById('activityFilePreview');
let selectedFile = null;

uploadArea.addEventListener('click', function() {
    activityFileInput.click();
});

activityFileInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        selectedFile = file;
        displayFilePreview(file);
        activitySubmitBtn.disabled = false;
    }
});

function displayFilePreview(file) {
    const fileSize = (file.size / 1024 / 1024).toFixed(2);
    activityFilePreview.innerHTML = `
        <div class="file-preview-item">
            <i class="fas fa-file"></i>
            <div class="file-info">
                <div class="file-name">${file.name}</div>
                <div class="file-size">${fileSize} MB</div>
            </div>
            <button class="remove-file-btn" onclick="removeSelectedFile()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    activityFilePreview.classList.remove('hidden');
}

function removeSelectedFile() {
    selectedFile = null;
    activityFileInput.value = '';
    activityFilePreview.classList.add('hidden');
    activitySubmitBtn.disabled = true;
}

function resetFileUpload() {
    selectedFile = null;
    activityFileInput.value = '';
    activityFilePreview.classList.add('hidden');
    activitySubmitBtn.disabled = true;
    document.getElementById('submissionSuccess').classList.add('hidden');
}
function openModal(modal) {
    if (currentOpenModal) {
        closeModal(currentOpenModal);
    }
    modal.classList.remove('hidden');
    currentOpenModal = modal;
    document.body.classList.add('modal-open');
}

function closeModal(modal) {
    modal.classList.add('hidden');
    currentOpenModal = null;
    document.body.classList.remove('modal-open');
}

// Global function for file removal
window.removeSelectedFile = removeSelectedFile;

// ===== NOTIFICATION DROPDOWN =====
document.addEventListener('DOMContentLoaded', function() {
    const notifIcon = document.getElementById('notifIcon');
    const notifDropdown = document.getElementById('notifDropdown');
    
    if (notifIcon && notifDropdown) {
        notifIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            notifDropdown.classList.toggle('hidden');
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (notifIcon && notifDropdown && 
            !notifIcon.contains(e.target) && 
            !notifDropdown.contains(e.target)) {
            notifDropdown.classList.add('hidden');
        }
    });
});

// ===== PROFILE FORM VALIDATION =====
function validateProfileForm() {
    let isValid = true;
    
    // Reset errors
    document.querySelectorAll('.validation-error').forEach(error => {
        error.classList.add('hidden');
    });
    
    // Email validation
    const email = document.getElementById('email');
    const emailError = document.getElementById('emailError');
    if (email && emailError) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email.value)) {
            emailError.classList.remove('hidden');
            isValid = false;
        }
    }
    
    // Password confirmation validation
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    const passwordError = document.getElementById('passwordError');
    
    if (newPassword && confirmPassword && passwordError) {
        if (newPassword.value !== confirmPassword.value) {
            passwordError.classList.remove('hidden');
            isValid = false;
        }
    }
    
    return isValid;
}

// ===== PROFILE DATA SAVE =====
function saveProfileData() {
    const form = document.getElementById('profileForm');
    const formData = new FormData(form);
    const profilePictureFile = document.getElementById('profileUpload').files[0];
    if (profilePictureFile) {
        formData.append('profile_picture', profilePictureFile);
    }

    saveProfileChangesBtn.disabled = true;
    saveProfileChangesBtn.textContent = 'Saving...';

    fetch('../php/update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeModal(profileModal);
            // Optionally, update the UI with new data without a full reload
            document.querySelector('.user-name').textContent = `${data.user.first_name} ${data.user.last_name}`;
            if (data.user.profile_picture) {
                const newPicUrl = `../uploads/profiles/${data.user.profile_picture}?t=${new Date().getTime()}`;
                document.querySelector('.user-avatar').src = newPicUrl;
                document.getElementById('profilePreview').src = newPicUrl;
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error updating profile:', error);
        showNotification('An error occurred while updating your profile.', 'error');
    })
    .finally(() => {
        saveProfileChangesBtn.disabled = false;
        saveProfileChangesBtn.textContent = 'Save Changes';
    });
}

// ===== COURSE VIEW FUNCTIONALITY =====
document.addEventListener('DOMContentLoaded', function() {
    // View course buttons in enrolled courses
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('view-course-btn')) {
            const courseCard = e.target.closest('.course-card');
            if (courseCard) {
                const courseCode = courseCard.getAttribute('data-course');
                const courseName = courseCard.getAttribute('data-title');
                viewCourseDetails(courseCode, courseName);
            }
        }
    });
    
    // Back buttons
    const backToCourses = document.getElementById('backToCourses');
    const backToEnrolledCourses = document.getElementById('backToEnrolledCourses');
    
    if (backToCourses) {
        backToCourses.addEventListener('click', resetDetailViews);
    }
    
    if (backToEnrolledCourses) {
        backToEnrolledCourses.addEventListener('click', resetDetailViews);
    }
});

function viewCourseDetails(courseCode, courseName) {
    const enrolledContainer = document.getElementById('enrolledCoursesContainer');
    const courseDetailView = document.getElementById('courseDetail');
    const backButton = document.getElementById('backToEnrolledCourses');

    // Hide the course list and show the detail view
    if (enrolledContainer) enrolledContainer.classList.add('hidden');
    if (courseDetailView) courseDetailView.classList.remove('hidden');
    if (backButton) backButton.classList.remove('hidden');

    const courseContentContainer = document.getElementById('courseDetailContent');
    if (!courseContentContainer) return;

    courseContentContainer.innerHTML = '<div>Loading course content...</div>';

    // Set the course code on the unenroll button inside the detail view
    const unenrollBtn = document.getElementById('unenrollCourseBtn');
    if (unenrollBtn) {
        unenrollBtn.setAttribute('data-course-code', courseCode);
        unenrollBtn.setAttribute('data-course-name', courseName);
    }

    fetch(`../php/get_course_details_guest.php?course_code=${courseCode}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                courseContentContainer.innerHTML = `<div class="error-message">Error: ${data.error}</div>`;
                return;
            }

            let contentHtml = `<h2>${data.course.course_name}</h2>`;
            contentHtml += `<p class="course-description-detail">${data.course.description}</p>`;

            const basicCompetencies = data.competencies.filter(comp => comp.type === 'basic');

            if (basicCompetencies.length > 0) {
                contentHtml += '<h4>Basic Competencies</h4>';
                basicCompetencies.forEach(comp => {
                    contentHtml += `<div class="competency-box">`;
                    contentHtml += `<div class="competency-header"><h4>${comp.name}</h4></div>`;

                    if (comp.topics && comp.topics.length > 0) {
                        comp.topics.forEach(topic => {
                            contentHtml += `<div class="topic-box"><h5>${topic.name}</h5></div>`;
                        });
                    } else {
                        contentHtml += '<p class="no-materials">No topics for this competency.</p>';
                    }
                    contentHtml += `</div>`;
                });
            } else {
                contentHtml += '<p>No basic competency materials are available for this course.</p>';
            }
            courseContentContainer.innerHTML = contentHtml;
        })
        .catch(error => {
            console.error('Error fetching course details:', error);
            courseContentContainer.innerHTML = '<div class="error-message">Failed to load course content.</div>';
        });
}

function resetDetailViews() {
    const enrolledContainer = document.getElementById('enrolledCoursesContainer');
    const courseDetailView = document.getElementById('courseDetail');
    const backButton = document.getElementById('backToEnrolledCourses');

    // Hide detail view, show course list
    if (enrolledContainer) enrolledContainer.classList.remove('hidden');
    if (courseDetailView) courseDetailView.classList.add('hidden');
    if (backButton) backButton.classList.add('hidden');
}

// ===== NOTIFICATION FUNCTION =====
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 4px;
        color: white;
        z-index: 10000;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add to body
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 3000);
}

// ===== HAMBURGER MENU =====
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.querySelector('.sidebar');
    
    if (hamburger && sidebar) {
        hamburger.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
});

// ===== ACTIVITY INSTRUCTIONS =====
function getActivityInstructions(activityType, activityTitle) {
    if (activityType === 'quiz') {
        return `
            <p>This quiz will test your understanding of the course material.</p>
            <ul>
                <li>Total questions: 10</li>
                <li>Time limit: 30 minutes</li>
                <li>Multiple choice format</li>
                <li>You cannot pause once started</li>
            </ul>
        `;
    } else {
        return `
            <p>Complete the assignment and submit your work before the due date.</p>
            <ul>
                <li>Submit in PDF, DOC, or DOCX format</li>
                <li>Maximum file size: 10MB</li>
                <li>Ensure your name is included in the submission</li>
                <li>Late submissions may be penalized</li>
            </ul>
        `;
    }
}

// ===== ENROLLMENT FUNCTION =====
function enrollInCourse(courseCode, courseName) {
    const enrollBtn = document.querySelector(`.enroll-btn[data-course-code="${courseCode}"]`);
    if (enrollBtn) {
        enrollBtn.disabled = true;
        enrollBtn.textContent = 'Enrolling...';
    }

    fetch('../php/guest_enroll.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `course_code=${encodeURIComponent(courseCode)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            if (enrollBtn) {
                enrollBtn.textContent = 'Enrolled';
                enrollBtn.classList.remove('enroll-btn');
                enrollBtn.classList.add('enrolled');
            }
            // Reload the page to update the "Enrolled Courses" tab
            // Reload and stay on the 'courses' tab
            setTimeout(() => window.location.href = `${window.location.pathname}?current_tab=courses`, 1500);
        } else {
            showNotification(data.message, 'error');
            if (enrollBtn) {
                enrollBtn.disabled = false;
                enrollBtn.textContent = 'Enroll';
            }
        }
    })
    .catch(error => {
        console.error('Enrollment Error:', error);
        showNotification('An error occurred during enrollment.', 'error');
    });
}

// ===== ACTIVITY STATUS UPDATE =====
function updateActivityStatus(activityId, status) {
    // Update activity status in UI
    const activityItem = document.querySelector(`[data-activity="${activityId}"]`);
    if (activityItem) {
        const statusElement = activityItem.querySelector('.material-status');
        if (statusElement) {
            statusElement.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            statusElement.className = 'material-status ' + status;
        }
    }
}