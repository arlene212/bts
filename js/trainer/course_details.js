document.addEventListener('DOMContentLoaded', () => {
  let currentBatchName = '';
  const enrolledTab = document.getElementById('enrolled');
  const courseDetail = document.getElementById('course-detail');
  const courseBackBtn = courseDetail ? courseDetail.querySelector('.back-btn') : null;

  function openCourseDetailFromCard(batch){
    const courseName = batch.dataset.course;
    const courseCode = batch.dataset.code;
    const courseHours = batch.dataset.hours;
    populateTrainerBatches(courseCode).then(() => {
      if (!currentBatchName) {
        const sel = document.getElementById('trainer-batch-select');
        currentBatchName = sel && sel.value ? sel.value : '';
      }
      if (!currentBatchName) {
        const comps = document.getElementById('competencies-list');
        const subs = document.getElementById('submissions-list');
        const cd = document.getElementById('course-description');
        if (cd) cd.textContent = 'No batches assigned to you for this course';
        if (comps) comps.innerHTML = '<div class="error-message">No batches assigned to you for this course</div>';
        if (subs) subs.innerHTML = '<div class="error-message">No batches assigned to you for this course</div>';
        return;
      }
      loadCourseDetails(courseCode, courseName, courseHours);
    });
    enrolledTab && enrolledTab.classList.add('hidden');
    courseDetail && courseDetail.classList.remove('hidden');
    if (courseDetail && courseDetail.style) courseDetail.style.display = 'flex';
  }

  document.querySelectorAll('#enrolled .batch-card').forEach(batch => {
    batch.addEventListener('click', () => openCourseDetailFromCard(batch));
  });

  document.addEventListener('click', function(e){
    const batch = e.target.closest('#enrolled .batch-card');
    if (batch) {
      e.preventDefault();
      openCourseDetailFromCard(batch);
    }
  });

  const toggleWrapper = document.querySelector('.course-detail-toggle');
  const initialSubView = document.getElementById('submissions-view');
  initialSubView && initialSubView.classList.add('hidden');
  if (toggleWrapper) {
    toggleWrapper.addEventListener('click', (e) => {
      const btn = e.target.closest('.switch-btn');
      if (!btn) return;
      const view = btn.dataset.view;
      const switchOval = toggleWrapper.querySelector('.switch-oval');
      toggleWrapper.querySelectorAll('.switch-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const matView = document.getElementById('view-material-view');
      const subView = document.getElementById('submissions-view');
      if (view === 'view-material') {
        matView && matView.classList.remove('hidden');
        subView && subView.classList.add('hidden');
        switchOval && switchOval.classList.remove('news-active');
      } else if (view === 'submissions') {
        matView && matView.classList.add('hidden');
        subView && subView.classList.remove('hidden');
        switchOval && switchOval.classList.add('news-active');
      }
    });
  }

  courseBackBtn && courseBackBtn.addEventListener('click', () => {
    courseDetail && courseDetail.classList.add('hidden');
    enrolledTab && enrolledTab.classList.remove('hidden');
    document.body.classList.remove('modal-open');
  });

  courseDetail && courseDetail.addEventListener('click', (e) => {
    if (e.target === courseDetail) {
      courseDetail.classList.add('hidden');
      enrolledTab && enrolledTab.classList.remove('hidden');
      document.body.classList.remove('modal-open');
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && courseDetail && !courseDetail.classList.contains('hidden')) {
      courseDetail.classList.add('hidden');
      enrolledTab && enrolledTab.classList.remove('hidden');
      document.body.classList.remove('modal-open');
    }
  });

  function loadCourseDetails(courseCode, courseName, courseHours) {
    if (document.getElementById('course-detail-title')) document.getElementById('course-detail-title').textContent = courseName || '';
    if (document.getElementById('course-code')) document.getElementById('course-code').textContent = courseCode || '';
    if (document.getElementById('course-hours')) document.getElementById('course-hours').textContent = courseHours || '';
    const comps = document.getElementById('competencies-list');
    const subs = document.getElementById('submissions-list');
    if (comps) comps.innerHTML = '<div class="loading">Loading...</div>';
    if (subs) subs.innerHTML = '<div class="loading">Loading...</div>';

    const bn = currentBatchName || (document.getElementById('trainer-batch-select')?.value || '');
    if (!bn) {
      const cd = document.getElementById('course-description');
      if (cd) cd.textContent = 'No batches assigned to you for this course';
      const comps = document.getElementById('competencies-list');
      const subs = document.getElementById('submissions-list');
      if (comps) comps.innerHTML = '<div class="error-message">No batches assigned to you for this course</div>';
      if (subs) subs.innerHTML = '<div class="error-message">No batches assigned to you for this course</div>';
      return;
    }
    fetch(`../php/get_course_details_trainer.php?course_code=${encodeURIComponent(courseCode)}&batch_name=${encodeURIComponent(bn)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json())
      .then(data => {
        if (data.error) throw new Error(data.error);
        const cd = document.getElementById('course-description');
        if (cd) cd.textContent = (data.course && data.course.description) ? data.course.description : 'No description available';
        currentBatchName = data.selectedBatch || bn;
        const competencies = (data.competencies || []).map(c => ({
          id: c.id,
          code: c.competency_code,
          name: c.competency_name,
          type: c.competency_type,
          description: c.description,
          topics: (data.topicsByCompetency && data.topicsByCompetency[c.id]) ? data.topicsByCompetency[c.id] : (c.topics || [])
        }));
        const materialsByComp = data.materialsByCompetency || {};
        window.__trainerCourseData = { courseCode, batchName: data.selectedBatch, competencies, topicsByCompetency: data.topicsByCompetency || {} };
        renderCompetencies(competencies, materialsByComp);
        bindActionButtons();
        renderSubmissionsView(data.topicsByCompetency || {});
      })
      .catch(err => {
        const cde = document.getElementById('course-description');
        if (cde) cde.textContent = 'Error loading course details';
        if (comps) comps.innerHTML = `<div class="error-message">${err.message}</div>`;
        if (subs) subs.innerHTML = `<div class="error-message">${err.message}</div>`;
      });
  }
  // expose for callbacks defined outside this scope
  window.loadCourseDetails = loadCourseDetails;

  function bindActionButtons() {
    const root = document.getElementById('competencies-list');
    if (!root) return;
    root.querySelectorAll('.edit-comp-material-btn').forEach(btn => {
      if (btn.__boundEditComp) return; btn.__boundEditComp = true;
      btn.addEventListener('click', () => {
        openEditMaterialModal({ id: btn.dataset.materialId, title: btn.dataset.materialTitle, description: btn.dataset.materialDescription, path: btn.dataset.materialPath }, 'course');
      });
    });
    root.querySelectorAll('.delete-comp-material-btn').forEach(btn => {
      if (btn.__boundDeleteComp) return; btn.__boundDeleteComp = true;
      btn.addEventListener('click', () => {
        const id = btn.dataset.materialId;
        openConfirm('Confirm Delete', 'Delete course material: ' + (btn.dataset.materialTitle || '') + '?', () => {
          const bn = document.getElementById('trainer-batch-select')?.value || currentBatchName;
          if (!bn) { openConfirm('Error', 'Please select a batch first', () => {}, { confirmText: 'OK', showCancel: false }); return; }
          fetch('../php/delete_course_material.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ material_id: id, batch_name: bn }) })
            .then(r => r.json())
            .then(d => {
              if (d.success) {
                openConfirm('Confirm Action', 'Course material deleted.', () => {
                  const code = document.getElementById('course-code')?.textContent || '';
                  const name = document.getElementById('course-detail-title')?.textContent || '';
                  const hours = document.getElementById('course-hours')?.textContent || '';
                  loadCourseDetails(code, name, hours);
                }, { confirmText: 'Confirm' });
              } else {
                openConfirm('Error', 'Delete failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false });
              }
            })
            .catch(() => openConfirm('Error', 'Network error while deleting course material', () => {}, { confirmText: 'OK', showCancel: false }));
        }, { confirmText: 'Delete' });
      });
    });
    root.querySelectorAll('.edit-material-btn').forEach(btn => {
      if (btn.__boundEdit) return; btn.__boundEdit = true;
      btn.addEventListener('click', () => {
        openEditMaterialModal({ id: btn.dataset.materialId, title: btn.dataset.materialTitle, description: btn.dataset.materialDescription, path: btn.dataset.materialPath });
      });
    });
    root.querySelectorAll('.delete-material-btn').forEach(btn => {
      if (btn.__boundDelete) return; btn.__boundDelete = true;
      btn.addEventListener('click', () => {
        const id = btn.dataset.materialId;
        openConfirm('Confirm Delete', 'Delete material: ' + (btn.dataset.materialTitle || '') + '?', () => {
          const bn = document.getElementById('trainer-batch-select')?.value || currentBatchName;
          if (!bn) { openConfirm('Error', 'Please select a batch first', () => {}, { confirmText: 'OK', showCancel: false }); return; }
          fetch('../php/delete_material.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ material_id: id, batch_name: bn }) })
            .then(r => r.json())
            .then(d => {
              if (d.success) {
                openConfirm('Confirm Action', 'Material deleted.', () => {
                  const code = document.getElementById('course-code')?.textContent || '';
                  const name = document.getElementById('course-detail-title')?.textContent || '';
                  const hours = document.getElementById('course-hours')?.textContent || '';
                  loadCourseDetails(code, name, hours);
                }, { confirmText: 'Confirm' });
              } else {
                openConfirm('Error', 'Delete failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false });
              }
            })
            .catch(() => openConfirm('Error', 'Network error while deleting material', () => {}, { confirmText: 'OK', showCancel: false }));
        }, { confirmText: 'Delete' });
      });
    });
    root.querySelectorAll('.edit-activity-btn').forEach(btn => {
      if (btn.__boundEditA) return; btn.__boundEditA = true;
      btn.addEventListener('click', () => {
        openEditActivityModal({ id: btn.dataset.activityId, title: btn.dataset.activityTitle, description: btn.dataset.activityDescription, due: btn.dataset.dueDate, max: btn.dataset.maxScore });
      });
    });
    root.querySelectorAll('.delete-activity-btn').forEach(btn => {
      if (btn.__boundDeleteA) return; btn.__boundDeleteA = true;
      btn.addEventListener('click', () => {
        const id = btn.dataset.activityId;
        openConfirm('Confirm Delete', 'Delete activity: ' + (btn.dataset.activityTitle || '') + '?', () => {
          const bn = document.getElementById('trainer-batch-select')?.value || currentBatchName;
          if (!bn) { openConfirm('Error', 'Please select a batch first', () => {}, { confirmText: 'OK', showCancel: false }); return; }
          fetch('../php/delete_activity.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ activity_id: id, batch_name: bn }) })
            .then(r => r.json())
            .then(d => {
              if (d.success) {
                openConfirm('Confirm Action', 'Activity deleted.', () => {
                  const code = document.getElementById('course-code')?.textContent || '';
                  const name = document.getElementById('course-detail-title')?.textContent || '';
                  const hours = document.getElementById('course-hours')?.textContent || '';
                  loadCourseDetails(code, name, hours);
                }, { confirmText: 'Confirm' });
              } else {
                openConfirm('Error', 'Delete failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false });
              }
            })
            .catch(() => openConfirm('Error', 'Network error while deleting activity', () => {}, { confirmText: 'OK', showCancel: false }));
        }, { confirmText: 'Delete' });
      });
    });
  }

  function renderCompetencies(competencies, materialsByComp) {
    const container = document.getElementById('competencies-list');
    if (!container) return;
    container.innerHTML = '';
    if (!competencies.length) {
      container.innerHTML = '<div class="no-competencies"><p>No competencies defined.</p></div>';
      return;
    }
    competencies.forEach(comp => {
      const el = document.createElement('div');
      el.className = 'competency-item';
      el.innerHTML = `
        <div class="competency-header">
          <div class="competency-title">
            <h4><span class="competency-type ${comp.type}">${String(comp.type).toUpperCase()}</span> ${comp.name}</h4>
            <div class="competency-actions">
              <button type="button" class="btn btn-outline-primary btn-sm add-topic-btn" data-competency-id="${comp.id}" data-competency-name="${comp.name}"><i class="fas fa-plus"></i> Add Topic</button>
            </div>
          </div>
          ${comp.description ? `<p class="competency-description">${comp.description}</p>` : ''}
        </div>
        <div class="competency-materials">
          <h6 class="content-divider">Competency Materials</h6>
          ${renderCompMaterials(materialsByComp[comp.id] || [])}
        </div>
        <div class="topics-list">${renderTopics(comp.topics || [])}</div>
      `;
      container.appendChild(el);
    });
  }

  function renderCompMaterials(materials) {
    if (!materials.length) return '<p class="no-materials">No competency-level materials.</p>';
    return `<div class="materials-list">${materials.map(m => {
      const href = m.file_path && (String(m.file_path).startsWith('http') ? m.file_path : ('../uploads/courses/' + m.file_path));
      return `
        <div class="material-item">
          <div class="material-details">
            <div class="material-info">
              <strong>${m.title}</strong>
              <p>${m.content || ''}</p>
              ${href ? `<small><a href="${href}" target="_blank">View Material</a></small>` : ''}
            </div>
            <div class="material-actions"></div>
          </div>
        </div>`;
    }).join('')}</div>`;
  }

  function renderTopics(topics) {
    if (!topics.length) {
      return '<div class="no-topics"><p>No topics added yet.</p></div>';
    }
    const cards = topics.map(t => moduleCardHtmlTrainer(t)).join('');
    return `<div class="modules-list" id="modules-pane">${cards}</div>`;
  }

  function renderMaterials(materials) {
    if (!materials.length) return '<p class="no-materials">No materials added.</p>';
    return `<div class="materials-list">${materials.map(m => `
      <div class="material-item">
        <div class="material-details">
          <div style="display:flex;align-items:flex-start;gap:12px;flex:1">
            <div class="material-icon"><i class="fas fa-file"></i></div>
            <div class="material-info">
              <strong>${m.material_title}</strong>
              <p>${m.material_description || ''}</p>
              ${m.material_file_path ? `<small><a class="attachment-link" href="${String(m.material_file_path).startsWith('http') ? m.material_file_path : ('../uploads/courses/' + m.material_file_path)}" target="_blank">View Material</a></small>` : ''}
            </div>
          </div>
          <div class="material-actions"></div>
        </div>
      </div>`).join('')}</div>`;
  }

  function renderActivities(activities) {
    if (!activities.length) return '<p class="no-materials">No activities added.</p>';
    return activities.map(a => `
      <div class="activity-item">
        <div class="activity-header">
          <div class="activity-info">
            <strong>${a.activity_title}</strong>
            <span class="activity-type ${a.activity_type}">${a.activity_type}</span>
          </div>
        </div>
        <div class="activity-actions">
          ${a.activity_type === 'quiz' ? `<button type="button" class="btn btn-outline-primary btn-sm manage-quiz-btn" title="Manage in Activities"><i class="fas fa-list"></i> Manage</button>` : ''}
        </div>
      </div>
    `).join('');
  }

  function buildTrainerSections(topic){
    const mats = (topic.materials || []).map(m => ({
      type: 'material',
      id: m.material_id,
      icon: 'fa-file',
      title: m.material_title || 'Material',
      submitted: false,
      score: null,
      max: null,
      due: null,
      given: !!m.given,
      editPayload: { materialId: m.material_id, materialTitle: m.material_title, materialDescription: m.material_description || '', materialPath: m.material_file_path || '' }
    }));
    const acts = (topic.activities || []).map(a => ({
      type: 'activity',
      id: a.activity_id,
      icon: 'fa-cube',
      title: a.activity_title || 'Activity',
      submitted: Array.isArray(a.submissions) && a.submissions.length > 0,
      score: null,
      max: a.max_score || null,
      due: a.due_date || null,
      given: !!a.given,
      editPayload: { activityId: a.activity_id, activityTitle: a.activity_title, activityDescription: a.activity_description || '', dueDate: a.due_date || '', maxScore: a.max_score || 100 }
    }));
    return [...mats, ...acts];
  }

  function sectionsTableHtmlTrainer(courseCode, topicId, sections){
    if (!sections || sections.length === 0) {
      return `<div class="sections-table"><div class="sections-header"><div>Section</div></div><div class="sections-row"><div class="sec-cell sec-title"><span>No sections available.</span></div></div></div>`;
    }
    const bn = document.getElementById('trainer-batch-select')?.value || '';
    return `
      <div class="sections-table">
        <div class="sections-header">
          <div>Section</div>
        </div>
        ${sections.map(s => `
          <div class="sections-row" data-resource-type="${s.type}" data-resource-id="${s.id}" data-course-code="${courseCode}" data-batch-name="${bn}" data-topic-id="${topicId}">
            <div class="sec-cell sec-title"><i class="fas ${s.icon}"></i> ${
              s.type === 'material' && s.editPayload.materialPath
                ? `<a href="${String(s.editPayload.materialPath).startsWith('http') ? s.editPayload.materialPath : ('../uploads/courses/' + s.editPayload.materialPath)}" target="_blank" rel="noopener noreferrer" class="open-material-link">${s.title}</a>`
                : (s.type === 'activity'
                    ? `<a href="#" class="open-activity-link" data-activity-id="${s.id}">${s.title}</a>`
                    : `<span>${s.title}</span>`)
            }</div>
            
          </div>
        `).join('')}
      </div>
    `;
  }

  function moduleCardHtmlTrainer(topic){
    const imgSrc = '../images/school.png';
    const acts = Array.isArray(topic.activities) ? topic.activities : [];
    const submitted = acts.reduce((acc,a)=>acc + ((Array.isArray(a.submissions) && a.submissions.length>0)?1:0),0);
    const progress = acts.length ? Math.round((submitted/acts.length)*100) : 0;
    const isCompleted = progress === 100 && acts.length>0;
    const sections = buildTrainerSections(topic);
    const sectionsCount = sections.length;
    const code = document.getElementById('course-code')?.textContent || '';
    return `
      <div class="module-card" data-expanded="false">
        <div class="module-header">
          <img class="module-thumb" src="${imgSrc}" alt="Module">
          <div class="module-title-area">
            <h4 class="module-title">${topic.topic_name || 'Module'}</h4>
          </div>
          <div class="module-actions">
            <button type="button" class="btn btn-outline-secondary btn-sm add-material-btn btn-on-dark" data-topic-id="${topic.id}"><i class="fas fa-file"></i> Add Module</button>
          </div>
          <button class="toggle-sections" aria-expanded="false"><span class="sections-count">${sectionsCount} section${sectionsCount!==1?'s':''}</span><i class="fas fa-chevron-down"></i></button>
        </div>
        <div class="module-sections hidden">
          ${sectionsTableHtmlTrainer(code, topic.id, sections)}
        </div>
      </div>
    `;
  }

  document.addEventListener('click', function(e){
    const openMat = e.target.closest('.open-material-link');
    if (openMat) { return; }
    const openAct = e.target.closest('.open-activity-link');
    if (openAct) {
      e.preventDefault();
      if (typeof switchToTab === 'function') { switchToTab('quizzes'); }
      else {
        const url = new URL(window.location.href);
        url.searchParams.set('current_tab', 'quizzes');
        window.location.href = url.toString();
      }
      return;
    }
    const tog = e.target.closest('.toggle-sections');
    if (tog) {
      const card = tog.closest('.module-card');
      const sec = card.querySelector('.module-sections');
      const expanded = tog.getAttribute('aria-expanded') === 'true';
      tog.setAttribute('aria-expanded', String(!expanded));
      sec.classList.toggle('hidden');
      const icon = tog.querySelector('i');
      if (icon) { icon.classList.toggle('fa-chevron-down'); icon.classList.toggle('fa-chevron-up'); }
      return;
    }
    const giveBtn = e.target.closest('.give-toggle');
    if (giveBtn) {
      const row = giveBtn.closest('.sections-row');
      const resourceType = row?.getAttribute('data-resource-type') || '';
      const resourceId = row?.getAttribute('data-resource-id') || '';
      const courseCode = row?.getAttribute('data-course-code') || '';
      const batchName = row?.getAttribute('data-batch-name') || '';
      if (!resourceType || !resourceId || !courseCode || !batchName) return;
      const action = giveBtn.getAttribute('data-given') === 'true' ? 'ungive' : 'give';
      const btn = giveBtn;
      btn.disabled = true;
      fetch('../php/toggle_batch_resource.php', { method:'POST', headers:{ 'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest' }, body: JSON.stringify({ course_code: courseCode, batch_name: batchName, resource_type: resourceType, resource_id: parseInt(resourceId,10), action }) })
        .then(r=>r.json())
        .then(d=>{
          if (d.success) {
            const nowGiven = action === 'give';
            btn.setAttribute('data-given', nowGiven ? 'true' : 'false');
            btn.textContent = nowGiven ? 'Ungive' : 'Give';
          }
        })
        .finally(()=>{ btn.disabled = false; });
    }
  });

  function renderSubmissionsView(topicsByCompetency) {
    const container = document.getElementById('submissions-list');
    if (!container) return;
    let all = [];
    Object.values(topicsByCompetency).forEach(arr => arr.forEach(t => { if (t.activities) all.push(...t.activities); }));
    if (!all.length) { container.innerHTML = '<p class="no-data">No activities have been created for this course yet.</p>'; return; }
    container.innerHTML = all.map(a => `
      <div class="submissions-card">
        <div class="card-header">
          <div class="title"><i class="fas fa-clipboard-list"></i> ${a.activity_title}</div>
          <div class="meta">${(a.submissions || []).length} submission${(a.submissions||[]).length!==1?'s':''}</div>
        </div>
        <div class="card-body">${renderSubmissions(a.submissions || [], a.max_score, a.id)}</div>
      </div>`).join('');
  }

  function renderSubmissions(submissions, maxScore, activityId) {
    if (!submissions.length) return '<p class="no-submissions">No submissions yet.</p>';
    return submissions.map(s => {
      const initials = ((s.trainee_first_name||'').charAt(0) + (s.trainee_last_name||'').charAt(0)).toUpperCase();
      const time = s.submitted_at ? new Date(s.submitted_at).toLocaleString() : '';
      const scoreText = s.score !== null ? `${s.score} / ${maxScore}` : 'Not Graded';
      return `
        <div class="submission-row" data-submission-id="${s.submission_id}">
          <div class="submission-name"><div class="avatar">${initials||'T'}</div><div>${s.trainee_first_name} ${s.trainee_last_name}</div></div>
          <div class="submission-time">${time}</div>
          <div><a href="../uploads/submissions/${s.submission_file_path}" target="_blank">View File</a></div>
          <div class="submission-actions"><span class="submission-score">${scoreText}</span> <button class="btn btn-primary grade-btn" data-submission-id="${s.submission_id}" data-current-score="${s.score || ''}" data-max-score="${maxScore}" data-feedback="${s.feedback || ''}">${s.score !== null ? 'Edit Grade' : 'Grade'}</button></div>
        </div>`;
    }).join('');
  }

  document.getElementById('competencies-list')?.addEventListener('click', function(e) {
    const gradeBtn = e.target.closest('.grade-btn');
    if (gradeBtn) {
      const id = gradeBtn.dataset.submissionId;
      const currentScore = gradeBtn.dataset.currentScore;
      const maxScore = gradeBtn.dataset.maxScore;
      const feedback = gradeBtn.dataset.feedback;
      openGradeModal(id, currentScore, maxScore, feedback);
    }
    const toggleBtn = e.target.closest('.toggle-materials-btn');
    if (toggleBtn) {
      const container = toggleBtn.closest('.topic-item').querySelector('.materials-container');
      container.classList.toggle('hidden');
      const icon = toggleBtn.querySelector('i');
      icon.classList.toggle('fa-chevron-down');
      icon.classList.toggle('fa-chevron-up');
    }
    const addTopicBtn = e.target.closest('.add-topic-btn');
    if (addTopicBtn) {
      const compId = addTopicBtn.dataset.competencyId;
      const compName = addTopicBtn.dataset.competencyName;
      const code = document.getElementById('course-code').textContent;
      const name = document.getElementById('course-detail-title').textContent;
      openAddTopicModal(code, name, compId, compName);
    }
    const addMaterialBtn = e.target.closest('.add-material-btn');
    if (addMaterialBtn) openAddMaterialModal(addMaterialBtn.dataset.topicId);
    
    const editCompMatBtn = e.target.closest('.edit-comp-material-btn');
    if (editCompMatBtn) {
      openEditMaterialModal({
        id: editCompMatBtn.dataset.materialId,
        title: editCompMatBtn.dataset.materialTitle,
        description: editCompMatBtn.dataset.materialDescription,
        path: editCompMatBtn.dataset.materialPath,
      }, 'course');
    }
    const deleteCompMatBtn = e.target.closest('.delete-comp-material-btn');
    if (deleteCompMatBtn) {
      const id = deleteCompMatBtn.dataset.materialId;
      openConfirm('Confirm Delete', 'Delete course material: ' + (deleteCompMatBtn.dataset.materialTitle || '') + '?', () => {
        const bnA = document.getElementById('trainer-batch-select')?.value || currentBatchName;
        if (!bnA) { openConfirm('Error', 'Please select a batch first', () => {}, { confirmText: 'OK', showCancel: false }); return; }
        fetch('../php/delete_course_material.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({ material_id: id, batch_name: bnA })
        })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            openConfirm('Confirm Action', 'Course material deleted.', () => {
              const code = document.getElementById('course-code')?.textContent || '';
              const name = document.getElementById('course-detail-title')?.textContent || '';
              const hours = document.getElementById('course-hours')?.textContent || '';
              loadCourseDetails(code, name, hours);
            }, { confirmText: 'Confirm' });
          } else {
            openConfirm('Error', 'Delete failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false });
          }
        })
        .catch(() => openConfirm('Error', 'Network error while deleting course material', () => {}, { confirmText: 'OK', showCancel: false }));
      }, { confirmText: 'Delete' });
    }
    const editMaterialBtn = e.target.closest('.edit-material-btn');
    if (editMaterialBtn) {
      openEditMaterialModal({
        id: editMaterialBtn.dataset.materialId,
        title: editMaterialBtn.dataset.materialTitle,
        description: editMaterialBtn.dataset.materialDescription,
        path: editMaterialBtn.dataset.materialPath,
      });
    }
    const deleteMaterialBtn = e.target.closest('.delete-material-btn');
    if (deleteMaterialBtn) {
      const id = deleteMaterialBtn.dataset.materialId;
      openConfirm('Confirm Delete', 'Delete material: ' + (deleteMaterialBtn.dataset.materialTitle || '') + '?', () => {
        const bnB = document.getElementById('trainer-batch-select')?.value || currentBatchName;
        if (!bnB) { openConfirm('Error', 'Please select a batch first', () => {}, { confirmText: 'OK', showCancel: false }); return; }
        fetch('../php/delete_material.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({ material_id: id, batch_name: bnB })
        })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            openConfirm('Confirm Action', 'Material deleted.', () => {
              const code = document.getElementById('course-code')?.textContent || '';
              const name = document.getElementById('course-detail-title')?.textContent || '';
              const hours = document.getElementById('course-hours')?.textContent || '';
              loadCourseDetails(code, name, hours);
            }, { confirmText: 'Confirm' });
          } else {
            openConfirm('Error', 'Delete failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false });
          }
        })
        .catch(() => openConfirm('Error', 'Network error while deleting material', () => {}, { confirmText: 'OK', showCancel: false }));
      }, { confirmText: 'Delete' });
    }
    const editActivityBtn = e.target.closest('.edit-activity-btn');
    if (editActivityBtn) {
      openEditActivityModal({
        id: editActivityBtn.dataset.activityId,
        title: editActivityBtn.dataset.activityTitle,
        description: editActivityBtn.dataset.activityDescription,
        due: editActivityBtn.dataset.dueDate,
        max: editActivityBtn.dataset.maxScore,
      });
    }
    const deleteActivityBtn = e.target.closest('.delete-activity-btn');
    if (deleteActivityBtn) {
      const id = deleteActivityBtn.dataset.activityId;
      openConfirm('Confirm Delete', 'Delete activity: ' + (deleteActivityBtn.dataset.activityTitle || '') + '?', () => {
        const bnC = document.getElementById('trainer-batch-select')?.value || currentBatchName;
        if (!bnC) { openConfirm('Error', 'Please select a batch first', () => {}, { confirmText: 'OK', showCancel: false }); return; }
        fetch('../php/delete_activity.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({ activity_id: id, batch_name: bnC })
        })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            openConfirm('Confirm Action', 'Activity deleted.', () => {
              const code = document.getElementById('course-code')?.textContent || '';
              const name = document.getElementById('course-detail-title')?.textContent || '';
              const hours = document.getElementById('course-hours')?.textContent || '';
              loadCourseDetails(code, name, hours);
            }, { confirmText: 'Confirm' });
          } else {
            openConfirm('Error', 'Delete failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false });
          }
        })
        .catch(() => openConfirm('Error', 'Network error while deleting activity', () => {}, { confirmText: 'OK', showCancel: false }));
      }, { confirmText: 'Delete' });
    }
    // per-competency Upload Module removed; use global header button
  });

  document.addEventListener('click', function(e) {
    const icon = e.target.closest('.material-actions .icon-btn i');
    if (icon) {
      const btn = icon.closest('button');
      if (btn && btn.classList.contains('edit-material-btn')) {
        openEditMaterialModal({ id: btn.dataset.materialId, title: btn.dataset.materialTitle, description: btn.dataset.materialDescription, path: btn.dataset.materialPath });
        return;
      }
      if (btn && btn.classList.contains('edit-comp-material-btn')) {
        openEditMaterialModal({ id: btn.dataset.materialId, title: btn.dataset.materialTitle, description: btn.dataset.materialDescription, path: btn.dataset.materialPath }, 'course');
        return;
      }
      if (btn && btn.classList.contains('edit-activity-btn')) {
        openEditActivityModal({ id: btn.dataset.activityId, title: btn.dataset.activityTitle, description: btn.dataset.activityDescription, due: btn.dataset.dueDate, max: btn.dataset.maxScore });
        return;
      }
    }
    const editCompMatBtn = e.target.closest('.edit-comp-material-btn');
    if (editCompMatBtn) {
      openEditMaterialModal({
        id: editCompMatBtn.dataset.materialId,
        title: editCompMatBtn.dataset.materialTitle,
        description: editCompMatBtn.dataset.materialDescription,
        path: editCompMatBtn.dataset.materialPath,
      }, 'course');
      return;
    }
    const deleteCompMatBtn = e.target.closest('.delete-comp-material-btn');
    if (deleteCompMatBtn) {
      const id = deleteCompMatBtn.dataset.materialId;
      openConfirm('Confirm Delete', 'Delete course material: ' + (deleteCompMatBtn.dataset.materialTitle || '') + '?', () => {
        const bnA = document.getElementById('trainer-batch-select')?.value || currentBatchName;
        if (!bnA) { openConfirm('Error', 'Please select a batch first', () => {}, { confirmText: 'OK', showCancel: false }); return; }
        fetch('../php/delete_course_material.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({ material_id: id, batch_name: bnA })
        })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            openConfirm('Confirm Action', 'Course material deleted.', () => {
              const code = document.getElementById('course-code')?.textContent || '';
              const name = document.getElementById('course-detail-title')?.textContent || '';
              const hours = document.getElementById('course-hours')?.textContent || '';
              loadCourseDetails(code, name, hours);
            }, { confirmText: 'Confirm' });
          } else {
            openConfirm('Error', 'Delete failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false });
          }
        })
        .catch(() => openConfirm('Error', 'Network error while deleting course material', () => {}, { confirmText: 'OK', showCancel: false }));
      }, { confirmText: 'Delete' });
      return;
    }
    const editMaterialBtn = e.target.closest('.edit-material-btn');
    if (editMaterialBtn) {
      openEditMaterialModal({
        id: editMaterialBtn.dataset.materialId,
        title: editMaterialBtn.dataset.materialTitle,
        description: editMaterialBtn.dataset.materialDescription,
        path: editMaterialBtn.dataset.materialPath,
      });
      return;
    }
    const deleteMaterialBtn = e.target.closest('.delete-material-btn');
    if (deleteMaterialBtn) {
      const id = deleteMaterialBtn.dataset.materialId;
      openConfirm('Confirm Delete', 'Delete material: ' + (deleteMaterialBtn.dataset.materialTitle || '') + '?', () => {
        const bnB = document.getElementById('trainer-batch-select')?.value || currentBatchName;
        if (!bnB) { openConfirm('Error', 'Please select a batch first', () => {}, { confirmText: 'OK', showCancel: false }); return; }
        fetch('../php/delete_material.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({ material_id: id, batch_name: bnB })
        })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            openConfirm('Confirm Action', 'Material deleted.', () => {
              const code = document.getElementById('course-code')?.textContent || '';
              const name = document.getElementById('course-detail-title')?.textContent || '';
              const hours = document.getElementById('course-hours')?.textContent || '';
              loadCourseDetails(code, name, hours);
            }, { confirmText: 'Confirm' });
          } else {
            openConfirm('Error', 'Delete failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false });
          }
        })
        .catch(() => openConfirm('Error', 'Network error while deleting material', () => {}, { confirmText: 'OK', showCancel: false }));
      }, { confirmText: 'Delete' });
    }
  });

  window.__editCompMaterial = function(el){
    const btn = el.closest('.edit-comp-material-btn');
    if (!btn) return;
    if (typeof openEditMaterialModal === 'function') {
      openEditMaterialModal({ id: btn.dataset.materialId, title: btn.dataset.materialTitle, description: btn.dataset.materialDescription, path: btn.dataset.materialPath }, 'course');
    } else if (typeof window.__openEditMaterialModal === 'function') {
      window.__openEditMaterialModal({ id: btn.dataset.materialId, title: btn.dataset.materialTitle, description: btn.dataset.materialDescription, path: btn.dataset.materialPath }, 'course');
    }
  };
  window.__deleteCompMaterial = function(el){
    const btn = el.closest('.delete-comp-material-btn');
    if (!btn) return;
    const id = btn.dataset.materialId;
    (typeof window.__openConfirm === 'function' ? window.__openConfirm : openConfirm)('Confirm Delete', 'Delete course material: ' + (btn.dataset.materialTitle || '') + '?', () => {
      const bnA = document.getElementById('trainer-batch-select')?.value || currentBatchName;
      if (!bnA) { openConfirm('Error', 'Please select a batch first', () => {}, { confirmText: 'OK', showCancel: false }); return; }
      fetch('../php/delete_course_material.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ material_id: id, batch_name: bnA }) })
        .then(r => r.json())
        .then(d => { if (d.success) { (typeof window.__openConfirm === 'function' ? window.__openConfirm : openConfirm)('Confirm Action', 'Course material deleted.', () => { const code = document.getElementById('course-code')?.textContent || ''; const name = document.getElementById('course-detail-title')?.textContent || ''; const hours = document.getElementById('course-hours')?.textContent || ''; if (typeof loadCourseDetails === 'function') { loadCourseDetails(code, name, hours); } else { window.location.reload(); } }, { confirmText: 'Confirm' }); } else { (typeof window.__openConfirm === 'function' ? window.__openConfirm : openConfirm)('Error', 'Delete failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false }); } })
        .catch(() => openConfirm('Error', 'Network error while deleting course material', () => {}, { confirmText: 'OK', showCancel: false }));
    }, { confirmText: 'Delete' });
  };
  window.__editTopicMaterial = function(el){
    const btn = el.closest('.edit-material-btn');
    if (!btn) return;
    if (typeof openEditMaterialModal === 'function') {
      openEditMaterialModal({ id: btn.dataset.materialId, title: btn.dataset.materialTitle, description: btn.dataset.materialDescription, path: btn.dataset.materialPath });
    } else if (typeof window.__openEditMaterialModal === 'function') {
      window.__openEditMaterialModal({ id: btn.dataset.materialId, title: btn.dataset.materialTitle, description: btn.dataset.materialDescription, path: btn.dataset.materialPath });
    }
  };
  window.__deleteTopicMaterial = function(el){
    const btn = el.closest('.delete-material-btn');
    if (!btn) return;
    const id = btn.dataset.materialId;
    (typeof window.__openConfirm === 'function' ? window.__openConfirm : openConfirm)('Confirm Delete', 'Delete material: ' + (btn.dataset.materialTitle || '') + '?', () => {
      const bnB = document.getElementById('trainer-batch-select')?.value || currentBatchName;
      if (!bnB) { openConfirm('Error', 'Please select a batch first', () => {}, { confirmText: 'OK', showCancel: false }); return; }
      fetch('../php/delete_material.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ material_id: id, batch_name: bnB }) })
        .then(r => r.json())
        .then(d => { if (d.success) { (typeof window.__openConfirm === 'function' ? window.__openConfirm : openConfirm)('Confirm Action', 'Material deleted.', () => { const code = document.getElementById('course-code')?.textContent || ''; const name = document.getElementById('course-detail-title')?.textContent || ''; const hours = document.getElementById('course-hours')?.textContent || ''; if (typeof loadCourseDetails === 'function') { loadCourseDetails(code, name, hours); } else { window.location.reload(); } }, { confirmText: 'Confirm' }); } else { (typeof window.__openConfirm === 'function' ? window.__openConfirm : openConfirm)('Error', 'Delete failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false }); } })
        .catch(() => openConfirm('Error', 'Network error while deleting material', () => {}, { confirmText: 'OK', showCancel: false }));
    }, { confirmText: 'Delete' });
  };

  (function ensureInit(){
    if (typeof window.loadCourseDetails === 'function') return;
    window.__openConfirm = function(title, message, onConfirm, opts){
      const modal = document.getElementById('confirmationModal');
      const tEl = document.getElementById('confirmationTitle');
      const mEl = document.getElementById('confirmationMessage');
      const cBtn = document.getElementById('confirmActionBtn');
      const xBtn = document.getElementById('cancelConfirmation');
      if (tEl) tEl.textContent = title || 'Confirm';
      if (mEl) mEl.textContent = message || '';
      if (cBtn) cBtn.textContent = (opts && opts.confirmText) ? opts.confirmText : 'Confirm';
      if (xBtn) xBtn.style.display = (opts && opts.showCancel === false) ? 'none' : '';
      const cb = (typeof onConfirm === 'function') ? onConfirm : null;
      cBtn && cBtn.addEventListener('click', function handler(){ cBtn.removeEventListener('click', handler); modal && modal.classList.add('hidden'); cb && cb(); });
      xBtn && xBtn.addEventListener('click', function handler(){ xBtn.removeEventListener('click', handler); modal && modal.classList.add('hidden'); });
      modal && modal.classList.remove('hidden');
    };
    window.__editMaterialScope = 'topic';
    window.__openEditMaterialModal = function(material, scope){
      window.__editMaterialScope = scope || 'topic';
      const modal = document.getElementById('editMaterialModal');
      const idEl = document.getElementById('edit_material_id');
      const titleEl = document.getElementById('edit_material_title');
      const descEl = document.getElementById('edit_material_description');
      const infoEl = document.getElementById('edit_material_file_info');
      const typeSel = document.getElementById('edit_material_type');
      const fileGrp = document.getElementById('edit_file_input_group');
      const linkGrp = document.getElementById('edit_link_input_group');
      if (idEl) idEl.value = material.id || '';
      if (titleEl) titleEl.value = material.title || '';
      if (descEl) descEl.value = material.description || '';
      if (infoEl) infoEl.innerHTML = material.path ? `<small>Current file: ${material.path}</small>` : '<small>No file attached</small>';
      if (typeSel) typeSel.value = '';
      fileGrp && fileGrp.classList.add('hidden');
      linkGrp && linkGrp.classList.add('hidden');
      modal && modal.classList.remove('hidden');
    };
    const editForm = document.getElementById('editMaterialForm');
    const typeSel = document.getElementById('edit_material_type');
    editForm && editForm.addEventListener('submit', function(e){
      e.preventDefault();
      const fd = new FormData(editForm);
      const btn = editForm.querySelector('button[type="submit"]');
      btn && (btn.disabled = true, btn.textContent = 'Saving...');
      const t = typeSel ? typeSel.value : '';
      if (t === 'file') {
        const f = document.querySelector('#edit_file_input_group input[type="file"]');
        if (!f || !f.files || f.files.length === 0) { window.__openConfirm('Error','Please select a file to upload',()=>{}, {confirmText:'OK', showCancel:false}); btn && (btn.disabled=false, btn.textContent='Save Changes'); return; }
      } else if (t === 'link') {
        const l = document.querySelector('#edit_link_input_group input[name="material_link"]');
        if (!l || !l.value.trim()) { window.__openConfirm('Error','Please provide a link URL',()=>{}, {confirmText:'OK', showCancel:false}); btn && (btn.disabled=false, btn.textContent='Save Changes'); return; }
        fd.append('material_link', l.value);
      }
      const bn = document.getElementById('trainer-batch-select')?.value || '';
      if (!bn) { window.__openConfirm('Error','Please select a batch first',()=>{}, {confirmText:'OK', showCancel:false}); btn && (btn.disabled=false, btn.textContent='Save Changes'); return; }
      fd.append('batch_name', bn);
      if (t) fd.append('material_type', t);
      const endpoint = (window.__editMaterialScope === 'course') ? '../php/update_course_material.php' : '../php/update_material.php';
      fetch(endpoint, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
        .then(r => r.json())
        .then(d => { if (d.success) { document.getElementById('editMaterialModal')?.classList.add('hidden'); window.__openConfirm('Confirm Action','Material updated.', ()=>{ const code = document.getElementById('course-code')?.textContent || ''; const name = document.getElementById('course-detail-title')?.textContent || ''; const hours = document.getElementById('course-hours')?.textContent || ''; if (typeof loadCourseDetails === 'function') { loadCourseDetails(code, name, hours); } else { window.location.reload(); } }, {confirmText:'Confirm'}); } else { window.__openConfirm('Error', 'Update failed: ' + (d.message || 'Unknown error'), ()=>{}, {confirmText:'OK', showCancel:false}); } })
        .catch(() => window.__openConfirm('Error','Network error during material update', ()=>{}, {confirmText:'OK', showCancel:false}))
        .finally(() => { btn && (btn.disabled=false, btn.textContent='Save Changes'); });
    });
  })();

  function openAddTopicModal(courseCode, courseName, competencyId, competencyName) {
    const modal = document.getElementById('addTopicModal');
    const codeEl = document.getElementById('topic_course_code');
    const compIdEl = document.getElementById('topic_competency_id');
    const compNameEl = document.getElementById('topic_competency_name');
    const courseNameEl = document.getElementById('topic_course_name');
    const typeSel = document.getElementById('topic_material_type');
    const fileGroup = document.getElementById('topic_file_input_group');
    const linkGroup = document.getElementById('topic_link_input_group');
    if (codeEl) codeEl.value = courseCode || '';
    if (compIdEl) compIdEl.value = competencyId || '';
    if (compNameEl) compNameEl.textContent = competencyName || '';
    if (courseNameEl) courseNameEl.textContent = courseName || '';
    // Hide and disable the initial material inputs in Add Topic modal
    const form = document.getElementById('addTopicForm');
    if (form) {
      const hideGroup = (input) => {
        if (!input) return;
        input.value = '';
        input.disabled = true;
        const grp = input.closest('.form-group');
        if (grp) grp.style.display = 'none';
      };
      hideGroup(form.querySelector('[name="material_title"]'));
      hideGroup(form.querySelector('[name="material_description"]'));
      hideGroup(form.querySelector('[name="material_type"]'));
      hideGroup(form.querySelector('[name="material_file"]'));
      hideGroup(form.querySelector('[name="material_link"]'));
      const divider = modal ? modal.querySelector('.content-divider') : null;
      if (divider) divider.style.display = 'none';
    }
    if (typeSel) { typeSel.value = ''; typeSel.disabled = true; }
    if (fileGroup) { fileGroup.classList.add('hidden'); fileGroup.style.display = 'none'; }
    if (linkGroup) { linkGroup.classList.add('hidden'); linkGroup.style.display = 'none'; }
    if (typeof openModal === 'function') { openModal(modal); } else { modal && modal.classList.remove('hidden'); modal && (modal.style.display = 'flex'); }
  }

  document.getElementById('submissions-list')?.addEventListener('click', function(e) {
    const gradeBtn = e.target.closest('.grade-btn');
    if (gradeBtn) {
      const id = gradeBtn.dataset.submissionId;
      const currentScore = gradeBtn.dataset.currentScore;
      const maxScore = gradeBtn.dataset.maxScore;
      const feedback = gradeBtn.dataset.feedback;
      openGradeModal(id, currentScore, maxScore, feedback);
    }
  });

  function openAddMaterialModal(topicId) {
    const modal = document.getElementById('addMaterialModal');
    document.getElementById('material_topic_id').value = topicId;
    if (typeof openModal === 'function') { openModal(modal); } else { modal && modal.classList.remove('hidden'); modal && (modal.style.display = 'flex'); }
  }

  function openAddActivityModal(topicId) {
    const modal = document.getElementById('addActivityModal');
    const form = document.getElementById('addActivityForm');
    const hiddenTopic = document.getElementById('activity_topic_id');
    if (!modal || !form || !hiddenTopic) return;
    const compSel = form.querySelector('#activity_competency_select') || (() => {
      const row = document.createElement('div'); row.className = 'form-row';
      const g1 = document.createElement('div'); g1.className = 'form-group'; g1.style.flex = '1';
      const l1 = document.createElement('label'); l1.textContent = 'Competency *';
      const s1 = document.createElement('select'); s1.id = 'activity_competency_select';
      g1.appendChild(l1); g1.appendChild(s1);
      const g2 = document.createElement('div'); g2.className = 'form-group'; g2.style.flex = '1';
      const l2 = document.createElement('label'); l2.textContent = 'Topic *';
      const s2 = document.createElement('select'); s2.id = 'activity_topic_select';
      g2.appendChild(l2); g2.appendChild(s2);
      row.appendChild(g1); row.appendChild(g2);
      const firstGroup = form.querySelector('.form-group');
      form.insertBefore(row, firstGroup);
      return s1;
    })();
    const topicSel = form.querySelector('#activity_topic_select');
    const data = window.__trainerCourseData || { competencies: [], topicsByCompetency: {} };
    const comps = data.competencies || [];
    compSel.innerHTML = comps.length ? comps.map(c => `<option value="${c.id}">${c.name}</option>`).join('') : '<option value="">No competencies</option>';
    function fillTopics(cid) {
      const arr = (data.topicsByCompetency && data.topicsByCompetency[cid]) ? data.topicsByCompetency[cid] : [];
      topicSel.innerHTML = arr.length ? arr.map(t => `<option value="${t.id}">${t.topic_name || t.name || 'Topic'}</option>`).join('') : '<option value="">No topics</option>';
      const val = topicSel.value;
      hiddenTopic.value = val || '';
    }
    // Preselect based on provided topicId
    let preCompId = ''; let preTopicId = topicId || '';
    if (preTopicId) {
      for (const c of comps) {
        const ts = (data.topicsByCompetency && data.topicsByCompetency[c.id]) ? data.topicsByCompetency[c.id] : [];
        if (ts.some(t => String(t.id) === String(preTopicId))) { preCompId = c.id; break; }
      }
    }
    if (!preCompId && comps.length) preCompId = comps[0].id;
    compSel.value = preCompId;
    fillTopics(preCompId);
    if (preTopicId) { topicSel.value = String(preTopicId); hiddenTopic.value = String(preTopicId); }
    compSel.onchange = () => { fillTopics(compSel.value); };
    topicSel.onchange = () => { hiddenTopic.value = topicSel.value; };
    if (typeof openModal === 'function') { openModal(modal); } else { modal.classList.remove('hidden'); modal.style.display = 'flex'; }
  }

  // Add Material modal interactions
  const addMaterialForm = document.getElementById('addMaterialForm');
  const materialTypeSel = document.getElementById('material_type');
  const fileGroup = document.getElementById('file_input_group');
  const linkGroup = document.getElementById('link_input_group');
  document.getElementById('cancelMaterial')?.addEventListener('click', () => {
    document.getElementById('addMaterialModal')?.classList.add('hidden');
  });
  document.getElementById('closeMaterialModal')?.addEventListener('click', () => {
    document.getElementById('addMaterialModal')?.classList.add('hidden');
  });
  materialTypeSel?.addEventListener('change', function() {
    const type = this.value;
    if (type === 'file') {
      fileGroup?.classList.remove('hidden');
      linkGroup?.classList.add('hidden');
    } else if (type === 'link') {
      linkGroup?.classList.remove('hidden');
      fileGroup?.classList.add('hidden');
    }
  });
  addMaterialForm?.addEventListener('submit', function(e) {
    e.preventDefault();
    const type = materialTypeSel?.value;
    if (type === 'file') {
      const fileInput = document.querySelector('#file_input_group input[type="file"]');
      if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        openConfirm('Error', 'Please select a file to upload', () => {}, { confirmText: 'OK', showCancel: false });
        return;
      }
    } else if (type === 'link') {
      const linkInput = document.querySelector('#link_input_group input[name="material_link"]');
      if (!linkInput || !linkInput.value.trim()) {
        openConfirm('Error', 'Please provide a link URL', () => {}, { confirmText: 'OK', showCancel: false });
        return;
      }
    }
    const formData = new FormData(addMaterialForm);
    const submitBtn = addMaterialForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true; submitBtn.textContent = 'Adding...';
    formData.append('batch_name', document.getElementById('trainer-batch-select')?.value || currentBatchName);
    fetch('../php/add_materials.php', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: formData })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          document.getElementById('addMaterialModal')?.classList.add('hidden');
          openConfirm('Confirm Action', 'Material added successfully.', () => {
            const code = document.getElementById('course-code')?.textContent || '';
            const name = document.getElementById('course-detail-title')?.textContent || '';
            const hours = document.getElementById('course-hours')?.textContent || '';
            loadCourseDetails(code, name, hours);
          }, { confirmText: 'Confirm' });
        } else {
          openConfirm('Error', 'Add material failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false });
        }
      })
      .catch(() => { openConfirm('Error', 'Network error while adding material', () => {}, { confirmText: 'OK', showCancel: false }); })
      .finally(() => { submitBtn.disabled = false; submitBtn.textContent = 'Add Material'; addMaterialForm.reset(); });
  });

  // Add Activity modal interactions
  const addActivityForm = document.getElementById('addActivityForm');
  const activityTypeSel = document.getElementById('activity_attachment_type');
  const actFileGroup = document.getElementById('activity_file_input_group');
  const actLinkGroup = document.getElementById('activity_link_input_group');
  document.getElementById('cancelActivity')?.addEventListener('click', () => { document.getElementById('addActivityModal')?.classList.add('hidden'); });
  document.getElementById('closeActivityModal')?.addEventListener('click', () => { document.getElementById('addActivityModal')?.classList.add('hidden'); });
  activityTypeSel?.addEventListener('change', function(){ const v = this.value; if (v === 'file') { actFileGroup?.classList.remove('hidden'); actLinkGroup?.classList.add('hidden'); } else if (v === 'link') { actLinkGroup?.classList.remove('hidden'); actFileGroup?.classList.add('hidden'); } else { actFileGroup?.classList.add('hidden'); actLinkGroup?.classList.add('hidden'); } });
  addActivityForm?.addEventListener('submit', function(e){
    e.preventDefault();
    const topicId = document.getElementById('activity_topic_id')?.value;
    if (!topicId) { openConfirm('Error', 'Please select a topic', () => {}, { confirmText: 'OK', showCancel: false }); return; }
    const dueDateDate = document.getElementById('due_date_date')?.value || '';
    const dueDateTime = document.getElementById('due_date_time')?.value || '';
    const startDateDate = document.getElementById('start_date_date')?.value || '';
    const startDateTime = document.getElementById('start_date_time')?.value || '';
    const dueHidden = document.getElementById('due_date');
    const startHidden = document.getElementById('start_date');
    if (dueHidden) dueHidden.value = (dueDateDate && dueDateTime) ? `${dueDateDate} ${dueDateTime}:00` : (dueDateDate ? `${dueDateDate} 00:00:00` : '');
    if (startHidden) startHidden.value = (startDateDate && startDateTime) ? `${startDateDate} ${startDateTime}:00` : (startDateDate ? `${startDateDate} 00:00:00` : '');
    const formData = new FormData(addActivityForm);
    const submitBtn = addActivityForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true; submitBtn.textContent = 'Adding...';
    formData.append('batch_name', document.getElementById('trainer-batch-select')?.value || window.__trainerCourseData?.batchName || '');
    fetch('../php/add_activity.php', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: formData })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          document.getElementById('addActivityModal')?.classList.add('hidden');
          openConfirm('Confirm Action', 'Activity added successfully.', () => {
            const code = document.getElementById('course-code')?.textContent || window.__trainerCourseData?.courseCode || '';
            const name = document.getElementById('course-detail-title')?.textContent || '';
            const hours = document.getElementById('course-hours')?.textContent || '';
            loadCourseDetails(code, name, hours);
          }, { confirmText: 'Confirm' });
        } else {
          openConfirm('Error', 'Add activity failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false });
        }
      })
      .catch(() => { openConfirm('Error', 'Network error while adding activity', () => {}, { confirmText: 'OK', showCancel: false }); })
      .finally(() => { submitBtn.disabled = false; submitBtn.textContent = 'Add Activity'; addActivityForm.reset(); });
  });

  // Connect quiz-type activities to Quizzes management tab
  document.getElementById('competencies-list')?.addEventListener('click', function(e) {
    const manageBtn = e.target.closest('.manage-quiz-btn');
    if (manageBtn) {
      if (typeof switchToTab === 'function') {
        switchToTab('quizzes');
      } else {
        const url = new URL(window.location.href);
        url.searchParams.set('current_tab', 'quizzes');
        window.location.href = url.toString();
      }
    }
  });

  const gradeModal = document.getElementById('gradeSubmissionModal');
  const gradeForm = document.getElementById('gradeSubmissionForm');
  function openGradeModal(submissionId, currentScore, maxScore, feedback) {
    document.getElementById('grade_submission_id').value = submissionId;
    document.getElementById('submission_score').value = currentScore;
    const max = (maxScore && Number(maxScore) > 0) ? Number(maxScore) : 100;
    document.getElementById('submission_score').max = String(max);
    document.getElementById('submission_max_score').textContent = String(max);
    document.getElementById('submission_feedback').value = feedback;
    if (typeof openModal === 'function') { openModal(gradeModal); } else { gradeModal.classList.remove('hidden'); gradeModal.style.display = 'flex'; }
  }
  window.openGradeModal = openGradeModal;

  document.getElementById('closeGradeModal')?.addEventListener('click', () => gradeModal.classList.add('hidden'));
  document.getElementById('cancelGrade')?.addEventListener('click', () => gradeModal.classList.add('hidden'));

  gradeForm?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const submissionId = formData.get('submission_id');
    const score = formData.get('score');
    const maxScore = document.getElementById('submission_max_score').textContent;
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true; submitBtn.textContent = 'Saving...';
    const bnVal = document.getElementById('trainer-batch-select')?.value || currentBatchName;
    if (!bnVal) {
      openConfirm('Error', 'Please select a batch first', () => {}, { confirmText: 'OK', showCancel: false });
      submitBtn.disabled = false; submitBtn.textContent = 'Save Grade';
      return;
    }
    formData.append('batch_name', bnVal);
    const numScore = Number(score);
    const numMax = Number(maxScore);
    if (!(numMax > 0)) {
      openConfirm('Error', 'Invalid max score', () => {}, { confirmText: 'OK', showCancel: false });
      submitBtn.disabled = false; submitBtn.textContent = 'Save Grade';
      return;
    }
    if (isNaN(numScore) || numScore < 0 || numScore > numMax) {
      openConfirm('Error', `Score must be between 0 and ${numMax}`, () => {}, { confirmText: 'OK', showCancel: false });
      submitBtn.disabled = false; submitBtn.textContent = 'Save Grade';
      return;
    }
    fetch('../php/grade_submission.php', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: formData })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          openConfirm('Success', 'Grade saved successfully!', () => {}, { confirmText: 'OK', showCancel: false });
          gradeModal.classList.add('hidden');
          const row = document.querySelector(`.submission-row[data-submission-id="${submissionId}"]`);
          if (row) {
            const scoreEl = row.querySelector('.submission-score');
            if (scoreEl) scoreEl.textContent = `${score} / ${maxScore}`;
            const btn = row.querySelector('.grade-btn');
            if (btn) { btn.textContent = 'Edit Grade'; btn.dataset.currentScore = score; btn.dataset.feedback = formData.get('feedback'); }
          }
        } else {
          openConfirm('Error', 'Error saving grade: ' + (d.message || 'Unknown'), () => {}, { confirmText: 'OK', showCancel: false });
        }
      })
      .catch(() => { openConfirm('Error', 'Network error while saving grade', () => {}, { confirmText: 'OK', showCancel: false }); })
      .finally(() => { submitBtn.disabled = false; submitBtn.textContent = 'Save Grade'; });
  });
  // Fallback click binding to ensure submission triggers
  document.querySelector('#gradeSubmissionForm button[type="submit"]')?.addEventListener('click', function(ev){
    const gf = document.getElementById('gradeSubmissionForm');
    if (!gf) return;
    // Let the bound submit handler run
    // Some environments require explicit requestSubmit for custom buttons
    if (typeof gf.requestSubmit === 'function') { gf.requestSubmit(); }
  });
  // Ensure grade form submission works even if initial binding missed
  (function ensureGradeBinding(){
    const gf = document.getElementById('gradeSubmissionForm');
    if (!gf || gf.__boundFallback) return;
    gf.__boundFallback = true;
    gf.addEventListener('submit', function(e){ /* no-op; already handled above */ });
  })();
  // Fallback global delegation for grade buttons
  document.addEventListener('click', function(ev){
    const gb = ev.target.closest('.grade-btn');
    if (!gb) return;
    ev.preventDefault();
    const id = gb.dataset.submissionId;
    const currentScore = gb.dataset.currentScore;
    const maxScore = gb.dataset.maxScore;
    const feedback = gb.dataset.feedback;
    if (id) window.openGradeModal(id, currentScore, maxScore, feedback);
  });
});
document.addEventListener('click', function(e){
  const b = e.target.closest('.edit-material-btn,.edit-comp-material-btn,.edit-activity-btn,.delete-material-btn,.delete-comp-material-btn,.delete-activity-btn');
  if (!b) return;
  e.preventDefault();
  const cls = b.className || '';
  if (cls.includes('edit-material-btn')) {
    openEditMaterialModal({ id: b.dataset.materialId, title: b.dataset.materialTitle, description: b.dataset.materialDescription, path: b.dataset.materialPath });
    return;
  }
  if (cls.includes('edit-comp-material-btn')) {
    openEditMaterialModal({ id: b.dataset.materialId, title: b.dataset.materialTitle, description: b.dataset.materialDescription, path: b.dataset.materialPath }, 'course');
    return;
  }
  if (cls.includes('edit-activity-btn')) {
    openEditActivityModal({ id: b.dataset.activityId, title: b.dataset.activityTitle, description: b.dataset.activityDescription, due: b.dataset.dueDate, max: b.dataset.maxScore });
    return;
  }
  if (cls.includes('delete-material-btn')) {
    const id = b.dataset.materialId;
    openConfirm('Confirm Delete', 'Delete material: ' + (b.dataset.materialTitle || '') + '?', () => {
      const bn = document.getElementById('trainer-batch-select')?.value || currentBatchName;
      if (!bn) { openConfirm('Error', 'Please select a batch first', () => {}, { confirmText: 'OK', showCancel: false }); return; }
      fetch('../php/delete_material.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ material_id: id, batch_name: bn }) })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            openConfirm('Confirm Action', 'Material deleted.', () => {
              const code = document.getElementById('course-code')?.textContent || '';
              const name = document.getElementById('course-detail-title')?.textContent || '';
              const hours = document.getElementById('course-hours')?.textContent || '';
              if (typeof loadCourseDetails === 'function') loadCourseDetails(code, name, hours);
            }, { confirmText: 'Confirm' });
          } else {
            openConfirm('Error', 'Delete failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false });
          }
        })
        .catch(() => openConfirm('Error', 'Network error while deleting material', () => {}, { confirmText: 'OK', showCancel: false }));
    }, { confirmText: 'Delete' });
    return;
  }
  if (cls.includes('delete-comp-material-btn')) {
    const id = b.dataset.materialId;
    openConfirm('Confirm Delete', 'Delete course material: ' + (b.dataset.materialTitle || '') + '?', () => {
      const bn = document.getElementById('trainer-batch-select')?.value || currentBatchName;
      if (!bn) { openConfirm('Error', 'Please select a batch first', () => {}, { confirmText: 'OK', showCancel: false }); return; }
      fetch('../php/delete_course_material.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ material_id: id, batch_name: bn }) })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            openConfirm('Confirm Action', 'Course material deleted.', () => {
              const code = document.getElementById('course-code')?.textContent || '';
              const name = document.getElementById('course-detail-title')?.textContent || '';
              const hours = document.getElementById('course-hours')?.textContent || '';
              if (typeof loadCourseDetails === 'function') loadCourseDetails(code, name, hours);
            }, { confirmText: 'Confirm' });
          } else {
            openConfirm('Error', 'Delete failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false });
          }
        })
        .catch(() => openConfirm('Error', 'Network error while deleting course material', () => {}, { confirmText: 'OK', showCancel: false }));
    }, { confirmText: 'Delete' });
    return;
  }
  if (cls.includes('delete-activity-btn')) {
    const id = b.dataset.activityId;
    openConfirm('Confirm Delete', 'Delete activity: ' + (b.dataset.activityTitle || '') + '?', () => {
      const bn = document.getElementById('trainer-batch-select')?.value || currentBatchName;
      if (!bn) { openConfirm('Error', 'Please select a batch first', () => {}, { confirmText: 'OK', showCancel: false }); return; }
      fetch('../php/delete_activity.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ activity_id: id, batch_name: bn }) })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            openConfirm('Confirm Action', 'Activity deleted.', () => {
              const code = document.getElementById('course-code')?.textContent || '';
              const name = document.getElementById('course-detail-title')?.textContent || '';
              const hours = document.getElementById('course-hours')?.textContent || '';
              if (typeof loadCourseDetails === 'function') loadCourseDetails(code, name, hours);
            }, { confirmText: 'Confirm' });
          } else {
            openConfirm('Error', 'Delete failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false });
          }
        })
        .catch(() => openConfirm('Error', 'Network error while deleting activity', () => {}, { confirmText: 'OK', showCancel: false }));
    }, { confirmText: 'Delete' });
  }
}, true);
  // Universal module uploader removed; modules are added per topic via Add Material
  const addTopicForm = document.getElementById('addTopicForm');
  document.getElementById('cancelTopic')?.addEventListener('click', () => {
    const m = document.getElementById('addTopicModal');
    if (typeof closeModal === 'function') { closeModal(m); } else { m && (m.style.display = 'none'); m && m.classList.add('hidden'); }
  });
  document.getElementById('closeTopicModal')?.addEventListener('click', () => {
    const m = document.getElementById('addTopicModal');
    if (typeof closeModal === 'function') { closeModal(m); } else { m && (m.style.display = 'none'); m && m.classList.add('hidden'); }
  });
  addTopicForm?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(addTopicForm);
    const submitBtn = addTopicForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true; submitBtn.textContent = 'Adding...';
    fetch('../php/add_topic.php', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: formData })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          document.getElementById('addTopicModal')?.classList.add('hidden');
          openConfirm('Confirm Action', 'Topic added successfully.', () => {
            const code = document.getElementById('course-code')?.textContent || '';
            const name = document.getElementById('course-detail-title')?.textContent || '';
            const hours = document.getElementById('course-hours')?.textContent || '';
            loadCourseDetails(code, name, hours);
          }, { confirmText: 'Confirm' });
        } else {
          openConfirm('Error', 'Add topic failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false });
        }
      })
      .catch(() => { openConfirm('Error', 'Network error while adding topic', () => {}, { confirmText: 'OK', showCancel: false }); })
      .finally(() => { submitBtn.disabled = false; submitBtn.textContent = 'Add Topic'; addTopicForm.reset(); });
  });

  const topicMaterialTypeSel = document.getElementById('topic_material_type');
  const topicFileGroup = document.getElementById('topic_file_input_group');
  const topicLinkGroup = document.getElementById('topic_link_input_group');
  topicMaterialTypeSel?.addEventListener('change', function() {
    const type = this.value;
    if (type === 'file') {
      topicFileGroup?.classList.remove('hidden');
      topicLinkGroup?.classList.add('hidden');
    } else if (type === 'link') {
      topicLinkGroup?.classList.remove('hidden');
      topicFileGroup?.classList.add('hidden');
    }
  });

  // Modal confirm helper
  const confirmModal = document.getElementById('confirmationModal');
  const confirmTitleEl = document.getElementById('confirmationTitle');
  const confirmMsgEl = document.getElementById('confirmationMessage');
  const confirmBtn = document.getElementById('confirmActionBtn');
  const cancelBtn = document.getElementById('cancelConfirmation');
  let confirmCallback = null;
  function openConfirm(title, message, onConfirm, opts = {}) {
    if (confirmTitleEl) confirmTitleEl.textContent = title || 'Confirm';
    if (confirmMsgEl) confirmMsgEl.textContent = message || '';
    const confirmText = opts.confirmText || 'Confirm';
    if (confirmBtn) confirmBtn.textContent = confirmText;
    if (cancelBtn) cancelBtn.style.display = opts.showCancel === false ? 'none' : '';
    confirmCallback = typeof onConfirm === 'function' ? onConfirm : null;
    confirmModal && confirmModal.classList.remove('hidden');
  }
  confirmBtn?.addEventListener('click', () => {
    confirmModal?.classList.add('hidden');
    const cb = confirmCallback; confirmCallback = null;
    if (cb) cb();
  });
  cancelBtn?.addEventListener('click', () => {
    confirmModal?.classList.add('hidden');
    confirmCallback = null;
  });
  let editMaterialScope = 'topic';
  function openEditMaterialModal(material, scope = 'topic') {
    editMaterialScope = scope;
    const modal = document.getElementById('editMaterialModal');
    const idEl = document.getElementById('edit_material_id');
    const titleEl = document.getElementById('edit_material_title');
    const descEl = document.getElementById('edit_material_description');
    const infoEl = document.getElementById('edit_material_file_info');
    const typeSel = document.getElementById('edit_material_type');
    const fileGrp = document.getElementById('edit_file_input_group');
    const linkGrp = document.getElementById('edit_link_input_group');
    if (idEl) idEl.value = material.id || '';
    if (titleEl) titleEl.value = material.title || '';
    if (descEl) descEl.value = material.description || '';
    if (infoEl) {
      infoEl.innerHTML = material.path ? `<small>Current file: ${material.path}</small>` : '<small>No file attached</small>';
    }
    if (typeSel) typeSel.value = '';
    if (fileGrp) fileGrp.classList.add('hidden');
    if (linkGrp) linkGrp.classList.add('hidden');
    modal && modal.classList.remove('hidden');
  }

  document.getElementById('cancelEditMaterial')?.addEventListener('click', () => document.getElementById('editMaterialModal')?.classList.add('hidden'));
  document.getElementById('closeEditMaterialModal')?.addEventListener('click', () => document.getElementById('editMaterialModal')?.classList.add('hidden'));

  const editMaterialForm = document.getElementById('editMaterialForm');
  const editMaterialTypeSel = document.getElementById('edit_material_type');
  const editFileGroup = document.getElementById('edit_file_input_group');
  const editLinkGroup = document.getElementById('edit_link_input_group');
  editMaterialTypeSel?.addEventListener('change', function() {
    const t = this.value;
    if (t === 'file') {
      editFileGroup?.classList.remove('hidden');
      editLinkGroup?.classList.add('hidden');
    } else if (t === 'link') {
      editLinkGroup?.classList.remove('hidden');
      editFileGroup?.classList.add('hidden');
    } else {
      editFileGroup?.classList.add('hidden');
      editLinkGroup?.classList.add('hidden');
    }
  });
  editMaterialForm?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(editMaterialForm);
    const submitBtn = editMaterialForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true; submitBtn.textContent = 'Saving...';
    const t = editMaterialTypeSel?.value || '';
    if (t === 'file') {
      const f = document.querySelector('#edit_file_input_group input[type="file"]');
      if (!f || !f.files || f.files.length === 0) {
        openConfirm('Error', 'Please select a file to upload', () => {}, { confirmText: 'OK', showCancel: false });
        submitBtn.disabled = false; submitBtn.textContent = 'Save Changes';
        return;
      }
    } else if (t === 'link') {
      const l = document.querySelector('#edit_link_input_group input[name="material_link"]');
      if (!l || !l.value.trim()) {
        openConfirm('Error', 'Please provide a link URL', () => {}, { confirmText: 'OK', showCancel: false });
        submitBtn.disabled = false; submitBtn.textContent = 'Save Changes';
        return;
      }
    }
    const endpoint = editMaterialScope === 'course' ? '../php/update_course_material.php' : '../php/update_material.php';
    const bnE = document.getElementById('trainer-batch-select')?.value || currentBatchName;
    if (!bnE) { openConfirm('Error', 'Please select a batch first', () => {}, { confirmText: 'OK', showCancel: false }); submitBtn.disabled = false; submitBtn.textContent = 'Save Changes'; return; }
    formData.append('batch_name', bnE);
    if (t) formData.append('material_type', t);
    if (t === 'link') {
      const l = document.querySelector('#edit_link_input_group input[name="material_link"]');
      if (l && l.value) formData.append('material_link', l.value);
    }
    fetch(endpoint, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: formData })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          document.getElementById('editMaterialModal')?.classList.add('hidden');
          openConfirm('Confirm Action', 'Material updated.', () => {
            const code = document.getElementById('course-code')?.textContent || '';
            const name = document.getElementById('course-detail-title')?.textContent || '';
            const hours = document.getElementById('course-hours')?.textContent || '';
            loadCourseDetails(code, name, hours);
          }, { confirmText: 'Confirm' });
        } else {
          openConfirm('Error', 'Update failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false });
        }
      })
      .catch(() => openConfirm('Error', 'Network error during material update', () => {}, { confirmText: 'OK', showCancel: false }))
      .finally(() => { submitBtn.disabled = false; submitBtn.textContent = 'Save Changes'; });
  });

  function openEditActivityModal(activity) {
    const modal = document.getElementById('editActivityModal');
    document.getElementById('edit_activity_id').value = activity.id || '';
    document.getElementById('edit_activity_title').value = activity.title || '';
    document.getElementById('edit_activity_description').value = activity.description || '';
    document.getElementById('edit_due_date').value = activity.due ? new Date(activity.due).toISOString().slice(0,16) : '';
    document.getElementById('edit_max_score').value = activity.max || 100;
    modal && modal.classList.remove('hidden');
  }

  document.getElementById('cancelEditActivity')?.addEventListener('click', () => document.getElementById('editActivityModal')?.classList.add('hidden'));
  document.getElementById('closeEditActivityModal')?.addEventListener('click', () => document.getElementById('editActivityModal')?.classList.add('hidden'));

  const editActivityForm = document.getElementById('editActivityForm');
  editActivityForm?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(editActivityForm);
    const submitBtn = editActivityForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true; submitBtn.textContent = 'Saving...';
    formData.append('batch_name', document.getElementById('trainer-batch-select')?.value || currentBatchName);
    fetch('../php/update_activity.php', { method: 'POST', body: formData })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          document.getElementById('editActivityModal')?.classList.add('hidden');
          openConfirm('Confirm Action', 'Activity updated.', () => {
            const code = document.getElementById('course-code')?.textContent || '';
            const name = document.getElementById('course-detail-title')?.textContent || '';
            const hours = document.getElementById('course-hours')?.textContent || '';
            loadCourseDetails(code, name, hours);
          }, { confirmText: 'Confirm' });
        } else {
          openConfirm('Error', 'Update failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false });
        }
      })
      .catch(() => openConfirm('Error', 'Network error during activity update', () => {}, { confirmText: 'OK', showCancel: false }))
      .finally(() => { submitBtn.disabled = false; submitBtn.textContent = 'Save Changes'; });
  });
  function populateTrainerBatches(courseCode) {
    const sel = document.getElementById('trainer-batch-select');
    if (!sel) return Promise.resolve();
    sel.innerHTML = '<option value="" disabled selected>Loading...</option>';
    return fetch(`../php/get_trainer_course_batches.php?course_code=${encodeURIComponent(courseCode)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json())
      .then(d => {
        if (!d.success) throw new Error(d.message || 'Failed to load batches');
        sel.innerHTML = '';
        (d.batches || []).forEach(b => {
          const opt = document.createElement('option');
          opt.value = b.batch_name;
          opt.textContent = b.batch_name;
          sel.appendChild(opt);
        });
        if (sel.options.length > 0) { sel.selectedIndex = 0; }
        currentBatchName = sel.value || '';
      })
      .catch(() => { sel.innerHTML = '<option value="" disabled selected>No batches</option>'; });
  }

  document.getElementById('trainer-batch-select')?.addEventListener('change', () => {
    currentBatchName = document.getElementById('trainer-batch-select').value || '';
    const code = document.getElementById('course-code')?.textContent || '';
    const name = document.getElementById('course-detail-title')?.textContent || '';
    const hours = document.getElementById('course-hours')?.textContent || '';
    if (code) loadCourseDetails(code, name, hours);
  });

// Fallback handlers to ensure clicks work even if DOMContentLoaded block didn't initialize
(function(){
  if (!window.__openConfirm) {
    window.__openConfirm = function(title, message, onConfirm, opts){
      const modal = document.getElementById('confirmationModal');
      const tEl = document.getElementById('confirmationTitle');
      const mEl = document.getElementById('confirmationMessage');
      const cBtn = document.getElementById('confirmActionBtn');
      const xBtn = document.getElementById('cancelConfirmation');
      if (tEl) tEl.textContent = title || 'Confirm';
      if (mEl) mEl.textContent = message || '';
      if (cBtn) cBtn.textContent = (opts && opts.confirmText) ? opts.confirmText : 'Confirm';
      if (xBtn) xBtn.style.display = (opts && opts.showCancel === false) ? 'none' : '';
      const cb = (typeof onConfirm === 'function') ? onConfirm : null;
      function onC(){ cBtn.removeEventListener('click', onC); modal && modal.classList.add('hidden'); cb && cb(); }
      function onX(){ xBtn.removeEventListener('click', onX); modal && modal.classList.add('hidden'); }
      cBtn && cBtn.addEventListener('click', onC);
      xBtn && xBtn.addEventListener('click', onX);
      modal && modal.classList.remove('hidden');
    };
  }
  if (!window.__openEditMaterialModal) {
    window.__openEditMaterialModal = function(material, scope){
      window.__editMaterialScope = scope || 'topic';
      const modal = document.getElementById('editMaterialModal');
      const idEl = document.getElementById('edit_material_id');
      const titleEl = document.getElementById('edit_material_title');
      const descEl = document.getElementById('edit_material_description');
      const infoEl = document.getElementById('edit_material_file_info');
      const typeSel = document.getElementById('edit_material_type');
      const fileGrp = document.getElementById('edit_file_input_group');
      const linkGrp = document.getElementById('edit_link_input_group');
      if (idEl) idEl.value = material.id || '';
      if (titleEl) titleEl.value = material.title || '';
      if (descEl) descEl.value = material.description || '';
      if (infoEl) infoEl.innerHTML = material.path ? `<small>Current file: ${material.path}</small>` : '<small>No file attached</small>';
      if (typeSel) typeSel.value = '';
      fileGrp && fileGrp.classList.add('hidden');
      linkGrp && linkGrp.classList.add('hidden');
      modal && modal.classList.remove('hidden');
    };
  }
  if (!window.__editCompMaterial) {
    window.__editCompMaterial = function(el){
      const btn = el.closest('.edit-comp-material-btn');
      if (!btn) return;
      window.__openEditMaterialModal({ id: btn.dataset.materialId, title: btn.dataset.materialTitle, description: btn.dataset.materialDescription, path: btn.dataset.materialPath }, 'course');
    };
  }
  if (!window.__deleteCompMaterial) {
    window.__deleteCompMaterial = function(el){
      const btn = el.closest('.delete-comp-material-btn');
      if (!btn) return;
      const id = btn.dataset.materialId;
      window.__openConfirm('Confirm Delete', 'Delete course material: ' + (btn.dataset.materialTitle || '') + '?', () => {
        const bnA = document.getElementById('trainer-batch-select')?.value || '';
        if (!bnA) { window.__openConfirm('Error', 'Please select a batch first', () => {}, { confirmText: 'OK', showCancel: false }); return; }
        fetch('../php/delete_course_material.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ material_id: id, batch_name: bnA }) })
          .then(r => r.json())
          .then(d => { if (d.success) { window.__openConfirm('Confirm Action', 'Course material deleted.', () => { const code = document.getElementById('course-code')?.textContent || ''; const name = document.getElementById('course-detail-title')?.textContent || ''; const hours = document.getElementById('course-hours')?.textContent || ''; if (typeof window.loadCourseDetails === 'function') { window.loadCourseDetails(code, name, hours); } else { window.location.reload(); } }, { confirmText: 'Confirm' }); } else { window.__openConfirm('Error', 'Delete failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false }); } })
          .catch(() => window.__openConfirm('Error', 'Network error while deleting course material', () => {}, { confirmText: 'OK', showCancel: false }));
      }, { confirmText: 'Delete' });
    };
  }
  if (!window.__editTopicMaterial) {
    window.__editTopicMaterial = function(el){
      const btn = el.closest('.edit-material-btn');
      if (!btn) return;
      window.__openEditMaterialModal({ id: btn.dataset.materialId, title: btn.dataset.materialTitle, description: btn.dataset.materialDescription, path: btn.dataset.materialPath });
    };
  }
  if (!window.__deleteTopicMaterial) {
    window.__deleteTopicMaterial = function(el){
      const btn = el.closest('.delete-material-btn');
      if (!btn) return;
      const id = btn.dataset.materialId;
      window.__openConfirm('Confirm Delete', 'Delete material: ' + (btn.dataset.materialTitle || '') + '?', () => {
        const bnB = document.getElementById('trainer-batch-select')?.value || '';
        if (!bnB) { window.__openConfirm('Error', 'Please select a batch first', () => {}, { confirmText: 'OK', showCancel: false }); return; }
        fetch('../php/delete_material.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ material_id: id, batch_name: bnB }) })
          .then(r => r.json())
          .then(d => { if (d.success) { window.__openConfirm('Confirm Action', 'Material deleted.', () => { const code = document.getElementById('course-code')?.textContent || ''; const name = document.getElementById('course-detail-title')?.textContent || ''; const hours = document.getElementById('course-hours')?.textContent || ''; if (typeof window.loadCourseDetails === 'function') { window.loadCourseDetails(code, name, hours); } else { window.location.reload(); } }, { confirmText: 'Confirm' }); } else { window.__openConfirm('Error', 'Delete failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false }); } })
          .catch(() => window.__openConfirm('Error', 'Network error while deleting material', () => {}, { confirmText: 'OK', showCancel: false }));
      }, { confirmText: 'Delete' });
    };
  }
  if (!window.__toggleGive) {
    window.__toggleGive = function(el){
      const btn = el.closest('.give-toggle');
      if (!btn) return;
      const row = btn.closest('.sections-row');
      const resourceType = row?.getAttribute('data-resource-type') || '';
      const resourceId = row?.getAttribute('data-resource-id') || '';
      const courseCode = row?.getAttribute('data-course-code') || '';
      let batchName = row?.getAttribute('data-batch-name') || '';
      if (!batchName) batchName = document.getElementById('trainer-batch-select')?.value || '';
      if (!resourceType || !resourceId || !courseCode || !batchName) return;
      const action = btn.getAttribute('data-given') === 'true' ? 'ungive' : 'give';
      btn.disabled = true;
      fetch('../php/toggle_batch_resource.php', { method:'POST', headers:{ 'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest' }, body: JSON.stringify({ course_code: courseCode, batch_name: batchName, resource_type: resourceType, resource_id: parseInt(resourceId,10), action }) })
        .then(r=>r.json())
        .then(d=>{ if (d.success) { const nowGiven = action === 'give'; btn.setAttribute('data-given', nowGiven ? 'true' : 'false'); btn.textContent = nowGiven ? 'Ungive' : 'Give'; } })
        .finally(()=>{ btn.disabled = false; });
    };
  }
  if (!window.__editActivity) {
    window.__editActivity = function(el){
      const btn = el.closest('.edit-activity-btn');
      if (!btn) return;
      const payload = { id: btn.dataset.activityId, title: btn.dataset.activityTitle, description: btn.dataset.activityDescription, due: btn.dataset.dueDate, max: btn.dataset.maxScore };
      if (typeof window.openEditActivityModal === 'function') { window.openEditActivityModal(payload); }
      else if (typeof openEditActivityModal === 'function') { openEditActivityModal(payload); }
    };
  }
  if (!window.__deleteActivity) {
    window.__deleteActivity = function(el){
      const btn = el.closest('.delete-activity-btn');
      if (!btn) return;
      const id = btn.dataset.activityId;
      window.__openConfirm('Confirm Delete', 'Delete activity: ' + (btn.dataset.activityTitle || '') + '?', () => {
        const bnC = document.getElementById('trainer-batch-select')?.value || '';
        if (!bnC) { window.__openConfirm('Error', 'Please select a batch first', () => {}, { confirmText: 'OK', showCancel: false }); return; }
        fetch('../php/delete_activity.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ activity_id: id, batch_name: bnC }) })
          .then(r => r.json())
          .then(d => { if (d.success) { window.__openConfirm('Confirm Action', 'Activity deleted.', () => { const code = document.getElementById('course-code')?.textContent || ''; const name = document.getElementById('course-detail-title')?.textContent || ''; const hours = document.getElementById('course-hours')?.textContent || ''; if (typeof window.loadCourseDetails === 'function') { window.loadCourseDetails(code, name, hours); } else { window.location.reload(); } }, { confirmText: 'Confirm' }); } else { window.__openConfirm('Error', 'Delete failed: ' + (d.message || 'Unknown error'), () => {}, { confirmText: 'OK', showCancel: false }); } })
          .catch(() => window.__openConfirm('Error', 'Network error while deleting activity', () => {}, { confirmText: 'OK', showCancel: false }));
      }, { confirmText: 'Delete' });
    };
  }
  // Ensure edit form submission works even if initial listeners didn't attach
  (function attachEditForm(){
    const editForm = document.getElementById('editMaterialForm');
    if (!editForm) return;
    if (editForm.__fallbackBound) return; editForm.__fallbackBound = true;
    const typeSel = document.getElementById('edit_material_type');
    editForm.addEventListener('submit', function(e){
      e.preventDefault();
      const fd = new FormData(editForm);
      const btn = editForm.querySelector('button[type="submit"]');
      btn && (btn.disabled = true, btn.textContent = 'Saving...');
      const t = typeSel ? typeSel.value : '';
      if (t === 'file') {
        const f = document.querySelector('#edit_file_input_group input[type="file"]');
        if (!f || !f.files || f.files.length === 0) { window.__openConfirm('Error','Please select a file to upload',()=>{}, {confirmText:'OK', showCancel:false}); btn && (btn.disabled=false, btn.textContent='Save Changes'); return; }
      } else if (t === 'link') {
        const l = document.querySelector('#edit_link_input_group input[name="material_link"]');
        if (!l || !l.value.trim()) { window.__openConfirm('Error','Please provide a link URL',()=>{}, {confirmText:'OK', showCancel:false}); btn && (btn.disabled=false, btn.textContent='Save Changes'); return; }
        fd.append('material_link', l.value);
      }
      const bn = document.getElementById('trainer-batch-select')?.value || '';
      if (!bn) { window.__openConfirm('Error','Please select a batch first',()=>{}, {confirmText:'OK', showCancel:false}); btn && (btn.disabled=false, btn.textContent='Save Changes'); return; }
      fd.append('batch_name', bn);
      if (t) fd.append('material_type', t);
      const endpoint = (window.__editMaterialScope === 'course') ? '../php/update_course_material.php' : '../php/update_material.php';
      fetch(endpoint, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
        .then(r => r.json())
        .then(d => { if (d.success) { document.getElementById('editMaterialModal')?.classList.add('hidden'); window.__openConfirm('Confirm Action','Material updated.', ()=>{ const code = document.getElementById('course-code')?.textContent || ''; const name = document.getElementById('course-detail-title')?.textContent || ''; const hours = document.getElementById('course-hours')?.textContent || ''; if (typeof window.loadCourseDetails === 'function') { window.loadCourseDetails(code, name, hours); } else { window.location.reload(); } }, {confirmText:'Confirm'}); } else { window.__openConfirm('Error', 'Update failed: ' + (d.message || 'Unknown error'), ()=>{}, {confirmText:'OK', showCancel:false}); } })
        .catch(() => window.__openConfirm('Error','Network error during material update', ()=>{}, {confirmText:'OK', showCancel:false}))
        .finally(() => { btn && (btn.disabled=false, btn.textContent='Save Changes'); });
    });
  })();
})();
