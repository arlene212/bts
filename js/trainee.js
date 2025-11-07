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

  document.querySelectorAll("#mycourses .course-card").forEach((card) => {
    card.addEventListener("click", () => {
      const courseCode = card.dataset.courseCode;
      const courseName = card.dataset.courseName;
      document
        .querySelectorAll("#mycourses .course-box")
        .forEach((box) => box.classList.add("hidden"));
      courseDetail?.classList.remove("hidden");
      courseDetail?.classList.add("active");

      loadCourseDetails(courseCode, courseName);
      resetCourseDetailView();
    });
  });

  backBtn?.addEventListener("click", () => {
    courseDetail?.classList.add("hidden");
    courseDetail?.classList.remove("active");
    document
      .querySelectorAll("#mycourses .course-box")
      .forEach((box) => box.classList.remove("hidden"));
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

  /* ========= CANCEL ENROLLMENT REQUEST ========= */
  document.getElementById("requests-body")?.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-cancel, .cancel-btn-request");
    if (btn) {
      const id = btn.dataset.requestId || btn.dataset.id;
      const courseName =
        btn.closest("tr")?.querySelector("td:first-child")?.textContent || "";
      if (confirm(`Cancel request for "${courseName}"?`)) {
        btn.disabled = true;
        btn.textContent = "Cancelling...";
        fetch("../php/cancel_enrollment_request.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: `enrollment_id=${encodeURIComponent(id)}`,
        })
          .then((r) => r.json())
          .then((data) => {
            alert(data.message);
            if (data.success) window.location.reload();
            else {
              btn.disabled = false;
              btn.textContent = "Cancel";
            }
          });
      }
    }
  });
});
