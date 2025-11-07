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
document.querySelectorAll('#mycourses .course-card').forEach(card => {
  card.addEventListener("click", () => {
    const courseCode = card.dataset.courseId;
    const courseName = card.dataset.courseName;
    
    // Hide all main tab contents before showing the detail view
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));

    courseDetail.classList.remove("hidden");
    courseDetail.classList.add("active");

      loadCourseDetails(courseCode, courseName);
      resetCourseDetailView();
    });
  });

// Back button - only ONE event listener
backBtn?.addEventListener("click", () => {
  courseDetail.classList.add("hidden");
  courseDetail.classList.remove("active");
  
  // Show the "My Courses" tab again
  const myCoursesTab = document.getElementById('mycourses');
  if (myCoursesTab) myCoursesTab.classList.add('active');
  
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
  function loadCourseDetails(courseCode, courseName) {
    const competenciesList = document.getElementById("competencies-list");
    const activitiesContainer = document.getElementById("activities-view");

    competenciesList.innerHTML = "<div>Loading course content...</div>";
    activitiesContainer.innerHTML = "<div>Loading activities...</div>";

    document.getElementById("course-detail-title").textContent = courseName;

    fetch(`../php/get_course_details_trainee.php?course_code=${courseCode}`)
      .then((r) => r.json())
      .then((data) => {
        if (data.error) {
          competenciesList.innerHTML = `<div class="error-message">${data.error}</div>`;
          return;
        }
        document.getElementById("course-detail-code").textContent = `Code: ${data.course.course_code}`;
        document.getElementById("course-detail-hours").textContent = `Hours: ${data.course.hours} hrs`;
        document.getElementById("course-detail-description").textContent = data.course.description;
        renderCompetencies(data.competencies, competenciesList);
        renderActivitiesTable(data.activities, activitiesContainer);
      })
      .catch(() => {
        competenciesList.innerHTML = `<div class="error-message">Failed to load course content.</div>`;
      });
  }

  // ========= ENROLLMENT CONFIRM ========= //
  const offeredCourses = document.getElementById("offered-courses");
  const enrollModal = document.getElementById("enrollModal");
  const confirmEnroll = document.getElementById("confirmEnroll");
  const cancelEnroll = document.getElementById("cancelEnroll");
  const enrollCourseName = document.getElementById("enrollCourseName");

  let courseToEnroll = { code: null, name: null, button: null };

  offeredCourses?.addEventListener("click", (e) => {
    const btn = e.target.closest(".enroll-btn");
    if (btn && !btn.disabled) {
      courseToEnroll = {
        code: btn.dataset.courseCode || null,
        name: btn.dataset.courseName || "Unnamed Course",
        button: btn,
      };
      enrollCourseName.textContent = courseToEnroll.name;
      enrollModal?.classList.remove("hidden");
    }
  });

  cancelEnroll?.addEventListener("click", () => {
    enrollModal?.classList.add("hidden");
    courseToEnroll = { code: null, name: null, button: null };
  });

  confirmEnroll?.addEventListener("click", async () => {
    enrollModal?.classList.add("hidden");
    const { code, button } = courseToEnroll;
    if (!code || !button) return;
    button.disabled = true;
    button.textContent = "Requesting...";

    try {
      const response = await fetch("../php/request_enrollment.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `course_code=${encodeURIComponent(code)}`,
      });
      const data = await response.json();
      if (data.success) {
        alert("✅ Enrollment request sent successfully!");
        window.location.reload();
      } else {
        alert("⚠️ " + (data.message || "An error occurred."));
        button.disabled = false;
        button.textContent = "Request to Enroll";
      }
    } catch {
      alert("⚠️ Error sending request.");
      button.disabled = false;
      button.textContent = "Request to Enroll";
    }
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

  // Handle Profile Form Submission
  const profileForm = document.getElementById('profileForm');
  profileForm?.addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      const profilePictureFile = document.getElementById('profileUpload').files[0];
      if (profilePictureFile) {
          formData.append('profile_picture', profilePictureFile);
      }

      const saveBtn = this.querySelector('.primary-btn');
      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      fetch('../php/update_profile.php', {
          method: 'POST',
          body: formData
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              alert('Profile updated successfully!');
              profileModal?.classList.add("hidden");
              // Update UI without reloading
              document.querySelector('.user-name').textContent = `${data.user.first_name} ${data.user.last_name}`;
              if (data.user.profile_picture) {
                  const newPicUrl = `../uploads/profiles/${data.user.profile_picture}?t=${new Date().getTime()}`;
                  document.querySelector('.user-avatar').src = newPicUrl;
                  document.getElementById('profilePreview').src = newPicUrl;
              }
          } else {
              alert('Error: ' + data.message);
          }
      })
      .finally(() => { saveBtn.disabled = false; saveBtn.textContent = 'Save Changes'; });
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
  });
});
