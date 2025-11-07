document.addEventListener("DOMContentLoaded", () => {
  /* ========= SIDEBAR & NAV ========= */
  const hamburger = document.getElementById("hamburger");
  const sidebar = document.querySelector(".sidebar");
  const navLinks = document.querySelectorAll(".sidebar .nav a");
  const tabContents = document.querySelectorAll(".tab-content");

  // Sidebar toggle
  hamburger?.addEventListener("click", (e) => {
    e.stopPropagation();
    sidebar?.classList.toggle("open");
  });

  // Sidebar tab switching
  navLinks.forEach((link) => {
    link.addEventListener("click", (e) => {
      e.preventDefault();
      navLinks.forEach((l) => l.classList.remove("active"));
      link.classList.add("active");

      const target = link.getAttribute("data-tab");
      tabContents.forEach((tab) =>
        tab.classList.toggle("active", tab.id === target)
      );

      sidebar?.classList.remove("open");

      // Reset My Courses view
      if (target === "mycourses") {
        showEnrolledTab();
        courseDetail?.classList.add("hidden");
        courseDetail?.classList.remove("active");
        document
          .querySelectorAll("#mycourses .course-box")
          .forEach((box) => box.classList.remove("hidden"));
        myCourseSwitchButtons.forEach((b) => b.classList.remove("active"));
        myCourseSwitchButtons[0]?.classList.add("active");
        myCourseSwitchInner.style.transform = "translateX(0%)";
      }
    });
  });

  /* ========= NOTIFICATIONS ========= */
  const notifIcon = document.getElementById("notifIcon");
  const notifDropdown = document.getElementById("notifDropdown");

  notifIcon?.addEventListener("click", (e) => {
    e.stopPropagation();
    notifDropdown?.classList.toggle("hidden");
  });

  document.addEventListener("click", (e) => {
    if (
      sidebar?.classList.contains("open") &&
      !sidebar.contains(e.target) &&
      !hamburger.contains(e.target)
    ) {
      sidebar.classList.remove("open");
    }
    if (
      notifDropdown &&
      !notifDropdown.contains(e.target) &&
      !notifIcon.contains(e.target)
    ) {
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
      switchButtons.forEach((b) => b.classList.remove("active"));
      document
        .querySelectorAll("#home .tab-inner")
        .forEach((tab) => tab.classList.remove("active"));
      btn.classList.add("active");
      switchInner.style.transform = `translateX(${index * 100}%)`;

      if (btn.dataset.tab === "dashboard") dashboardTab.classList.add("active");
      else if (btn.dataset.tab === "news") newsTab.classList.add("active");
    });
  });

  /* ========= MY COURSES SWITCH ========= */
  const myCourseSwitchButtons = document.querySelectorAll(
    "#mycourses .switch-btn"
  );
  const myCourseSwitchInner = document.querySelector("#mycourses .switch-inner");
  const enrolledTab = document.getElementById("enrolled");
  const completedTab = document.getElementById("completed");

  function showEnrolledTab() {
    enrolledTab?.classList.add("active");
    completedTab?.classList.remove("active");
  }

  function showCompletedTab() {
    completedTab?.classList.add("active");
    enrolledTab?.classList.remove("active");
  }

  showEnrolledTab();

  myCourseSwitchButtons.forEach((btn, index) => {
    btn.addEventListener("click", () => {
      myCourseSwitchButtons.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      myCourseSwitchInner.style.transform = `translateX(${index * 100}%)`;
      btn.dataset.tab === "enrolled" ? showEnrolledTab() : showCompletedTab();
    });
  });

  /* ========= COURSE DETAIL VIEW ========= */
  const courseDetail = document.getElementById("courseDetail");
  const backBtn = courseDetail?.querySelector(".back-btn");
  const courseSwitchBtns = courseDetail?.querySelectorAll(
    ".course-switch .switch-btn"
  );
  const courseSwitchInner = courseDetail?.querySelector(
    ".course-switch .switch-inner"
  );
  const modulesView = document.getElementById("modules-view");
  const activitiesView = document.getElementById("activities-view");

  function resetCourseDetailView() {
    if (courseSwitchInner && courseSwitchBtns?.length) {
      courseSwitchInner.style.transition = "transform 0.3s ease";
      courseSwitchInner.style.transform = "translateX(0%)";
      modulesView?.classList.add("active");
      activitiesView?.classList.remove("active");
      modulesView.style.display = "block";
      activitiesView.style.display = "none";
      courseSwitchBtns[0]?.classList.add("active");
      courseSwitchBtns[1]?.classList.remove("active");
    }
  }

// Open course detail
document.querySelectorAll('#mycourses .course-card.view-course-content-btn').forEach(card => {
  card.addEventListener("click", () => {
    const courseCode = card.dataset.courseCode;
    const courseName = card.dataset.courseName;
    const courseHours = card.dataset.courseHours;
    const courseDescription = card.dataset.courseDescription;
    
    // Hide all main tab contents before showing the detail view
    document.querySelector('#mycourses .course-box').classList.add('hidden');

    courseDetail.classList.remove("hidden");
    courseDetail.classList.add("active");

    loadCourseDetails(courseCode, courseName, courseHours, courseDescription);
    resetCourseDetailView();
  });
});

// Back button - only ONE event listener
backBtn?.addEventListener("click", () => {
  courseDetail.classList.add("hidden");
  courseDetail.classList.remove("active");
  
  // Show the course list box again
  const courseBox = document.querySelector('#mycourses .course-box');
  if (courseBox) courseBox.classList.remove('hidden');
  
  // Reset to Modules view for next time
  resetCourseDetailView();
});

  if (courseSwitchBtns && courseSwitchInner && modulesView && activitiesView) {
    courseSwitchBtns.forEach((btn, index) => {
      btn.addEventListener("click", () => {
        courseSwitchBtns.forEach((b) => b.classList.remove("active"));
        btn.classList.add("active");
        courseSwitchInner.style.transition = "transform 0.3s ease";
        courseSwitchInner.style.transform = `translateX(${index * 100}%)`;

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

  // ==== LOAD COURSE DETAILS (AJAX) ====
  function loadCourseDetails(courseCode, courseName, courseHours, courseDescription) {
    const competenciesList = document.getElementById("competencies-list");
    const activitiesContainer = document.getElementById("activities-view");
  
    competenciesList.innerHTML = "<div>Loading course content...</div>";
    activitiesContainer.innerHTML = "<div>Loading activities...</div>";

    document.getElementById("course-detail-title").textContent = courseName || "Course Details";
    document.getElementById("course-detail-code").textContent = `Code: ${courseCode || 'N/A'}`;
    document.getElementById("course-detail-hours").textContent = `Hours: ${courseHours || 'N/A'} hrs`;
    document.getElementById("course-detail-description").textContent = courseDescription || 'No description available.';

    fetch(`../php/get_course_details_trainee.php?course_code=${courseCode}`)
      .then((r) => r.json())
      .then((data) => {
        if (data.error) {
          competenciesList.innerHTML = `<div class="error-message">${data.error}</div>`;
          activitiesContainer.innerHTML = `<div class="error-message">${data.error}</div>`;
          return;
        }
        renderCompetencies(data.competencies || [], competenciesList);
        renderActivitiesTable(data.activities || [], activitiesContainer, data.submissions || {});
      })
      .catch(() => {
        competenciesList.innerHTML = `<div class="error-message">Failed to load course content.</div>`;
      });
  }

  function renderCompetencies(competencies, container) {
    container.innerHTML = '';
    if (!competencies || competencies.length === 0) {
      container.innerHTML = '<p>No competencies found for this course.</p>';
      return;
    }

    const competencyTypes = ['basic', 'common', 'core'];
    competencyTypes.forEach(type => {
      const compsOfType = competencies.filter(c => c.type === type);
      if (compsOfType.length > 0) {
        const typeHeader = document.createElement('h3');
        typeHeader.className = 'competency-type-header';
        typeHeader.textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} Competencies`;
        container.appendChild(typeHeader);

        compsOfType.forEach(comp => {
          const compElement = document.createElement('div');
          compElement.className = 'competency-item';
          compElement.innerHTML = `
            <div class="competency-header">
              <h4>${comp.name}</h4>
              <p>${comp.description || ''}</p>
            </div>
            <div class="topics-list">
              ${renderTopics(comp.topics || [])}
            </div>
          `;
          container.appendChild(compElement);
        });
      }
    });
  }

  function renderTopics(topics) {
    if (!topics || topics.length === 0) {
      return '<p class="no-materials">No topics for this competency.</p>';
    }
    return topics.map(topic => `
      <div class="topic-container">
        <h4 class="topic-title">${topic.topic_name || topic.name || 'Unnamed Topic'}</h4>
        <div class="topic-content-section">
          <h6 class="content-divider">Materials</h6>
          ${renderMaterials(topic.materials || [])}
        </div>
        ${renderActivitiesForTopic(topic.activities || [])}
      </div>
    `).join('');
  }


  function renderMaterials(materials) {
    if (!materials || materials.length === 0) {
      return '<p class="no-materials">No materials for this topic.</p>';
    }
    let html = '<div class="materials-list">';
    html += materials.map(material => {
      const filePath = material.material_file_path || material.file_path;
      const isLink = filePath && (filePath.startsWith('http://') || filePath.startsWith('https://'));
      const downloadPath = isLink ? filePath : `../uploads/courses/${filePath}`;
      return `
        <div class="material-item">
          <i class="fas fa-file-alt"></i>
          <div class="material-info">
            <strong>${material.material_title || material.title || 'Unnamed Material'}</strong>
            <p>${material.material_description || ''}</p>
            ${filePath ? `<a href="${downloadPath}" target="_blank">View Material</a>` : ''}
          </div>
        </div>
      `;
    }).join('');
    html += '</div>';
    return html;
  }

  function renderActivitiesForTopic(activities) {
    if (!activities || activities.length === 0) {
      return '';
    }
    let html = '<div class="topic-content-section">';
    html += '<h6 class="content-divider">Activities</h6>';
    html += '<div class="activities-list">';
    activities.forEach(activity => {
      html += `
        <div class="topic-activity-item" data-activity-id="${activity.id}">
          <div class="activity-header">
            <div class="activity-info-cleaned">
              <strong>${activity.title || 'Unnamed Activity'}</strong>
              <p>Due: ${formatDisplayDate(activity.due_date)}</p>
            </div>
          </div>
          <div class="activity-content hidden">
            <div class="activity-instructions">
              <h4><i class="fas fa-info-circle"></i> Instructions</h4>
              <p>${activity.description || 'No instructions provided.'}</p>
            </div>
            
            ${activity.submission ? `
              <div class="submission-history">
                <h4><i class="fas fa-history"></i> Your Submission</h4>
                <div class="history-content">
                  <div class="history-item">
                    <div class="history-meta">
                      <span>Submitted on: ${formatDisplayDate(activity.submission.date)}</span>
                      <span class="status-badge ${activity.submission.score !== null ? 'graded' : 'submitted'}">
                        ${activity.submission.score !== null ? 'Graded' : 'Submitted'}
                      </span>
                    </div>
                    ${activity.submission.file ? `<div class="submission-file"><i class="fas fa-paperclip"></i> <a href="../uploads/submissions/${activity.submission.file}" target="_blank">${activity.submission.file.split('/').pop()}</a></div>` : ''}
                    ${activity.submission.score !== null ? `<div class="submission-score"><strong>Score:</strong> ${activity.submission.score} / ${activity.max_score}</div>` : ''}
                    ${activity.submission.feedback ? `<div class="submission-feedback"><strong>Trainer Feedback:</strong> <p>${activity.submission.feedback.replace(/\n/g, '<br>')}</p></div>` : ''}
                  </div>
                </div>
              </div>
            ` : `
              <div class="upload-section">
                <h4><i class="fas fa-upload"></i> Submit Your Work</h4>
                <textarea class="submission-comment" placeholder="Add a comment (optional)..."></textarea>
                <div class="upload-area">
                  <i class="fas fa-cloud-upload-alt"></i>
                  <p>Click to upload or drag and drop a file</p>
                  <input type="file" class="activity-file-input" style="display:none;">
                </div>
                <div class="file-preview hidden"></div>
                <button class="submit-btn" disabled>Submit Assignment</button>
              </div>
            `}
          </div>
        </div>
      `;
    });
    html += '</div>';
    return html + '</div>';
  }


  function renderActivitiesTable(activities, container, submissions) {
    if (!activities || activities.length === 0) {
      container.innerHTML = '<p>No activities found for this course.</p>';
      return;
    }

    let tableHtml = `
      <table class="activities-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Date Given</th>
            <th>Due Date</th>
            <th>Status</th>
            <th>Score</th>
          </tr>
        </thead>
        <tbody>
    `;

    activities.forEach(activity => {
      const submission = activity.submission;
      const status = submission ? (submission.score !== null ? 'Graded' : 'Submitted') : 'Not Submitted';
      const statusClass = submission ? (submission.score !== null ? 'graded' : 'submitted') : 'not-submitted';
      const score = submission && submission.score !== null ? `${submission.score} / ${activity.max_score}` : '—';

      tableHtml += `
        <tr>
          <td>${activity.activity_title || activity.title || 'Unnamed Activity'}</td>
          <td>${formatDisplayDate(activity.start_date)}</td>
          <td>${formatDisplayDate(activity.due_date)}</td>
          <td><span class="status-badge ${statusClass}">${status}</span></td>
          <td>${score}</td>
        </tr>
      `;
    });

    tableHtml += `
        </tbody>
      </table>
    `;
    container.innerHTML = tableHtml;

  }  

  // Event Delegation for new dynamic activity accordions
  document.getElementById('modules-view').addEventListener('click', function(e) {
    const header = e.target.closest('.activity-header');
    if (header) {
      const content = header.nextElementSibling;
      content.classList.toggle('hidden');
      const icon = header.querySelector('.toggle-icon');
      if (icon) { // Check if icon exists before trying to toggle classes
        icon.classList.toggle('fa-chevron-down');
        icon.classList.toggle('fa-chevron-up');
      }
    }

    // Handle file input click
    const uploadArea = e.target.closest('.upload-area');
    if (uploadArea) {
      uploadArea.querySelector('.activity-file-input').click();
    }

    // Handle file removal
    const removeBtn = e.target.closest('.remove-file-btn');
    if (removeBtn) {
        const activityItem = removeBtn.closest('.topic-activity-item');
        const fileInput = activityItem.querySelector('.activity-file-input');
        const filePreview = activityItem.querySelector('.file-preview');
        const submitBtn = activityItem.querySelector('.submit-btn');

        fileInput.value = '';
        filePreview.classList.add('hidden');
        filePreview.innerHTML = '';
        submitBtn.disabled = true;
    }

    // Handle submission
    const submitBtn = e.target.closest('.submit-btn');
    if (submitBtn) {
        const activityItem = submitBtn.closest('.topic-activity-item');
        const activityId = activityItem.dataset.activityId;
        const fileInput = activityItem.querySelector('.activity-file-input');
        const comment = activityItem.querySelector('.submission-comment').value;

        if (!fileInput.files[0]) {
            alert("Please select a file to submit.");
            return;
        }

        const formData = new FormData();
        formData.append('activity_id', activityId);
        formData.append('submission_file', fileInput.files[0]);
        formData.append('comment', comment);

        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        fetch('../php/submit_activity.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Submission successful!');
                // Reload course details to show the updated submission status
                const courseCode = document.getElementById("course-detail-code").textContent.replace('Code: ', '');
                loadCourseDetails(courseCode, null, null, null);
            } else {
                alert('Submission failed: ' + (data.error || 'Unknown error'));
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Assignment';
            }
        })
        .catch(error => {
            console.error('Submission error:', error);
            alert('An error occurred during submission.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Assignment';
        });
    }
  });

  // ========= ENROLLMENT CONFIRM ========= //
  const offeredCourses = document.getElementById("offered-courses");
  const enrollModal = document.getElementById("enrollModal");
  const confirmEnroll = document.getElementById("confirmEnroll");
  const cancelEnroll = document.getElementById("cancelEnroll");
  const enrollCourseName = document.getElementById("enrollCourseName");

  let courseToEnroll = { code: null, name: null, button: null };

  // Event handler for enrollment button clicks
  document.body.addEventListener("click", (e) => {
    const btn = e.target.closest(".enroll-btn");
    if (btn && !btn.disabled) {
        e.preventDefault(); // Prevent any default button action
        const code = btn.getAttribute("data-course-code");
        const name = btn.getAttribute("data-course-name");
        
        if (!code) {
            console.error("Course code is missing from the button data attributes");
            return;
        }

        // Update button state
        btn.disabled = true;
        btn.textContent = "Requesting...";

        // Prepare and send the enrollment request
        const formData = new FormData();
        formData.append('course_code', code);

        fetch('../php/request_enrollment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Create notification
                const notification = `
                    <div class="notification-item">
                        <i class="fas fa-info-circle"></i>
                        <div class="notification-content">
                            <div class="notification-title">Enrollment Request Sent</div>
                            <div class="notification-message">Your request to enroll in ${name} is pending approval.</div>
                            <div class="notification-time">Just now</div>
                        </div>
                    </div>
                `;
                
                // Add notification to dropdown
                const notifDropdown = document.getElementById('notifDropdown');
                if (notifDropdown) {
                    if (notifDropdown.innerHTML.includes('No new notifications')) {
                        notifDropdown.innerHTML = '';
                    }
                    notifDropdown.insertAdjacentHTML('afterbegin', notification);
                }
                
                // Update button state
                btn.textContent = "Pending Approval";
                btn.classList.add('pending');
                
                // Show success message
                alert("✅ " + data.message);
                
                // Reload page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                alert("⚠️ " + (data.message || "An error occurred while sending the enrollment request."));
                btn.disabled = false;
                btn.textContent = "Request to Enroll";
                btn.classList.remove('pending');
            }
        })
        .catch(error => {
            console.error('Enrollment request error:', error);
            alert("⚠️ An error occurred while sending the enrollment request. Please try again.");
            btn.disabled = false;
            btn.textContent = "Request to Enroll";
            btn.classList.remove('pending');
        });
    }
  });

  cancelEnroll?.addEventListener("click", () => {
    enrollModal?.classList.add("hidden");
    courseToEnroll = { code: null, name: null, button: null };
  });

  // Event handlers for enrollment modal are no longer needed since we're handling enrollment directly

  // Handle file selection for dynamic activity forms
  document.getElementById('modules-view').addEventListener('change', function(e) {
      if (e.target.classList.contains('activity-file-input')) {
          const file = e.target.files[0];
          const activityItem = e.target.closest('.topic-activity-item');
          const filePreview = activityItem.querySelector('.file-preview');
          const submitBtn = activityItem.querySelector('.submit-btn');

          if (file) {
              filePreview.innerHTML = `
                  <div class="file-preview-item">
                      <i class="fas fa-file-alt"></i>
                      <div class="file-details">
                          <strong>${file.name}</strong>
                          <span>(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                      </div>
                      <button class="remove-file-btn">&times;</button>
                  </div>
              `;
              filePreview.classList.remove('hidden');
              submitBtn.disabled = false;
          } else {
              filePreview.classList.add('hidden');
              filePreview.innerHTML = '';
              submitBtn.disabled = true;
          }
      }
  });

  /* ========= PROFILE MODAL ========= */
  // Direct click handler for debugging
const editProfileBtn = document.getElementById('editProfileBtn');
console.log('Edit button found:', !!editProfileBtn);

if (editProfileBtn) {
    editProfileBtn.onclick = function(e) {
        console.log('Edit button clicked!');
        e.preventDefault();
        
        const profileModal = document.getElementById('profileModal');
        console.log('Modal found:', !!profileModal);
        
        if (profileModal) {
            profileModal.style.display = 'flex';
            console.log('Modal display set to flex');
        }
    };
}

document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM loaded, initializing profile modal...");
    
    // Get all required elements
    const editProfileBtn = document.getElementById("editProfileBtn");
    const profileModal = document.getElementById("profileModal");
    const closeProfileModal = document.getElementById("closeProfileModal");
    const cancelProfileChanges = document.getElementById("cancelProfileChanges");
    const changeProfileBtn = document.getElementById("changeProfileBtn");
    const profileUpload = document.getElementById("profileUpload");
    const profilePreview = document.getElementById("profilePreview");
    const profileForm = document.getElementById("profileForm");

    console.log("Elements found:", {
        editProfileBtn: !!editProfileBtn,
        profileModal: !!profileModal,
        closeProfileModal: !!closeProfileModal,
        cancelProfileChanges: !!cancelProfileChanges
    });

    if (!editProfileBtn || !profileModal) {
        console.error("Could not find required profile modal elements!");
        return;
    }

    function showModal() {
        console.log("Showing modal");
        profileModal.classList.add("show");
        document.body.style.overflow = 'hidden';
        console.log("Modal class list:", profileModal.classList);
        console.log("Modal style display:", window.getComputedStyle(profileModal).display);
    }

    function hideModal() {
        console.log("Hiding modal");
        profileModal.classList.remove("show");
        document.body.style.overflow = '';
    }

    // Profile Picture Upload
    if (changeProfileBtn && profileUpload) {
        changeProfileBtn.addEventListener("click", () => {
            profileUpload.click();
        });

        profileUpload.addEventListener("change", function() {
            const file = this.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) { // 5MB limit
                    alert("File size must be less than 5MB");
                    this.value = "";
                    return;
                }

                if (!file.type.match('image.*')) {
                    alert("Please select an image file");
                    this.value = "";
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    profilePreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Modal Open/Close
    editProfileBtn.addEventListener("click", (e) => {
        e.preventDefault();
        showModal();
    });

    [closeProfileModal, cancelProfileChanges].forEach(btn => {
        if (btn) {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                hideModal();
            });
        }
    });

    // Close on outside click
    profileModal.addEventListener("click", (e) => {
        if (e.target === profileModal) {
            hideModal();
        }
    });

    // Form Submission
    if (profileForm) {
        profileForm.onsubmit = async function(e) {
            e.preventDefault();
            console.log("Form submission started");

            const saveBtn = this.querySelector('.save-btn');
            if (!saveBtn) {
                console.error("Save button not found");
                return;
            }

            try {
                const formData = new FormData(this);
                
                // Add profile picture if one was selected
                const profilePicFile = profileUpload?.files[0];
                if (profilePicFile) {
                    formData.append('profile_picture', profilePicFile);
                }

                // Disable submit button and show loading state
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

                const response = await fetch('../php/update_profile.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();
                
                if (data.success) {
                    // Update UI elements
                    const sidebarAvatar = document.querySelector('.user-avatar');
                    const userName = document.querySelector('.user-name');
                    const timestamp = new Date().getTime();

                    if (sidebarAvatar && data.user.profile_picture) {
                        const newPicUrl = `../uploads/profiles/${data.user.profile_picture}?t=${timestamp}`;
                        sidebarAvatar.src = newPicUrl;
                        profilePreview.src = newPicUrl;
                    }
                    
                    if (userName) {
                        userName.textContent = `${data.user.first_name} ${data.user.last_name}`;
                    }

                    alert('Profile updated successfully!');
                    hideModal();
                } else {
                    throw new Error(data.message || 'Failed to update profile');
                }
            } catch (error) {
                console.error('Error updating profile:', error);
                alert('Error updating profile: ' + error.message);
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
            }
        };
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

  /* Profile form submission is now handled in the DOMContentLoaded event listener */

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

  // Format date for display
  function formatDisplayDate(dateString) {
    if (!dateString) return 'N/A'; // Handle null or undefined dates
    // Replace space with 'T' to make it ISO 8601 compliant for robust parsing
    const date = new Date(dateString.replace(' ', 'T'));
    if (isNaN(date.getTime())) { // Check for invalid date
        return 'Invalid Date';
    }
    return date.toLocaleString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }
});
