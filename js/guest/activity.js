let selectedFile = null;
let currentActivity = null;

function getActivityInstructions(activityType, activityTitle) {
  if (activityType === 'quiz') {
    return '<p>This quiz will test your understanding of the course material.</p><ul><li>Total questions: 10</li><li>Time limit: 30 minutes</li><li>Multiple choice format</li><li>You cannot pause once started</li></ul>';
  } else {
    return '<p>Complete the assignment and submit your work before the due date.</p><ul><li>Submit in PDF, DOC, or DOCX format</li><li>Maximum file size: 10MB</li><li>Ensure your name is included in the submission</li><li>Late submissions may be penalized</li></ul>';
  }
}

function resetFileUpload() {
  selectedFile = null;
  const input = document.getElementById('activityFileInput');
  const preview = document.getElementById('activityFilePreview');
  const submitBtn = document.getElementById('activitySubmitBtn');
  if (input) input.value = '';
  if (preview) preview.classList.add('hidden');
  if (submitBtn) submitBtn.disabled = true;
  const successEl = document.getElementById('submissionSuccess');
  if (successEl) successEl.classList.add('hidden');
}

function removeSelectedFile() {
  selectedFile = null;
  const input = document.getElementById('activityFileInput');
  const preview = document.getElementById('activityFilePreview');
  const submitBtn = document.getElementById('activitySubmitBtn');
  if (input) input.value = '';
  if (preview) preview.classList.add('hidden');
  if (submitBtn) submitBtn.disabled = true;
}
window.removeSelectedFile = removeSelectedFile;

function displayFilePreview(file) {
  const preview = document.getElementById('activityFilePreview');
  const sizeMb = (file.size / 1024 / 1024).toFixed(2);
  preview.innerHTML = `<div class="file-preview-item"><i class="fas fa-file"></i><div class="file-info"><div class="file-name">${file.name}</div><div class="file-size">${sizeMb} MB</div></div><button class="remove-file-btn" onclick="removeSelectedFile()"><i class="fas fa-times"></i></button></div>`;
  preview.classList.remove('hidden');
}

function openActivityModal(activityId, activityType, activityTitle) {
  currentActivity = { id: activityId, type: activityType, title: activityTitle };
  const activityModal = document.getElementById('activityModal');
  const titleEl = document.getElementById('activityModalTitle');
  const instructionsEl = document.getElementById('activityInstructions');
  const dueDateEl = document.getElementById('activityDueDate');
  const uploadSection = document.getElementById('uploadSection');
  const quizSection = document.getElementById('quizSection');
  if (titleEl) titleEl.textContent = activityTitle;
  if (instructionsEl) instructionsEl.innerHTML = getActivityInstructions(activityType, activityTitle);
  if (dueDateEl) { const d = new Date(); d.setDate(d.getDate() + 7); dueDateEl.textContent = d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }); }
  if (uploadSection && quizSection) { if (activityType === 'quiz') { uploadSection.classList.add('hidden'); quizSection.classList.remove('hidden'); } else { uploadSection.classList.remove('hidden'); quizSection.classList.add('hidden'); } }
  resetFileUpload();
  if (activityModal) openModal(activityModal);
}

function setupActivityBindings() {
  document.addEventListener('click', function(e) {
    const materialItem = e.target.closest('.material-item.activity');
    if (materialItem) {
      const activityId = materialItem.getAttribute('data-activity');
      const activityType = materialItem.getAttribute('data-type');
      const activityTitle = materialItem.querySelector('.material-title').textContent;
      openActivityModal(activityId, activityType, activityTitle);
    }
  });
  const uploadArea = document.getElementById('uploadArea');
  const activityFileInput = document.getElementById('activityFileInput');
  const submitBtn = document.getElementById('activitySubmitBtn');
  if (uploadArea && activityFileInput) { uploadArea.addEventListener('click', function() { activityFileInput.click(); }); }
  if (activityFileInput) {
    activityFileInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) { selectedFile = file; displayFilePreview(file); if (submitBtn) submitBtn.disabled = false; }
    });
  }
  document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'activitySubmitBtn') {
      (async function(){
        const submitBtn = document.getElementById('activitySubmitBtn');
        const activityModal = document.getElementById('activityModal');
        if (!selectedFile) { showNotification('Please select a file to submit.', 'error'); return; }
        if (!currentActivity || !currentActivity.id) { showNotification('Activity not selected.', 'error'); return; }
        submitBtn.disabled = true; submitBtn.textContent = 'Submitting...';
        try {
          const formData = new FormData();
          formData.append('activity_id', currentActivity.id);
          formData.append('submission_file', selectedFile);
          const resp = await fetch('../php/submit_activity.php', { method: 'POST', body: formData });
          const data = await resp.json();
          if (data.success) {
            showNotification('Assignment submitted successfully!', 'success');
            updateActivityStatus(currentActivity.id, 'submitted');
            const successEl = document.getElementById('submissionSuccess');
            if (successEl) successEl.classList.remove('hidden');
            submitBtn.disabled = true;
            setTimeout(() => { if (activityModal) closeModal(activityModal); }, 1200);
          } else {
            showNotification(data.error || data.message || 'Submission failed.', 'error');
            submitBtn.disabled = false; submitBtn.textContent = 'Submit Assignment';
          }
        } catch (err) {
          showNotification('An error occurred during submission. Please try again.', 'error');
          submitBtn.disabled = false; submitBtn.textContent = 'Submit Assignment';
        }
      })();
    }
  });
  const startQuizBtnEl = document.getElementById('startQuizBtn');
  if (startQuizBtnEl) { startQuizBtnEl.addEventListener('click', function() { showNotification('Quiz functionality would be implemented with full backend integration', 'info'); const activityModal = document.getElementById('activityModal'); if (activityModal) closeModal(activityModal); }); }
}

function updateActivityStatus(activityId, status) {
  const activityItem = document.querySelector(`[data-activity="${activityId}"]`);
  if (activityItem) { const statusElement = activityItem.querySelector('.material-status'); if (statusElement) { statusElement.textContent = status.charAt(0).toUpperCase() + status.slice(1); statusElement.className = 'material-status ' + status; } }
}