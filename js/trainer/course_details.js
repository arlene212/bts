document.addEventListener('DOMContentLoaded', () => {
  const enrolledTab = document.getElementById('enrolled');
  const courseDetail = document.getElementById('course-detail');
  const courseBackBtn = courseDetail ? courseDetail.querySelector('.back-btn') : null;

  document.querySelectorAll('#enrolled .batch-card').forEach(batch => {
    batch.addEventListener('click', () => {
      const courseName = batch.dataset.course;
      const courseCode = batch.dataset.code;
      const courseHours = batch.dataset.hours;
      const courseDataStr = batch.dataset.courseData;
      loadCourseDetails(courseCode, courseName, courseHours, courseDataStr);
      enrolledTab && enrolledTab.classList.add('hidden');
      courseDetail && courseDetail.classList.remove('hidden');
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
  });

  function loadCourseDetails(courseCode, courseName, courseHours, courseDataStr) {
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
        document.getElementById('course-description')?.textContent = data.course?.description || 'No description available';
        let courseData = courseDataStr ? JSON.parse(courseDataStr) : { competency_types: [] };
        courseData.competency_types.forEach(comp => { comp.topics = data.topicsByCompetency[comp.name] || []; });
        renderCompetencies(courseData.competency_types || []);
        renderSubmissionsView(data.topicsByCompetency || {});
      })
      .catch(err => {
        document.getElementById('course-description')?.textContent = 'Error loading course details';
        if (comps) comps.innerHTML = `<div class="error-message">${err.message}</div>`;
        if (subs) subs.innerHTML = `<div class="error-message">${err.message}</div>`;
      });
  }

  function renderCompetencies(competencies) {
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
              <button class="add-topic-btn" data-competency-id="${comp.id || comp.name}" data-competency-name="${comp.name}"><i class="fas fa-plus"></i> Add Topic</button>
            </div>
          </div>
          ${comp.description ? `<p class="competency-description">${comp.description}</p>` : ''}
        </div>
        <div class="topics-list">${renderTopics(comp.topics || [])}</div>
      `;
      container.appendChild(el);
    });
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
            <button class="add-material-btn" data-topic-id="${topic.id}"><i class="fas fa-plus-circle"></i> Add Material</button>
            <button class="add-activity-btn" data-topic-id="${topic.id}"><i class="fas fa-tasks"></i> Add Activity</button>
            <button class="toggle-materials-btn" title="Show/Hide"><i class="fas fa-chevron-down"></i></button>
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
          <div class="material-info">
            <strong>${m.material_title}</strong>
            <p>${m.material_description || ''}</p>
            <small><a href="${m.material_file_path && String(m.material_file_path).startsWith('http') ? m.material_file_path : ('../uploads/courses/' + m.material_file_path)}" target="_blank">View Material</a></small>
          </div>
          <div class="material-actions">
            <button class="icon-btn edit-material-btn" data-material-id="${m.id}"><i class="fas fa-edit"></i></button>
            <button class="icon-btn delete-material-btn" data-material-id="${m.id}" data-material-title="${m.material_title}"><i class="fas fa-trash"></i></button>
          </div>
        </div>
      </div>`).join('')}</div>`;
  }

  function renderActivities(activities) {
    if (!activities.length) return '<p class="no-materials">No activities added.</p>';
    return activities.map(a => `
      <div class="activity-item">
        <div class="activity-header">
          <div class="activity-info-main">
            <div class="activity-info"><strong>${a.activity_title}</strong><span class="activity-type ${a.activity_type}">${a.activity_type}</span></div>
            <div class="activity-actions">
              <button class="icon-btn edit-activity-btn" data-activity-id="${a.id}"><i class="fas fa-edit"></i></button>
              <button class="icon-btn delete-activity-btn" data-activity-id="${a.id}" data-activity-title="${a.activity_title}"><i class="fas fa-trash"></i></button>
            </div>
          </div>
          <div class="activity-meta"><small>Due: ${new Date(a.due_date).toLocaleString()}</small><small>Max Score: ${a.max_score}</small>${a.attachment_path ? `<small><a href="${String(a.attachment_path).startsWith('http') ? a.attachment_path : ('../uploads/activities/' + a.attachment_path)}" target="_blank" class="attachment-link"><i class="fas fa-paperclip"></i> View Attachment</a></small>` : ''}</div>
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
  });

  function openAddTopicModal(courseCode, courseName, competencyId, competencyName) {
    const modal = document.getElementById('addTopicModal');
    const codeEl = document.getElementById('topic_course_code');
    const compIdEl = document.getElementById('topic_competency_id');
    const compNameEl = document.getElementById('topic_competency_name');
    const courseNameEl = document.getElementById('topic_course_name');
    if (codeEl) codeEl.value = courseCode || '';
    if (compIdEl) compIdEl.value = competencyId || '';
    if (compNameEl) compNameEl.textContent = competencyName || '';
    if (courseNameEl) courseNameEl.textContent = courseName || '';
    modal && modal.classList.remove('hidden');
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
    modal.classList.remove('hidden');
  }

  function openAddActivityModal(topicId) {
    const modal = document.getElementById('addActivityModal');
    document.getElementById('activity_topic_id').value = topicId;
    modal.classList.remove('hidden');
  }

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
          alert('Grade saved successfully!');
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
          alert('Error saving grade: ' + (d.message || 'Unknown'));
        }
      })
      .catch(() => {})
      .finally(() => { submitBtn.disabled = false; submitBtn.textContent = 'Save Grade'; });
  });
});