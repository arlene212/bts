function setupCourseEditing() {
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('edit-course-btn')) {
      const courseDataStr = e.target.getAttribute('data-course-data');
      if (courseDataStr) {
        try { openEditCourseModal(JSON.parse(courseDataStr)); } catch (_) { alert('Error loading course data.'); }
      }
    }
  });
  const editCourseForm = document.getElementById('editCourseForm');
  if (editCourseForm) {
    editCourseForm.addEventListener('submit', function(e) { e.preventDefault(); submitEditCourseForm(this); });
  }
  
  // Handle preview toggle for edit form
  const allowPreviewSelect = document.getElementById('edit_allow_preview');
  if (allowPreviewSelect) {
    allowPreviewSelect.addEventListener('change', function() {
      const previewContentGroup = document.getElementById('edit_preview_content_group');
      if (this.value == '1') {
        previewContentGroup.style.display = 'block';
      } else {
        previewContentGroup.style.display = 'none';
      }
    });
  }
  
  // Handle preview toggle for add form
  const addAllowPreviewSelect = document.getElementById('allow_preview');
  if (addAllowPreviewSelect) {
    addAllowPreviewSelect.addEventListener('change', function() {
      const previewContentGroup = document.getElementById('preview_content_group');
      if (this.value == '1') {
        previewContentGroup.style.display = 'block';
      } else {
        previewContentGroup.style.display = 'none';
      }
    });
  }
}

function openEditCourseModal(courseData) {
  document.getElementById('edit_course_code').value = courseData.course_code;
  document.getElementById('edit_course_code_display').value = courseData.course_code;
  document.getElementById('edit_course_name').value = courseData.course_name || '';
  document.getElementById('edit_course_hours').value = courseData.hours || '';
  document.getElementById('edit_course_description').value = courseData.description || '';
  document.getElementById('edit_course_learning_outcomes').value = courseData.learning_outcomes || '';
  document.getElementById('edit_course_status').value = courseData.course_status || 'published';
  document.getElementById('edit_allow_preview').value = courseData.allow_preview || 0;
  document.getElementById('edit_course_preview_content').value = courseData.preview_content || '';
  document.getElementById('edit_require_verification').value = courseData.require_verification || 0;
  document.getElementById('edit_verification_type').value = courseData.verification_type || 'email';
  
  // Show/hide preview content based on allow_preview setting
  const previewContentGroup = document.getElementById('edit_preview_content_group');
  if (courseData.allow_preview == 1) {
    previewContentGroup.style.display = 'block';
  } else {
    previewContentGroup.style.display = 'none';
  }
  
  const imagePreview = document.getElementById('current_image_preview');
  if (courseData.image) {
    imagePreview.innerHTML = `<p><strong>Current Image:</strong></p><img src="../uploads/courses/${courseData.image}">`;
  } else {
    imagePreview.innerHTML = '<p class="field-note"><em>No current image</em></p>';
  }
  populateCompetencies(courseData.competency_types || []);
  openModal('editCourseModal');
  
  // Debug: Check if buttons exist
  setTimeout(() => {
    const submitBtn = document.querySelector('#editCourseModal .submit-btn');
    const cancelBtn = document.querySelector('#editCourseModal .cancel-btn');
    console.log('Submit button:', submitBtn);
    console.log('Cancel button:', cancelBtn);
    if (submitBtn) {
      submitBtn.style.display = 'inline-flex';
      submitBtn.style.opacity = '1';
      submitBtn.style.visibility = 'visible';
    }
    if (cancelBtn) {
      cancelBtn.style.display = 'inline-flex';
      cancelBtn.style.opacity = '1';
      cancelBtn.style.visibility = 'visible';
    }
  }, 500);
}

function populateCompetencies(competencies) {
  const container = document.getElementById('edit_competencies_container');
  container.innerHTML = '';
  if (competencies.length === 0) { container.innerHTML = '<p><em>No competencies defined</em></p>'; return; }
  competencies.forEach((comp, index) => {
    const compHtml = `
      <div class="competency-edit-group" data-index="${index}">
        <div class="competency-header">
          <h4>${comp.type ? comp.type.charAt(0).toUpperCase() + comp.type.slice(1) : 'Basic'} Competency</h4>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Type:</label>
            <select name="competencies[${index}][type]" required>
              <option value="basic" ${comp.type === 'basic' ? 'selected' : ''}>Basic</option>
              <option value="common" ${comp.type === 'common' ? 'selected' : ''}>Common</option>
              <option value="core" ${comp.type === 'core' ? 'selected' : ''}>Core</option>
            </select>
          </div>
          <div class="form-group">
            <label>Name:</label>
            <input type="text" name="competencies[${index}][name]" value="${comp.name || ''}" required placeholder="Enter competency name">
          </div>
        </div>
        <div class="form-group">
          <label>Description:</label>
          <textarea name="competencies[${index}][description]" rows="2" placeholder="Enter description (optional)">${comp.description || ''}</textarea>
        </div>
        <div class="competency-actions" style="text-align: right;">
          <button type="button" class="remove-competency-btn" data-index="${index}">Remove</button>
        </div>
        <hr>
      </div>`;
    container.innerHTML += compHtml;
  });
  container.querySelectorAll('.remove-competency-btn').forEach(btn => {
    btn.addEventListener('click', function() { const index = this.getAttribute('data-index'); removeCompetency(index); });
  });
}

function removeCompetency(index) {
  if (confirm('Are you sure you want to remove this competency?')) {
    const competencyGroup = document.querySelector(`.competency-edit-group[data-index="${index}"]`);
    if (competencyGroup) { competencyGroup.remove(); reindexCompetencies(); }
  }
}

function reindexCompetencies() {
  const competencyGroups = document.querySelectorAll('.competency-edit-group');
  let newIndex = 0;
  competencyGroups.forEach(group => {
    group.setAttribute('data-index', newIndex);
    group.querySelectorAll('[name]').forEach(input => {
      const oldName = input.getAttribute('name');
      const newName = oldName.replace(/competencies\[\d+\]/, `competencies[${newIndex}]`);
      input.setAttribute('name', newName);
    });
    const removeBtn = group.querySelector('.remove-competency-btn');
    if (removeBtn) { removeBtn.setAttribute('data-index', newIndex); }
    newIndex++;
  });
  const container = document.getElementById('edit_competencies_container');
  if (newIndex === 0) { container.innerHTML = '<p><em>No competencies defined</em></p>'; }
}

function submitEditCourseForm(form) {
  const formData = new FormData(form);
  const submitBtn = form.querySelector('.submit-btn');
  const originalText = submitBtn.textContent;
  submitBtn.textContent = 'Updating...';
  submitBtn.disabled = true;
  fetch('../php/update_course.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => { if (data.success) { alert('Course updated successfully!'); closeModal('editCourseModal'); window.location.href = window.location.pathname + '?current_tab=courses'; } else { alert('Error updating course: ' + data.message); } })
    .catch(() => { alert('Error updating course. Please try again.'); })
    .finally(() => { submitBtn.textContent = originalText; submitBtn.disabled = false; });
}