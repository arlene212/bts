// ===== MODAL MANAGEMENT =====
let currentOpenModal = null;

// Profile Modal Elements
const editProfileBtn = document.getElementById('editProfileBtn');
const profileModal = document.getElementById('profileModal');
const closeProfileModal = document.getElementById('closeProfileModal');
const cancelProfileChanges = document.getElementById('cancelProfileChanges');
const saveProfileChanges = document.getElementById('saveProfileChanges');

// Delete Account Modal Elements
const openDeleteModal = document.getElementById('openDeleteModal');
const deleteAccountModal = document.getElementById('deleteAccountModal');
const closeDeleteModal = document.getElementById('closeDeleteModal');
const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

// Enrollment Modal Elements
const enrollModal = document.getElementById('enrollModal');
const closeEnrollModal = document.getElementById('closeEnrollModal');
const cancelEnroll = document.getElementById('cancelEnroll');
const confirmEnroll = document.getElementById('confirmEnroll');

// Unenroll Modal Elements
const unenrollModal = document.getElementById('unenrollModal');
const closeUnenrollModal = document.getElementById('closeUnenrollModal');
const cancelUnenroll = document.getElementById('cancelUnenroll');
const confirmUnenroll = document.getElementById('confirmUnenroll');

// Activity Modal Elements
const activityModal = document.getElementById('activityModal');
const closeActivityModal = document.getElementById('closeActivityModal');
const activitySubmitBtn = document.getElementById('activitySubmitBtn');
const startQuizBtn = document.getElementById('startQuizBtn');

// ===== MODAL FUNCTIONS =====
function openModal(modal) {
    if (currentOpenModal) {
        closeModal(currentOpenModal);
    }
    modal.classList.remove('hidden');
    currentOpenModal = modal;
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeModal(modal) {
    modal.classList.add('hidden');
    currentOpenModal = null;
    document.body.style.overflow = ''; // Restore scrolling
}

function closeCurrentModal() {
    if (currentOpenModal) {
        closeModal(currentOpenModal);
    }
}

// ===== MODAL EVENT LISTENERS =====

// Profile Modal
editProfileBtn.addEventListener('click', () => openModal(profileModal));
closeProfileModal.addEventListener('click', () => closeModal(profileModal));
cancelProfileChanges.addEventListener('click', () => closeModal(profileModal));

// Delete Account Modal
openDeleteModal.addEventListener('click', () => {
    closeModal(profileModal);
    openModal(deleteAccountModal);
});
closeDeleteModal.addEventListener('click', () => closeModal(deleteAccountModal));
cancelDeleteBtn.addEventListener('click', () => closeModal(deleteAccountModal));

// Enrollment Modal
closeEnrollModal.addEventListener('click', () => closeModal(enrollModal));
cancelEnroll.addEventListener('click', () => closeModal(enrollModal));

// Unenroll Modal
closeUnenrollModal.addEventListener('click', () => closeModal(unenrollModal));
cancelUnenroll.addEventListener('click', () => closeModal(unenrollModal));

// Activity Modal
closeActivityModal.addEventListener('click', () => closeModal(activityModal));

// ===== CLOSE MODAL ON OUTSIDE CLICK =====
document.addEventListener('click', (e) => {
    if (currentOpenModal && e.target === currentOpenModal) {
        closeCurrentModal();
    }
});

// ===== CLOSE MODAL ON ESC KEY =====
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && currentOpenModal) {
        closeCurrentModal();
    }
});

// ===== PROFILE MODAL FUNCTIONALITY =====
saveProfileChanges.addEventListener('click', function() {
    if (validateProfileForm()) {
        saveProfileData();
        closeModal(profileModal);
        showNotification('Profile updated successfully!', 'success');
    }
});

// Profile Image Upload
const profileUpload = document.getElementById('profileUpload');
const changeProfileBtn = document.getElementById('changeProfileBtn');
const profilePreview = document.getElementById('profilePreview');

changeProfileBtn.addEventListener('click', function() {
    profileUpload.click();
});

profileUpload.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            profilePreview.src = e.target.result;
        };
        reader.readAsDataURL(file);
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
            const courseCard = e.target.closest('.course-card');
            const courseCode = courseCard.getAttribute('data-course');
            const courseName = courseCard.getAttribute('data-title');
            
            openUnenrollModal(courseCode, courseName);
        }
    });
});

function openEnrollModal(courseCode, courseName) {
    document.getElementById('enrollCourseName').textContent = courseName;
    
    // Set up confirmation handler
    confirmEnroll.onclick = function() {
        enrollInCourse(courseCode, courseName);
        closeModal(enrollModal);
    };
    
    openModal(enrollModal);
}

function openUnenrollModal(courseCode, courseName) {
    document.getElementById('unenrollCourseName').textContent = courseName;
    
    // Set up confirmation handler
    confirmUnenroll.onclick = function() {
        unenrollFromCourse(courseCode, this);
        closeModal(unenrollModal);
    };
    
    openModal(unenrollModal);
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
            setTimeout(() => window.location.reload(), 1500);
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

// ===== TAB NAVIGATION =====
document.addEventListener('DOMContentLoaded', function() {
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
    // This would typically send data via AJAX
    // For now, just show success notification
    console.log('Profile save functionality - ready for backend integration');
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
    const courseGrid = document.getElementById('courseGrid');
    const courseDetail = document.getElementById('courseDetail');
    const backButton = document.getElementById('backToCourses');
    const headerTitle = document.getElementById('courseHeaderTitle');
    
    if (courseGrid) courseGrid.classList.add('hidden');
    if (courseDetail) courseDetail.classList.remove('hidden');
    if (backButton) backButton.classList.remove('hidden');
    if (headerTitle) headerTitle.textContent = courseName;
    
    // Simulate course content - in real app, this would come from AJAX
    const courseContentContainer = document.querySelector('#courseDetail .course-content');
    if (courseContentContainer) {
        courseContentContainer.innerHTML = '<div>Loading course content...</div>';

        fetch(`../php/get_course_details_guest.php?course_code=${courseCode}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    courseContentContainer.innerHTML = `<div class="error-message">${data.error}</div>`;
                    return;
                }

                let contentHtml = `<h3>${data.course.course_name}</h3>`;
                contentHtml += `<p>${data.course.description}</p>`;
                contentHtml += '<h4>Basic Competencies</h4>';

                if (data.content && data.content.length > 0) {
                    // Group by topic
                    const topics = {};
                    data.content.forEach(item => {
                        if (!topics[item.topic_id]) {
                            topics[item.topic_id] = {
                                name: item.topic_name,
                                materials: []
                            };
                        }
                        if (item.material_id) {
                            topics[item.topic_id].materials.push(item);
                        }
                        if (item.activity_id) {
                            topics[item.topic_id].materials.push({ ...item, is_activity: true });
                        }
                    });

                    for (const topicId in topics) {
                        const topic = topics[topicId];
                        contentHtml += `<div class="topic-box"><h5>${topic.name}</h5><div class="materials-list">`;
                        topic.materials.forEach(material => {
                            contentHtml += `
                                <div class="material-item ${material.is_activity ? 'activity' : ''}" data-activity="${material.activity_id}" data-type="${material.activity_type}">
                                    <i class="fas ${material.is_activity ? 'fa-tasks' : 'fa-file-alt'}"></i>
                                    <div class="material-info">
                                        <div class="material-title">${material.material_title || material.activity_title}</div>
                                    </div>
                                </div>`;
                        });
                        contentHtml += `</div></div>`;
                    }
                } else {
                    contentHtml += '<p>No basic competency materials are available for this course yet.</p>';
                }
                courseContentContainer.innerHTML = contentHtml;
            })
            .catch(error => {
                console.error('Error fetching course details:', error);
                courseContentContainer.innerHTML = '<div class="error-message">Failed to load course content.</div>';
            });
    }
}

function resetDetailViews() {
    const courseGrid = document.getElementById('courseGrid');
    const courseDetail = document.getElementById('courseDetail');
    const enrolledContainer = document.getElementById('enrolledCoursesContainer');
    const backButtons = document.querySelectorAll('.back-btn');
    const headerTitle = document.getElementById('courseHeaderTitle');
    
    if (courseGrid) courseGrid.classList.remove('hidden');
    if (courseDetail) courseDetail.classList.add('hidden');
    if (enrolledContainer) enrolledContainer.classList.remove('hidden');
    
    backButtons.forEach(btn => {
        btn.classList.add('hidden');
    });
    
    if (headerTitle) {
        headerTitle.textContent = 'Offered Courses';
    }
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
            setTimeout(() => window.location.reload(), 1500);
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