document.addEventListener('click', function(e) {
  const btn = e.target.closest('.view-details-btn');
  if (!btn) return;
  const courseDataStr = btn.getAttribute('data-course');
  try {
    const courseData = JSON.parse(courseDataStr);
    const courseGrid = document.querySelector('.courses-grid');
    const detailView = document.getElementById('course-detail-view');
    if (!courseGrid || !detailView) return;
    courseGrid.classList.add('hidden');
    detailView.classList.remove('hidden');
    detailView.innerHTML = '<div class="loading">Loading course details...</div>';
    fetch(`../php/get_course_details.php?course_code=${courseData.course_code}`)
      .then(response => response.json())
      .then(courseDetails => {
        renderCourseDetails(detailView, courseDetails);
        const backBtn = detailView.querySelector('.back-to-course-list-btn');
        if (backBtn) {
          backBtn.classList.remove('hidden');
          backBtn.addEventListener('click', () => {
            detailView.classList.add('hidden');
            courseGrid.classList.remove('hidden');
            detailView.innerHTML = '';
          });
        }
      })
      .catch(() => { detailView.innerHTML = '<div class="error">Error loading course details. Please try again.</div>'; });
  } catch (_) { alert('Error loading course details.'); }
});

function renderCourseDetails(content, data) {
  if (data.error) { content.innerHTML = `<div class="error">${data.error}</div>`; return; }
  let html = `
    <button class="back-to-course-list-btn hidden">← Back to Course Details</button>
    <div class="course-details"><style>
      .course-details { padding: 12px; }
      .course-header { display:flex; gap:16px; align-items:flex-start; }
      .course-detail-image { width:160px; height:120px; object-fit:cover; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
      .course-basic-info h3 { margin:0 0 6px 0; }
      .detail-section { margin-top:16px; }
      #batch-selection-section .batches-list { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:10px; }
      .batch-item { display:flex; align-items:center; justify-content:space-between; padding:10px; border:1px solid #e2e6ea; border-radius:8px; background:#fafafa; cursor:pointer; }
      .competency-index { display:flex; flex-wrap:wrap; gap:8px; margin:8px 0 12px; }
      .comp-index-chip { padding:6px 10px; border-radius:16px; border:1px solid #e2e6ea; background:#f8f9fa; font-size:0.85em; cursor:pointer; }
      .comp-index-chip.core { background:#ffe3e3; border-color:#ff8787; color:#c92a2a; }
      .comp-index-chip.common { background:#fff3bf; border-color:#fcc419; color:#7c5d00; }
      .comp-index-chip.basic { background:#e7f5ff; border-color:#74c0fc; color:#1c7ed6; }
      .competencies-accordion .competency-item { border:1px solid #e2e6ea; border-radius:8px; margin-bottom:10px; overflow:hidden; }
      .competency-header { display:flex; justify-content:space-between; align-items:center; padding:10px 12px; background:#f8f9fa; cursor:pointer; }
      .competency-content { display:none; padding:10px 12px; }
      .competency-content.active { display:block; }
      .toggle-icon { font-size:0.9em; color:#6c757d; }
      .comp-meta { display:flex; flex-wrap:wrap; gap:12px; padding:8px; background:#f9fafb; border:1px solid #e9ecef; border-radius:6px; margin-bottom:10px; }
      .comp-meta .meta-item { min-width:240px; }
      .comp-meta .lo-content { padding:6px 8px; background:#fff; border:1px dashed #e2e6ea; border-radius:6px; }
      .topics-section .topic-item { border-top:1px dashed #e2e6ea; padding:10px 0; }
      .materials-section, .activities-section { margin-top:8px; }
      .submissions-section { margin-top:8px; }
    </style><div class="course-header">`;
  if (data.course && data.course.image) {
    html += `<img src="../uploads/courses/${data.course.image}" alt="${data.course.course_name}" class="course-detail-image">`;
  } else {
    html += `<div class="course-detail-image-placeholder"><i class="fas fa-book"></i></div>`;
  }
  html += `<div class="course-basic-info"><h3>${data.course?.course_name || 'Unknown Course'}</h3><p><strong>Code:</strong> ${data.course?.course_code || 'N/A'}</p><p><strong>Hours:</strong> ${data.course?.hours || 'N/A'}</p><p><strong>Description:</strong> ${data.course?.description || 'No description available'}</p></div></div>`;
  if (data.batches && data.batches.length > 0) {
    html += `<div class="course-section" id="batch-selection-section"><h4>Batches</h4><div class="batches-list">`;
    data.batches.forEach(batch => { html += `<div class="batch-item" data-batch-name="${batch.batch_name}"><i class="fas fa-users"></i><div class="batch-item-info"><strong>${batch.batch_name}</strong><small>${batch.trainee_count} trainee(s)</small></div><span class="view-content-arrow">→</span></div>`; });
    html += `</div></div>`;
  } else {
    html += `<div class="course-section" id="batch-selection-section"><h4>Batches</h4><p class="no-data">No batches defined for this course.</p></div>`;
  }
  if (data.competencies && data.competencies.length > 0) {
    // Competency index chips
    html += `<div class="detail-section"><h4>Competency Index</h4><div class="competency-index">`;
    data.competencies.forEach(ci => {
      const t = ci.competency_type || ci.type; const n = ci.competency_name || ci.name; const code = ci.competency_code || ci.code;
      if (t && n) { html += `<span class="comp-index-chip ${t}" data-comp-code="${code}" title="${String(t).toUpperCase()} - ${n}">${n}</span>`; }
    });
    html += `</div></div>`;
    // Competencies accordion
    html += `<div class="course-section" id="course-content-section"><h4>Competencies & Content</h4><div class="competencies-accordion">`;
    data.competencies.forEach(comp => {
      const type = comp.competency_type || comp.type;
      const name = comp.competency_name || comp.name;
      const desc = comp.description || '';
      const code = comp.competency_code || comp.code;
      if (type && name) {
        html += `<div class="competency-item" id="comp-${code}"><div class="competency-header"><strong>${String(type).toUpperCase()}:</strong> ${name}${desc ? ` - ${desc}` : ''}<span class="toggle-icon">▼</span></div><div class="competency-content">`;
        const mt = comp.module_title || '';
        const nh = comp.nominal_hours != null ? comp.nominal_hours : '';
        const lo = comp.learning_outcomes || '';
        html += `<div class="comp-meta">
          <div class="meta-item"><strong>Module Title:</strong> ${mt || '—'}</div>
          <div class="meta-item"><strong>Hours:</strong> ${nh || '—'}</div>
          <div class="meta-item" style="flex:1 1 100%"><strong>Learning Outcomes:</strong><div class="lo-content">${lo}</div></div>
        </div>`;
        const modules = (data.materialsByCompetency && data.materialsByCompetency[code]) ? data.materialsByCompetency[code] : [];
        if (modules.length) {
          html += `<div class="materials-section"><h6>📚 Modules:</h6><ul class="materials-list">`;
          modules.forEach(m => {
            const fp = m.file_path;
            const isLink = fp && (fp.startsWith('http://') || fp.startsWith('https://'));
            const href = isLink ? fp : (fp ? `../uploads/courses/${fp}` : '');
            html += `<li><strong>${m.title || '(Untitled Module)'}</strong>${href ? `<br><a href="${href}" target="_blank" class="download-link">View</a>` : ''}${m.date_created ? `<br><small>Added ${new Date(m.date_created).toLocaleDateString()}</small>` : ''}</li>`;
          });
          html += `</ul></div>`;
        }
        const quizzes = (data.quizzesByCompetency && data.quizzesByCompetency[code]) ? data.quizzesByCompetency[code] : [];
        if (quizzes.length) {
          html += `<div class="materials-section"><h6>🧪 Quizzes:</h6><ul class="materials-list">`;
          quizzes.forEach(q => {
            const due = q.due_date ? new Date(q.due_date).toLocaleString() : '';
            const tl = q.time_limit ? `${q.time_limit} min` : 'No limit';
            const ps = (q.passing_score != null) ? `${q.passing_score}%` : '—';
            const qa = (q.attempt_count != null) ? q.attempt_count : 0;
            const qc = (q.question_count != null) ? q.question_count : 0;
            html += `<li>
              <strong>${q.title || '(Untitled Activity)'}</strong>
              <br><small>Status: ${q.status || 'draft'}</small>
              ${due ? `<br><small>Due: ${due}</small>` : ''}
              <br><small>Questions: ${qc} • Attempts: ${qa} • Time: ${tl} • Pass: ${ps}</small>
            </li>`;
          });
          html += `</ul></div>`;
        }
        if ((comp.topics && comp.topics.length > 0)) {
          html += `<div class="topics-section">`;
          comp.topics.forEach(topic => {
            html += `<div class="topic-item"><h5>🧩 ${topic.topic_name}</h5>${topic.topic_description ? `<p class="topic-description">${topic.topic_description}</p>` : ''}`;
            if (topic.activities && topic.activities.length > 0) {
              html += `<div class="activities-section"><h6>📝 Activities:</h6><div class="activities-list">`;
              topic.activities.forEach(activity => {
                html += `<div class="activity-item"><div class="activity-header"><strong>${activity.activity_title}</strong><span class="activity-type ${activity.activity_type}">${activity.activity_type}</span><span class="toggle-submissions-icon">▶</span></div><div class="activity-details">${activity.due_date ? `<small>Due: ${new Date(activity.due_date).toLocaleString()}</small>` : ''}${activity.max_score ? `<small>Max Score: ${activity.max_score}</small>` : ''}${activity.activity_description ? `<p>${activity.activity_description}</p>` : ''}</div><div class="submissions-section hidden"><h6>📤 Submissions (${(activity.submissions || []).length}):</h6>`;
                if (activity.submissions && activity.submissions.length > 0) {
                  html += `<table class="submissions-table"><thead><tr><th>Trainee</th><th>Submitted</th><th>Status</th><th>Score</th><th>Actions</th></tr></thead><tbody>`;
                  activity.submissions.forEach(submission => {
                    const status = submission.score !== null ? 'Graded' : 'Submitted';
                    const statusClass = submission.score !== null ? 'graded' : 'submitted';
                    html += `<tr><td>${submission.trainee_first_name || submission.first_name || ''} ${submission.trainee_last_name || submission.last_name || ''}</td><td>${new Date(submission.submitted_at).toLocaleString()}</td><td><span class="status-badge ${statusClass}">${status}</span></td><td>${submission.score !== null ? submission.score : 'Not graded'}</td><td>${submission.submission_file_path || submission.file_path ? `<a href="../uploads/submissions/${submission.submission_file_path || submission.file_path}" target="_blank" class="view-submission-btn">View</a>` : ''}${submission.submission_text ? `<button class="view-text-btn" data-text="${submission.submission_text}">View Text</button>` : ''}</td></tr>`;
                  });
                  html += `</tbody></table>`;
                } else {
                  html += `<p class="no-submissions">No submissions yet</p>`;
                }
                html += `</div></div>`;
              });
              html += `</div></div>`;
            }
            html += `</div>`;
          });
          html += `</div>`;
        } else {
          html += `<p class="no-topics">No topics added for this competency yet.</p>`;
        }
        html += `</div></div>`;
      }
    });
    html += `</div></div>`;
  } else {
    html += `<div class="course-section hidden" id="course-content-section-fallback"><h4>Competencies</h4><p class="no-data">No competencies defined for this course.</p></div>`;
  }
  html += `</div>`;
  content.innerHTML = html;
  initializeBatchView(content);
  initializeAccordions();
  initializeCompetencyIndex();
  initializeSubmissionViewers();
  content.querySelectorAll('.activity-header').forEach(header => {
    header.addEventListener('click', function() {
      const submissionSection = this.closest('.activity-item').querySelector('.submissions-section');
      const icon = this.querySelector('.toggle-submissions-icon');
      if (submissionSection) {
        submissionSection.classList.toggle('hidden');
        if (icon) { icon.textContent = submissionSection.classList.contains('hidden') ? '▶' : '▼'; }
      }
    });
  });
}

function initializeBatchView(modalContent) {
  const batchItems = modalContent.querySelectorAll('.batch-item');
  const batchSection = modalContent.querySelector('#batch-selection-section');
  const contentSection = modalContent.querySelector('#course-content-section');
  const backButton = modalContent.querySelector('.back-to-course-list-btn');
  const fallbackContentSection = modalContent.querySelector('#course-content-section-fallback');
  batchItems.forEach(item => { item.addEventListener('click', () => { batchSection.classList.add('hidden'); if (contentSection) contentSection.classList.remove('hidden'); if (backButton) backButton.classList.remove('hidden'); }); });
  if (backButton) { backButton.addEventListener('click', () => { batchSection.classList.remove('hidden'); if (contentSection) { contentSection.classList.add('hidden'); } if (fallbackContentSection) { fallbackContentSection.classList.add('hidden'); } backButton.classList.add('hidden'); }); }
}

function initializeAccordions() {
  const competencyHeaders = document.querySelectorAll('.competency-header');
  competencyHeaders.forEach(header => { header.addEventListener('click', function() { const content = this.nextElementSibling; const icon = this.querySelector('.toggle-icon'); content.classList.toggle('active'); icon.textContent = content.classList.contains('active') ? '▲' : '▼'; }); });
}

function initializeCompetencyIndex() {
  document.querySelectorAll('.comp-index-chip').forEach(chip => {
    chip.addEventListener('click', function() {
      const code = this.getAttribute('data-comp-code');
      const target = document.getElementById('comp-' + code);
      if (target) {
        const section = document.getElementById('course-content-section');
        const batchSection = document.getElementById('batch-selection-section');
        batchSection && batchSection.classList.add('hidden');
        section && section.classList.remove('hidden');
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        const content = target.querySelector('.competency-content');
        const icon = target.querySelector('.toggle-icon');
        if (content && !content.classList.contains('active')) {
          content.classList.add('active');
          if (icon) icon.textContent = '▲';
        }
      }
    });
  });
}

function initializeSubmissionViewers() {
  document.querySelectorAll('.view-text-btn').forEach(btn => { btn.addEventListener('click', function() { const text = this.getAttribute('data-text'); showTextSubmissionModal(text); }); });
}

function showTextSubmissionModal(text) {
  const modal = document.createElement('div');
  modal.className = 'modal';
  modal.style.display = 'flex';
  modal.innerHTML = `<div class="modal-content"><div class="modal-header"><h2>Submission Text</h2><span class="close">&times;</span></div><div class="modal-body"><div class="submission-text-content">${(text || '').replace(/\n/g, '<br>')}</div></div><div class="modal-footer"><button class="cancel-btn">Close</button></div></div>`;
  document.body.appendChild(modal);
  const closeBtn = modal.querySelector('.close');
  const cancelBtn = modal.querySelector('.cancel-btn');
  const close = () => { modal.style.opacity = '0'; setTimeout(() => { modal.remove(); }, 300); };
  closeBtn.addEventListener('click', close);
  cancelBtn.addEventListener('click', close);
  modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
}
