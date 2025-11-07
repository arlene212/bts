// DOM Elements - Wait for full DOM load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin JS loaded - Initializing...');
    
    // Initialize all functionality
    initializeModalButtons();
    initializeSwitches();
    initializeCompetencyHandlers();
    initializeEditCompetencyHandlers();
    setupGlobalEventListeners();
    setupTabNavigation();
    setupDashboardCards();
    setupNotificationDropdown();
    setupDynamicBatchLoading();
    setupUserManagement();
    setupFormValidation();
    setupCourseEditing();
    activateTabFromUrl(); // Add this line to handle tab state on load
    setupAjaxPagination();
    setupEnrollmentActions(); // Add this line
    setupGuestEnrollment();
    setupConfirmationModals();

    console.log('Initialization complete');
});
// Competency Management Functions
function initializeCompetencyHandlers() {
    // Add competency button handlers
    document.querySelectorAll('.add-competency-btn').forEach(button => {
        button.addEventListener('click', function() {
            const type = this.getAttribute('data-type');
            addCompetencyField(type);
        });
    });

    // Remove competency button handlers (for dynamically added fields)
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-competency-btn')) {
            e.preventDefault();
            const fieldGroup = e.target.closest('.competency-field-group');
            if (fieldGroup) {
                fieldGroup.remove();
            }
        }
    });
}

function setupEnrollmentActions() {
    const enrollmentTable = document.getElementById('enrollments');
    if (!enrollmentTable) return;

    enrollmentTable.addEventListener('click', function(e) {
        // Use .closest() to ensure we get the button, even if the icon or span is clicked
        const button = e.target.closest('.action-btn');
        if (button) {
            const action = button.dataset.action;
            const enrollmentId = button.closest('.enrollment-actions').dataset.enrollmentId;
            
            let remarks = '';
            if (action === 'reject') {
                remarks = prompt('Please provide a reason for rejection (optional):');
                // If user cancels the prompt, do nothing
                if (remarks === null) return;
            } else {
                // Optional remarks for approval
                remarks = prompt('Optional remarks for approval:');
                if (remarks === null) return;
            }

            processEnrollment(enrollmentId, action, remarks, button);
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

    fetch('../php/process_enrollment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Remove the table row from the UI
            button.closest('tr').remove();
        } else {
            alert('Error: ' + data.message);
            button.disabled = false;
            button.textContent = action.charAt(0).toUpperCase() + action.slice(1);
        }
    })
    .catch(error => {
        console.error('Error processing enrollment:', error);
        alert('An unexpected error occurred.');
        button.disabled = false;
        button.textContent = action.charAt(0).toUpperCase() + action.slice(1);
    });
}

function addCompetencyField(type) {
    const group = document.getElementById(`${type}CompetenciesGroup`);
    if (!group) return;

    const fieldsContainer = group.querySelector('.competency-fields');
    const newFieldGroup = document.createElement('div');
    newFieldGroup.className = 'competency-field-group';
    
    newFieldGroup.innerHTML = `
        <div class="form-group">
            <label>Name:</label>
            <input type="text" name="${type}_competency[]" placeholder="Enter ${type} competency name">
        </div>
        <div class="form-group">
            <label>Description:</label>
            <textarea name="${type}_competency_desc[]" rows="2" placeholder="Enter description (optional)"></textarea>
        </div>
        <button type="button" class="remove-competency-btn" style="background: #ff4444; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; margin-top: 10px;">
            Remove
        </button>
    `;
    
    fieldsContainer.appendChild(newFieldGroup);
}

// Edit Course Competency Functions
function initializeEditCompetencyHandlers() {
    const addCompetencyBtn = document.getElementById('add_new_competency_btn');
    if (addCompetencyBtn) {
        addCompetencyBtn.addEventListener('click', addNewCompetencyField);
    }

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-edit-competency-btn')) {
            e.preventDefault();
            const fieldGroup = e.target.closest('.edit-competency-field-group');
            if (fieldGroup) {
                fieldGroup.remove();
            }
        }
    });
}

function addNewCompetencyField() {
    const container = document.getElementById('edit_competencies_container');
    const index = container.children.length;
    
    const newFieldGroup = document.createElement('div');
    newFieldGroup.className = 'edit-competency-field-group';
    newFieldGroup.style.border = '1px solid #ddd';
    newFieldGroup.style.padding = '15px';
    newFieldGroup.style.marginBottom = '10px';
    newFieldGroup.style.borderRadius = '5px';
    
    newFieldGroup.innerHTML = `
        <div class="form-group">
            <label>Competency Type:</label>
            <select name="competencies[${index}][type]" required>
                <option value="basic">Basic Competency</option>
                <option value="common">Common Competency</option>
                <option value="core">Core Competency</option>
            </select>
        </div>
        <div class="form-group">
            <label>Name:</label>
            <input type="text" name="competencies[${index}][name]" placeholder="Enter competency name" required>
        </div>
        <div class="form-group">
            <label>Description:</label>
            <textarea name="competencies[${index}][description]" rows="2" placeholder="Enter description (optional)"></textarea>
        </div>
        <button type="button" class="remove-edit-competency-btn" style="background: #ff4444; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">
            Remove Competency
        </button>
    `;
    
    container.appendChild(newFieldGroup);
}

function loadCompetenciesForEdit(competencies) {
    const container = document.getElementById('edit_competencies_container');
    container.innerHTML = '';
    
    if (competencies && competencies.length > 0) {
        competencies.forEach((competency, index) => {
            const fieldGroup = document.createElement('div');
            fieldGroup.className = 'edit-competency-field-group';
            fieldGroup.style.border = '1px solid #ddd';
            fieldGroup.style.padding = '15px';
            fieldGroup.style.marginBottom = '10px';
            fieldGroup.style.borderRadius = '5px';
            
            fieldGroup.innerHTML = `
                <div class="form-group">
                    <label>Competency Type:</label>
                    <select name="competencies[${index}][type]" required>
                        <option value="basic" ${competency.type === 'basic' ? 'selected' : ''}>Basic Competency</option>
                        <option value="common" ${competency.type === 'common' ? 'selected' : ''}>Common Competency</option>
                        <option value="core" ${competency.type === 'core' ? 'selected' : ''}>Core Competency</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Name:</label>
                    <input type="text" name="competencies[${index}][name]" value="${competency.name || ''}" placeholder="Enter competency name" required>
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="competencies[${index}][description]" rows="2" placeholder="Enter description (optional)">${competency.description || ''}</textarea>
                </div>
                <button type="button" class="remove-edit-competency-btn" style="background: #ff4444; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">
                    Remove Competency
                </button>
            `;
            
            container.appendChild(fieldGroup);
        });
    }
}
function initializeModalButtons() {
    console.log('Initializing modal buttons...');
    
    const modalButtons = {
        'createTrainerBtn': 'createTrainerModal',
        'createTraineeBtn': 'createTraineeModal', 
        'addCourseBtn': 'addCourseModal',
        'addBatchBtn': 'addBatchModal',
        'addAnnouncementBtn': 'addAnnouncementModal',
        'editProfileBtn': 'editProfileModal'
    };
    
    Object.keys(modalButtons).forEach(buttonId => {
        const button = document.getElementById(buttonId);
        const modalId = modalButtons[buttonId];
        
        if (button) {
            console.log(`Found button: ${buttonId}, binding to modal: ${modalId}`);
            
            // Remove any existing event listeners
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Add new event listener
            newButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log(`Button clicked: ${buttonId}, opening modal: ${modalId}`);
                openModal(modalId);
            });
        } else {
            console.warn(`Button not found: ${buttonId}`);
        }
    });
}

// Enhanced openModal function
function openModal(modalId) {
    console.log(`Attempting to open modal: ${modalId}`);
    const modal = document.getElementById(modalId);
    if (modal) {
        // Remove hidden class and set display to flex
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        modal.style.opacity = '0';
        document.body.style.overflow = 'hidden';
        
        // Animate fade in
        setTimeout(() => {
            modal.style.opacity = '1';
            modal.style.transition = 'opacity 0.3s ease';
        }, 10);
        
        // Focus on first input
        const firstInput = modal.querySelector('input, textarea, select');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 300);
        }
        
        console.log(`Modal ${modalId} opened successfully`);

        // Dispatch a custom event
        dispatchModalOpened(modalId);
    } else {
        console.error(`Modal not found: ${modalId}`);
    }
}

// Enhanced closeModal function  
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
            modal.classList.add('hidden'); // Add hidden class back
            document.body.style.overflow = 'auto';
            
            // Reset form
            const form = modal.querySelector('form');
            if (form) form.reset();
        }, 300);
    }
}

function setupGlobalEventListeners() {
    // Modal close functionality
    const closeButtons = document.querySelectorAll('.close, .cancel-btn');
    const modals = document.querySelectorAll('.modal');
    
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                closeModal(modal.id);
            }
        });
    });
    
    // Close modal when clicking outside
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
    
    // Sidebar toggle
    const hamburger = document.getElementById('hamburger');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (hamburger) {
        hamburger.addEventListener('click', () => {
            document.body.classList.toggle('sidebar-open');
        });
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            document.body.classList.remove('sidebar-open');
        });
    }
    
    // Escape key to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal:not(.hidden)');
            openModals.forEach(modal => {
                closeModal(modal.id);
            });
        }
    });
}

// Switch functionality
function initializeSwitches() {
    const trainerSwitchButtons = document.querySelectorAll('#trainers .switch-btn');
    const traineeSwitchButtons = document.querySelectorAll('#trainees .switch-btn');
    const trainerSwitchInner = document.getElementById('trainerSwitchInner');
    const traineeSwitchInner = document.getElementById('traineeSwitchInner');
    
    // Trainer switch
    if (trainerSwitchButtons.length > 0) {
        updateSwitchPosition(trainerSwitchButtons, trainerSwitchInner);
        
        trainerSwitchButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                const parentSection = this.closest('.tab-content');
                
                // Update active state
                trainerSwitchButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                updateSwitchPosition(trainerSwitchButtons, trainerSwitchInner);
                
                // Show/hide content
                const tabInners = parentSection.querySelectorAll('.tab-inner');
                tabInners.forEach(tab => tab.classList.remove('active'));
                const targetElement = parentSection.querySelector(`#${targetTab}`);
                if (targetElement) {
                    targetElement.classList.add('active');
                }
            });
        });
    }
    
    // Trainee switch
    if (traineeSwitchButtons.length > 0) {
        updateSwitchPosition(traineeSwitchButtons, traineeSwitchInner);
        
        traineeSwitchButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                const parentSection = this.closest('.tab-content');
                
                // Update active state
                traineeSwitchButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                updateSwitchPosition(traineeSwitchButtons, traineeSwitchInner);
                
                // Show/hide content
                const tabInners = parentSection.querySelectorAll('.tab-inner');
                tabInners.forEach(tab => tab.classList.remove('active'));
                const targetElement = parentSection.querySelector(`#${targetTab}`);
                if (targetElement) {
                    targetElement.classList.add('active');
                }
            });
        });
    }
}

function updateSwitchPosition(buttons, switchInner) {
    const activeBtn = Array.from(buttons).find(btn => btn.classList.contains('active'));
    if (activeBtn && switchInner) {
        const btnRect = activeBtn.getBoundingClientRect();
        const containerRect = activeBtn.parentElement.getBoundingClientRect();
        
        switchInner.style.width = `${btnRect.width}px`;
        switchInner.style.height = `${btnRect.height}px`;
        switchInner.style.transform = `translateX(${btnRect.left - containerRect.left}px)`;
    }
}

function setupTabNavigation() {
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Remove active class from all tabs and contents
            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            link.classList.add('active');
            const tabId = link.getAttribute('data-tab');
            const targetContent = document.getElementById(tabId);
            if (targetContent) {
                targetContent.classList.add('active');
            }
            
            // Close sidebar on mobile after clicking a tab
            if (window.innerWidth <= 768) {
                document.body.classList.remove('sidebar-open');
            }
            
            // Reinitialize switches when switching to trainers or trainees tab
            if (tabId === 'trainers' || tabId === 'trainees') {
                setTimeout(() => {
                    initializeSwitches();
                }, 100);
            }
        });
    });
}

function activateTabFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const tabId = params.get('current_tab') || window.location.hash.substring(1);

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

            console.log(`Activated tab from URL: ${tabId}`);
        }
    }
}


function setupDashboardCards() {
    const dashboardCards = document.querySelectorAll('.dashboard-card');
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    
    dashboardCards.forEach(card => {
        card.addEventListener('click', () => {
            const targetTab = card.getAttribute('data-target');
            const targetTabLink = document.querySelector(`.tab-link[data-tab="${targetTab}"]`);
            
            if (targetTabLink) {
                tabLinks.forEach(l => l.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                targetTabLink.classList.add('active');
                const targetContent = document.getElementById(targetTab);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
                
                // Reinitialize switches when switching to trainers or trainees tab
                if (targetTab === 'trainers' || targetTab === 'trainees') {
                    setTimeout(() => {
                        initializeSwitches();
                    }, 100);
                }
            }
        });
    });
}

function setupNotificationDropdown() {
    const notifIcon = document.getElementById('notifIcon');
    const notifDropdown = document.getElementById('notifDropdown');
    
    if (notifIcon && notifDropdown) {
        notifIcon.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!notifIcon.contains(e.target) && !notifDropdown.contains(e.target)) {
                notifDropdown.classList.add('hidden');
            }
        });
    }
}

function setupDynamicBatchLoading() {
    const traineeCourseSelect = document.getElementById('trainee_course');
    const traineeBatchSelect = document.getElementById('trainee_batch');

    if (traineeCourseSelect && traineeBatchSelect) {
        traineeCourseSelect.addEventListener('change', function() {
            const courseCode = this.value;
            traineeBatchSelect.innerHTML = '<option value="">Select a batch</option>';
            
            if (courseCode) {
                // Fetch batches for the selected course via AJAX
                fetch(`../php/get_batches.php?course_code=${encodeURIComponent(courseCode)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(batches => {
                        if (batches && batches.length > 0) {
                            batches.forEach(batch => {
                                const option = document.createElement('option');
                                option.value = batch.batch_name || batch;
                                option.textContent = batch.batch_name || batch;
                                traineeBatchSelect.appendChild(option);
                            });
                        } else {
                            const option = document.createElement('option');
                            option.value = "";
                            option.textContent = "No batches available";
                            traineeBatchSelect.appendChild(option);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching batches:', error);
                        // Fallback: try to get batches from the add batch modal
                        try {
                            const batchOptions = Array.from(document.querySelectorAll('#batch_course_code option'))
                                .filter(opt => opt.value === courseCode);
                            
                            if (batchOptions.length > 0) {
                                // If we have the course, try to find batches in the page
                                const batchElements = document.querySelectorAll('.course-batches li');
                                const batches = [];
                                
                                batchElements.forEach(li => {
                                    const batchText = li.textContent.trim();
                                    const batchMatch = batchText.match(/^([^(]+)/);
                                    if (batchMatch) {
                                        batches.push(batchMatch[1].trim());
                                    }
                                });
                                
                                if (batches.length > 0) {
                                    batches.forEach(batch => {
                                        const option = document.createElement('option');
                                        option.value = batch;
                                        option.textContent = batch;
                                        traineeBatchSelect.appendChild(option);
                                    });
                                } else {
                                    const option = document.createElement('option');
                                    option.value = "";
                                    option.textContent = "No batches available";
                                    traineeBatchSelect.appendChild(option);
                                }
                            }
                        } catch (fallbackError) {
                            console.error('Fallback batch loading failed:', fallbackError);
                            const option = document.createElement('option');
                            option.value = "";
                            option.textContent = "Error loading batches";
                            traineeBatchSelect.appendChild(option);
                        }
                    });
            }
        });
    }
}

function setupUserManagement() {
    // Reset password functionality
    document.querySelectorAll('.reset-password-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            
            const resetUserId = document.getElementById('resetUserId');
            const resetUserName = document.getElementById('resetUserName');
            
            if (resetUserId && resetUserName) {
                resetUserId.value = userId;
                resetUserName.textContent = userName;
                openModal('resetPasswordModal');
            }
        });
    });

    // Handle Reset Password Form Submission
    const resetPasswordForm = document.getElementById('resetPasswordForm');
    if (resetPasswordForm) {
        resetPasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('.submit-btn');
            const originalText = submitBtn.textContent;

            submitBtn.textContent = 'Resetting...';
            submitBtn.disabled = true;

            fetch('../php/reset_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('resetPasswordModal');
                    document.getElementById('resetPasswordConfirmationContent').innerHTML = `
                        <span style="background: #fff3cd; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 1.2em;">
                            ${data.temp_password}
                        </span>
                    `;
                    openModal('resetPasswordConfirmationModal');
                } else {
                    alert('Error resetting password: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });
    }

    // Handle Edit Trainer Form Submission
    const editTrainerForm = document.getElementById('editTrainerForm');
    if (editTrainerForm) {
        editTrainerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);
            formData.append('user_role', 'trainer'); // Add role to the form data

            fetch('../php/update_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Error updating trainer: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }

    // Handle Edit Trainee Form Submission
    const editTraineeForm = document.getElementById('editTraineeForm');
    if (editTraineeForm) {
        editTraineeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);
            formData.append('user_role', 'trainee'); // Add role to the form data

            fetch('../php/update_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Error updating trainee: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }

    // Edit Trainer functionality
    document.querySelectorAll('.edit-trainer-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userDataStr = this.getAttribute('data-user-data');

            try {
                const userData = JSON.parse(userDataStr);

                // Populate basic fields
                document.getElementById('editTrainerId').value = userId;
                document.getElementById('edit_trainer_first_name').value = userData.first_name || '';
                document.getElementById('edit_trainer_last_name').value = userData.last_name || '';
                document.getElementById('edit_trainer_email').value = userData.email || '';
                document.getElementById('edit_trainer_contact').value = userData.contact_number || '';

                // Fetch and select assigned courses
                const coursesSelect = document.getElementById('edit_trainer_courses');
                // Clear previous selections
                Array.from(coursesSelect.options).forEach(option => option.selected = false);

                fetch(`../php/get_trainer_courses.php?trainer_id=${userId}`)
                    .then(response => response.json())
                    .then(assignedCourses => {
                        assignedCourses.forEach(course => {
                            const option = coursesSelect.querySelector(`option[value="${course.course_code}"]`);
                            if (option) {
                                option.selected = true;
                            }
                        });
                    })
                    .catch(error => console.error('Error fetching trainer courses:', error));

                openModal('editTrainerModal');
            } catch (error) {
                console.error('Error parsing user data:', error);
                alert('Error loading user data. Please try again.');
            }
        });
    });

    // Add event listeners for dynamic email generation in edit trainer modal
    const editTrainerFirstName = document.getElementById('edit_trainer_first_name');
    const editTrainerLastName = document.getElementById('edit_trainer_last_name');
    const editTrainerEmail = document.getElementById('edit_trainer_email');

    if (editTrainerFirstName && editTrainerLastName && editTrainerEmail) {
        const updateTrainerEmail = () => {
            const firstName = editTrainerFirstName.value.trim().replace(/[^a-zA-Z]/g, '').toLowerCase();
            const lastName = editTrainerLastName.value.trim().replace(/[^a-zA-Z]/g, '').toLowerCase();
            if (firstName && lastName) {
                editTrainerEmail.value = `${lastName}.${firstName}@bts.gov.ph`;
            }
        };
        editTrainerFirstName.addEventListener('input', updateTrainerEmail);
        editTrainerLastName.addEventListener('input', updateTrainerEmail);
    }

    // Add event listener for dynamic email generation in edit trainee modal
    const editTraineeLastName = document.getElementById('edit_trainee_last_name');
    const editTraineeEmail = document.getElementById('edit_trainee_email');
    const editTraineeIdInput = document.getElementById('editTraineeId');


    // Edit Trainee functionality
    document.querySelectorAll('.edit-trainee-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userDataStr = this.getAttribute('data-user-data');

            try {
                const userData = JSON.parse(userDataStr);

                // Populate basic fields
                document.getElementById('editTraineeId').value = userId;
                document.getElementById('edit_trainee_first_name').value = userData.first_name || '';
                document.getElementById('edit_trainee_last_name').value = userData.last_name || '';
                document.getElementById('edit_trainee_email').value = userData.email || '';
                document.getElementById('edit_trainee_contact').value = userData.contact_number || '';

                const courseSelect = document.getElementById('edit_trainee_course');
                const batchSelect = document.getElementById('edit_trainee_batch');

                // Fetch current enrollment
                fetch(`../php/get_trainee_enrollments.php?trainee_id=${userId}`)
                    .then(response => response.json())
                    .then(enrollment => {
                        courseSelect.value = enrollment.course_code || '';

                        // Trigger change event to load batches for the selected course
                        const event = new Event('change');
                        courseSelect.dispatchEvent(event);

                        // Set the batch after a short delay to allow batches to load
                        setTimeout(() => {
                            batchSelect.value = enrollment.batch_name || '';
                        }, 300); // 300ms delay
                    })
                    .catch(error => console.error('Error fetching trainee enrollments:', error));

                openModal('editTraineeModal');
            } catch (error) {
                console.error('Error parsing user data:', error);
                alert('Error loading user data. Please try again.');
            }
        });
    });

    if (editTraineeLastName && editTraineeEmail && editTraineeIdInput) {
        const updateTraineeEmail = () => {
            const lastName = editTraineeLastName.value.trim().replace(/[^a-zA-Z]/g, '').toLowerCase();
            const userId = editTraineeIdInput.value;
            if (lastName && userId) {
                editTraineeEmail.value = `${lastName}.${userId}@bts.gov.ph`;
            }
        };
        editTraineeLastName.addEventListener('input', updateTraineeEmail);
        // Also update if the ID somehow changes (though it shouldn't for an existing user)
        new MutationObserver(updateTraineeEmail).observe(editTraineeIdInput, { attributes: true, childList: true, subtree: true });
    }

    // Dynamic batch loading for the EDIT trainee modal
    const editTraineeCourseSelect = document.getElementById('edit_trainee_course');
    const editTraineeBatchSelect = document.getElementById('edit_trainee_batch');

    if (editTraineeCourseSelect && editTraineeBatchSelect) {
        editTraineeCourseSelect.addEventListener('change', function() {
            const courseCode = this.value;
            editTraineeBatchSelect.innerHTML = '<option value="">Select Batch</option>'; // Reset

            if (courseCode) {
                fetch(`../php/get_batches.php?course_code=${courseCode}`)
                    .then(response => response.json())
                    .then(batches => {
                        batches.forEach(batch => {
                            const option = new Option(batch.batch_name, batch.batch_name);
                            editTraineeBatchSelect.add(option);
                        });
                    });
            }
        });
    }

    // Enhanced View Course Details functionality
document.querySelectorAll('.view-details-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const courseDataStr = this.getAttribute('data-course');        
        try {
            const courseData = JSON.parse(courseDataStr);
            const courseGrid = document.querySelector('.courses-grid');
            const detailView = document.getElementById('course-detail-view');
            
            if (!courseGrid || !detailView) {
                console.error('Course grid or detail view container not found.');
                return;
            }

            // Hide grid, show detail view with loading state
            courseGrid.classList.add('hidden');
            detailView.classList.remove('hidden');
            detailView.innerHTML = '<div class="loading">Loading course details...</div>';

            // Fetch comprehensive course data
            fetch(`../php/get_course_details.php?course_code=${courseData.course_code}`)
                .then(response => response.json())
                .then(courseDetails => {
                    renderCourseDetails(detailView, courseDetails);

                    // Add back button functionality
                    const backBtn = detailView.querySelector('.back-to-course-list-btn');
                    if (backBtn) {
                        backBtn.classList.remove('hidden'); // Make sure it's visible
                        backBtn.addEventListener('click', () => {
                            detailView.classList.add('hidden');
                            courseGrid.classList.remove('hidden');
                            detailView.innerHTML = ''; // Clear content
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching course details:', error);
                    content.innerHTML = '<div class="error">Error loading course details. Please try again.</div>';
                });
        } catch (error) {
            console.error('Error parsing course data:', error);
            alert('Error loading course details. Please try again.');
        }
    });
});

function renderCourseDetails(content, data) {
    // The back button is now part of the rendered HTML
    if (data.error) {
        content.innerHTML = `<div class="error">${data.error}</div>`;
        return;
    }
    let html = `
        <button class="back-to-course-list-btn hidden">← Back to Course Details</button>
        <div class="course-details">
            <div class="course-header">
    `;
    
    // Course image
    if (data.course && data.course.image) {
        html += `<img src="../uploads/courses/${data.course.image}" alt="${data.course.course_name}" class="course-detail-image">`;
    } else {
        html += `<div class="course-detail-image-placeholder">
                    <i class="fas fa-book"></i>
                 </div>`;
    }
    
    html += `
                <div class="course-basic-info">
                    <h3>${data.course?.course_name || 'Unknown Course'}</h3>
                    <p><strong>Code:</strong> ${data.course?.course_code || 'N/A'}</p>
                    <p><strong>Hours:</strong> ${data.course?.hours || 'N/A'}</p>
                    <p><strong>Description:</strong> ${data.course?.description || 'No description available'}</p>
                </div>
            </div>
    `;
    
    // Batches Section
    if (data.batches && data.batches.length > 0) {
        html += `
            <div class="course-section" id="batch-selection-section">
                <h4>Batches</h4>
                <div class="batches-list">
        `;
        data.batches.forEach(batch => {
            html += `
                <div class="batch-item" data-batch-name="${batch.batch_name}">
                    <i class="fas fa-users"></i>
                    <div class="batch-item-info">
                        <strong>${batch.batch_name}</strong>
                        <small>${batch.trainee_count} trainee(s)</small>
                    </div>
                    <span class="view-content-arrow">→</span>
                </div>
            `;
        });
        html += `</div></div>`;
    } else {
        html += `
            <div class="course-section" id="batch-selection-section">
                <h4>Batches</h4>
                <p class="no-data">No batches defined for this course.</p>
            </div>
        `;
    }

    // Competencies with Topics, Materials, and Activities
    if (data.competencies && data.competencies.length > 0) {
        html += `
            <div class="course-section hidden" id="course-content-section">
                <h4>Competencies & Content</h4>
                <div class="competencies-accordion">
        `;
        
        if (data.competencies && data.competencies.length > 0) {
            data.competencies.forEach(comp => {
                if (comp && comp.type && comp.name) {
                    html += `
                        <div class="competency-item">
                            <div class="competency-header">
                                <strong>${comp.type.toUpperCase()} COMPETENCY:</strong>
                                ${comp.name}
                                ${comp.description ? `- ${comp.description}` : ''}
                                <span class="toggle-icon">▼</span>
                            </div>
                            <div class="competency-content">
                    `;
                    
                    // Topics for this competency
                    if (comp.topics && comp.topics.length > 0) {
                        html += `<div class="topics-section">`;
                        
                        comp.topics.forEach(topic => {
                            html += `
                                <div class="topic-item">
                                    <h5>📚 ${topic.topic_name}</h5>
                                    ${topic.topic_description ? `<p class="topic-description">${topic.topic_description}</p>` : ''}
                            `;
                            
                            // Materials for this topic
                            if (topic.materials && topic.materials.length > 0) {
                                html += `
                                    <div class="materials-section">
                                        <h6>📎 Course Materials:</h6>
                                        <ul class="materials-list">
                                `;
                                topic.materials.forEach(material => {
                                    const filePath = material.material_file_path || material.file_path;
                                    const isLink = filePath && (filePath.startsWith('http://') || filePath.startsWith('https://'));
                                    const downloadPath = isLink ? filePath : `../uploads/courses/${filePath}`;
                                    html += `
                                        <li>
                                            <strong>${material.material_title}</strong>
                                            ${material.material_description ? `<br><small>${material.material_description}</small>` : ''}
                                            ${filePath ? `<br><a href="${downloadPath}" target="_blank" class="download-link">View Material</a>` : ''}
                                            <br><small>Uploaded by: ${material.first_name || 'Unknown'} ${material.last_name || ''} on ${new Date(material.uploaded_at).toLocaleDateString()}</small>
                                        </li>
                                    `;
                                });
                                html += `</ul></div>`;
                            }
                            
                            // Activities for this topic
                            if (topic.activities && topic.activities.length > 0) {
                                html += `
                                    <div class="activities-section">
                                        <h6>📝 Activities:</h6>
                                        <div class="activities-list">
                                `;
                                topic.activities.forEach(activity => {
                                    html += `
                                        <div class="activity-item">
                                            <div class="activity-header">
                                                <strong>${activity.activity_title}</strong>
                                                <span class="activity-type ${activity.activity_type}">${activity.activity_type}</span>
                                                <span class="toggle-submissions-icon">▶</span>
                                            </div>
                                            <div class="activity-details">
                                                ${activity.due_date ? `<small>Due: ${new Date(activity.due_date).toLocaleString()}</small>` : ''}
                                                ${activity.max_score ? `<small>Max Score: ${activity.max_score}</small>` : ''}
                                                ${activity.activity_description ? `<p>${activity.activity_description}</p>` : ''}
                                            </div>
                                            
                                            <!-- Submissions for this activity -->
                                            <div class="submissions-section hidden">
                                                <h6>📤 Submissions (${activity.submissions.length}):</h6>
                                    `;
                                    if (activity.submissions && activity.submissions.length > 0) {
                                        html += `
                                                <table class="submissions-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Trainee</th>
                                                            <th>Submitted</th>
                                                            <th>Status</th>
                                                            <th>Score</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                        `;
                                        activity.submissions.forEach(submission => {
                                            const status = submission.score !== null ? 'Graded' : 'Submitted';
                                            const statusClass = submission.score !== null ? 'graded' : 'submitted';
                                            html += `
                                                <tr>
                                                    <td>${submission.first_name} ${submission.last_name}</td>
                                                    <td>${new Date(submission.submitted_at).toLocaleString()}</td>
                                                    <td><span class="status-badge ${statusClass}">${status}</span></td>
                                                    <td>${submission.score !== null ? submission.score : 'Not graded'}</td>
                                                    <td>
                                                        ${submission.file_path ? `<a href="../uploads/submissions/${submission.file_path}" target="_blank" class="view-submission-btn">View</a>` : ''}
                                                        ${submission.submission_text ? `<button class="view-text-btn" data-text="${submission.submission_text}">View Text</button>` : ''}
                                                    </td>
                                                </tr>
                                            `;
                                        });
                                        html += `</tbody></table>`;
                                    } else {
                                        html += `<p class="no-submissions">No submissions yet</p>`;
                                    }
                                    html += `</div></div>`; // Close activity-item and submissions-section
                                });
                                html += `</div></div>`; // Close activities-list and activities-section
                            }
                            
                            html += `</div>`; // Close topic-item
                        });
                        
                        html += `</div>`; // Close topics-section
                    } else {
                        html += `<p class="no-topics">No topics added for this competency yet.</p>`;
                    }
                    
                    html += `</div></div>`; // Close competency-content and competency-item
                }
            });
        } else {
            html += `<p class="no-data">No competencies defined for this course.</p>`;
        }
        
        html += `</div></div>`; // Close competencies-accordion and course-section
    } else {
        html += `
            <div class="course-section hidden" id="course-content-section-fallback">
                <h4>Competencies</h4>
                <p class="no-data">No competencies defined for this course.</p>
            </div>
        `;
    }
    
    html += `</div>`; // Close course-details
    content.innerHTML = html;
    
    // Add accordion functionality
    initializeBatchView(content);
    initializeAccordions();
    initializeSubmissionViewers();

    // Add event listener for toggling submissions
    content.querySelectorAll('.activity-header').forEach(header => {
        header.addEventListener('click', function() {
            const submissionSection = this.closest('.activity-item').querySelector('.submissions-section');
            const icon = this.querySelector('.toggle-submissions-icon');
            if (submissionSection) {
                submissionSection.classList.toggle('hidden');
                if (icon) {
                    icon.textContent = submissionSection.classList.contains('hidden') ? '▶' : '▼';
                }
            }
        });
    });
}

function initializeBatchView(modalContent) {
    const batchItems = modalContent.querySelectorAll('.batch-item');
    const batchSection = modalContent.querySelector('#batch-selection-section');
    const contentSection = modalContent.querySelector('#course-content-section');
    const backButton = modalContent.querySelector('.back-to-course-list-btn');
    const fallbackContentSection = modalContent.querySelector('#course-content-section-fallback');

    batchItems.forEach(item => {
        item.addEventListener('click', () => {
            batchSection.classList.add('hidden');
            if (contentSection) contentSection.classList.remove('hidden');
            backButton.classList.remove('hidden');
        });
    });

    backButton.addEventListener('click', () => {
        batchSection.classList.remove('hidden');
        if (contentSection) {
            contentSection.classList.add('hidden');
        }
        if (fallbackContentSection) {
            fallbackContentSection.classList.add('hidden');
        }
        backButton.classList.add('hidden');
    });
}

function initializeAccordions() {
    const competencyHeaders = document.querySelectorAll('.competency-header');
    competencyHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const content = this.nextElementSibling;
            const icon = this.querySelector('.toggle-icon');
            
            content.classList.toggle('active');
            icon.textContent = content.classList.contains('active') ? '▲' : '▼';
        });
    });
}

function initializeSubmissionViewers() {
    // View text submission modal
    document.querySelectorAll('.view-text-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const text = this.getAttribute('data-text');
            showTextSubmissionModal(text);
        });
    });
}

function showTextSubmissionModal(text) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'flex';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2>Submission Text</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="submission-text-content">
                    ${text.replace(/\n/g, '<br>')}
                </div>
            </div>
            <div class="modal-footer">
                <button class="cancel-btn">Close</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Close functionality
    const closeBtn = modal.querySelector('.close');
    const cancelBtn = modal.querySelector('.cancel-btn');
    
    const closeModal = () => {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.remove();
        }, 300);
    };
    
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
}

    // Profile picture preview
    const profilePictureInput = document.getElementById('profile_picture');
    const currentProfilePicture = document.getElementById('currentProfilePicture');

    if (profilePictureInput && currentProfilePicture) {
        profilePictureInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, GIF).');
                    this.value = '';
                    return;
                }
                
                // Validate file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB.');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    currentProfilePicture.src = e.target.result;
                };
                reader.onerror = function() {
                    alert('Error reading file. Please try another image.');
                };
                reader.readAsDataURL(file);
            }
        });
    }
}

function setupGuestEnrollment() {
    document.querySelectorAll('.enroll-guest-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            
            const enrollGuestId = document.getElementById('enrollGuestId');
            const enrollGuestName = document.getElementById('enrollGuestName');
            
            if (enrollGuestId && enrollGuestName) {
                enrollGuestId.value = userId;
                enrollGuestName.textContent = userName;
                openModal('enrollGuestModal');
            }
        });
    });
}

function setupConfirmationModals() {
    const createTrainerModal = document.getElementById('createTrainerModal');
    const createTraineeModal = document.getElementById('createTraineeModal');

    if (createTrainerModal) {
        const submitBtn = createTrainerModal.querySelector('.submit-btn');
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.getElementById('createTrainerForm');
            // Basic validation
            const firstName = form.querySelector('#trainer_first_name').value.trim();
            const lastName = form.querySelector('#trainer_last_name').value.trim();
            const contactNumber = form.querySelector('#trainer_number').value.trim();

            if (!firstName || !lastName || !contactNumber) {
                alert('Please fill in all required fields: First Name, Last Name, and Contact Number.');
                return;
            }

            showTrainerConfirmation(form);
        });
    }

    if (createTraineeModal) {
        const submitBtn = createTraineeModal.querySelector('.submit-btn');
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.getElementById('createTraineeForm');
            // Basic validation
            const firstName = form.querySelector('#trainee_first_name').value.trim();
            const lastName = form.querySelector('#trainee_last_name').value.trim();
            const contactNumber = form.querySelector('#trainee_number').value.trim();

            if (!firstName || !lastName || !contactNumber) {
                alert('Please fill in all required fields: First Name, Last Name, and Contact Number.');
                return;
            }

            showTraineeConfirmation(form);
        });
    }

    // Confirm trainer creation
    const confirmTrainerBtn = document.getElementById('confirmTrainerBtn');
    if (confirmTrainerBtn) {
        confirmTrainerBtn.addEventListener('click', function() {
            const form = document.getElementById('createTrainerForm');
            if (form) {
                submitTrainerForm(form);
            }
        });
    }

    // Confirm trainee creation  
    const confirmTraineeBtn = document.getElementById('confirmTraineeBtn');
    if (confirmTraineeBtn) {
        confirmTraineeBtn.addEventListener('click', function() {
            const form = document.getElementById('createTraineeForm');
            if (form) {
                submitTraineeForm(form);
            }
        });
    }
}

function submitTrainerForm(form) {
    const formData = new FormData(form);
    
    // Show loading state
    const confirmBtn = document.getElementById('confirmTrainerBtn');
    const originalText = confirmBtn.textContent;
    confirmBtn.textContent = 'Creating...';
    confirmBtn.disabled = true;
    
    fetch('../php/create_trainer.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update confirmation modal with success and credentials
            updateConfirmationWithCredentials('trainer', data.credentials);
        } else {
            alert('Error creating trainer: ' + data.message);
            closeModal('trainerConfirmationModal');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error creating trainer. Please try again.');
        closeModal('trainerConfirmationModal');
    })
    .finally(() => {
        confirmBtn.textContent = originalText;
        confirmBtn.disabled = false;
    });
}

function submitTraineeForm(form) {
    const formData = new FormData(form);
    
    // Show loading state
    const confirmBtn = document.getElementById('confirmTraineeBtn');
    const originalText = confirmBtn.textContent;
    confirmBtn.textContent = 'Creating...';
    confirmBtn.disabled = true;
    
    fetch('../php/create_trainee.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update confirmation modal with success and credentials
            updateConfirmationWithCredentials('trainee', data.credentials);
        } else {
            alert('Error creating trainee: ' + data.message);
            closeModal('traineeConfirmationModal');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error creating trainee. Please try again.');
        closeModal('traineeConfirmationModal');
    })
    .finally(() => {
        confirmBtn.textContent = originalText;
        confirmBtn.disabled = false;
    });
}

function showTrainerConfirmation(form) {
    const formData = new FormData(form);
    
    const firstName = formData.get('trainer_first_name');
    const middleName = formData.get('trainer_middle_name') || '';
    const lastName = formData.get('trainer_last_name');
    const suffix = formData.get('trainer_suffix') || '';
    const contactNumber = formData.get('trainer_number');
    const courseSelect = form.querySelector('#trainer_courses');
    const selectedCourses = Array.from(courseSelect.selectedOptions).map(opt => opt.textContent.split(' (')[0]);
    
    let confirmationHTML = `
        <div class="confirmation-details">
            <h3>Please review the trainer details:</h3>
            <div class="confirmation-item">
                <strong>Name:</strong> ${firstName} ${middleName} ${lastName} ${suffix}
            </div>
            <div class="confirmation-item">
                <strong>Contact Number:</strong> ${contactNumber}
            </div>
    `;
    
    if (selectedCourses.length > 0) {
        confirmationHTML += `
            <div class="confirmation-item">
                <strong>Assigned Courses:</strong> ${selectedCourses.join(', ')}
            </div>`;
    } else {
        confirmationHTML += `
            <div class="confirmation-item">
                <strong>Assigned Courses:</strong> None
            </div>`;
    }
    
    confirmationHTML += `
        <div class="confirmation-note">
            <p><em>Click "Create Trainer" to generate credentials.</em></p>
        </div>
        <div id="trainerCredentialsSection" style="display: none; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <!-- Credentials will be displayed here after creation -->
        </div>
    </div>`;
    
    document.getElementById('trainerConfirmationContent').innerHTML = confirmationHTML;

    // Reset confirm button to original state
    const confirmBtn = document.getElementById('confirmTrainerBtn');
    confirmBtn.textContent = 'Create Trainer';
    confirmBtn.onclick = function() {
        submitTrainerForm(form);
    };

    openModal('trainerConfirmationModal');
}

function showTraineeConfirmation(form) {
    const formData = new FormData(form);
    
    const firstName = formData.get('trainee_first_name');
    const middleName = formData.get('trainee_middle_name') || '';
    const lastName = formData.get('trainee_last_name');
    const suffix = formData.get('trainee_suffix') || '';
    const contactNumber = formData.get('trainee_number');
    const courseCode = formData.get('trainee_course');
    const batchName = formData.get('trainee_batch');
    
    let confirmationHTML = `
        <div class="confirmation-details">
            <h3>Please review the trainee details:</h3>
            <div class="confirmation-item">
                <strong>Name:</strong> ${firstName} ${middleName} ${lastName} ${suffix}
            </div>
            <div class="confirmation-item">
                <strong>Contact Number:</strong> ${contactNumber}
            </div>
            <div class="confirmation-item">
                <strong>Course:</strong> ${courseCode || 'Not assigned'}
            </div>
            <div class="confirmation-item">
                <strong>Batch:</strong> ${batchName || 'Not assigned'}
            </div>
        <div class="confirmation-note">
            <p><em>Click "Create Trainee" to generate credentials.</em></p>
        </div>
        <div id="traineeCredentialsSection" style="display: none; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <!-- Credentials will be displayed here after creation -->
        </div>
    </div>`;
    
    document.getElementById('traineeConfirmationContent').innerHTML = confirmationHTML;

    // Reset confirm button to original state
    const confirmBtn = document.getElementById('confirmTraineeBtn');
    confirmBtn.textContent = 'Create Trainee';
    confirmBtn.onclick = function() {
        submitTraineeForm(form);
    };

    openModal('traineeConfirmationModal');
}

// Unified confirmation functions
function showAccountConfirmation(type, form) {
    const formData = new FormData(form);
    
    let firstName, middleName, lastName, suffix, contactNumber, courseInfo, batchInfo;
    
    if (type === 'trainer') {
        firstName = formData.get('trainer_first_name');
        middleName = formData.get('trainer_middle_name') || '';
        lastName = formData.get('trainer_last_name');
        suffix = formData.get('trainer_suffix') || '';
        contactNumber = formData.get('trainer_number');
        
        const courseSelect = form.querySelector('#trainer_courses');
        const selectedCourses = Array.from(courseSelect.selectedOptions).map(opt => {
            const text = opt.textContent;
            return text.split(' (')[0]; // Remove course code from display
        });
        courseInfo = selectedCourses.length > 0 ? selectedCourses.join(', ') : 'None';
        batchInfo = 'N/A';
        
    } else if (type === 'trainee') {
        firstName = formData.get('trainee_first_name');
        middleName = formData.get('trainee_middle_name') || '';
        lastName = formData.get('trainee_last_name');
        suffix = formData.get('trainee_suffix') || '';
        contactNumber = formData.get('trainee_number');
        
        const courseCode = formData.get('trainee_course');
        const batchName = formData.get('trainee_batch');
        
        // Get course name from select option
        const courseSelect = form.querySelector('#trainee_course');
        const selectedCourse = courseSelect.selectedOptions[0];
        courseInfo = selectedCourse ? selectedCourse.textContent.split(' (')[0] : 'Not assigned';
        batchInfo = batchName || 'Not assigned';
    }
    
    // Generate expected information for display
    const expectedInfo = generateExpectedAccountInfo(type, firstName, lastName);
    
    const content = document.getElementById('accountConfirmationContent');
    content.innerHTML = `
        <div class="confirmation-details">
            <h3>Please review the ${type} details:</h3>
            
            <div class="confirmation-section">
                <h4>Personal Information</h4>
                <div class="confirmation-item">
                    <strong>Full Name:</strong> ${firstName} ${middleName} ${lastName} ${suffix}
                </div>
                <div class="confirmation-item">
                    <strong>Contact Number:</strong> ${contactNumber}
                </div>
            </div>
            
            <div class="confirmation-section">
                <h4>Account Information</h4>
                <div class="confirmation-item">
                    <strong>Expected User ID:</strong> ${expectedInfo.userId}
                </div>
                <div class="confirmation-item">
                    <strong>Expected Email:</strong> ${expectedInfo.email}
                </div>
            </div>
            
            <div class="confirmation-section">
                <h4>${type === 'trainer' ? 'Course Assignment' : 'Enrollment Information'}</h4>
                <div class="confirmation-item">
                    <strong>${type === 'trainer' ? 'Assigned Courses' : 'Course'}:</strong> ${courseInfo}
                </div>
                ${type === 'trainee' ? `<div class="confirmation-item"><strong>Batch:</strong> ${batchInfo}</div>` : ''}
            </div>
            
            <div class="confirmation-note">
                <p><em>Click "Confirm Creation" to generate the account with auto-generated credentials.</em></p>
            </div>
            
            <div id="accountCredentialsSection" style="display: none; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #28a745;">
                <!-- Credentials will be displayed here after creation -->
            </div>
        </div>
    `;
    
    // Update modal title
    document.getElementById('accountConfirmationTitle').textContent = `Confirm ${type.charAt(0).toUpperCase() + type.slice(1)} Creation`;
    
    // Set up confirm button
    const confirmBtn = document.getElementById('confirmAccountBtn');
    confirmBtn.textContent = 'Confirm Creation';
    confirmBtn.onclick = function() {
        if (type === 'trainer') {
            submitAccountForm(type, form);
        } else if (type === 'trainee') {
            submitAccountForm(type, form);
        }
    };
    
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
    } else if (type === 'trainee') {
        userId = '3' + datePart + randomPart;
        const cleanLastName = lastName.replace(/[^a-zA-Z]/g, '').toLowerCase();
        email = `${cleanLastName}.${userId}@bts.gov.ph`;
    }
    
    return { userId, email };
}

function submitAccountForm(type, form) {
    const formData = new FormData(form);
    const url = type === 'trainer' ? '../php/create_trainer.php' : '../php/create_trainee.php';
    
    // Show loading state
    const confirmBtn = document.getElementById('confirmAccountBtn');
    const originalText = confirmBtn.textContent;
    confirmBtn.textContent = 'Creating...';
    confirmBtn.disabled = true;
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAccountCreationSuccess(type, data.credentials);
        } else {
            alert(`Error creating ${type}: ` + data.message);
            closeModal('accountConfirmationModal');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(`Error creating ${type}. Please try again.`);
        closeModal('accountConfirmationModal');
    })
    .finally(() => {
        confirmBtn.textContent = originalText;
        confirmBtn.disabled = false;
    });
}

function showAccountCreationSuccess(type, credentials) {
    const content = document.getElementById('accountConfirmationContent');
    const credentialsSection = document.getElementById('accountCredentialsSection');
    const confirmBtn = document.getElementById('confirmAccountBtn');
    
    // Show credentials section
    credentialsSection.style.display = 'block';
    credentialsSection.innerHTML = `
        <div class="success-message" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <h4 style="margin: 0 0 10px 0;">✅ ${type.charAt(0).toUpperCase() + type.slice(1)} Created Successfully!</h4>
            <p style="margin: 0;">Account has been created with the following credentials:</p>
        </div>
        <div class="credentials-display">
            <div class="credential-item">
                <strong>User ID:</strong> 
                <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 1.1em;">
                    ${credentials.user_id}
                </span>
            </div>
            <div class="credential-item">
                <strong>Email:</strong> 
                <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-family: monospace;">
                    ${credentials.email}
                </span>
            </div>
            <div class="credential-item">
                <strong>Temporary Password:</strong> 
                <span style="background: #fff3cd; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 1.1em; font-weight: bold;">
                    ${credentials.temp_password}
                </span>
            </div>
        </div>
        <div class="confirmation-note" style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 5px; border-left: 4px solid #007bff;">
            <p style="margin: 0;"><strong>⚠️ Important:</strong> Please save these credentials as they will not be shown again. 
            The ${type} will need these credentials to log in for the first time.</p>
        </div>
    `;
    
    // Change button to close modal
    confirmBtn.textContent = 'Done';
    confirmBtn.onclick = function() {
        closeModal('accountConfirmationModal');
        const targetTab = type === 'trainer' ? 'trainers' : 'trainees';
        // Reload the page to show the new user
        // and navigate to the correct tab
        setTimeout(() => window.location.href = window.location.pathname + `?current_tab=${targetTab}`, 300);
    };
    
    // Scroll to credentials section
    credentialsSection.scrollIntoView({ behavior: 'smooth' });
}

// Update your setupConfirmationModals function to use the unified modal
function setupConfirmationModals() {
    const createTrainerModal = document.getElementById('createTrainerModal');
    const createTraineeModal = document.getElementById('createTraineeModal');

    if (createTrainerModal) {
        const submitBtn = createTrainerModal.querySelector('.submit-btn');
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.getElementById('createTrainerForm');
            // Basic validation
            const firstName = form.querySelector('#trainer_first_name').value.trim();
            const lastName = form.querySelector('#trainer_last_name').value.trim();
            const contactNumberInput = form.querySelector('#trainer_number');
            const contactNumber = contactNumberInput.value.trim();

            if (!form.checkValidity()) {
                alert('Please fill in all required fields correctly. Phone number must be in 09XXXXXXXXX or +639XXXXXXXXX format.');
                return;
            }

            // The new implementation uses client-side filtering, so the AJAX check is removed.
            // We will rely on the server-side check in create_trainer.php for final validation.
            showAccountConfirmation('trainer', form);
        });
    }

    if (createTraineeModal) {
        const submitBtn = createTraineeModal.querySelector('.submit-btn');
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.getElementById('createTraineeForm');
            if (!form.checkValidity()) {
                alert('Please fill in all required fields correctly. Phone number must be in 09XXXXXXXXX or +639XXXXXXXXX format.');
                return;
            }
            // The new implementation uses client-side filtering, so the AJAX check is removed.
            // We will rely on the server-side check in create_trainee.php for final validation.
            showAccountConfirmation('trainee', form);
        });
    }
}

function setupCourseEditing() {
    console.log('Setting up course editing...');
    
    // Edit course button functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-course-btn')) {
            const courseDataStr = e.target.getAttribute('data-course-data');
            
            if (courseDataStr) {
                try {
                    const courseData = JSON.parse(courseDataStr);
                    openEditCourseModal(courseData);
                } catch (error) {
                    console.error('Error parsing course data:', error);
                    alert('Error loading course data. Please try again. ' + error);
                }
            }
        }
    });

    // Handle edit course form submission
    const editCourseForm = document.getElementById('editCourseForm');
    if (editCourseForm) {
        editCourseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitEditCourseForm(this);
        });
    }
}

function openEditCourseModal(courseData) {
    console.log('Opening edit course modal for:', courseData);
    
    // Populate basic course information
    document.getElementById('edit_course_code').value = courseData.course_code;
    document.getElementById('edit_course_code_display').value = courseData.course_code;
    document.getElementById('edit_course_name').value = courseData.course_name || '';
    document.getElementById('edit_course_hours').value = courseData.hours || '';
    document.getElementById('edit_course_description').value = courseData.description || '';
    
    // Show current image preview
    const imagePreview = document.getElementById('current_image_preview');
    if (courseData.image) {
        imagePreview.innerHTML = `
            <p><strong>Current Image:</strong></p>
            <img src="../uploads/courses/${courseData.image}" 
                 style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; padding: 5px;">
        `;
    } else {
        imagePreview.innerHTML = '<p><em>No current image</em></p>';
    }
    
    // Populate competencies
    populateCompetencies(courseData.competency_types || []);
    
    openModal('editCourseModal');
}

function populateCompetencies(competencies) {
    const container = document.getElementById('edit_competencies_container');
    container.innerHTML = '';
    
    if (competencies.length === 0) {
        container.innerHTML = '<p><em>No competencies defined</em></p>';
        return;
    }
    
    competencies.forEach((comp, index) => {
        const compHtml = `
            <div class="competency-edit-group" data-index="${index}">
                <div class="competency-header">
                    <h4>${comp.type ? comp.type.charAt(0).toUpperCase() + comp.type.slice(1) : 'Basic'} Competency</h4>
                    <button type="button" class="remove-competency-btn" data-index="${index}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Type:</label>
                        <select name="competencies[${index}][type]" required>
                            <option value="basic" ${comp.type === 'basic' ? 'selected' : ''}>Basic</option>
                            <option value="common" ${comp.type === 'common' ? 'selected' : ''}>Common</option>
                            <option value="core" ${comp.type === 'core' ? 'selected' : ''}>Core</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" name="competencies[${index}][name]" 
                               value="${comp.name || ''}" required 
                               placeholder="Enter competency name">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="competencies[${index}][description]" 
                              rows="2" placeholder="Enter description (optional)">${comp.description || ''}</textarea>
                </div>
                <hr>
            </div>
        `;
        container.innerHTML += compHtml;
    });
    
    // Add event listeners for remove buttons
    container.querySelectorAll('.remove-competency-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const index = this.getAttribute('data-index');
            removeCompetency(index);
        });
    });
}

function removeCompetency(index) {
    if (confirm('Are you sure you want to remove this competency?')) {
        const competencyGroup = document.querySelector(`.competency-edit-group[data-index="${index}"]`);
        if (competencyGroup) {
            competencyGroup.remove();
            // Reindex remaining competencies
            reindexCompetencies();
        }
    }
}

function reindexCompetencies() {
    const competencyGroups = document.querySelectorAll('.competency-edit-group');
    let newIndex = 0;
    
    competencyGroups.forEach(group => {
        const oldIndex = group.getAttribute('data-index');
        group.setAttribute('data-index', newIndex);
        
        // Update all inputs within this group
        group.querySelectorAll('[name]').forEach(input => {
            const oldName = input.getAttribute('name');
            const newName = oldName.replace(/competencies\[\d+\]/, `competencies[${newIndex}]`);
            input.setAttribute('name', newName);
        });
        
        // Update remove button
        const removeBtn = group.querySelector('.remove-competency-btn');
        if (removeBtn) {
            removeBtn.setAttribute('data-index', newIndex);
        }
        
        newIndex++;
    });
    
    // If no competencies left, show message
    const container = document.getElementById('edit_competencies_container');
    if (newIndex === 0) {
        container.innerHTML = '<p><em>No competencies defined</em></p>';
    }
}

function submitEditCourseForm(form) {
    const formData = new FormData(form);
    const submitBtn = form.querySelector('.submit-btn');
    const originalText = submitBtn.textContent;
    
    submitBtn.textContent = 'Updating...';
    submitBtn.disabled = true;
    
    fetch('../php/update_course.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Course updated successfully!');
            closeModal('editCourseModal');
            // Reload the page and navigate to the courses tab
            setTimeout(() => {
                window.location.href = window.location.pathname + '?current_tab=courses';
            }, 300);
        } else {
            alert('Error updating course: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating course. Please try again.');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

function generateTempPassword(length = 10) {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    let password = '';
    for (let i = 0; i < length; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return password;
}

function showTraineeConfirmation(form) {
    const formData = new FormData(form);
    
    const firstName = formData.get('trainee_first_name');
    const middleName = formData.get('trainee_middle_name') || '';
    const lastName = formData.get('trainee_last_name');
    const suffix = formData.get('trainee_suffix') || '';
    const contactNumber = formData.get('trainee_number');
    const courseCode = formData.get('trainee_course');
    const batchName = formData.get('trainee_batch');
    
    // Generate expected user ID and email (for display only - actual generation happens on server)
    const datePart = new Date().toLocaleDateString('en-US', { month: '2-digit', day: '2-digit' }).replace(/\//g, '');
    const randomPart = Math.random().toString().substr(2, 3);
    const expectedUserId = '3' + datePart + randomPart;
    
    const cleanLastName = lastName.replace(/[^a-zA-Z]/g, '');
    const cleanFirstName = firstName.replace(/[^a-zA-Z]/g, '');
    const expectedEmail = (cleanLastName + '.' + cleanFirstName).toLowerCase() + '@bts.gov.ph';
    
    const content = document.getElementById('traineeConfirmationContent');
    content.innerHTML = `
        <div class="confirmation-details">
            <h3>Please review the trainee details:</h3>
            <div class="confirmation-item">
                <strong>Name:</strong> ${firstName} ${middleName} ${lastName} ${suffix}
            </div>
            <div class="confirmation-item">
                <strong>Contact Number:</strong> ${contactNumber}
            </div>
            <div class="confirmation-item">
                <strong>Course:</strong> ${courseCode || 'Not assigned'}
            </div>
            <div class="confirmation-item">
                <strong>Batch:</strong> ${batchName || 'Not assigned'}
            </div>
            <div class="confirmation-item">
                <strong>Expected User ID:</strong> ${expectedUserId}
            </div>
            <div class="confirmation-item">
                <strong>Expected Email:</strong> ${expectedEmail}
            </div>
            <div class="confirmation-note">
                <p><em>A temporary password will be generated and shown after creation.</em></p>
            </div>
        </div>
    `;
    
    openModal('traineeConfirmationModal');
}

function setupFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'red';
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
}

function showTrainerConfirmation(form) {
    const formData = new FormData(form);
    const firstName = formData.get('trainer_first_name') || '';
    const middleName = formData.get('trainer_middle_name') || '';
    const lastName = formData.get('trainer_last_name') || '';
    const suffix = formData.get('trainer_suffix') || '';
    const contactNumber = formData.get('trainer_number') || '';
    const courseSelect = form.querySelector('#trainer_courses');
    const selectedCourses = Array.from(courseSelect.selectedOptions).map(opt => opt.textContent.split(' (')[0]);
    
    let confirmationHTML = `
        <div class="confirmation-details">
            <h3>Please review the trainer details:</h3>
            <div class="confirmation-item">
                <strong>Name:</strong> ${firstName} ${middleName} ${lastName} ${suffix}
            </div>
            <div class="confirmation-item">
                <strong>Contact Number:</strong> ${contactNumber}
            </div>
    `;
    
    if (selectedCourses.length > 0) {
        confirmationHTML += `
            <div class="confirmation-item">
                <strong>Assigned Courses:</strong> ${selectedCourses.join(', ')}
            </div>`;
    } else {
        confirmationHTML += `
            <div class="confirmation-item">
                <strong>Assigned Courses:</strong> None
            </div>`;
    }
    
    confirmationHTML += `
        <div class="confirmation-note">
            <p><em>A temporary password will be generated and shown after creation.</em></p>
        </div>
    </div>`;
    
    document.getElementById('trainerConfirmationContent').innerHTML = confirmationHTML;

    openModal('trainerConfirmationModal');
}

function setupAjaxPagination() {
    // This function is now replaced by client-side filtering.
    // The search inputs will now filter the tables directly.
    const setupClientSideSearch = (inputId, tableSelector) => {
        const searchInput = document.getElementById(inputId);
        const table = document.querySelector(tableSelector);
        if (!searchInput || !table) return;

        const tableRows = table.querySelectorAll('tbody tr');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            tableRows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                if (rowText.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    };

    // Apply client-side search to all relevant tables
    setupClientSideSearch('trainerSearchInput', '#activeTrainers .trainer-table');
    setupClientSideSearch('trainerSearchInput', '#archivedTrainers .trainer-table');
    setupClientSideSearch('traineeSearchInput', '#activeTrainees .trainee-table');
    setupClientSideSearch('traineeSearchInput', '#archivedTrainees .trainee-table');
    setupClientSideSearch('guestSearchInput', '#guests .guest-table');
    setupClientSideSearch('enrollmentSearchInput', '#enrollments .enrollment-table');

    document.addEventListener('modalOpened', function(e) {
        if (e.detail.modalId === 'addCourseModal') {
            initializeCompetencyHandlers();
        }
        if (e.detail.modalId === 'editCourseModal') {
            initializeEditCompetencyHandlers();
        }
    });
}
