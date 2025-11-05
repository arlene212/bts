document.addEventListener("DOMContentLoaded", () => {
  /* ========= SIDEBAR & NAV ========= */
  const hamburger = document.getElementById("hamburger");
  const sidebar = document.querySelector(".sidebar");
  const navLinks = document.querySelectorAll(".sidebar .nav a");
  const tabContents = document.querySelectorAll(".tab-content");

  // Sidebar toggle
  hamburger?.addEventListener("click", (e) => {
    e.stopPropagation();
    sidebar.classList.toggle("open");
  });

  // Sidebar tab switching
  navLinks.forEach(link => {
    link.addEventListener("click", (e) => {
      e.preventDefault();
      navLinks.forEach(l => l.classList.remove("active"));
      link.classList.add("active");

      const target = link.getAttribute("data-tab");
      tabContents.forEach(tab => {
        tab.classList.toggle("active", tab.id === target);
      });

      sidebar.classList.remove("open");

      // Reset My Courses view
      if (target === "mycourses") {
        showEnrolledTab();
        courseDetail.classList.add("hidden");
        courseDetail.classList.remove("active");
        document.querySelectorAll("#mycourses .course-box").forEach(box => box.classList.remove("hidden"));
        myCourseSwitchButtons.forEach(b => b.classList.remove("active"));
        myCourseSwitchButtons[0].classList.add("active");
        myCourseSwitchInner.style.transform = "translateX(0%)";
      }
    });
  });

  /* ========= NOTIFICATIONS ========= */
  const notifIcon = document.getElementById("notifIcon");
  const notifDropdown = document.getElementById("notifDropdown");

  notifIcon?.addEventListener("click", (e) => {
    e.stopPropagation();
    notifDropdown.classList.toggle("hidden");
  });

  document.addEventListener("click", (e) => {
    if (sidebar?.classList.contains("open") && !sidebar.contains(e.target) && !hamburger.contains(e.target)) {
      sidebar.classList.remove("open");
    }
    if (notifDropdown && !notifDropdown.contains(e.target) && !notifIcon.contains(e.target)) {
      notifDropdown.classList.add("hidden");
    }
  });

  /* ========= DASHBOARD / NEWS SWITCH ========= */
  const switchButtons = document.querySelectorAll("#home .switch-btn");
  const switchInner = document.querySelector("#home .switch-inner");
  const dashboardTab = document.getElementById("dashboard");
  const newsTab = document.getElementById("news");

  switchButtons.forEach((btn, index) => {
    btn.addEventListener("click", () => {
      switchButtons.forEach(b => b.classList.remove("active"));
      document.querySelectorAll("#home .tab-inner").forEach(tab => tab.classList.remove("active"));
      btn.classList.add("active");
      switchInner.style.transform = `translateX(${index * 100}%)`;

      if (btn.dataset.tab === "dashboard") dashboardTab.classList.add("active");
      else if (btn.dataset.tab === "news") newsTab.classList.add("active");
    });
  });

  /* ========= MY COURSES SWITCH ========= */
  const myCourseSwitchButtons = document.querySelectorAll("#mycourses .switch-btn");
  const myCourseSwitchInner = document.querySelector("#mycourses .switch-inner");
  const enrolledTab = document.getElementById("enrolled");
  const completedTab = document.getElementById("completed");

  function showEnrolledTab() {
    enrolledTab.classList.add("active");
    completedTab.classList.remove("active");
  }

  function showCompletedTab() {
    completedTab.classList.add("active");
    enrolledTab.classList.remove("active");
  }

  showEnrolledTab();

  myCourseSwitchButtons.forEach((btn, index) => {
    btn.addEventListener("click", () => {
      myCourseSwitchButtons.forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      myCourseSwitchInner.style.transform = `translateX(${index * 100}%)`;
      if (btn.dataset.tab === "enrolled") showEnrolledTab();
      else showCompletedTab();
    });
  });

/* ========= COURSE DETAIL VIEW ========= */
const courseDetail = document.getElementById('courseDetail');
const backBtn = courseDetail?.querySelector('.back-btn');
const courseSwitchBtns = courseDetail?.querySelectorAll('.course-switch .switch-btn');
const courseSwitchInner = courseDetail?.querySelector('.course-switch .switch-inner');
const modulesView = document.getElementById('modules-view');
const activitiesView = document.getElementById('activities-view');

// Function to reset course detail view to Modules
function resetCourseDetailView() {
  if (courseSwitchInner && courseSwitchBtns?.length) {
    courseSwitchInner.style.transition = "transform 0.3s ease";
    courseSwitchInner.style.transform = 'translateX(0%)';
    modulesView.classList.add("active");
    activitiesView.classList.remove("active");
    modulesView.style.display = "block";
    activitiesView.style.display = "none";
    courseSwitchBtns[0].classList.add("active");
    courseSwitchBtns[1].classList.remove("active");
  }
}

// Open course detail
document.querySelectorAll('#mycourses .course-card').forEach(card => {
  card.addEventListener("click", () => {
    const courseCode = card.dataset.courseId;
    const courseName = card.dataset.courseName;
    document.querySelectorAll('#mycourses .course-box').forEach(box => box.classList.add('hidden'));
    courseDetail.classList.remove("hidden");
    courseDetail.classList.add("active");

    loadCourseDetails(courseCode, courseName);

    // Always reset to Modules view when opening course detail
    resetCourseDetailView();
  });
});

// Back button - only ONE event listener
backBtn?.addEventListener("click", () => {
  courseDetail.classList.add("hidden");
  courseDetail.classList.remove("active");
  document.querySelectorAll("#mycourses .course-box").forEach(box => box.classList.remove("hidden"));
  
  // Reset to Modules view for next time
  resetCourseDetailView();
});

// Fix: Smooth & reliable switch between Modules/Activities
if (courseSwitchBtns && courseSwitchInner && modulesView && activitiesView) {
  courseSwitchBtns.forEach((btn, index) => {
    btn.addEventListener("click", () => {
      courseSwitchBtns.forEach(b => b.classList.remove("active"));
      btn.classList.add("active");

      courseSwitchInner.style.transition = "transform 0.3s ease";
      courseSwitchInner.style.transform = `translateX(${index * 100}%)`;

      // FIX: Use data-tab instead of data-view
      if (btn.dataset.tab === "modules") {
        modulesView.classList.add("active");
        activitiesView.classList.remove("active");
        modulesView.style.display = "block";
        activitiesView.style.display = "none";
      } else {
        activitiesView.classList.add("active");
        modulesView.classList.remove("active");
        activitiesView.style.display = "block";
        modulesView.style.display = "none";
      }
    });
  });
}

function loadCourseDetails(courseCode, courseName) {
    const competenciesList = document.getElementById('competencies-list');
    const activities = []; // To store activities for later use
    const activitiesContainer = document.getElementById('activities-view');
    competenciesList.innerHTML = '<div>Loading course content...</div>';
    activitiesContainer.innerHTML = '<div>Loading activities...</div>';

    document.getElementById('course-detail-title').textContent = courseName;

    fetch(`../php/get_course_details_trainee.php?course_code=${courseCode}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                competenciesList.innerHTML = `<div class="error-message">${data.error}</div>`;
                return;
            }

            // Populate course meta
            document.getElementById('course-detail-code').textContent = `Code: ${data.course.course_code}`;
            document.getElementById('course-detail-hours').textContent = `Hours: ${data.course.hours} hrs`;
            document.getElementById('course-detail-description').textContent = data.course.description;

            // Render modules
            renderCompetencies(data.competencies, competenciesList, data.activities);
            // Render activities
            renderActivitiesTable(data.activities, activitiesContainer);
        })
        .catch(error => {
            console.error('Error fetching course details:', error);
            competenciesList.innerHTML = `<div class="error-message">Failed to load course content.</div>`;
        });
}

function renderCompetencies(competencies, container, allActivities) {
    if (!competencies || competencies.length === 0) {
        container.innerHTML = '<div class="no-content">No modules have been added to this course yet.</div>';
        return;
    }

    container.innerHTML = competencies.map(comp => `
        <div class="competency-box">
            <div class="competency-header">
                <span class="competency-type ${comp.type}">${comp.type.toUpperCase()}</span>
                <h3>${comp.name}</h3>
            </div>
            ${comp.topics.map(topic => `
                <div class="topic-box">
                    <button class="toggle-btn">
                        <h4>${topic.name}</h4>
                        <span class="arrow"></span>
                    </button>
                    <div class="materials-list hidden">
                        ${topic.materials.map(material => `
                            <a href="${material.file_path.startsWith('http') ? material.file_path : `../uploads/courses/${material.file_path}`}" target="_blank" class="material-item">
                                <i class="fas fa-file-alt"></i>
                                <span>${material.title}</span>
                            </a>
                        `).join('') || '<p class="no-materials">No materials for this topic.</p>'}
                        
                        ${topic.activities.map(activity => `
                            <div class="material-item activity" data-activity-id="${activity.id}">
                                <i class="fas fa-tasks"></i>
                                <span>${activity.title} (Activity)</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `).join('')}
        </div>
    `).join('');

    // Add event listeners for toggles and activities
    container.querySelectorAll('.toggle-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const materials = btn.nextElementSibling;
            materials.classList.toggle('hidden');
            btn.querySelector('.arrow').classList.toggle('rotated');
        });
    });

    container.querySelectorAll('.material-item.activity').forEach(item => {
        item.addEventListener('click', () => {
            const activityId = item.dataset.activityId;
            const activity = allActivities.find(a => a.id == activityId);
            openActivityModal(activity);
        });
    });
}

function renderActivitiesTable(activities, container) {
    if (!activities || activities.length === 0) {
        container.innerHTML = '<div class="no-content">No activities found for this course.</div>';
        return;
    }

    const tableHTML = `
        <table class="activities-table">
            <thead>
                <tr>
                    <th>Activity Name</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Score</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                ${activities.map(act => {
                    let status = 'Not Submitted';
                    let statusClass = 'pending';
                    if (act.submission) {
                        status = act.submission.score !== null ? 'Graded' : 'Submitted';
                        statusClass = act.submission.score !== null ? 'graded' : 'submitted';
                    } else if (new Date(act.due_date) < new Date()) {
                        status = 'Missed';
                        statusClass = 'missed';
                    }

                    return `
                        <tr>
                            <td>${act.title}</td>
                            <td>${new Date(act.due_date).toLocaleString()}</td>
                            <td><span class="status-badge status-${statusClass}">${status}</span></td>
                            <td>${act.submission && act.submission.score !== null ? `${act.submission.score} / ${act.max_score}` : '-'}</td>
                            <td>
                                <button class="view-activity-btn" data-activity-id="${act.id}">
                                    ${act.submission ? 'View' : 'Submit'}
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('')}
            </tbody>
        </table>
    `;
    container.innerHTML = tableHTML;

    container.querySelectorAll('.view-activity-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const activityId = btn.dataset.activityId;
            const activity = activities.find(a => a.id == activityId);
            openActivityModal(activity);
        });
    });
}

  /* ========= ENROLL CONFIRMATION MODAL ========= */
  const enrollBtns = document.querySelectorAll(".enroll-btn");
  const enrollModal = document.getElementById("enrollModal");
  const confirmEnroll = document.getElementById("confirmEnroll");
  const cancelEnroll = document.getElementById("cancelEnroll");
  let courseToEnroll = { code: null, name: null, button: null };

  enrollBtns.forEach(btn => {
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      courseToEnroll.code = btn.dataset.courseCode;
      courseToEnroll.name = btn.dataset.courseName;
      courseToEnroll.button = btn;
      document.getElementById('enrollCourseName').textContent = courseToEnroll.name;
      enrollModal.classList.remove("hidden");
    });
  });

  cancelEnroll?.addEventListener("click", () => {
    enrollModal.classList.add("hidden");
    courseToEnroll = { code: null, name: null, button: null };
  });

  confirmEnroll?.addEventListener("click", () => {
    enrollModal.classList.add("hidden");
    if (courseToEnroll.code && courseToEnroll.button) {
      const btn = courseToEnroll.button;
      btn.disabled = true;
      btn.textContent = 'Requesting...';

      fetch('../php/request_enrollment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `course_code=${encodeURIComponent(courseToEnroll.code)}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('✅ Enrollment request sent successfully!');
          btn.textContent = 'Request Pending';
          btn.classList.add('pending');
        } else {
          alert('⚠️ ' + (data.message || 'An error occurred.'));
          btn.disabled = false;
          btn.textContent = 'Enroll';
        }
      })
      .catch(error => {
        console.error('Enrollment request error:', error);
        alert('⚠️ An error occurred while sending your request.');
        btn.disabled = false;
        btn.textContent = 'Enroll';
      });
    }
    courseToEnroll = { code: null, name: null, button: null };
  });

  /* ========= ENROLLMENT REQUEST TABLE ========= */
  const enrollmentRequests = [
    { course: "Intro to JavaScript", status: "pending...", remarks: "-" },
    { course: "Advanced CSS", status: "accepted", remarks: "-" },
    { course: "Database Management", status: "rejected", remarks: "Prerequisite not met" }
  ];

  const requestsBody = document.getElementById("requests-body");
  enrollmentRequests.forEach(req => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td class="course-name">${req.course}</td>
      <td class="status ${req.status}">${req.status.charAt(0).toUpperCase() + req.status.slice(1)}</td>
      <td>${req.remarks}</td>
      <td><button class="cancel-btn-request">Cancel Request</button></td>
    `;
    row.querySelector(".cancel-btn-request").addEventListener("click", () => {
      alert(`Request for ${req.course} has been canceled.`);
      row.remove();
    });
    requestsBody.appendChild(row);
  });

  /* ========= PROFILE MODAL ========= */
  const editProfileBtn = document.querySelector(".user-card #editProfileBtn, #editProfileBtn");
  const profileModal = document.getElementById("profileModal");
  const closeProfileModal = document.getElementById("closeProfileModal");
  const cancelProfileChanges = document.getElementById("cancelProfileChanges");
  const changeProfileBtn = document.getElementById("changeProfileBtn");
  const profileUpload = document.getElementById("profileUpload");
  const profilePreview = document.getElementById("profilePreview");

  // 🔹 Works even if Edit button is inside user card
  editProfileBtn?.addEventListener("click", (e) => {
    e.preventDefault();
    profileModal?.classList.remove("hidden");
    profileModal?.classList.add("active");
  });

  const closeProfile = () => {
    profileModal?.classList.add("hidden");
    profileModal?.classList.remove("active");
  };

  closeProfileModal?.addEventListener("click", closeProfile);
  cancelProfileChanges?.addEventListener("click", closeProfile);

  changeProfileBtn?.addEventListener("click", () => profileUpload?.click());
  profileUpload?.addEventListener("change", (e) => {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = ev => { profilePreview.src = ev.target.result; };
      reader.readAsDataURL(file);
    }
  });

  /* ========= DELETE ACCOUNT MODAL ========= */
  const deleteModal = document.getElementById("deleteAccountModal");
  const openDeleteModal = document.getElementById("openDeleteModal");
  const closeDeleteModal = document.getElementById("closeDeleteModal");
  const cancelDeleteBtn = document.getElementById("cancelDeleteBtn");
  const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");

  openDeleteModal?.addEventListener("click", () => deleteModal.classList.remove("hidden"));
  closeDeleteModal?.addEventListener("click", () => deleteModal.classList.add("hidden"));
  cancelDeleteBtn?.addEventListener("click", () => deleteModal.classList.add("hidden"));
  confirmDeleteBtn?.addEventListener("click", () => {
    alert("❌ Account deleted permanently.");
    deleteModal.classList.add("hidden");
  });

    /* ========= ACTIVITY MODAL ========= */
  const activityModal = document.getElementById("activityModal");
  const closeActivityModal = document.getElementById("closeActivityModal");
  const activityModalTitle = document.getElementById("activityModalTitle");
  let currentActivityId = null; // To store the ID of the currently open activity
  const uploadSection = document.getElementById("uploadSection");
  const uploadArea = document.getElementById("uploadArea");
  const activityFileInput = document.getElementById("activityFileInput");
  const activityFilePreview = document.getElementById("activityFilePreview");
  const activitySubmitBtn = document.getElementById("activitySubmitBtn");
  const submissionSuccess = document.getElementById("submissionSuccess");
  const studentComment = document.getElementById("studentComment");
  const teacherRemarksSection = document.getElementById("teacherRemarksSection");
  const teacherRemarksContent = document.getElementById("teacherRemarksContent");
  const teacherRemarksMeta = document.getElementById("teacherRemarksMeta");
  const submissionHistory = document.getElementById("submissionHistory");
  const historyContent = document.getElementById("historyContent");

  function openActivityModal(activity) {
    if (!activity) return;

    currentActivityId = activity.id; // Store the activity ID
    activityModalTitle.textContent = activity.title;
    document.getElementById('activityInstructions').textContent = activity.description || 'No instructions provided.';
    document.getElementById('activityDueDate').textContent = new Date(activity.due_date).toLocaleString();

    // Handle attachment display
    const attachmentLinkContainer = document.getElementById('activityAttachmentLink');
    if (activity.attachment_path) {
        const attachmentUrl = activity.attachment_path.startsWith('http') 
            ? activity.attachment_path 
            : `../uploads/activities/${activity.attachment_path}`;
        
        attachmentLinkContainer.href = attachmentUrl;
        attachmentLinkContainer.textContent = 'View Attached File';
        attachmentLinkContainer.parentElement.classList.remove('hidden');
    } else {
        attachmentLinkContainer.parentElement.classList.add('hidden');
    }


    // Reset modal state
    submissionSuccess.classList.add("hidden");
    activitySubmitBtn.disabled = true;
    activityFilePreview.classList.add("hidden");
    activityFilePreview.innerHTML = "";
    studentComment.value = "";
    submissionHistory.classList.add("hidden");
    teacherRemarksSection.classList.add("hidden");

    // Show submission and grading info if it exists
    if (activity.submission) {
        submissionHistory.classList.remove("hidden");
        renderSubmissionHistory([activity.submission]);

        if (activity.submission.feedback) {
            teacherRemarksSection.classList.remove("hidden");
            teacherRemarksContent.textContent = activity.submission.feedback;
            teacherRemarksMeta.textContent = `Score: ${activity.submission.score}/${activity.max_score}`;
        }
    }

    // Show upload section only if not submitted or if resubmission is allowed (future feature)
    uploadSection.style.display = activity.submission ? 'none' : 'block';

    activityModal.classList.remove("hidden");
  }

  // Format date for display
  function formatDisplayDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  // Render submission history
  function renderSubmissionHistory(submissions) {
    historyContent.innerHTML = '';
    submissions.forEach(submission => {
      const historyItem = document.createElement('div');
      historyItem.className = 'history-item';

      historyItem.innerHTML = `
        <div class="history-meta">
          <span>Submitted: ${formatDisplayDate(submission.date)}</span>
        </div>
        ${submission.file ? `
          <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #6c757d;">
            <i class="fas fa-paperclip"></i> <a href="../uploads/submissions/${submission.file}" target="_blank">${submission.file}</a>
          </div>
        ` : ''}
      `;
      historyContent.appendChild(historyItem);
    });
  }

  // Submit assignment
  activitySubmitBtn.addEventListener("click", () => {
    if (!activityFileInput.files[0]) {
      alert("Please select a file to upload.");
      return;
    }

    const file = activityFileInput.files[0];
    const comment = studentComment.value.trim();

    const formData = new FormData();
    formData.append('activity_id', currentActivityId);
    formData.append('submission_file', file);
    formData.append('comment', comment);

    // Simulate upload process
    activitySubmitBtn.disabled = true;
    activitySubmitBtn.textContent = "Uploading...";

    fetch('../php/submit_activity.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        submissionSuccess.classList.remove("hidden");
        activitySubmitBtn.textContent = "Submitted";
        uploadSection.style.display = 'none'; // Hide upload section after success
        
        // Close modal after 3 seconds and reload course details
        setTimeout(() => {
          activityModal.classList.add("hidden");
          const courseCode = document.getElementById('course-detail-code').textContent.replace('Code: ', '');
          const courseName = document.getElementById('course-detail-title').textContent;
          loadCourseDetails(courseCode, courseName); // Reload to show updated status
        }, 3000);
      } else {
        alert('Submission failed: ' + data.error);
        activitySubmitBtn.disabled = false;
        activitySubmitBtn.textContent = "Submit Assignment";
      }
    })
    .catch(error => {
      console.error('Error submitting activity:', error);
      alert('An error occurred during submission.');
      activitySubmitBtn.disabled = false;
      activitySubmitBtn.textContent = "Submit Assignment";
    });
  });

  // Close activity modal
  closeActivityModal.addEventListener("click", () => {
    activityModal.classList.add("hidden");
  });

  // File upload functionality
  uploadArea.addEventListener("click", () => {
    activityFileInput.click();
  });

  activityFileInput.addEventListener("change", (e) => {
    const file = e.target.files[0];
    if (file) {
      // Validate file size (10MB)
      if (file.size > 10 * 1024 * 1024) {
        alert("File size must be less than 10MB");
        return;
      }

      // Validate file type
      const allowedTypes = ['.pdf', '.doc', '.docx', '.jpg', '.jpeg', '.png'];
      const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
      if (!allowedTypes.includes(fileExtension)) {
        alert("Please upload a PDF, DOC, DOCX, JPG, or PNG file");
        return;
      }

      // Show file preview
      activityFilePreview.classList.remove("hidden");
      activityFilePreview.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between;">
          <div>
            <strong>${file.name}</strong>
            <div style="font-size: 0.8rem; color: #6c757d;">${(file.size / 1024 / 1024).toFixed(2)} MB</div>
          </div>
          <button type="button" class="remove-file-btn" style="background: none; border: none; color: #dc3545; cursor: pointer;">
            <i class="fas fa-times"></i>
          </button>
        </div>
      `;

      // Enable submit button
      activitySubmitBtn.disabled = false;

      // Add remove file functionality
      activityFilePreview.querySelector('.remove-file-btn').addEventListener('click', () => {
        activityFileInput.value = '';
        activityFilePreview.classList.add('hidden');
        activitySubmitBtn.disabled = true;
      });
    }
  });

  // Submit assignment
  activitySubmitBtn.addEventListener("click", () => {
    if (!activityFileInput.files[0]) {
      alert("Please select a file to upload");
      return;
    }

    const file = activityFileInput.files[0];
    const comment = studentComment.value.trim();

    // Simulate upload process
    activitySubmitBtn.disabled = true;
    activitySubmitBtn.textContent = "Uploading...";

    setTimeout(() => {
      // Add to submission history (in a real app, this would be sent to a server)
      const currentActivity = getCurrentActivity();
      if (currentActivity) {
        const newSubmission = {
          id: Date.now(),
          date: new Date().toISOString().replace('T', ' ').substring(0, 19),
          comment: comment || null,
          fileName: file.name,
          fileSize: `${(file.size / 1024 / 1024).toFixed(1)} MB`,
          status: 'submitted'
        };
        
        if (!currentActivity.submissions) {
          currentActivity.submissions = [];
        }
        currentActivity.submissions.unshift(newSubmission);
        
        // Update submission history display
        renderSubmissionHistory(currentActivity.submissions);
        submissionHistory.classList.remove('hidden');
      }

      submissionSuccess.classList.remove("hidden");
      activitySubmitBtn.textContent = "Submitted";
      
      // Reset form
      studentComment.value = "";
      activityFileInput.value = "";
      activityFilePreview.classList.add("hidden");
      
      // Close modal after 3 seconds
      setTimeout(() => {
        activityModal.classList.add("hidden");
        activitySubmitBtn.textContent = "Submit Assignment";
        activitySubmitBtn.disabled = true;
      }, 3000);
    }, 1500);
  });

  // Helper function to get current activity
  function getCurrentActivity() {
    const activityTitle = activityModalTitle.textContent;
    for (const [key, activity] of Object.entries(activityData)) {
      if (activity.title === activityTitle) {
        return activity;
      }
    }
    return null;
  }

  // Close modal when clicking outside
  activityModal.addEventListener("click", (e) => {
    if (e.target === activityModal) {
      activityModal.classList.add("hidden");
    }
  });
});