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
}

function openEditCourseModal(courseData) {
  document.getElementById('edit_course_code').value = courseData.course_code;
  document.getElementById('edit_course_code_display').value = courseData.course_code;
  document.getElementById('edit_course_name').value = courseData.course_name || '';
  document.getElementById('edit_course_hours').value = courseData.hours || '';
  document.getElementById('edit_course_description').value = courseData.description || '';
  const imagePreview = document.getElementById('current_image_preview');
  if (courseData.image) {
    imagePreview.innerHTML = `<p><strong>Current Image:</strong></p><img src="../uploads/courses/${courseData.image}" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; padding: 5px;">`;
  } else {
    imagePreview.innerHTML = '<p><em>No current image</em></p>';
  }
  populateCompetencies(courseData.competency_types || []);
  openModal('editCourseModal');
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
          <button type="button" class="remove-competency-btn" data-index="${index}"><i class="fas fa-times"></i></button>
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