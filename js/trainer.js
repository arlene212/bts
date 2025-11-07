document.addEventListener("DOMContentLoaded", () => {
    console.log("DOM loaded - trainer.js executing");
    console.log("Tab links found:", document.querySelectorAll(".tab-link").length);

    // ===== TAB SWITCHING =====
    const tabLinks = document.querySelectorAll(".tab-link");
    const tabContents = document.querySelectorAll(".tab-content");

    tabLinks.forEach(link => {
        link.addEventListener("click", e => {
            e.preventDefault();
            tabLinks.forEach(l => l.classList.remove("active"));
            tabContents.forEach(t => t.classList.remove("active"));
            link.classList.add("active");
            const tab = document.getElementById(link.dataset.tab);
            if (tab) tab.classList.add("active");
        });
    });

    // ===== COURSE DETAIL TOGGLE =====
    const courseDetailToggleBtns = document.querySelectorAll('.course-detail-toggle .switch-btn');
    const courseDetailViews = document.querySelectorAll('#course-detail .detail-view');
    const courseDetailSwitchInner = document.querySelector('.course-detail-toggle .switch-inner');

    courseDetailToggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            courseDetailToggleBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
    
            const target = this.dataset.view;
            courseDetailViews.forEach(view => view.classList.remove('active'));
    
            const viewToShow = document.getElementById(`${target}-view`);
            if (viewToShow) viewToShow.classList.add('active');
            updateSwitchPosition(courseDetailToggleBtns, courseDetailSwitchInner);
        });
    });

    // ===== BATCH → COURSE DETAIL =====
    const batchCards = document.querySelectorAll("#enrolled .batch-card");
    const courseDetail = document.getElementById("course-detail");
    const enrolledTab = document.getElementById("enrolled");
    const courseBackBtn = courseDetail.querySelector(".back-btn");

    batchCards.forEach(batch => {
        batch.addEventListener("click", () => {
            const courseName = batch.dataset.course;
            const courseCode = batch.dataset.code;
            const courseHours = batch.dataset.hours;
            const courseDataStr = batch.dataset.courseData;

            // Load course details with batches
            loadCourseDetails(courseCode, courseName, courseHours, courseDataStr);

            // Show course detail, hide batches
            enrolledTab.classList.add("hidden");
            courseDetail.classList.remove("hidden");
        });
    });

    // Back button from course detail → batch list
    courseBackBtn.addEventListener("click", () => {
        courseDetail.classList.add("hidden");
        enrolledTab.classList.remove("hidden");
    });

    // ===== COURSE DETAIL FUNCTIONALITY =====
    function loadCourseDetails(courseCode, courseName, courseHours, courseDataStr) {
        console.log("Loading details for:", courseCode);
        
        // Set basic course details
        document.getElementById("course-detail-title").textContent = courseName;
        document.getElementById("course-code").textContent = courseCode;
        document.getElementById("course-hours").textContent = courseHours;
        document.getElementById("competencies-list").innerHTML = '<div class="loading">Loading...</div>';
        document.getElementById("submissions-list").innerHTML = '<div class="loading">Loading...</div>';
        document.getElementById("course-description").textContent = "Loading...";
        
        // Fetch course details including batches
        fetch(`../php/get_course_details_trainer.php?course_code=${courseCode}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) { throw new Error(data.error); }
                
                // Set course description
                document.getElementById("course-description").textContent = data.course.description || 'No description available';

                // Render competencies from the passed course data
                if (courseDataStr) {
                    let courseData = JSON.parse(courseDataStr);
                    // Attach the fetched topics to the competencies
                    courseData.competency_types.forEach(comp => {
                        comp.topics = data.topicsByCompetency[comp.name] || [];
                    });
                    renderCompetencies(courseData.competency_types || [], courseCode);
                }
                
                // Render the new submissions view
                renderSubmissionsView(data.topicsByCompetency || {});
            })
            .catch(error => {
                console.error('Error loading course details:', error);
                document.getElementById("course-description").textContent = "Error loading course details";
                document.getElementById("competencies-list").innerHTML = `<div class="error-message">${error.message}</div>`;
                document.getElementById("submissions-list").innerHTML = `<div class="error-message">${error.message}</div>`;
            });
    }

    // ===== COMPETENCIES FUNCTIONALITY =====

    function renderCompetencies(competencies, courseCode) {
        const container = document.getElementById("competencies-list");
        container.innerHTML = '';
        
        if (competencies.length === 0) {
            container.innerHTML = `
                <div class="no-competencies">
                    <p>No competencies defined for this course yet.</p>
                    <p><small>Competencies need to be added by the administrator first.</small></p>
                </div>
            `;
            return;
        }
        
        competencies.forEach(competency => {
            const competencyElement = document.createElement('div');
            competencyElement.className = 'competency-item';
            competencyElement.innerHTML = `
                <div class="competency-header">
                    <div class="competency-title">
                        <h4>
                            <span class="competency-type ${competency.type}">
                                ${competency.type.toUpperCase()}
                            </span>
                            ${competency.name}
                        </h4>
                        <div class="competency-actions">
                            <button class="add-topic-btn" data-competency-id="${competency.id || competency.name}" data-competency-name="${competency.name}">
                                <i class="fas fa-plus"></i> Add Topic
                            </button>
                        </div>
                    </div>
                    ${competency.description ? `<p class="competency-description">${competency.description}</p>` : ''}
                    <div class="competency-meta">
                        <small>Added by: ${competency.added_by_name || 'Administrator'}</small>
                    </div>
                </div>
                <div class="topics-list" id="topics-${competency.id}">
                    ${renderTopics(competency.topics || [], competency.name)}
                </div>
            `;
            container.appendChild(competencyElement);
        });
        
        // Add event listeners for Add Topic buttons
        document.querySelectorAll('.add-topic-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const competencyId = this.dataset.competencyId;
                const competencyName = this.dataset.competencyName;
                const courseCode = document.getElementById("course-code").textContent;
                const courseName = document.getElementById("course-detail-title").textContent;
                openAddTopicModal(courseCode, courseName, competencyId, competencyName);
            });
        });
    }

    function renderTopics(topics, competencyId) {
        if (topics.length === 0) {
            return `
                <div class="no-topics">
                    <p>No topics added yet.</p>
                    <button class="add-topic-btn-inline" data-competency-id="${competencyId}" data-competency-name="${competencyId}">
                        <i class="fas fa-plus"></i> Add First Topic
                    </button>
                </div>
            `;
        }
        
        return topics.map(topic => `
            <div class="topic-item" data-topic-id="${topic.id}">
                <div class="topic-header">
                    <div class="topic-info">
                        <h5>${topic.topic_name}</h5>
                        ${topic.topic_description ? `<p class="topic-description">${topic.topic_description}</p>` : ''}
                        ${topic.learning_objectives ? `<p class="learning-objectives"><strong>Learning Objectives:</strong> ${topic.learning_objectives}</p>` : ''}
                        <div class="topic-meta">
                            <small>Added: ${new Date(topic.created_at).toLocaleDateString()}</small>
                        </div>
                    </div>
                    <div class="topic-actions">
                        <button class="add-material-btn" data-topic-id="${topic.id}" title="Add Material">
                            <i class="fas fa-plus-circle"></i> Add Material
                        </button>
                        <button class="add-activity-btn" data-topic-id="${topic.id}" title="Add Activity">
                            <i class="fas fa-tasks"></i> Add Activity
                        </button>
                        <button class="edit-topic-btn" data-topic-id="${topic.id}" title="Edit Topic">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="delete-topic-btn" data-topic-id="${topic.id}" title="Delete Topic">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        <button class="toggle-materials-btn" title="Show/Hide Materials">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
                <div class="materials-container hidden">
                    ${renderMaterials(topic.materials || [])}
                    <h6 class="content-divider">Activities</h6>
                    ${renderActivities(topic.activities || [])}
                </div>
            </div>
        `).join('');
    }

    function renderMaterials(materials) {
        if (materials.length === 0) {
            return '<p class="no-materials">No materials added to this topic yet.</p>';
        }

        return `
            <div class="materials-list">
                ${materials.map(material => `
                    <div class="material-item">
                        <div class="material-icon">
                            <i class="${getIconForMaterial(material.file_path)}"></i>
                        </div>
                        <div class="material-details">
                            <div class="material-info">
                                <strong>${material.material_title}</strong>
                                <p>${material.material_description || ''}</p>
                                <small>
                                    <a href="${material.material_file_path.startsWith('http') ? material.material_file_path : '../uploads/courses/' + material.material_file_path}" target="_blank">
                                        View Material
                                    </a>
                                </small>
                            </div>
                            <div class="material-actions">
                                <button class="icon-btn edit-material-btn" title="Edit Material" data-material-id="${material.id}"><i class="fas fa-edit"></i></button>
                                <button class="icon-btn delete-material-btn" title="Delete Material" data-material-id="${material.id}" data-material-title="${material.material_title}"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function renderActivities(activities) {
        if (activities.length === 0) {
            return '<p class="no-materials">No activities added to this topic yet.</p>';
        }
        return activities.map(activity => `
            <div class="activity-item">
                <div class="activity-header">
                    <div class="activity-info-main">
                        <div class="activity-info">
                            <strong>${activity.activity_title}</strong>
                            <span class="activity-type ${activity.activity_type}">${activity.activity_type}</span>
                        </div>
                        <div class="activity-actions">
                            <button class="icon-btn edit-activity-btn" title="Edit Activity" data-activity-id="${activity.id}"><i class="fas fa-edit"></i></button>
                            <button class="icon-btn delete-activity-btn" title="Delete Activity" data-activity-id="${activity.id}" data-activity-title="${activity.activity_title}"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="activity-meta">
                        <small>Due: ${new Date(activity.due_date).toLocaleString()}</small>
                        <small>Max Score: ${activity.max_score}</small>
                        ${activity.attachment_path ? `
                            <small>
                                <a href="${activity.attachment_path.startsWith('http') ? activity.attachment_path : `../uploads/activities/${activity.attachment_path}`}" target="_blank" class="attachment-link">
                                    <i class="fas fa-paperclip"></i> View Attachment
                                </a>
                            </small>
                        ` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    }

    function renderSubmissions(submissions, maxScore, activityId) {
        if (submissions.length === 0) {
            return '<p class="no-submissions">No submissions yet.</p>';
        }
        return `
            <table class="submissions-table">
                <thead>
                    <tr>
                        <th>Trainee</th>
                        <th>Submitted At</th>
                        <th>Submission</th>
                        <th>Score</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${submissions.map(sub => `
                        <tr data-submission-id="${sub.submission_id}">
                            <td>${sub.trainee_first_name} ${sub.trainee_last_name}</td>
                            <td>${new Date(sub.submitted_at).toLocaleString()}</td>
                            <td>
                                <a href="../uploads/submissions/${sub.submission_file_path}" target="_blank">View File</a>
                            </td>
                            <td class="score-cell">
                                ${sub.score !== null ? `${sub.score} / ${maxScore}` : 'Not Graded'}
                            </td>
                            <td>
                                <button class="grade-btn" 
                                        data-submission-id="${sub.submission_id}" 
                                        data-current-score="${sub.score || ''}"
                                        data-max-score="${maxScore}"
                                        data-feedback="${sub.feedback || ''}">
                                    ${sub.score !== null ? 'Edit Grade' : 'Grade'}
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }

    // Event delegation for grading and other dynamic buttons
    document.getElementById('competencies-list').addEventListener('click', function(e) {
        if (e.target.closest('.grade-btn')) {
            const btn = e.target.closest('.grade-btn');
            const submissionId = btn.dataset.submissionId;
            const currentScore = btn.dataset.currentScore;
            const maxScore = btn.dataset.maxScore;
            const feedback = btn.dataset.feedback;

            openGradeModal(submissionId, currentScore, maxScore, feedback);
        }

        // Handle Edit/Delete Material
        if (e.target.closest('.edit-material-btn')) {
            const materialId = e.target.closest('.edit-material-btn').dataset.materialId;
            openEditMaterialModal(materialId);
        }
        if (e.target.closest('.delete-material-btn')) {
            const btn = e.target.closest('.delete-material-btn');
            openConfirmationModal('material', btn.dataset.materialId, btn.dataset.materialTitle);
        }

        // Handle Edit/Delete Activity
        if (e.target.closest('.edit-activity-btn')) {
            openEditActivityModal(e.target.closest('.edit-activity-btn').dataset.activityId);
        }
        if (e.target.closest('.delete-activity-btn')) {
            const btn = e.target.closest('.delete-activity-btn');
            openConfirmationModal('activity', btn.dataset.activityId, btn.dataset.activityTitle);
        }

        const addTopicBtn = e.target.closest('.add-topic-btn, .add-topic-btn-inline');
        if (addTopicBtn) {
            const competencyId = addTopicBtn.dataset.competencyId;
            const competencyName = addTopicBtn.dataset.competencyName;
            const courseCode = document.getElementById("course-code").textContent;
            const courseName = document.getElementById("course-detail-title").textContent;
            openAddTopicModal(courseCode, courseName, competencyId, competencyName);
        }

        const addMaterialBtn = e.target.closest('.add-material-btn');
        if (addMaterialBtn) {
            const topicId = addMaterialBtn.dataset.topicId;
            openAddMaterialModal(topicId);
        }

        const addActivityBtn = e.target.closest('.add-activity-btn');
        if (addActivityBtn) {
            const topicId = addActivityBtn.dataset.topicId;
            openAddActivityModal(topicId);
        }

        const toggleBtn = e.target.closest('.toggle-materials-btn');
        if (toggleBtn) {
            const container = toggleBtn.closest('.topic-item').querySelector('.materials-container');
            container.classList.toggle('hidden');
            toggleBtn.querySelector('i').classList.toggle('fa-chevron-down');
            toggleBtn.querySelector('i').classList.toggle('fa-chevron-up');
        }
    });

    // Event delegation specifically for the Submissions tab
    document.getElementById('submissions-list').addEventListener('click', function(e) {
        if (e.target.closest('.grade-btn')) {
            const btn = e.target.closest('.grade-btn');
            const submissionId = btn.dataset.submissionId;
            const currentScore = btn.dataset.currentScore;
            const maxScore = btn.dataset.maxScore;
            const feedback = btn.dataset.feedback;

            // Open the existing grading modal
            openGradeModal(submissionId, currentScore, maxScore, feedback);
        }
    });

    function getIconForMaterial(filePath) {
        if (!filePath) return 'fas fa-file';
        if (String(filePath).startsWith('http')) return 'fas fa-link';
        
        const extension = filePath.split('.').pop().toLowerCase();
        switch (extension) {
            case 'pdf': return 'fas fa-file-pdf';
            case 'doc':
            case 'docx': return 'fas fa-file-word';
            case 'xls':
            case 'xlsx': return 'fas fa-file-excel';
            case 'ppt':
            case 'pptx': return 'fas fa-file-powerpoint';
            case 'zip':
            case 'rar': return 'fas fa-file-archive';
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif': return 'fas fa-file-image';
            case 'mp4':
            case 'mov':
            case 'avi': return 'fas fa-file-video';
            default: return 'fas fa-file';
        }
    }

    // ===== SUBMISSIONS VIEW =====
    function renderSubmissionsView(topicsByCompetency) {
        const container = document.getElementById("submissions-list");
        let allActivities = [];
        Object.values(topicsByCompetency).forEach(competency => {
            competency.forEach(topic => {
                if (topic.activities) {
                    allActivities.push(...topic.activities);
                }
            });
        });

        if (allActivities.length === 0) {
            container.innerHTML = '<p class="no-data">No activities have been created for this course yet.</p>';
            return;
        }

        let html = allActivities.map(activity => `
            <div class="activity-submission-item">
                <div class="activity-header">
                    <strong>${activity.activity_title}</strong> (${activity.submissions.length} submissions)
                </div>
                ${renderSubmissions(activity.submissions, activity.max_score, activity.id)}
            </div>
        `).join('');
        container.innerHTML = html;
    }

    // ===== ADD MATERIAL MODAL =====
    function openAddMaterialModal(topicId) {
        const modal = document.getElementById("addMaterialModal");
        document.getElementById("material_topic_id").value = topicId;
        modal.classList.remove("hidden");
    }

    document.getElementById("closeMaterialModal").addEventListener("click", () => {
        document.getElementById("addMaterialModal").classList.add("hidden");
    });

    document.getElementById("cancelMaterial").addEventListener("click", () => {
        document.getElementById("addMaterialModal").classList.add("hidden");
    });

    // Handle material type change
    const materialTypeSelect = document.getElementById('material_type');
    const fileInputGroup = document.getElementById('file_input_group');
    const linkInputGroup = document.getElementById('link_input_group');
    materialTypeSelect.addEventListener('change', function() {
        if (this.value === 'file') {
            fileInputGroup.classList.remove('hidden');
            linkInputGroup.classList.add('hidden');
        } else {
            fileInputGroup.classList.add('hidden');
            linkInputGroup.classList.remove('hidden');
        }
    });

    // Handle Add Material Form Submission
    document.getElementById("addMaterialForm").addEventListener("submit", function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.textContent = 'Uploading...';
        submitBtn.disabled = true;

        fetch('../php/add_materials.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Material added successfully!');
                document.getElementById("addMaterialModal").classList.add("hidden");
                this.reset();
                // Reload and stay on the courses tab
                window.location.href = window.location.pathname + '?current_tab=mycourses';
            } else {
                alert('Error adding material: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please check the console and try again.');
        })
        .finally(() => {
            submitBtn.textContent = 'Add Material';
            submitBtn.disabled = false;
        });
    });

    // ===== ADD ACTIVITY MODAL =====
    function openAddActivityModal(topicId) {
        const modal = document.getElementById("addActivityModal");
        document.getElementById("activity_topic_id").value = topicId;
        modal.classList.remove("hidden");
    }
    

    document.getElementById("closeActivityModal").addEventListener("click", () => {
        document.getElementById("addActivityModal").classList.add("hidden");
    });

    document.getElementById("cancelActivity").addEventListener("click", () => {
        document.getElementById("addActivityModal").classList.add("hidden");
    });

    // ===== ADD ACTIVITY DATE PICKER LOGIC =====
    const startDateInput = document.getElementById('start_date_date');
    const startTimeInput = document.getElementById('start_date_time');
    const dueDateInput = document.getElementById('due_date_date');
    const dueTimeInput = document.getElementById('due_date_time');
    const hiddenStartDateInput = document.getElementById('start_date');
    const hiddenDueDateInput = document.getElementById('due_date');

    if (startDateInput && startTimeInput && dueDateInput && dueTimeInput) {
        const now = new Date();
        // Set seconds and milliseconds to 0 for cleaner time values
        now.setSeconds(0);
        now.setMilliseconds(0);

        const localDate = now.toISOString().split('T')[0]; // YYYY-MM-DD format
        const localTime = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });

        // Set minimums to prevent picking past dates/times
        startDateInput.min = localDate;
        dueDateInput.min = localDate;

        // Set default start time to now
        startDateInput.value = localDate;
        startTimeInput.value = localTime;

        // Set default due date to one hour from now
        const oneHourLater = new Date(now.getTime() + 60 * 60 * 1000);
        dueDateInput.value = oneHourLater.toISOString().split('T')[0];
        dueTimeInput.value = oneHourLater.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });

        // Function to combine date and time for the hidden inputs
        const combineDateTime = () => {
            // Helper to convert local date/time to a UTC string in 'YYYY-MM-DD HH:MM:SS' format
            const toUTCString = (dateStr, timeStr) => {
                if (!dateStr || !timeStr) return '';                
                // Create a date object by explicitly parsing the local date and time parts.
                // This is more reliable across browsers than new Date('YYYY-MM-DDTHH:MM').
                const [year, month, day] = dateStr.split('-').map(Number);
                const [hours, minutes] = timeStr.split(':').map(Number);
                const localDateTime = new Date(year, month - 1, day, hours, minutes);

                // Convert the local date to a UTC string and format it for the database.
                return localDateTime.toISOString().slice(0, 19).replace('T', ' ');
            };

            if (startDateInput.value && startTimeInput.value) {
                hiddenStartDateInput.value = toUTCString(startDateInput.value, startTimeInput.value);
            }
            if (dueDateInput.value && dueTimeInput.value) {
                hiddenDueDateInput.value = toUTCString(dueDateInput.value, dueTimeInput.value);
            }
        };

        // Update hidden fields whenever a date or time input changes
        [startDateInput, startTimeInput, dueDateInput, dueTimeInput].forEach(el => {
            el.addEventListener('change', combineDateTime);
        });

        // Also update when the start date changes to ensure due date is valid
        startDateInput.addEventListener('change', () => {
            dueDateInput.min = startDateInput.value;
            // If due date is now before start date, set it to the start date
            if (dueDateInput.value < startDateInput.value) {
                dueDateInput.value = startDateInput.value;
            }
            // If the dates are the same, ensure the due time is not before the start time
            if (dueDateInput.value === startDateInput.value && dueTimeInput.value < startTimeInput.value) {
                dueTimeInput.value = startTimeInput.value;
            }
        });

        // Initial combination on load
        combineDateTime();
    }


    // Handle activity attachment type change
    const activityAttachmentTypeSelect = document.getElementById('activity_attachment_type');
    const activityFileInputGroup = document.getElementById('activity_file_input_group');
    const activityLinkInputGroup = document.getElementById('activity_link_input_group');
    const activityFileInput = document.getElementById('activity_file');
    const activityLinkInput = document.getElementById('activity_link');

    if (activityAttachmentTypeSelect) {
        activityAttachmentTypeSelect.addEventListener('change', function() {
            const selectedType = this.value;
            activityFileInputGroup.classList.toggle('hidden', selectedType !== 'file');
            activityLinkInputGroup.classList.toggle('hidden', selectedType !== 'link');

            // Update required status
            if (activityFileInput) activityFileInput.required = (selectedType === 'file');
            if (activityLinkInput) activityLinkInput.required = (selectedType === 'link');
        });
    }


    document.getElementById("addActivityForm").addEventListener("submit", function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.textContent = 'Adding...';
        submitBtn.disabled = true;

        fetch('../php/add_activity.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Activity added successfully!');
                document.getElementById("addActivityModal").classList.add("hidden");
                this.reset();
                // Reload and stay on the courses tab
                window.location.href = window.location.pathname + '?current_tab=mycourses';
            } else {
                alert('Error adding activity: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding the activity.');
        })
        .finally(() => {
            submitBtn.textContent = 'Add Activity';
            submitBtn.disabled = false;
        });
    });

    // ===== GRADING MODAL LOGIC =====
    const gradeModal = document.getElementById('gradeSubmissionModal');
    const gradeForm = document.getElementById('gradeSubmissionForm');

    function openGradeModal(submissionId, currentScore, maxScore, feedback) {
        document.getElementById('grade_submission_id').value = submissionId;
        document.getElementById('submission_score').value = currentScore;
        document.getElementById('submission_score').max = maxScore;
        document.getElementById('submission_max_score').textContent = maxScore;
        document.getElementById('submission_feedback').value = feedback;
        gradeModal.classList.remove('hidden');
    }

    document.getElementById('closeGradeModal').addEventListener('click', () => gradeModal.classList.add('hidden'));
    document.getElementById('cancelGrade').addEventListener('click', () => gradeModal.classList.add('hidden'));

    gradeForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submissionId = formData.get('submission_id');
        const score = formData.get('score');
        const maxScore = document.getElementById('submission_max_score').textContent;

        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        fetch('../php/grade_submission.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Grade saved successfully!');
                gradeModal.classList.add('hidden');

                // --- UPDATE UI IN REAL-TIME ---
                const submissionRow = document.querySelector(`tr[data-submission-id="${submissionId}"]`);
                if (submissionRow) {
                    const scoreCell = submissionRow.querySelector('.score-cell');
                    scoreCell.textContent = `${score} / ${maxScore}`;

                    const gradeBtn = submissionRow.querySelector('.grade-btn');
                    gradeBtn.textContent = 'Edit Grade';
                    gradeBtn.dataset.currentScore = score;
                    gradeBtn.dataset.feedback = formData.get('feedback');
                }
            } else {
                alert('Error saving grade: ' . data.message);
            }
        })
        .catch(error => console.error('Error:', error))
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Grade';
        });
    });

    // ===== ADD TOPIC MODAL =====
    function openAddTopicModal(courseCode, courseName, competencyId, competencyName) {
        const modal = document.getElementById("addTopicModal");
        document.getElementById("topic_competency_name").textContent = competencyName;
    }

    // ===== ADD TOPIC MODAL =====
    function openAddTopicModal(courseCode, courseName, competencyId, competencyName) {
        const modal = document.getElementById("addTopicModal");
        document.getElementById("topic_competency_name").textContent = competencyName;
        document.getElementById("topic_course_name").textContent = courseName;
        document.getElementById("topic_course_code").value = courseCode;
        document.getElementById("topic_competency_id").value = competencyId;
        
        modal.classList.remove("hidden");
    }

    // Close Add Topic Modal
    document.getElementById("closeTopicModal").addEventListener("click", () => {
        document.getElementById("addTopicModal").classList.add("hidden");
    });

    document.getElementById("cancelTopic").addEventListener("click", () => {
        document.getElementById("addTopicModal").classList.add("hidden");
    });

    // Add Topic Form Submission
    document.getElementById("addTopicForm").addEventListener("submit", function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const courseCode = document.getElementById("topic_course_code").value;
        
        fetch('../php/add_topic.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Topic added successfully!');
                document.getElementById("addTopicModal").classList.add("hidden");
                this.reset();
                // Reload the page to show the new topic.
                location.reload();
            } else {
                alert('Error adding topic: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding topic. Please try again.');
        });
    });

    // ===== BATCH MANAGEMENT FUNCTIONS =====
    function viewBatchTrainees(batchName, courseCode, courseName) {
        alert(`View trainees for ${batchName} in ${courseName}`);
        // You can implement this functionality to show trainees in a modal or new section
    }

    function manageBatch(batchName, courseCode) {
        alert(`Manage batch ${batchName} for course ${courseCode}`);
        // You can implement this functionality to manage batch settings
    }

    function showErrorInBatches(message) {
        const container = document.getElementById("batches-list");
        container.innerHTML = `
            <div class="error-message">
                <p>${message}</p>
            </div>
        `;
    }

    // ===== DASHBOARD CARD CLICKS =====
    document.querySelectorAll('.clickable-card').forEach(card => {
        card.addEventListener('click', () => {
            const target = card.dataset.target;
            const tabLink = document.querySelector(`.tab-link[data-tab="${target}"]`);
            if (tabLink) tabLink.click();
        });
    });

    // ===== NOTIFICATION BELL =====
    const notifIcon = document.getElementById("notifIcon");
    const notifDropdown = document.getElementById("notifDropdown");

    if (notifIcon && notifDropdown) {
        notifIcon.addEventListener("click", e => {
            e.stopPropagation();
            notifDropdown.classList.toggle("hidden");
        });

        document.addEventListener("click", () => {
            notifDropdown.classList.add("hidden");
        });
    }

    // ===== HAMBURGER MENU =====
    const hamburger = document.getElementById("hamburger");
    const sidebar = document.querySelector(".sidebar");

    if (hamburger && sidebar) {
        hamburger.addEventListener("click", () => {
            sidebar.classList.toggle("active");
        });
    }

    // ===== PROFILE MODAL =====
    const profileModal = document.getElementById("profileModal");
    const editProfileBtn = document.getElementById("editProfileBtn");
    const closeProfileModal = document.getElementById("closeProfileModal");
    const cancelProfileChanges = document.getElementById("cancelProfileChanges");
    const profileForm = document.getElementById("profileForm");
    const profileUpload = document.getElementById("profileUpload");
    const changeProfileBtn = document.getElementById("changeProfileBtn");
    const profilePreview = document.getElementById("profilePreview");

    // Open Profile Modal
    editProfileBtn.addEventListener("click", () => {
        profileModal.classList.remove("hidden");
    });

    // Close Profile Modal
    closeProfileModal.addEventListener("click", () => {
        profileModal.classList.add("hidden");
    });

    cancelProfileChanges.addEventListener("click", () => {
        profileModal.classList.add("hidden");
    });

    // Profile Picture Upload
    changeProfileBtn.addEventListener("click", () => {
        profileUpload.click();
    });

    profileUpload.addEventListener("change", function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                profilePreview.src = e.target.result;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    // ===== DELETE ACCOUNT MODAL =====
    const deleteAccountModal = document.getElementById("deleteAccountModal");
    const openDeleteModal = document.getElementById("openDeleteModal");
    const closeDeleteModal = document.getElementById("closeDeleteModal");
    const cancelDeleteBtn = document.getElementById("cancelDeleteBtn");

    openDeleteModal.addEventListener("click", () => {
        profileModal.classList.add("hidden");
        deleteAccountModal.classList.remove("hidden");
    });

    closeDeleteModal.addEventListener("click", () => {
        deleteAccountModal.classList.add("hidden");
    });

    cancelDeleteBtn.addEventListener("click", () => {
        deleteAccountModal.classList.add("hidden");
    });

    // ===== GUEST SEARCH FUNCTIONALITY =====
    const guestSearch = document.getElementById("guestSearch");
    const guestsBody = document.getElementById("guestsBody");
    const totalGuests = document.getElementById("totalGuests");

    if (guestSearch && guestsBody) {
        guestSearch.addEventListener("input", function() {
            const searchTerm = this.value.toLowerCase();
            const rows = guestsBody.getElementsByTagName("tr");
            let visibleCount = 0;

            for (let row of rows) {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = "";
                    visibleCount++;
                } else {
                    row.style.display = "none";
                }
            }

            totalGuests.textContent = visibleCount;
        });
    }

    // ===== EMAIL AUTO-UPDATE =====
    const firstNameInput = document.getElementById("firstName");
    const lastNameInput = document.getElementById("lastName");
    const emailInput = document.getElementById("email");

    function updateEmailPreview() {
        const firstName = firstNameInput.value.trim().toLowerCase();
        const lastName = lastNameInput.value.trim().toLowerCase();
        
        if (firstName && lastName) {
            const newEmail = `${firstName}.${lastName}@benguettechnicalschool.edu.ph`;
            emailInput.value = newEmail;
        }
    }

    firstNameInput.addEventListener("input", updateEmailPreview);
    lastNameInput.addEventListener("input", updateEmailPreview);
});