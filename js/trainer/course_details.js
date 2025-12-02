document.addEventListener('DOMContentLoaded', () => {
  const enrolledTab = document.getElementById('enrolled');
  const courseDetail = document.getElementById('course-detail');
  const courseBackBtn = courseDetail ? courseDetail.querySelector('.back-btn') : null;

  document.querySelectorAll('#enrolled .batch-card').forEach(batch => {
    batch.addEventListener('click', () => {
      const courseName = batch.dataset.course;
      const courseCode = batch.dataset.code;
      const courseHours = batch.dataset.hours;
      loadCourseDetails(courseCode, courseName, courseHours);
      enrolledTab && enrolledTab.classList.add('hidden');
      courseDetail && courseDetail.classList.remove('hidden');
      document.body.classList.add('modal-open');
    });
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

    fetch(`../php/get_course_details_trainer.php?course_code=${encodeURIComponent(courseCode)}`)
      .then(r => r.json())
      .then(data => {
        if (data.error) throw new Error(data.error);
        const cd = document.getElementById('course-description');
        if (cd) cd.textContent = (data.course && data.course.description) ? data.course.description : 'No description available';
        const competencies = (data.competencies || []).map(c => ({
          id: c.id,
          code: c.competency_code,
          name: c.competency_name,
          type: c.competency_type,
          description: c.description,
          topics: (data.topicsByCompetency && data.topicsByCompetency[c.id]) ? data.topicsByCompetency[c.id] : (c.topics || [])
        }));
        const materialsByComp = data.materialsByCompetency || {};
        renderCompetencies(competencies, materialsByComp);
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
            <div class="material-actions">
              <button class="icon-btn edit-comp-material-btn" data-material-id="${m.id}" data-material-title="${m.title}" data-material-description="${m.content || ''}" data-material-path="${m.file_path || ''}"><i class="fas fa-edit"></i></button>
              <button class="icon-btn delete-comp-material-btn" data-material-id="${m.id}" data-material-title="${m.title}"><i class="fas fa-trash"></i></button>
            </div>
          </div>
        </div>`;
    }).join('')}</div>`;
  }

  function renderTopics(topics) {
    if (!topics.length) {
      return '<div class="no-topics"><p>No topics added yet.</p></div>';
    }
    return topics.map(topic => `
      <div class="topic-item" data-topic-id="${topic.id}">
        <div class="topic-header">
          <div class="topic-info">
            <h5>${topic.topic_name}</h5>
            ${topic.topic_description ? `<p class="topic-description">${topic.topic_description}</p>` : ''}
            ${topic.learning_objectives ? `<p class="learning-objectives"><strong>Learning Objectives:</strong> ${topic.learning_objectives}</p>` : ''}
          </div>
          <div class="topic-actions">
            <button class="btn btn-outline-primary btn-sm add-material-btn" data-topic-id="${topic.id}"><i class="fas fa-plus-circle"></i> Add Material</button>
            <button class="btn btn-outline-primary btn-sm add-activity-btn" data-topic-id="${topic.id}"><i class="fas fa-tasks"></i> Add Activity</button>
            <button class="btn btn-icon toggle-materials-btn" title="Show/Hide"><i class="fas fa-chevron-down"></i></button>
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
          <div class="material-actions">
            <button class="icon-btn edit-material-btn" data-material-id="${m.id || m.material_id}" data-material-title="${m.material_title}" data-material-description="${m.material_description || ''}" data-material-path="${m.material_file_path || ''}"><i class="fas fa-edit"></i></button>
            <button class="icon-btn delete-material-btn" data-material-id="${m.id || m.material_id}" data-material-title="${m.material_title}"><i class="fas fa-trash"></i></button>
          </div>
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
          <div class="activity-meta">
            <small>Due: ${new Date(a.due_date).toLocaleString()}</small>
            <small>Max Score: ${a.max_score}</small>
            ${a.attachment_path ? `<small><a href="${String(a.attachment_path).startsWith('http') ? a.attachment_path : ('../uploads/activities/' + a.attachment_path)}" target="_blank" class="attachment-link"><i class="fas fa-paperclip"></i> View Attachment</a></small>` : ''}
          </div>
        </div>
        <div class="activity-actions">
          ${a.activity_type === 'quiz' ? `<button class="btn btn-outline-primary btn-sm manage-quiz-btn" title="Manage in Activities"><i class="fas fa-list"></i> Manage</button>` : ''}
          <button class="icon-btn edit-activity-btn" data-activity-id="${a.id || a.activity_id}" data-activity-title="${a.activity_title}" data-activity-description="${a.activity_description || ''}" data-due-date="${a.due_date || ''}" data-max-score="${a.max_score || 100}"><i class="fas fa-edit"></i></button>
          <button class="icon-btn delete-activity-btn" data-activity-id="${a.id || a.activity_id}" data-activity-title="${a.activity_title}"><i class="fas fa-trash"></i></button>
        </div>
      </div>
    `).join('');
  }

  function renderSubmissionsView(topicsByCompetency) {
    const container = document.getElementById('submissions-list');
    if (!container) return;
    let all = [];
    Object.values(topicsByCompetency).forEach(arr => arr.forEach(t => { if (t.activities) all.push(...t.activities); }));
    if (!all.length) { container.innerHTML = '<p class="no-data">No activities have been created for this course yet.</p>'; return; }
    container.innerHTML = all.map(a => `
      <div class="activity-submission-item">
        <div class="activity-header"><strong>${a.activity_title}</strong> (${(a.submissions || []).length} submissions)</div>
        ${renderSubmissions(a.submissions || [], a.max_score, a.id)}
      </div>`).join('');
  }

  function renderSubmissions(submissions, maxScore, activityId) {
    if (!submissions.length) return '<p class="no-submissions">No submissions yet.</p>';
    return `
      <table class="submissions-table"><thead><tr><th>Trainee</th><th>Submitted At</th><th>Submission</th><th>Score</th><th>Actions</th></tr></thead><tbody>
      ${submissions.map(s => `
        <tr data-submission-id="${s.submission_id}"><td>${s.trainee_first_name} ${s.trainee_last_name}</td><td>${new Date(s.submitted_at).toLocaleString()}</td><td><a href="../uploads/submissions/${s.submission_file_path}" target="_blank">View File</a></td><td class="score-cell">${s.score !== null ? `${s.score} / ${maxScore}` : 'Not Graded'}</td><td><button class="grade-btn" data-submission-id="${s.submission_id}" data-current-score="${s.score || ''}" data-max-score="${maxScore}" data-feedback="${s.feedback || ''}">${s.score !== null ? 'Edit Grade' : 'Grade'}</button></td></tr>
      `).join('')}
      </tbody></table>`;
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
    const addActivityBtn = e.target.closest('.add-activity-btn');
    if (addActivityBtn) openAddActivityModal(addActivityBtn.dataset.topicId);
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
        fetch('../php/delete_course_material.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ material_id: id })
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
        fetch('../php/delete_material.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ material_id: id })
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
        fetch('../php/delete_activity.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ activity_id: id })
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
    if (typeSel) typeSel.value = 'file';
    if (fileGroup) fileGroup.classList.remove('hidden');
    if (linkGroup) linkGroup.classList.add('hidden');
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
    document.getElementById('activity_topic_id').value = topicId;
    if (typeof openModal === 'function') { openModal(modal); } else { modal && modal.classList.remove('hidden'); modal && (modal.style.display = 'flex'); }
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
    fetch('../php/add_materials.php', { method: 'POST', body: formData })
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
  const activityAttachmentSel = document.getElementById('activity_attachment_type');
  const activityFileGroup = document.getElementById('activity_file_input_group');
  const activityLinkGroup = document.getElementById('activity_link_input_group');
  document.getElementById('cancelActivity')?.addEventListener('click', () => {
    document.getElementById('addActivityModal')?.classList.add('hidden');
  });
  document.getElementById('closeActivityModal')?.addEventListener('click', () => {
    document.getElementById('addActivityModal')?.classList.add('hidden');
  });
  activityAttachmentSel?.addEventListener('change', function() {
    const type = this.value;
    if (type === 'file') {
      activityFileGroup?.classList.remove('hidden');
      activityLinkGroup?.classList.add('hidden');
    } else if (type === 'link') {
      activityLinkGroup?.classList.remove('hidden');
      activityFileGroup?.classList.add('hidden');
    } else {
      activityFileGroup?.classList.add('hidden');
      activityLinkGroup?.classList.add('hidden');
    }
  });
  addActivityForm?.addEventListener('submit', function(e) {
    e.preventDefault();
    const startDateDate = document.getElementById('start_date_date')?.value || '';
    const startDateTime = document.getElementById('start_date_time')?.value || '';
    const dueDateDate = document.getElementById('due_date_date')?.value || '';
    const dueDateTime = document.getElementById('due_date_time')?.value || '';
    const startHidden = document.getElementById('start_date');
    const dueHidden = document.getElementById('due_date');
    if (startHidden) startHidden.value = (startDateDate && startDateTime) ? `${startDateDate} ${startDateTime}:00` : '';
    if (dueHidden) dueHidden.value = (dueDateDate && dueDateTime) ? `${dueDateDate} ${dueDateTime}:00` : '';
    const formData = new FormData(addActivityForm);
    const submitBtn = addActivityForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true; submitBtn.textContent = 'Adding...';
    fetch('../php/add_activity.php', { method: 'POST', body: formData })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          document.getElementById('addActivityModal')?.classList.add('hidden');
          openConfirm('Confirm Action', 'Activity added successfully.', () => {
            const code = document.getElementById('course-code')?.textContent || '';
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
    document.getElementById('submission_score').max = maxScore;
    document.getElementById('submission_max_score').textContent = maxScore;
    document.getElementById('submission_feedback').value = feedback;
    gradeModal.classList.remove('hidden');
  }

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
    fetch('../php/grade_submission.php', { method: 'POST', body: formData })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          openConfirm('Success', 'Grade saved successfully!', () => {}, { confirmText: 'OK', showCancel: false });
          gradeModal.classList.add('hidden');
          const row = document.querySelector(`tr[data-submission-id="${submissionId}"]`);
          if (row) {
            const cell = row.querySelector('.score-cell');
            cell.textContent = `${score} / ${maxScore}`;
            const btn = row.querySelector('.grade-btn');
            btn.textContent = 'Edit Grade';
            btn.dataset.currentScore = score;
            btn.dataset.feedback = formData.get('feedback');
          }
        } else {
          openConfirm('Error', 'Error saving grade: ' + (d.message || 'Unknown'), () => {}, { confirmText: 'OK', showCancel: false });
        }
      })
      .catch(() => { openConfirm('Error', 'Network error while saving grade', () => {}, { confirmText: 'OK', showCancel: false }); })
      .finally(() => { submitBtn.disabled = false; submitBtn.textContent = 'Save Grade'; });
  });
});
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
    fetch('../php/add_topic.php', { method: 'POST', body: formData })
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
    if (idEl) idEl.value = material.id || '';
    if (titleEl) titleEl.value = material.title || '';
    if (descEl) descEl.value = material.description || '';
    if (infoEl) {
      infoEl.innerHTML = material.path ? `<small>Current file: ${material.path}</small>` : '<small>No file attached</small>';
    }
    modal && modal.classList.remove('hidden');
  }

  document.getElementById('cancelEditMaterial')?.addEventListener('click', () => document.getElementById('editMaterialModal')?.classList.add('hidden'));
  document.getElementById('closeEditMaterialModal')?.addEventListener('click', () => document.getElementById('editMaterialModal')?.classList.add('hidden'));

  const editMaterialForm = document.getElementById('editMaterialForm');
  editMaterialForm?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(editMaterialForm);
    const submitBtn = editMaterialForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true; submitBtn.textContent = 'Saving...';
    const endpoint = editMaterialScope === 'course' ? '../php/update_course_material.php' : '../php/update_material.php';
    fetch(endpoint, { method: 'POST', body: formData })
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
