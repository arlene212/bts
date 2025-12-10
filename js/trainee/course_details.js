document.addEventListener('DOMContentLoaded', () => {
  const courseDetail = document.getElementById('courseDetail') || document.getElementById('course-detail');
  const backBtn = courseDetail ? courseDetail.querySelector('.back-btn') : null;
  const modulesPane = document.getElementById('modules-pane') || document.getElementById('modules-view');
  const activitiesPane = document.getElementById('activities-pane') || document.getElementById('activities-view');

  function openCourseDetail(card) {
    const courseCode = card.getAttribute('data-course-code') || card.getAttribute('data-course');
    const courseName = card.getAttribute('data-course-name') || card.getAttribute('data-title');
    const courseHours = card.getAttribute('data-course-hours');
    const courseDescription = card.getAttribute('data-course-description');
    const creditedHours = card.getAttribute('data-credited-hours');

    if (document.getElementById('course-detail-title')) document.getElementById('course-detail-title').textContent = courseName || 'Course Details';
    if (document.getElementById('course-detail-code')) document.getElementById('course-detail-code').textContent = `Code: ${courseCode || 'N/A'}`;
    if (document.getElementById('course-detail-hours')) document.getElementById('course-detail-hours').textContent = `Hours: ${courseHours || 'N/A'} hrs`;
    if (document.getElementById('course-detail-description')) document.getElementById('course-detail-description').textContent = courseDescription || '';
    if (document.getElementById('course-detail-credited-hours')) document.getElementById('course-detail-credited-hours').textContent = `Credited: ${creditedHours || '0'} hrs`;
    const basicChipInit = document.getElementById('course-detail-basic-hours');
    const commonChipInit = document.getElementById('course-detail-common-hours');
    const coreChipInit = document.getElementById('course-detail-core-hours');
    basicChipInit && (basicChipInit.textContent = 'Basic: —');
    commonChipInit && (commonChipInit.textContent = 'Common: —');
    coreChipInit && (coreChipInit.textContent = 'Core: —');

    if (courseDetail) {
      courseDetail.classList.remove('hidden');
      courseDetail.classList.add('active');
    }
    const courseBox = document.querySelector('#mycourses .course-box');
    if (courseBox) courseBox.classList.add('hidden');

    loadCourseDetails(courseCode, courseName, courseHours, courseDescription);
  }

  document.addEventListener('click', function(e) {
    const card = e.target.closest('.batch-card.view-course-content-btn') || e.target.closest('.course-card.view-course-content-btn');
    if (card) {
      openCourseDetail(card);
    }
  });

  backBtn && backBtn.addEventListener('click', () => {
    if (courseDetail) {
      courseDetail.classList.add('hidden');
      courseDetail.classList.remove('active');
    }
    const courseBox = document.querySelector('#mycourses .course-box');
    if (courseBox) courseBox.classList.remove('hidden');
    resetCourseDetailView();
  });

  function resetCourseDetailView() {
    if (modulesPane && activitiesPane) {
      modulesPane.classList.add('active');
      activitiesPane.classList.remove('active');
    }
    const tabs = document.getElementById('detailTabs');
    if (tabs) {
      const btns = tabs.querySelectorAll('.tab-btn');
      btns.forEach(b => b.classList.remove('active'));
      const first = btns[0];
      if (first) {
        first.classList.add('active');
        const indicator = tabs.querySelector('.tab-indicator');
        if (indicator) {
          const rect = first.getBoundingClientRect();
          const tabsRect = tabs.getBoundingClientRect();
          indicator.style.transform = `translateX(${rect.left - tabsRect.left}px)`;
          indicator.style.width = `${rect.width}px`;
        }
      }
    }
  }

  function loadCourseDetails(courseCode) {
    const competenciesCard = document.getElementById('competencies-card');
    if (competenciesCard) { competenciesCard.innerHTML = '<div>Loading competencies...</div>'; }

    fetch(`../php/get_course_details_trainee.php?course_code=${encodeURIComponent(courseCode)}`)
      .then(r => r.json())
      .then(data => {
        if (data.error) {
          if (competenciesCard) competenciesCard.innerHTML = `<div class="error-message">${data.error}</div>`;
          return;
        }
        if (competenciesCard) renderCompetenciesSummary(data.competencies || [], competenciesCard);
        const totalHours = (data.competencies || []).reduce((sum, c) => sum + (parseInt(c.hours || 0, 10) || 0), 0);
        const hoursChip = document.getElementById('course-detail-hours');
        if (hoursChip) hoursChip.textContent = `Hours: ${totalHours} hrs`;
        const byType = { basic: 0, common: 0, core: 0 };
        (data.competencies || []).forEach(c => {
          const t = String(c.type || '').toLowerCase();
          const h = parseInt(c.hours || 0, 10) || 0;
          if (t in byType) byType[t] += h;
        });
        const basicChip = document.getElementById('course-detail-basic-hours');
        const commonChip = document.getElementById('course-detail-common-hours');
        const coreChip = document.getElementById('course-detail-core-hours');
        basicChip && (basicChip.textContent = `Basic: ${byType.basic} hrs`);
        commonChip && (commonChip.textContent = `Common: ${byType.common} hrs`);
        coreChip && (coreChip.textContent = `Core: ${byType.core} hrs`);
        const modulesListEl = document.getElementById('modules-list');
        const activitiesListEl = document.getElementById('activities-list');
        if (modulesListEl) {
          renderModulesFromCompetencies(data.competencies || [], modulesListEl);
        }
        if (activitiesListEl) {
          const activitiesArr = Array.isArray(data.activities) ? data.activities : Object.values(data.activities || {});
          renderActivitiesInteractive(activitiesArr, activitiesListEl);
        }
        // no tab counts; content now in per-competency modal
      })
      .catch(() => {
        competenciesCard.innerHTML = '<div class="error-message">Failed to load competencies.</div>';
      });
  }

  function renderCompetenciesSummary(competencies, container) {
    container.innerHTML = '';
    if (!competencies || competencies.length === 0) {
      container.innerHTML = '<p>No competencies found for this course.</p>';
      return;
    }
    const types = ['basic', 'common', 'core'];
    types.forEach(type => {
      const group = competencies.filter(c => String(c.type || '').toLowerCase() === type);
      if (group.length) {
        const section = document.createElement('div');
        section.className = 'competency-section';
        const header = document.createElement('h3');
        header.className = 'competency-type-header';
        header.textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} Competencies`;
        section.appendChild(header);
        const list = document.createElement('div');
        list.className = 'competency-list';
        group.forEach(comp => {
          const el = document.createElement('div');
          el.className = 'competency-item';
          el.setAttribute('data-comp-id', comp.id || '');
          const topics = Array.isArray(comp.topics) ? comp.topics : [];
          el.innerHTML = `
            <div class="competency-header">
              <h4>${comp.name}</h4>
              <p>${comp.description || ''}</p>
            </div>
            <div class="competency-topics">
              ${topics.length ? topics.map(t => `
                <div class="topic-line">
                  <span class="topic-title">${t.topic_name || t.name || 'Unnamed Topic'}</span>
                  <span class="chip">Materials: ${(t.materials || []).length}</span>
                  <span class="chip">Activities: ${(t.activities || []).length}</span>
                </div>
              `).join('') : '<div class="empty-state">No topics for this competency.</div>'}
            </div>
          `;
          list.appendChild(el);
        });
        section.appendChild(list);
        container.appendChild(section);
      }
    });
  }

  function renderModulesFromCompetencies(competencies, container) {
    container.innerHTML = '';
    const byType = { basic: [], common: [], core: [] };
    (competencies || []).forEach(c => {
      const t = String(c.type || '').toLowerCase();
      if (t in byType) byType[t].push(c);
    });
    const total = Object.values(byType).reduce((sum, arr) => sum + arr.length, 0);
    if (!total) { container.innerHTML = '<p class="no-materials">No modules found for this course.</p>'; return; }
    container.classList.add('modules-ui');
    const groupOrder = ['basic', 'common', 'core'];
    container.innerHTML = groupOrder.map(type => {
      const comps = byType[type];
      if (!comps.length) return '';
      const title = type.charAt(0).toUpperCase() + type.slice(1) + ' Competencies';
      return `
        <div class="module-group" data-group="${type}">
          <div class="group-header">
            <h3>${title}</h3>
          </div>
          <div class="group-list">
            ${comps.map(c => moduleCardHtmlCompetency(c)).join('')}
          </div>
        </div>
      `;
    }).join('');
  }

  function moduleCardHtmlCompetency(comp) {
    const title = comp.module_title || comp.name || 'Unnamed Module';
    const sections = buildSectionsFromCompetency(comp);
    const sectionsCount = sections.length;
    const allActivities = (comp.topics || []).flatMap(t => t.activities || []);
    const submitted = allActivities.filter(a => !!a.submission).length;
    const progress = allActivities.length ? Math.round((submitted / allActivities.length) * 100) : 0;
    const isCompleted = progress === 100 && allActivities.length > 0;
    const imgSrc = '../images/school.png';
    return `
      <div class="module-card" data-expanded="false">
        <div class="module-header">
          <img class="module-thumb" src="${imgSrc}" alt="Module">
          <div class="module-title-area">
            <h4 class="module-title">${escapeHtml(title)}</h4>
            ${isCompleted ? `
              <div class="module-status"><span>Completed</span> <i class="fas fa-check text-success"></i></div>
            ` : `
              <div class="module-status"><i class="fas fa-circle-notch text-success"></i> <span>${progress}% Resume</span> <i class="fas fa-play"></i></div>
            `}
          </div>
          <button class="toggle-sections btn btn-outline-secondary" aria-expanded="false">
            <span class="sections-count">${sectionsCount} section${sectionsCount !== 1 ? 's' : ''}</span>
            <i class="fas fa-chevron-down"></i>
          </button>
        </div>
        <div class="module-sections hidden">
          ${sectionsTableHtml(sections)}
        </div>
      </div>
    `;
  }

  function moduleCardHtml(topic) {
    const imgSrc = topic.image || '../images/school.png';
    const title = topic.topic_name || topic.name || 'Unnamed Module';
    const sections = buildSections(topic);
    const acts = Array.isArray(topic.activities) ? topic.activities : [];
    const submitted = acts.filter(a => !!a.submission).length;
    const progress = acts.length ? Math.round((submitted / acts.length) * 100) : 0;
    const isCompleted = progress === 100 && acts.length > 0;
    const sectionsCount = sections.length;
    return `
      <div class="module-card" data-expanded="false">
        <div class="module-header">
          <img class="module-thumb" src="${imgSrc}" alt="Module">
          <div class="module-title-area">
            <h4 class="module-title">${escapeHtml(title)}</h4>
            ${isCompleted ? `
              <div class="module-status"><span>Completed</span> <i class="fas fa-check text-success"></i></div>
            ` : `
              <div class="module-status"><i class="fas fa-circle-notch text-success"></i> <span>${progress}% Resume</span> <i class="fas fa-play"></i></div>
            `}
          </div>
          <button class="toggle-sections btn btn-outline-secondary" aria-expanded="false">
            <span class="sections-count">${sectionsCount} section${sectionsCount !== 1 ? 's' : ''}</span>
            <i class="fas fa-chevron-down"></i>
          </button>
        </div>
        <div class="module-sections hidden">
          ${sectionsTableHtml(sections)}
        </div>
      </div>
    `;
  }

  function buildSections(topic) {
    const mats = (topic.materials || []).map(m => ({
      icon: 'fa-file',
      title: m.material_title || m.title || 'Material',
      submitted: null,
      score: null,
      max: null,
      due: null,
      status: false,
      type: 'material',
      id: m.id,
      path: m.file_path
    }));
    const acts = (topic.activities || []).map(a => ({
      icon: 'fa-folder-open',
      title: a.activity_title || a.title || 'Activity',
      submitted: !!a.submission,
      score: a.submission && a.submission.score !== null ? a.submission.score : null,
      max: a.max_score || null,
      due: a.due_date || null,
      status: a.submission ? true : false,
      type: 'activity',
      id: a.id
    }));
    return [...mats, ...acts];
  }

  function materialKey(idOrPath) {
    return 'material_opened_' + String(idOrPath || 'unknown');
  }
  function isMaterialOpened(idOrPath) {
    try { return localStorage.getItem(materialKey(idOrPath)) === '1'; } catch (e) { return false; }
  }
  function markMaterialOpened(idOrPath) {
    try { localStorage.setItem(materialKey(idOrPath), '1'); } catch (e) {}
  }

  function sectionsTableHtml(sections) {
    if (!sections || sections.length === 0) {
      return `<div class="sections-table"><div class="sections-header"><div>Section</div><div>Submitted</div><div>Score</div><div>Due</div><div>Status</div></div><div class="sections-row"><div class="sec-cell sec-title"><span>No sections available.</span></div><div class="sec-cell">—</div><div class="sec-cell">—</div><div class="sec-cell">—</div><div class="sec-cell">—</div></div></div>`;
    }
    return `
      <div class="sections-table">
        <div class="sections-header">
          <div>Section</div>
          <div>Submitted</div>
          <div>Score</div>
          <div>Due</div>
          <div>Status</div>
        </div>
        ${sections.map(s => {
          const isLink = s.type === 'material' && s.path && (String(s.path).startsWith('http://') || String(s.path).startsWith('https://'));
          const href = s.type === 'material' ? (isLink ? s.path : (s.path ? `../uploads/courses/${s.path}` : '')) : '';
          const linkOpen = href ? `<a href="${href}" target="_blank" class="section-link">` : '';
          const linkClose = href ? `</a>` : '';

          // Compute display values based on type and state
          let submittedDisplay = '—';
          let scoreDisplay = '—';
          let dueDisplay = '—';
          let statusDisplay = '-';

          if (s.type === 'material') {
            const opened = isMaterialOpened(s.id || s.path);
            statusDisplay = opened ? '<i class="fas fa-check text-success"></i>' : '-';
          } else if (s.type === 'activity') {
            const now = new Date();
            const due = s.due ? new Date(String(s.due).replace(' ', 'T')) : null;
            const missed = !s.submitted && !!due && now > due;
            const pendingScore = !!s.submitted && s.score === null;

            // Submitted column rules
            if (missed) {
              submittedDisplay = '0';
            } else if (pendingScore) {
              submittedDisplay = '~';
            } else if (s.submitted) {
              submittedDisplay = '<i class="fas fa-check text-success"></i>';
            } else {
              submittedDisplay = '<i class="fas fa-flag"></i>';
            }

            // Score column rules
            if (missed) {
              scoreDisplay = 'M';
            } else if (pendingScore) {
              scoreDisplay = '~';
            } else {
              scoreDisplay = (s.score !== null && s.max !== null) ? `${s.score}/${s.max}` : '—';
            }

            // Due column shows actual due date/time
            dueDisplay = s.due || '—';

            // Status: check if submitted, flag if not
            statusDisplay = s.submitted ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-flag"></i>';
          }
          return `
          <div class="sections-row" data-resource-type="${s.type || ''}" data-resource-id="${s.id || ''}">
            <div class="sec-cell sec-title">${linkOpen}<i class="fas ${s.icon}"></i> <span>${escapeHtml(s.title)}</span>${linkClose}</div>
            <div class="sec-cell">${submittedDisplay}</div>
            <div class="sec-cell">${scoreDisplay}</div>
            <div class="sec-cell">${dueDisplay}</div>
            <div class="sec-cell">${statusDisplay}</div>
          </div>
        `}).join('')}
      </div>
    `;
  }

  document.getElementById('modules-pane')?.addEventListener('click', function(e){
    const toggle = e.target.closest('.toggle-sections');
    if (toggle) {
      const card = toggle.closest('.module-card');
      const sec = card.querySelector('.module-sections');
      const expanded = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!expanded));
      sec.classList.toggle('hidden');
      const icon = toggle.querySelector('i');
      if (icon) { icon.classList.toggle('fa-chevron-down'); icon.classList.toggle('fa-chevron-up'); }
    }
    const rowLink = e.target.closest('.sections-row .section-link');
    if (rowLink) {
      const row = rowLink.closest('.sections-row');
      const type = row?.getAttribute('data-resource-type');
      const id = row?.getAttribute('data-resource-id');
      if (type === 'material') {
        markMaterialOpened(id || rowLink.getAttribute('href'));
        const statusCell = row.querySelector('.sec-cell:nth-child(5)');
        if (statusCell) statusCell.innerHTML = '<i class="fas fa-check text-success"></i>';
      }
      return;
    }
    const row = e.target.closest('.sections-row');
    if (row) {
      const type = row.getAttribute('data-resource-type');
      const id = row.getAttribute('data-resource-id');
      if (type === 'material') {
        // Try to find the material path in the inner link if present
        const linkEl = row.querySelector('.section-link');
        const href = linkEl?.getAttribute('href');
        if (href) {
          window.open(href, '_blank');
          markMaterialOpened(id || href);
          const statusCell = row.querySelector('.sec-cell:nth-child(5)');
          if (statusCell) statusCell.innerHTML = '<i class="fas fa-check text-success"></i>';
          return;
        }
      }
      if (type === 'activity' && id) {
        const tabLink = document.querySelector(`.tab-link[data-tab="activities-summary"]`);
        if (tabLink) { tabLink.click(); }
        let tries = 0;
        const tryHighlight = function(){
          const selector = `#summaryTableBody a[href*="activity_view.php?id=${CSS.escape(id)}"]`;
          const link = document.querySelector(selector);
          if (link) {
            const row = link.closest('tr');
            if (row) {
              row.classList.add('row-highlight');
              try { row.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) {}
              setTimeout(() => row.classList.remove('row-highlight'), 2000);
            }
            return;
          }
          if (tries < 10) { tries++; setTimeout(tryHighlight, 200); }
        };
        setTimeout(tryHighlight, 250);
      }
    }
  });

  if (typeof escapeHtml !== 'function') {
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text == null ? '' : String(text);
      return div.innerHTML;
    }
  }

  function renderMaterials(materials) {
    if (!materials || materials.length === 0) return '<p class="no-materials">No materials for this topic.</p>';
    let html = '<div class="materials-list">';
    html += materials.map(m => {
      const path = m.material_file_path || m.file_path;
      const isLink = path && (String(path).startsWith('http://') || String(path).startsWith('https://'));
      const href = isLink ? path : `../uploads/courses/${path}`;
      return `
        <div class="material-item">
          <i class="fas fa-file-alt"></i>
          <div class="material-info">
            <strong>${m.material_title || m.title || 'Unnamed Material'}</strong>
            <p>${m.material_description || ''}</p>
            ${path ? `<a href="${href}" target="_blank">View Material</a>` : ''}
          </div>
        </div>
      `;
    }).join('');
    html += '</div>';
    return html;
  }

  function renderActivitiesInteractive(activities, container) {
    if (!activities || activities.length === 0) { container.innerHTML = '<p>No activities found for this course.</p>'; return; }
    const rows = activities.map(a => {
      const s = a.submission;
      const now = new Date();
      const due = a.due_date ? new Date(String(a.due_date).replace(' ', 'T')) : null;
      const missed = !s && !!due && now > due;
      const pendingScore = !!s && s.score === null;
      let submittedDisplay = '—';
      let scoreDisplay = '—';
      let dueDisplay = a.due_date || '—';
      let statusDisplay = '-';
      if (missed) {
        submittedDisplay = '0';
        scoreDisplay = 'M';
        statusDisplay = '<i class="fas fa-flag"></i>';
      } else if (pendingScore) {
        submittedDisplay = '~';
        scoreDisplay = '~';
        statusDisplay = '<i class="fas fa-check text-success"></i>';
      } else if (s) {
        submittedDisplay = '<i class="fas fa-check text-success"></i>';
        scoreDisplay = (s.score !== null && a.max_score !== null) ? `${s.score}/${a.max_score}` : '—';
        statusDisplay = '<i class="fas fa-check text-success"></i>';
      } else {
        submittedDisplay = '<i class="fas fa-flag"></i>';
        statusDisplay = '<i class="fas fa-flag"></i>';
      }
      const title = a.activity_title || a.title || 'Activity';
      return `
        <div class="sections-row" data-resource-type="activity" data-resource-id="${a.id}">
          <div class="sec-cell sec-title"><i class="fas fa-folder-open"></i> <span>${escapeHtml(title)}</span></div>
          <div class="sec-cell">${submittedDisplay}</div>
          <div class="sec-cell">${scoreDisplay}</div>
          <div class="sec-cell">${dueDisplay}</div>
          <div class="sec-cell">${statusDisplay}</div>
        </div>
      `;
    }).join('');
    container.innerHTML = `
      <div class="sections-table">
        <div class="sections-header">
          <div>Section</div>
          <div>Submitted</div>
          <div>Score</div>
          <div>Due</div>
          <div>Status</div>
        </div>
        ${rows}
      </div>
    `;
  }

  function renderSubmissionHistory(activity) {
    const s = activity.submission;
    return `
      <div class="submission-history">
        <h4><i class="fas fa-history"></i> Your Submission</h4>
        <div class="history-content">
          <div class="history-item">
            <div class="history-meta">
              <span>Submitted on: ${formatDisplayDate(s.date)}</span>
              <span class="status-badge ${s.score !== null ? 'graded' : 'submitted'}">${s.score !== null ? 'Graded' : 'Submitted'}</span>
            </div>
            ${s.file ? `<div class="submission-file"><i class="fas fa-paperclip"></i> <a href="../uploads/submissions/${s.file}" target="_blank">${String(s.file).split('/').pop()}</a></div>` : ''}
            ${s.score !== null ? `<div class="submission-score"><strong>Score:</strong> ${s.score} / ${activity.max_score}</div>` : ''}
            ${s.feedback ? `<div class="submission-feedback"><strong>Trainer Feedback:</strong> <p>${s.feedback.replace(/\n/g, '<br>')}</p></div>` : ''}
          </div>
        </div>
      </div>
    `;
  }

  function renderSubmissionForm(activity) {
    return `
      <div class="upload-section">
        <h4><i class="fas fa-upload"></i> Submit Your Work</h4>
        <textarea class="submission-comment" placeholder="Add a comment (optional)..."></textarea>
        <div class="upload-area"><i class="fas fa-cloud-upload-alt"></i><p>Click to upload or drag and drop a file</p><input type="file" class="activity-file-input" style="display:none;"></div>
        <div class="file-preview hidden"></div>
        <button class="submit-btn" disabled>Submit Assignment</button>
      </div>
    `;
  }

  function renderActivitiesTable(activities, container) {
    if (!activities || activities.length === 0) { container.innerHTML = '<p>No activities found for this course.</p>'; return; }
    let html = `
      <table class="activities-table"><thead><tr><th>Title</th><th>Date Given</th><th>Due Date</th><th>Status</th><th>Score</th></tr></thead><tbody>`;
    activities.forEach(a => {
      const s = a.submission;
      const status = s ? (s.score !== null ? 'Graded' : 'Submitted') : 'Not Submitted';
      const statusClass = s ? (s.score !== null ? 'graded' : 'submitted') : 'not-submitted';
      const score = s && s.score !== null ? `${s.score} / ${a.max_score}` : '—';
      html += `<tr><td>${a.activity_title || a.title || 'Unnamed Activity'}</td><td>${formatDisplayDate(a.start_date)}</td><td>${formatDisplayDate(a.due_date)}</td><td><span class="status-badge ${statusClass}">${status}</span></td><td>${score}</td></tr>`;
    });
    html += '</tbody></table>';
    container.innerHTML = html;
  }

  document.getElementById('activities-pane')?.addEventListener('click', function(e) {
    const header = e.target.closest('.activity-header');
    if (header) {
      const content = header.nextElementSibling;
      content.classList.toggle('hidden');
    }
    const uploadArea = e.target.closest('.upload-area');
    if (uploadArea) {
      uploadArea.querySelector('.activity-file-input').click();
    }
    const removeBtn = e.target.closest('.remove-file-btn');
    if (removeBtn) {
      const item = removeBtn.closest('.topic-activity-item');
      const fileInput = item.querySelector('.activity-file-input');
      const filePreview = item.querySelector('.file-preview');
      const submitBtn = item.querySelector('.submit-btn');
      fileInput.value = '';
      filePreview.classList.add('hidden');
      filePreview.innerHTML = '';
      submitBtn.disabled = true;
    }
    const submitBtn = e.target.closest('.submit-btn');
    if (submitBtn) {
      const item = submitBtn.closest('.topic-activity-item');
      const activityId = item.dataset.activityId;
      const fileInput = item.querySelector('.activity-file-input');
      const comment = item.querySelector('.submission-comment').value;
      if (!fileInput.files[0]) { alert('Please select a file to submit.'); return; }
      const fd = new FormData();
      fd.append('activity_id', activityId);
      fd.append('submission_file', fileInput.files[0]);
      fd.append('comment', comment);
      submitBtn.disabled = true;
      submitBtn.textContent = 'Submitting...';
      fetch('../php/submit_activity.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            alert('Submission successful!');
            const code = (document.getElementById('course-detail-code')?.textContent || '').replace('Code: ', '');
            if (code) loadCourseDetails(code);
          } else {
            alert('Submission failed: ' + (data.error || 'Unknown error'));
            submitBtn.disabled = false; submitBtn.textContent = 'Submit Assignment';
          }
        })
        .catch(() => { alert('An error occurred during submission.'); submitBtn.disabled = false; submitBtn.textContent = 'Submit Assignment'; });
    }
  });

  document.getElementById('activities-pane')?.addEventListener('change', function(e) {
    if (e.target.classList.contains('activity-file-input')) {
      const file = e.target.files[0];
      const item = e.target.closest('.topic-activity-item');
      const filePreview = item.querySelector('.file-preview');
      const submitBtn = item.querySelector('.submit-btn');
      if (file) {
        filePreview.innerHTML = `
          <div class="file-preview-item"><i class="fas fa-file-alt"></i><div class="file-details"><strong>${file.name}</strong><span>(${(file.size/1024/1024).toFixed(2)} MB)</span></div><button class="remove-file-btn">&times;</button></div>
        `;
        filePreview.classList.remove('hidden');
        submitBtn.disabled = false;
      } else {
        filePreview.classList.add('hidden'); filePreview.innerHTML=''; submitBtn.disabled = true;
      }
    }
  });

  function formatDisplayDate(dateString) {
    if (!dateString) return 'N/A';
    const d = new Date(String(dateString).replace(' ', 'T'));
    if (isNaN(d.getTime())) return 'Invalid Date';
    return d.toLocaleString('en-US', { year:'numeric', month:'long', day:'numeric', hour:'2-digit', minute:'2-digit' });
  }

  // Tab switching functionality for course detail
  function initializeTabSwitching() {
    const switchContainer = courseDetail?.querySelector('.course-switch');
    if (!switchContainer) return;

    const switchButtons = switchContainer.querySelectorAll('.switch-btn');
    const switchInner = courseDetail?.querySelector('#courseDetailSwitchInner');

    switchButtons.forEach(button => {
      button.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const targetTab = this.getAttribute('data-tab');

        switchButtons.forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');

        const buttonIndex = Array.from(switchButtons).indexOf(this);
        const buttonWidth = 100 / switchButtons.length;
        if (switchInner) {
          switchInner.style.transform = `translateX(${buttonIndex * 100}%)`;
          switchInner.style.width = `${buttonWidth}%`;
        }

        const modulesView = modulesPane;
        const activitiesView = activitiesPane;

        if (targetTab === 'modules-view') {
          if (modulesView) {
            modulesView.classList.add('active');
            modulesView.style.display = 'block';
          }
          if (activitiesView) {
            activitiesView.classList.remove('active');
            activitiesView.style.display = 'none';
          }
        } else if (targetTab === 'activities-view') {
          if (modulesView) {
            modulesView.classList.remove('active');
            modulesView.style.display = 'none';
          }
          if (activitiesView) {
            activitiesView.classList.add('active');
            activitiesView.style.display = 'block';
          }
        }
      });
    });
  }

  // Initialize tab switching when DOM is ready
  initializeTabSwitching();

  const modulesSearch = document.getElementById('modules-search');
  const activitiesSearch = document.getElementById('activities-search');
  const modulesList = document.getElementById('modules-list');
  const activitiesList = document.getElementById('activities-list');
  function attachFilter(input, list, selectors) {
    if (!input || !list) return;
    input.addEventListener('input', function(){
      const q = this.value.toLowerCase();
      list.querySelectorAll(selectors).forEach(el => {
        const t = el.textContent.toLowerCase();
        el.style.display = t.includes(q) ? '' : 'none';
      });
      const modulesCountEl = document.getElementById('modules-count');
      const activitiesCountEl = document.getElementById('activities-count');
      modulesCountEl && (modulesCountEl.textContent = (modulesList?.querySelectorAll('.module-card:not([style*="display: none"])')?.length || 0));
      activitiesCountEl && (activitiesCountEl.textContent = (activitiesList?.querySelectorAll('.activity-item:not([style*="display: none"]), .topic-activity-item:not([style*="display: none"])')?.length || 0));
    });
  }
  attachFilter(modulesSearch, modulesList, '.module-card');
  attachFilter(activitiesSearch, activitiesList, '.activity-item, .topic-activity-item');
  function openCompetencyModal(comp) {
    const modal = document.getElementById('competencyModal');
    const title = document.getElementById('competencyModalTitle');
    const typeChip = document.getElementById('competencyModalType');
    const hoursChip = document.getElementById('competencyModalHours');
    const modulesList = document.getElementById('competencyModulesList');
    const activitiesList = document.getElementById('competencyActivitiesList');
    if (!modal || !title || !typeChip || !hoursChip || !modulesList || !activitiesList) return;

    try {
      title.textContent = comp.name || 'Competency Details';
      typeChip.textContent = `Type: ${String(comp.type || '').charAt(0).toUpperCase() + String(comp.type || '').slice(1)}`;
      hoursChip.textContent = `Hours: ${(parseInt(comp.hours || 0, 10) || 0)} hrs`;

      const topics = (comp.topics || []);
      const compMaterials = Array.isArray(comp.materials) ? comp.materials : [];
      let modulesHtml = '';
      if (compMaterials.length) {
        modulesHtml += `
          <div class="topic-container">
            <h4 class="topic-title">Competency Materials</h4>
            <div class="topic-content-section">
              ${renderMaterials(compMaterials)}
            </div>
          </div>
        `;
      }
      if (!topics.length) {
        modulesHtml += '<div class="empty-state">No modules found for this competency.</div>';
      } else {
        modulesHtml += topics.map(t => `
          <div class="topic-container">
            <h4 class="topic-title">${t.topic_name || t.name || 'Unnamed Topic'}</h4>
            <div class="topic-content-section">
              <h6 class="content-divider">Materials</h6>
              ${renderMaterials(t.materials || [])}
            </div>
          </div>
        `).join('');
      }
      modulesList.innerHTML = modulesHtml;

      const activities = topics.flatMap(t => t.activities || []);
      if (!activities.length) {
        activitiesList.innerHTML = '<p>No activities assigned for this competency.</p>';
      } else {
        let html = '<div class="activities-list">';
        activities.forEach(a => {
          html += `
            <div class="topic-activity-item" data-activity-id="${a.id}">
              <div class="activity-header">
                <div class="activity-info-cleaned">
                  <strong>${a.activity_title || a.title || 'Unnamed Activity'}</strong>
                  <p>Date Given: ${formatDisplayDate(a.start_date)}</p>
                  <p>Due: ${formatDisplayDate(a.due_date)}</p>
                </div>
                <div class="activity-actions">
                  <a href="../trainee/activity_view.php?id=${a.id}" target="_blank" class="btn btn-outline-primary view-activity-btn">View Activity</a>
                </div>
              </div>
              <div class="activity-content hidden">
                <div class="activity-instructions"><h4><i class="fas fa-info-circle"></i> Instructions</h4><p>${a.activity_description || a.description || 'No instructions provided.'}</p></div>
              </div>
            </div>
          `;
        });
        html += '</div>';
        activitiesList.innerHTML = html;
      }
    } catch (err) {
      modulesList.innerHTML = '<div class="error-message">Failed to render modules.</div>';
      activitiesList.innerHTML = '<div class="error-message">Failed to render activities.</div>';
    }

    if (typeof openModal === 'function') openModal(modal); else { modal.classList.remove('hidden'); modal.style.display = 'flex'; }
  }

  document.getElementById('closeCompetencyModal')?.addEventListener('click', function(){
    const m = document.getElementById('competencyModal');
    if (typeof closeModal === 'function') closeModal(m); else { m.classList.add('hidden'); m.style.display = 'none'; }
  });
});
  function buildSectionsFromCompetency(comp) {
    const topics = Array.isArray(comp.topics) ? comp.topics : [];
    const compMaterials = Array.isArray(comp.materials) ? comp.materials : [];
    const mats = topics.flatMap(t => (t.materials || []).map(m => ({
      icon: 'fa-folder',
      title: t.topic_name || t.name || m.material_title || m.title || 'Material',
      submitted: null,
      score: null,
      max: null,
      due: null,
      status: false,
      type: 'material',
      id: m.id,
      path: m.file_path
    })));
    const compMats = compMaterials.map(m => ({
      icon: 'fa-file',
      title: m.title || 'Material',
      submitted: null,
      score: null,
      max: null,
      due: null,
      status: false,
      type: 'material',
      id: m.id,
      path: m.file_path
    }));
    const acts = topics.flatMap(t => (t.activities || []).map(a => ({
      icon: 'fa-folder',
      title: a.activity_title || a.title || 'Activity',
      submitted: !!a.submission,
      score: a.submission && a.submission.score !== null ? a.submission.score : null,
      max: a.max_score || null,
      due: a.due_date || null,
      status: a.submission ? true : false,
      type: 'activity',
      id: a.id
    })));
    return [...compMats, ...mats, ...acts];
  }
