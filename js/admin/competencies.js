function initializeCompetencyHandlers() {
  document.querySelectorAll('.add-competency-btn').forEach(button => {
    button.addEventListener('click', function() {
      const type = this.getAttribute('data-type');
      addCompetencyField(type);
    });
  });
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-competency-btn')) {
      e.preventDefault();
      const fieldGroup = e.target.closest('.competency-field-group');
      if (fieldGroup) {
        fieldGroup.remove();
      }
    }
  });
}

function addCompetencyField(type) {
  const group = document.getElementById(`${type}CompetenciesGroup`);
  if (!group) return;
  const fieldsContainer = group.querySelector('.competency-fields');
  const newFieldGroup = document.createElement('div');
  newFieldGroup.className = 'competency-field-group';
  newFieldGroup.innerHTML = `
    <div class="form-group">
      <label>Name:</label>
      <input type="text" name="${type}_competency[]" placeholder="Enter ${type} competency name">
    </div>
    <div class="form-group">
      <label>Module Title:</label>
      <input type="text" name="${type}_module_title[]" placeholder="Enter module title">
    </div>
    <div class="form-group">
      <label>Nominal Hours:</label>
      <input type="number" name="${type}_nominal_hours[]" min="1" placeholder="e.g., 8">
    </div>
    <div class="form-group">
      <label>Learning Outcomes:</label>
      <textarea name="${type}_learning_outcomes[]" rows="3" placeholder="Enter learning outcomes"></textarea>
    </div>
    <div class="form-group">
      <label>Description:</label>
      <textarea name="${type}_competency_desc[]" rows="2" placeholder="Enter description (optional)"></textarea>
    </div>
    <div class="form-group">
      <label>Generated Code:</label>
      <input type="text" class="generated-comp-code" readonly placeholder="Auto-generated">
    </div>
    <button type="button" class="remove-competency-btn" style="background: #ff4444; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; margin-top: 10px;">Remove</button>
  `;
  fieldsContainer.appendChild(newFieldGroup);
  updateAddCompetencyCodes();
}

function initializeEditCompetencyHandlers() {
  const addCompetencyBtn = document.getElementById('add_new_competency_btn');
  if (addCompetencyBtn) {
    addCompetencyBtn.addEventListener('click', addNewCompetencyField);
  }
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-edit-competency-btn')) {
      e.preventDefault();
      const fieldGroup = e.target.closest('.edit-competency-field-group');
      if (fieldGroup) {
        fieldGroup.remove();
      }
    }
  });
}

function addNewCompetencyField() {
  const container = document.getElementById('edit_competencies_container');
  const index = container.children.length;
  const newFieldGroup = document.createElement('div');
  newFieldGroup.className = 'edit-competency-field-group';
  newFieldGroup.style.border = '1px solid #ddd';
  newFieldGroup.style.padding = '15px';
  newFieldGroup.style.marginBottom = '10px';
  newFieldGroup.style.borderRadius = '5px';
  newFieldGroup.innerHTML = `
    <div class="form-group">
      <label>Competency Type:</label>
      <select name="competencies[${index}][type]" required>
        <option value="basic">Basic Competency</option>
        <option value="common">Common Competency</option>
        <option value="core">Core Competency</option>
      </select>
    </div>
    <div class="form-group">
      <label>Name:</label>
      <input type="text" name="competencies[${index}][name]" placeholder="Enter competency name" required>
    </div>
    <div class="form-group">
      <label>Description:</label>
      <textarea name="competencies[${index}][description]" rows="2" placeholder="Enter description (optional)"></textarea>
    </div>
    <button type="button" class="remove-edit-competency-btn" style="background: #ff4444; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">Remove Competency</button>
  `;
  container.appendChild(newFieldGroup);
}
function updateAddCompetencyCodes() {
  const codeInput = document.getElementById('course_code');
  const courseCode = (codeInput && codeInput.value) ? codeInput.value.trim() : '';
  ['basic','common','core'].forEach(type => {
    const group = document.getElementById(`${type}CompetenciesGroup`);
    if (!group) return;
    const items = Array.from(group.querySelectorAll('.competency-field-group'));
    items.forEach((item, idx) => {
      const codeEl = item.querySelector('.generated-comp-code');
      if (codeEl) {
        const num = idx + 1;
        const typeCap = type.charAt(0).toUpperCase() + type.slice(1);
        codeEl.value = courseCode ? `${courseCode}-${typeCap}-${num}` : `${typeCap}-${num}`;
      }
    });
  });
}

document.addEventListener('input', function(e){
  if (e.target && (e.target.id === 'course_code' || e.target.name === 'basic_competency[]' || e.target.name === 'common_competency[]' || e.target.name === 'core_competency[]')) {
    updateAddCompetencyCodes();
  }
});

document.addEventListener('DOMContentLoaded', function(){ updateAddCompetencyCodes(); initializeCompetencyHandlers(); });
