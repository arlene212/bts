document.addEventListener('DOMContentLoaded', () => {
  const courseDetail = document.getElementById('courseDetail') || document.getElementById('course-detail');
  const backBtn = courseDetail ? courseDetail.querySelector('.back-btn') : null;
  const modulesView = document.getElementById('modules-view');
  const activitiesView = document.getElementById('activities-view');

  function openCourseDetail(card) {
    const courseCode = card.getAttribute('data-course-code') || card.getAttribute('data-course');
    const courseName = card.getAttribute('data-course-name') || card.getAttribute('data-title');
    const courseHours = card.getAttribute('data-course-hours');
    const courseDescription = card.getAttribute('data-course-description');

    if (document.getElementById('course-detail-title')) document.getElementById('course-detail-title').textContent = courseName || 'Course Details';
    if (document.getElementById('course-detail-code')) document.getElementById('course-detail-code').textContent = `Code: ${courseCode || 'N/A'}`;
    if (document.getElementById('course-detail-hours')) document.getElementById('course-detail-hours').textContent = `Hours: ${courseHours || 'N/A'} hrs`;
    if (document.getElementById('course-detail-description')) document.getElementById('course-detail-description').textContent = courseDescription || '';

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
    const switchInner = courseDetail?.querySelector('.course-switch .switch-inner');
    const buttons = courseDetail?.querySelectorAll('.course-switch .switch-btn');
    if (switchInner && buttons && buttons.length) {
      switchInner.style.transform = 'translateX(0%)';
      modulesView?.classList.add('active');
      activitiesView?.classList.remove('active');
      modulesView && (modulesView.style.display = 'block');
      activitiesView && (activitiesView.style.display = 'none');
      buttons[0].classList.add('active');
      buttons[1].classList.remove('active');
    }
  }

  function loadCourseDetails(courseCode) {
    const competenciesList = document.getElementById('competencies-list');
    const activitiesContainer = document.getElementById('activities-view');
    if (!competenciesList || !activitiesContainer) return;
    competenciesList.innerHTML = '<div>Loading course content...</div>';
    activitiesContainer.innerHTML = '<div>Loading activities...</div>';

    fetch(`../php/get_course_details_trainee.php?course_code=${encodeURIComponent(courseCode)}`)
      .then(r => r.json())
      .then(data => {
        if (data.error) {
          competenciesList.innerHTML = `<div class="error-message">${data.error}</div>`;
          activitiesContainer.innerHTML = `<div class="error-message">${data.error}</div>`;
          return;
        }
        renderCompetencies(data.competencies || [], competenciesList);
        renderActivitiesTable(data.activities || [], activitiesContainer, data.submissions || {});
      })
      .catch(() => {
        competenciesList.innerHTML = '<div class="error-message">Failed to load course content.</div>';
      });
  }

  function renderCompetencies(competencies, container) {
    container.innerHTML = '';
    if (!competencies || competencies.length === 0) {
      container.innerHTML = '<p>No competencies found for this course.</p>';
      return;
    }
    const types = ['basic', 'common', 'core'];
    types.forEach(type => {
      const group = competencies.filter(c => c.type === type);
      if (group.length) {
        const header = document.createElement('h3');
        header.className = 'competency-type-header';
        header.textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} Competencies`;
        container.appendChild(header);
        group.forEach(comp => {
          const el = document.createElement('div');
          el.className = 'competency-item';
          el.innerHTML = `
            <div class="competency-header">
              <h4>${comp.name}</h4>
              <p>${comp.description || ''}</p>
            </div>
            <div class="topics-list">${renderTopics(comp.topics || [])}</div>
          `;
          container.appendChild(el);
        });
      }
    });
  }

  function renderTopics(topics) {
    if (!topics || topics.length === 0) return '<p class="no-materials">No topics for this competency.</p>';
    return topics.map(t => `
      <div class="topic-container">
        <h4 class="topic-title">${t.topic_name || t.name || 'Unnamed Topic'}</h4>
        <div class="topic-content-section">
          <h6 class="content-divider">Materials</h6>
          ${renderMaterials(t.materials || [])}
        </div>
        ${renderActivitiesForTopic(t.activities || [])}
      </div>
    `).join('');
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

  function renderActivitiesForTopic(activities) {
    if (!activities || activities.length === 0) return '';
    let html = '<div class="topic-content-section">';
    html += '<h6 class="content-divider">Activities</h6>';
    html += '<div class="activities-list">';
    activities.forEach(a => {
      html += `
        <div class="topic-activity-item" data-activity-id="${a.id}">
          <div class="activity-header">
            <div class="activity-info-cleaned">
              <strong>${a.title || 'Unnamed Activity'}</strong>
              <p>Due: ${formatDisplayDate(a.due_date)}</p>
            </div>
          </div>
          <div class="activity-content hidden">
            <div class="activity-instructions"><h4><i class="fas fa-info-circle"></i> Instructions</h4><p>${a.description || 'No instructions provided.'}</p></div>
            ${a.submission ? renderSubmissionHistory(a) : renderSubmissionForm(a)}
          </div>
        </div>
      `;
    });
    html += '</div>';
    return html + '</div>';
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

  document.getElementById('modules-view')?.addEventListener('click', function(e) {
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

  document.getElementById('modules-view')?.addEventListener('change', function(e) {
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
});