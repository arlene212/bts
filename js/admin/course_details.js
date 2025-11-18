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
    <div class="course-details"><div class="course-header">`;
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
    html += `<div class="course-section hidden" id="course-content-section"><h4>Competencies & Content</h4><div class="competencies-accordion">`;
    data.competencies.forEach(comp => {
      if (comp && comp.type && comp.name) {
        html += `<div class="competency-item"><div class="competency-header"><strong>${comp.type.toUpperCase()} COMPETENCY:</strong>${comp.name}${comp.description ? ` - ${comp.description}` : ''}<span class="toggle-icon">▼</span></div><div class="competency-content">`;
        if (comp.topics && comp.topics.length > 0) {
          html += `<div class="topics-section">`;
          comp.topics.forEach(topic => {
            html += `<div class="topic-item"><h5>📚 ${topic.topic_name}</h5>${topic.topic_description ? `<p class="topic-description">${topic.topic_description}</p>` : ''}`;
            if (topic.materials && topic.materials.length > 0) {
              html += `<div class="materials-section"><h6>📎 Course Materials:</h6><ul class="materials-list">`;
              topic.materials.forEach(material => {
                const filePath = material.material_file_path || material.file_path;
                const isLink = filePath && (filePath.startsWith('http://') || filePath.startsWith('https://'));
                const downloadPath = isLink ? filePath : `../uploads/courses/${filePath}`;
                html += `<li><strong>${material.material_title}</strong>${material.material_description ? `<br><small>${material.material_description}</small>` : ''}${filePath ? `<br><a href="${downloadPath}" target="_blank" class="download-link">View Material</a>` : ''}<br><small>Uploaded by: ${material.first_name || 'Unknown'} ${material.last_name || ''} on ${new Date(material.uploaded_at).toLocaleDateString()}</small></li>`;
              });
              html += `</ul></div>`;
            }
            if (topic.activities && topic.activities.length > 0) {
              html += `<div class="activities-section"><h6>📝 Activities:</h6><div class="activities-list">`;
              topic.activities.forEach(activity => {
                html += `<div class="activity-item"><div class="activity-header"><strong>${activity.activity_title}</strong><span class="activity-type ${activity.activity_type}">${activity.activity_type}</span><span class="toggle-submissions-icon">▶</span></div><div class="activity-details">${activity.due_date ? `<small>Due: ${new Date(activity.due_date).toLocaleString()}</small>` : ''}${activity.max_score ? `<small>Max Score: ${activity.max_score}</small>` : ''}${activity.activity_description ? `<p>${activity.activity_description}</p>` : ''}</div><div class="submissions-section hidden"><h6>📤 Submissions (${(activity.submissions || []).length}):</h6>`;
                if (activity.submissions && activity.submissions.length > 0) {
                  html += `<table class="submissions-table"><thead><tr><th>Trainee</th><th>Submitted</th><th>Status</th><th>Score</th><th>Actions</th></tr></thead><tbody>`;
                  activity.submissions.forEach(submission => {
                    const status = submission.score !== null ? 'Graded' : 'Submitted';
                    const statusClass = submission.score !== null ? 'graded' : 'submitted';
                    html += `<tr><td>${submission.first_name} ${submission.last_name}</td><td>${new Date(submission.submitted_at).toLocaleString()}</td><td><span class="status-badge ${statusClass}">${status}</span></td><td>${submission.score !== null ? submission.score : 'Not graded'}</td><td>${submission.file_path ? `<a href="../uploads/submissions/${submission.file_path}" target="_blank" class="view-submission-btn">View</a>` : ''}${submission.submission_text ? `<button class="view-text-btn" data-text="${submission.submission_text}">View Text</button>` : ''}</td></tr>`;
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