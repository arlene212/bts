function setupCourseDetailView() {
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('view-course-btn')) {
    const courseCard = e.target.closest('.enrolled-course');
    if (courseCard) {
      const courseCode = courseCard.getAttribute('data-course');
      const courseName = courseCard.getAttribute('data-title');
      viewCourseDetails(courseCode, courseName, '', '');
    }
  }
  const contentBtn = e.target.closest('.view-course-content-btn');
  if (contentBtn) {
    const courseCode = contentBtn.getAttribute('data-course-code');
    const courseName = contentBtn.getAttribute('data-course-name');
    const courseHours = contentBtn.getAttribute('data-course-hours') || '';
    const courseDescription = contentBtn.getAttribute('data-course-description') || '';
    const credited = contentBtn.getAttribute('data-credited-hours') || '';
    if (courseCode && courseName) {
      viewCourseDetails(courseCode, courseName, courseHours, courseDescription, credited);
    }
  }
  });
  const backToCourses = document.getElementById('backToCourses');
  const backToEnrolledCourses = document.getElementById('backToEnrolledCourses');
  if (backToCourses) { backToCourses.addEventListener('click', resetDetailViews); }
  if (backToEnrolledCourses) { backToEnrolledCourses.addEventListener('click', resetDetailViews); }
}

function viewCourseDetails(courseCode, courseName, hours, description, credited) {
  const activeCoursesContent = document.getElementById('enrolled-active-courses');
  const completedCoursesContent = document.getElementById('enrolled-completed-courses');
  const courseSwitchContainer = document.getElementById('courseSwitchContainer');
  const courseDetailView = document.getElementById('courseDetail');
  const backButton = document.getElementById('backToEnrolledCourses');
  if (activeCoursesContent) { activeCoursesContent.style.display = 'none'; activeCoursesContent.classList.remove('active'); }
  if (completedCoursesContent) { completedCoursesContent.style.display = 'none'; completedCoursesContent.classList.remove('active'); }
  if (courseSwitchContainer) courseSwitchContainer.style.display = 'none';
  if (courseDetailView) { courseDetailView.classList.remove('hidden'); courseDetailView.style.display = 'block'; }
  if (backButton) backButton.classList.remove('hidden');
  const titleEl = document.getElementById('course-detail-title');
  const codeEl = document.getElementById('course-detail-code');
  const hoursEl = document.getElementById('course-detail-hours');
  const creditedEl = document.getElementById('course-detail-credited-hours');
  if (titleEl) titleEl.textContent = courseName || '';
  if (codeEl) codeEl.textContent = courseCode || '';
  if (hoursEl) hoursEl.textContent = hours ? `Basic Hours: ${hours} hrs` : '';
  if (creditedEl) creditedEl.textContent = credited ? `Credited: ${credited} hrs` : '';
  document.dispatchEvent(new CustomEvent('courseDetailOpened', { detail: { courseCode, courseName } }));
  const openedKeyPrefix = 'guest_opened_' + courseCode;
  function getOpened() { try { const s = localStorage.getItem(openedKeyPrefix); return s ? JSON.parse(s) : []; } catch (_) { return []; } }
  function saveOpened(arr) { try { localStorage.setItem(openedKeyPrefix, JSON.stringify(arr)); } catch (_) {} }
  const openedSet = new Set(getOpened());
  const courseContentContainer = document.getElementById('courseDetailContent');
  if (!courseContentContainer) return;
  courseContentContainer.innerHTML = '<div>Loading course content...</div>';
  fetch(`../php/get_course_details_guest.php?course_code=${encodeURIComponent(courseCode)}&_=${Date.now()}`)
    .then(response => response.json())
    .then(data => {
      if (data.error) { courseContentContainer.innerHTML = `<div class="error-message">Error: ${data.error}</div>`; return; }
      const descEl = document.getElementById('course-detail-description');
      if (descEl && data.course && data.course.description) {
        descEl.textContent = data.course.description;
      }
      const competencies = (data.competencies || []);
      const groups = { basic: [], common: [], core: [] };
      const cmByComp = data.materialsByCompetency || {};
      competencies.forEach(comp => {
        const rows = [];
        let totalActivities = 0;
        let completedActivities = 0;
        const extraMaterials = cmByComp[comp.id] || [];
        extraMaterials.forEach(m => {
          const isExternal = m.file_path && /^https?:\/\//i.test(m.file_path);
          const link = m.file_path ? (isExternal ? m.file_path : `../php/download.php?source=course&material_id=${m.id}`) : '';
          if (link) rows.push({ type:'module', id: m.id, title: m.title || 'Module', link, download: !isExternal, locked: (comp.type !== 'basic'), opened: openedSet.has('cm:' + m.id) });
        });
        (comp.topics || []).forEach(topic => {
          (topic.materials || []).forEach(mat => {
            const isExternal = mat.file_path && /^https?:\/\//i.test(mat.file_path);
            const link = mat.file_path ? (isExternal ? mat.file_path : `../php/download.php?source=topic&material_id=${mat.id}`) : '#';
            rows.push({ type:'material', id: mat.id, title: mat.title || 'Material', link, locked: (comp.type !== 'basic'), opened: openedSet.has('tm:' + mat.id) });
          });
          (topic.activities || []).forEach(act => {
            totalActivities += 1;
            const submitted = !!act.submission;
            if (submitted) completedActivities += 1;
            rows.push({ type:'activity', id: act.id, title: act.title || 'Activity', submitted, score: act.submission && (act.submission.score ?? null), due: act.due_date || null, activityType: act.type || 'assignment', locked: (comp.type !== 'basic') });
          });
        });
        const progress = totalActivities > 0 ? Math.round((completedActivities / totalActivities) * 100) : 0;
        const sectionsCount = rows.length;
        groups[(comp.type || 'basic')].push({ comp, rows, progress, sectionsCount });
      });

      const totalModules = groups.basic.length + groups.common.length + groups.core.length;
      let html = `<div class="modules-ui"><div class="list-header"><h3><i class="fas fa-book"></i> Modules</h3><input type="text" id="modules-search" placeholder="Search modules..."><span class="chip" id="modules-count">${totalModules} modules</span></div>`;
      function renderGroup(label, key){
        if (groups[key].length === 0) return '';
        let g = `<div class="module-group" data-group="${key}"><div class="group-header"><span class="competency-marker">${label}</span></div><div class="group-list">`;
        groups[key].forEach(m => {
          g += `<div class="module-card"><div class="module-header">`
             + `<img class="module-thumb" src="../images/school.png" alt="logo">`
             + `<div class="module-title-area"><h4 class="module-title">${m.comp.name}</h4></div>`
             + `<span class="sections-pill">${m.sectionsCount} sections <i class="fas fa-chevron-down"></i></span>`
             + `</div>`;
          g += `<div class="module-sections"><div class="sections-card"><div class="sections-header-row">`
             + `<div class="col section">Section</div><div class="col submitted">Submitted</div><div class="col score">Score</div><div class="col due">Due</div><div class="col status">Status</div></div>`;
          m.rows.forEach(r => {
            const icon = r.type === 'material' ? '<i class="fas fa-folder" style="color:#1d4ed8"></i>' : '<i class="fas fa-folder" style="color:#475569"></i>';
            const submittedIcon = r.submitted ? '<i class="fas fa-flag"></i>' : '—';
            const scoreText = (r.score || r.score === 0) ? r.score : '—';
            const dueText = r.due ? r.due : '—';
            const statusText = r.type === 'activity' ? (r.submitted ? 'Submitted' : 'Pending') : (r.opened ? 'Opened' : 'Not opened');
            const downloadAttr = r.download ? ' download' : '';
            const linkHtml = r.locked ? '<span class="section-link disabled-link">' + (r.title) + '</span>' : (r.link ? '<a href="' + r.link + '" target="_blank" rel="noopener noreferrer" class="section-link"' + downloadAttr + '>' + r.title + '</a>' : r.title);
            const activityData = r.type === 'activity' ? ` data-activity-id="${r.id}" data-activity-type="${r.activityType || 'assignment'}" data-activity-title="${r.title.replace(/"/g,'') }"` : '';
            const materialKey = r.type === 'module' ? ` data-material-key="cm:${r.id || ''}"` : (r.type === 'material' ? ` data-material-key="tm:${r.id || ''}"` : '');
            const lockedAttr = r.locked ? ' data-locked="1"' : '';
            g += `<div class="sections-row"${activityData}${materialKey}${lockedAttr}>` + `<div class="cell sec-title">${icon} ${linkHtml}</div>` + `<div class="cell sec-submitted">${submittedIcon}</div>` + `<div class="cell sec-score">${scoreText}</div>` + `<div class="cell sec-due">${dueText}</div>` + `<div class="cell sec-status">${statusText}</div>` + `</div>`;
          });
          g += `</div></div></div>`;
        });
        g += `</div>`; return g;
      }
      html += renderGroup('Basic Competencies','basic');
      html += renderGroup('Common Competencies','common');
      html += renderGroup('Core Competencies','core');
      html += `</div>`;
      courseContentContainer.innerHTML = html;

      const searchInput = document.getElementById('modules-search');
      if (searchInput) {
        searchInput.addEventListener('input', function(){
          const q = this.value.toLowerCase();
          document.querySelectorAll('#courseDetail .module-card').forEach(card => {
            const title = (card.querySelector('.module-title')?.textContent || '').toLowerCase();
            card.style.display = title.includes(q) ? '' : 'none';
          });
        });
      }

      const cards = Array.from(document.querySelectorAll('#courseDetail .module-card'));
      cards.forEach((card, idx) => {
        if (idx === 0) { card.classList.add('open'); card.classList.remove('collapsed'); }
        else { card.classList.add('collapsed'); card.classList.remove('open'); }
        const pill = card.querySelector('.sections-pill');
        if (pill) {
          pill.setAttribute('role','button');
          pill.setAttribute('tabindex','0');
          pill.setAttribute('aria-expanded', idx === 0 ? 'true' : 'false');
          const toggle = () => {
            const isOpen = card.classList.contains('open');
            card.classList.toggle('open', !isOpen);
            card.classList.toggle('collapsed', isOpen);
            pill.setAttribute('aria-expanded', (!isOpen).toString());
          };
          pill.addEventListener('click', toggle);
          pill.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); } });
        }
      });

      document.addEventListener('click', function(e){
        const row = e.target.closest('#courseDetail .sections-row');
        if (!row) return;
        if (row.getAttribute('data-locked') === '1') { showNotification('Available for trainees only. Basic competencies are accessible.', 'info'); return; }
        const aid = row.getAttribute('data-activity-id');
        const atype = row.getAttribute('data-activity-type') || 'assignment';
        const titleEl = row.querySelector('.section-link');
        const atitle = titleEl ? titleEl.textContent : (row.getAttribute('data-activity-title') || 'Activity');
        if (aid) { try { openActivityModal(aid, atype, atitle); } catch (_) {} }
      });
      document.addEventListener('click', function(e){
        const link = e.target.closest('#courseDetail .section-link');
        if (!link) return;
        const row = link.closest('#courseDetail .sections-row');
        if (!row) return;
        const mkey = row.getAttribute('data-material-key');
        if (!mkey) return;
        const arr = getOpened();
        if (!arr.includes(mkey)) { arr.push(mkey); saveOpened(arr); }
        const statusCell = row.querySelector('.cell.sec-status');
        if (statusCell) { statusCell.textContent = 'Opened'; }
      });
    })
    .catch(() => { courseContentContainer.innerHTML = '<div class="error-message">Failed to load course content.</div>'; });
}

function resetDetailViews() {
  const activeCoursesContent = document.getElementById('enrolled-active-courses');
  const completedCoursesContent = document.getElementById('enrolled-completed-courses');
  const courseSwitchContainer = document.getElementById('courseSwitchContainer');
  const courseDetailView = document.getElementById('courseDetail');
  const backButton = document.getElementById('backToEnrolledCourses');
  if (courseDetailView) { courseDetailView.classList.add('hidden'); courseDetailView.style.display = 'none'; }
  if (backButton) backButton.classList.add('hidden');
  if (courseSwitchContainer) courseSwitchContainer.style.display = 'block';
  if (activeCoursesContent) { activeCoursesContent.style.display = 'block'; activeCoursesContent.classList.add('active'); }
  if (completedCoursesContent) { completedCoursesContent.style.display = 'none'; completedCoursesContent.classList.remove('active'); }
}

// Initialize listeners when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() {
    setupCourseDetailView();
  });
} else {
  setupCourseDetailView();
}
// Open a course detail using course code from the enrolled list
function openCourseByCode(code){
  const contentBtn = document.querySelector(`.view-course-content-btn[data-course-code="${code}"]`) || document.querySelector(`.view-course-content-btn[data-course="${code}"]`);
  if (!contentBtn) return false;
  const courseCode = contentBtn.getAttribute('data-course-code') || contentBtn.getAttribute('data-course');
  const courseName = contentBtn.getAttribute('data-course-name') || contentBtn.getAttribute('data-title') || '';
  const courseHours = contentBtn.getAttribute('data-course-hours') || '';
  const courseDescription = contentBtn.getAttribute('data-course-description') || '';
  const credited = contentBtn.getAttribute('data-credited-hours') || '';
  if (courseCode) { viewCourseDetails(courseCode, courseName, courseHours, courseDescription, credited); return true; }
  return false;
}

window.openCourseByCode = openCourseByCode;
