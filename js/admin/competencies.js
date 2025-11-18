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
      <label>Description:</label>
      <textarea name="${type}_competency_desc[]" rows="2" placeholder="Enter description (optional)"></textarea>
    </div>
    <button type="button" class="remove-competency-btn" style="background: #ff4444; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; margin-top: 10px;">Remove</button>
  `;
  fieldsContainer.appendChild(newFieldGroup);
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