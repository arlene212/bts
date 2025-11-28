function setupCourseEditing() {
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.edit-course-btn');
    if (!btn) return;
    const courseDataStr = btn.getAttribute('data-course-data');
    if (courseDataStr) {
      try { openEditCourseModal(JSON.parse(courseDataStr)); } catch (_) { alert('Error loading course data.'); }
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

  const addCourseImageInput = document.getElementById('course_image');
  const addCourseImagePreview = document.getElementById('add_course_image_preview');
  if (addCourseImageInput && addCourseImagePreview) {
    addCourseImageInput.addEventListener('change', function(e) {
      const file = this.files && this.files[0];
      if (!file) { addCourseImagePreview.innerHTML = ''; return; }
      const reader = new FileReader();
      reader.onload = function(ev) {
        addCourseImagePreview.innerHTML = '<img src="' + ev.target.result + '" style="max-width:100%;height:auto;border-radius:6px;">';
      };
      reader.readAsDataURL(file);
    });
  }

  const editCourseImageInput = document.getElementById('edit_course_image');
  const currentImagePreview = document.getElementById('current_image_preview');
  if (editCourseImageInput && currentImagePreview) {
    editCourseImageInput.addEventListener('change', function(e) {
      const file = this.files && this.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = function(ev) {
        currentImagePreview.innerHTML = '<p><strong>Selected Image Preview:</strong></p><img src="' + ev.target.result + '" style="max-width:100%;height:auto;border-radius:6px;">';
      };
      reader.readAsDataURL(file);
    });
  }

  // Require verification toggles for add and edit
  const reqVerAdd = document.getElementById('require_verification');
  const verTypeAdd = document.getElementById('verification_type');
  const reqVerEdit = document.getElementById('edit_require_verification');
  const verTypeEdit = document.getElementById('edit_verification_type');
  function toggleVerificationType(sel, typeSel) {
    if (!sel || !typeSel) return;
    const enabled = String(sel.value) === '1';
    typeSel.disabled = !enabled;
    if (!enabled) { typeSel.value = 'email'; }
  }
  if (reqVerAdd && verTypeAdd) {
    toggleVerificationType(reqVerAdd, verTypeAdd);
    reqVerAdd.addEventListener('change', function(){ toggleVerificationType(reqVerAdd, verTypeAdd); });
  }
  if (reqVerEdit && verTypeEdit) {
    toggleVerificationType(reqVerEdit, verTypeEdit);
    reqVerEdit.addEventListener('change', function(){ toggleVerificationType(reqVerEdit, verTypeEdit); });
  }

  // Limit selectable days based on days per week (Add)
  initDaysLimiter('schedule_days_group', 'schedule_days_per_week');
  // Limit selectable days based on days per week (Edit)
  initDaysLimiter('edit_schedule_days_group', 'edit_schedule_days_per_week');

  // Batch end date auto calculation
  setupBatchScheduling();

  const addBtn = document.getElementById('add_new_competency_btn');
  if (addBtn) {
    addBtn.addEventListener('click', function(){
      const container = document.getElementById('edit_competencies_container');
      const form = document.createElement('div');
      form.className = 'competency-add-form';
      form.innerHTML = `
        <div class="form-row">
          <div class="form-group"><label>Name</label><input type="text" id="new_comp_name" placeholder="Competency name"></div>
          <div class="form-group"><label>Type</label><select id="new_comp_type"><option value="basic">Basic</option><option value="common">Common</option><option value="core">Core</option></select></div>
        </div>
        <div class="form-row"><div class="form-group" style="flex:1"><label>Description</label><textarea id="new_comp_desc" rows="2"></textarea></div></div>
        <div style="text-align:right"><button type="button" id="create_comp_btn">Create</button></div>
        <hr>`;
      container.prepend(form);
      form.querySelector('#create_comp_btn').addEventListener('click', function(){
        const fd = new FormData();
        const nameVal = document.getElementById('new_comp_name').value.trim();
        const typeVal = document.getElementById('new_comp_type').value;
        const descVal = document.getElementById('new_comp_desc').value.trim();
        fd.append('competency_name', nameVal);
        fd.append('competency_type', typeVal);
        fd.append('description', descVal);
        const refresh = () => {
          const code = document.getElementById('edit_course_code').value;
          fetch('../php/get_competencies.php?course_code=' + encodeURIComponent(code))
            .then(r=>r.json()).then(list=>populateCompetencies(list||[]));
        };
        if (window.__edit_course_id) { fd.append('course_id', window.__edit_course_id); }
        fetch('../php/competencies_add.php', { method: 'POST', body: fd })
          .then(r => r.json()).then(d => {
            if (d.success) { alert('Competency created'); form.remove(); refresh(); }
            else { alert(d.message || 'Create failed'); }
          });
      });
    });
  }
}

function openEditCourseModal(courseData) {
  const code = courseData.course_code;
  document.getElementById('edit_course_code').value = code;
  document.getElementById('edit_course_code_display').value = code;
  // Fetch latest course details to ensure all available data is loaded
  fetch('../php/get_course_details.php?course_code=' + encodeURIComponent(code))
    .then(r => r.json())
    .then(data => {
      const c = data.course || {};
      window.__edit_course_id = c.id || courseData.course_id || null;
      document.getElementById('edit_course_name').value = c.course_name || courseData.course_name || '';
      document.getElementById('edit_course_hours').value = c.hours || courseData.hours || '';
      document.getElementById('edit_course_description').value = c.description || courseData.description || '';
      document.getElementById('edit_course_learning_outcomes').value = c.learning_outcomes || courseData.learning_outcomes || '';
      document.getElementById('edit_course_status').value = c.course_status ?? courseData.course_status ?? 'published';
      document.getElementById('edit_allow_preview').value = c.allow_preview ?? courseData.allow_preview ?? 0;
      document.getElementById('edit_course_preview_content').value = c.preview_content ?? courseData.preview_content ?? '';
      document.getElementById('edit_require_verification').value = c.require_verification ?? courseData.require_verification ?? 0;
      document.getElementById('edit_verification_type').value = c.verification_type ?? courseData.verification_type ?? 'email';

      // Schedule fields
      const editDaysPerWeek = document.getElementById('edit_schedule_days_per_week');
      const editDaysGroup = document.getElementById('edit_schedule_days_group');
      const editSessionHours = document.getElementById('edit_session_hours');
      const daysPerWeek = c.schedule_days_per_week ?? courseData.schedule_days_per_week ?? '';
      const sessionHours = c.session_hours ?? courseData.session_hours ?? '';
      const daysStr = String(c.schedule_days ?? courseData.schedule_days ?? '').trim();
      if (editDaysPerWeek) { editDaysPerWeek.value = daysPerWeek; }
      if (editSessionHours) { editSessionHours.value = sessionHours; }
      if (editDaysGroup) {
        const days = daysStr ? daysStr.split(',').map(d => d.trim()) : [];
        editDaysGroup.querySelectorAll('input[type="checkbox"]').forEach(cb => { cb.checked = days.includes(cb.value); });
      }

      // Show/hide preview content based on allow_preview setting
      const previewContentGroup = document.getElementById('edit_preview_content_group');
      if ((c.allow_preview ?? courseData.allow_preview ?? 0) == 1) { previewContentGroup.style.display = 'block'; }
      else { previewContentGroup.style.display = 'none'; }

      // Image
      const imagePreview = document.getElementById('current_image_preview');
      const img = c.image ?? courseData.image;
      if (img) { imagePreview.innerHTML = `<p><strong>Current Image:</strong></p><img src="../uploads/courses/${img}">`; }
      else { imagePreview.innerHTML = '<p class="field-note"><em>No current image</em></p>'; }

      // Competencies (by course_id)
      const courseId = c.id ?? courseData.course_id ?? null;
      const compUrl = courseId ? ('../php/get_competencies.php?course_id=' + encodeURIComponent(courseId)) : ('../php/get_competencies.php?course_code=' + encodeURIComponent(code));
      fetch(compUrl)
        .then(rr => rr.json())
        .then(list => { populateCompetencies(list || []); })
        .catch(() => { populateCompetencies([]); });

      openModal('editCourseModal');
    })
    .catch(() => {
      // Fallback to provided dataset
      document.getElementById('edit_course_name').value = courseData.course_name || '';
      document.getElementById('edit_course_hours').value = courseData.hours || '';
      document.getElementById('edit_course_description').value = courseData.description || '';
      document.getElementById('edit_course_learning_outcomes').value = courseData.learning_outcomes || '';
      document.getElementById('edit_course_status').value = courseData.course_status || 'published';
      document.getElementById('edit_allow_preview').value = courseData.allow_preview || 0;
      document.getElementById('edit_course_preview_content').value = courseData.preview_content || '';
      document.getElementById('edit_require_verification').value = courseData.require_verification || 0;
      document.getElementById('edit_verification_type').value = courseData.verification_type || 'email';
      const editDaysPerWeek = document.getElementById('edit_schedule_days_per_week');
      const editDaysGroup = document.getElementById('edit_schedule_days_group');
      const editSessionHours = document.getElementById('edit_session_hours');
      if (editDaysPerWeek) { editDaysPerWeek.value = courseData.schedule_days_per_week || ''; }
      if (editSessionHours) { editSessionHours.value = courseData.session_hours || ''; }
      if (editDaysGroup) {
        const daysStr = String(courseData.schedule_days || '').trim();
        const days = daysStr ? daysStr.split(',').map(d => d.trim()) : [];
        editDaysGroup.querySelectorAll('input[type="checkbox"]').forEach(cb => { cb.checked = days.includes(cb.value); });
      }
      const previewContentGroup = document.getElementById('edit_preview_content_group');
      if (courseData.allow_preview == 1) { previewContentGroup.style.display = 'block'; } else { previewContentGroup.style.display = 'none'; }
      const imagePreview = document.getElementById('current_image_preview');
      if (courseData.image) { imagePreview.innerHTML = `<p><strong>Current Image:</strong></p><img src="../uploads/courses/${courseData.image}">`; }
      else { imagePreview.innerHTML = '<p class="field-note"><em>No current image</em></p>'; }
      const fallbackCourseId = courseData.course_id ? ('&course_id=' + encodeURIComponent(courseData.course_id)) : '';
      fetch('../php/get_competencies.php?course_code=' + encodeURIComponent(code) + (fallbackCourseId ? '' : ''))
        .then(r => r.json())
        .then(list => { populateCompetencies(list || []); })
        .catch(() => { populateCompetencies([]); });
      openModal('editCourseModal');
    });
  
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
  competencies.forEach((comp) => {
    const row = document.createElement('div');
    row.className = 'competency-edit-row';
    row.innerHTML = `
      <div class="form-row">
        <div class="form-group">
          <label>Code</label>
          <input type="text" class="comp-code" value="${comp.competency_code || ''}" placeholder="e.g., CMP-001">
        </div>
        <div class="form-group">
          <label>Name</label>
          <input type="text" class="comp-name" value="${comp.competency_name || ''}" placeholder="Competency name">
        </div>
        <div class="form-group">
          <label>Type</label>
          <select class="comp-type">
            <option value="basic" ${comp.competency_type === 'basic' ? 'selected' : ''}>Basic</option>
            <option value="common" ${comp.competency_type === 'common' ? 'selected' : ''}>Common</option>
            <option value="core" ${comp.competency_type === 'core' ? 'selected' : ''}>Core</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group" style="flex:1">
          <label>Description</label>
          <textarea class="comp-desc" rows="2">${comp.description || ''}</textarea>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select class="comp-status">
            <option value="active" ${comp.status === 'active' ? 'selected' : ''}>Active</option>
            <option value="archived" ${comp.status === 'archived' ? 'selected' : ''}>Archived</option>
          </select>
        </div>
      </div>
      <div class="competency-actions" style="text-align:right">
        <button type="button" class="save-comp-btn" data-id="${comp.id}">Save</button>
        <button type="button" class="archive-comp-btn" data-id="${comp.id}">Archive</button>
      </div>
      <hr>`;
    container.appendChild(row);
  });
  container.addEventListener('click', function(e){
    const saveBtn = e.target.closest('.save-comp-btn');
    const archBtn = e.target.closest('.archive-comp-btn');
    if (saveBtn) {
      const row = saveBtn.closest('.competency-edit-row');
      const fd = new FormData();
      fd.append('id', saveBtn.getAttribute('data-id'));
      fd.append('competency_code', row.querySelector('.comp-code').value.trim());
      fd.append('competency_name', row.querySelector('.comp-name').value.trim());
      fd.append('competency_type', row.querySelector('.comp-type').value);
      fd.append('description', row.querySelector('.comp-desc').value.trim());
      fd.append('status', row.querySelector('.comp-status').value);
      fetch('../php/competencies_update.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => { if (d.success) { alert('Saved'); } else { alert(d.message || 'Save failed'); } });
    }
    if (archBtn) {
      const fd = new FormData();
      fd.append('id', archBtn.getAttribute('data-id'));
      fd.append('competency_code', '');
      fd.append('competency_name', '');
      fd.append('competency_type', 'basic');
      fd.append('description', '');
      fd.append('status', 'archived');
      fetch('../php/competencies_update.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => { if (d.success) { alert('Archived'); } else { alert(d.message || 'Archive failed'); } });
    }
  });
}

function removeCompetency(index) {}
function reindexCompetencies() {}

function submitEditCourseForm(form) {
  const formData = new FormData(form);
  const submitBtn = form.querySelector('.submit-btn');
  const originalText = submitBtn.textContent;
  submitBtn.textContent = 'Updating...';
  submitBtn.disabled = true;
  fetch('../php/update_course.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => { if (data.success) { alert('Course updated successfully!'); closeModal('editCourseModal'); } else { alert('Error updating course: ' + data.message); } })
    .catch(() => { alert('Error updating course. Please try again.'); })
    .finally(() => { submitBtn.textContent = originalText; submitBtn.disabled = false; });
}

function initDaysLimiter(groupId, selectId) {
  const group = document.getElementById(groupId);
  const sel = document.getElementById(selectId);
  if (!group || !sel) return;
  function limit() { const v = parseInt(sel.value || '0', 10); return isNaN(v) ? 0 : v; }
  sel.addEventListener('change', function(){
    const l = limit();
    const checked = Array.from(group.querySelectorAll('input[type="checkbox"]:checked'));
    if (l > 0 && checked.length > l) { checked.slice(l).forEach(cb => { cb.checked = false; }); }
  });
  group.addEventListener('change', function(e){
    const t = e.target;
    if (t && t.type === 'checkbox' && t.checked) {
      const l = limit();
      const count = group.querySelectorAll('input[type="checkbox"]:checked').length;
      if (l > 0 && count > l) { t.checked = false; }
    }
  });
}

function setupBatchScheduling() {
  const form = document.getElementById('addBatchForm');
  if (!form) return;
  const courseSel = document.getElementById('batch_course_code');
  const startInput = document.getElementById('batch_start_date');
  const endInput = document.getElementById('batch_end_date');
  const infoDiv = document.getElementById('batch_schedule_info');
  function parseFloatSafe(v){ const n = parseFloat(v); return isNaN(n) ? 0 : n; }
  function parseIntSafe(v){ const n = parseInt(v,10); return isNaN(n) ? 0 : n; }
  function computeEnd(){
    if (!courseSel || !startInput || !endInput) return;
    const opt = courseSel.selectedOptions[0];
    if (!opt) { endInput.value=''; infoDiv.textContent=''; return; }
    const totalHours = parseFloatSafe(opt.getAttribute('data-hours'));
    const daysPerWeek = parseIntSafe(opt.getAttribute('data-days-per-week')) || 0;
    const sessionHours = parseFloatSafe(opt.getAttribute('data-session-hours')) || 0;
    const startVal = startInput.value;
    if (!startVal || !totalHours || !daysPerWeek || !sessionHours) { endInput.value=''; infoDiv.textContent=''; return; }
    const sessionsNeeded = Math.ceil(totalHours / sessionHours);
    const weeksNeeded = Math.ceil(sessionsNeeded / daysPerWeek);
    const startDate = new Date(startVal + 'T00:00:00');
    const endDate = new Date(startDate);
    endDate.setDate(endDate.getDate() + (weeksNeeded * 7) - 1);
    const iso = endDate.toISOString().slice(0,10);
    endInput.value = iso;
    infoDiv.textContent = `Total hours: ${totalHours}, ${daysPerWeek} days/week, ${sessionHours} h/session → ${sessionsNeeded} sessions, ~${weeksNeeded} weeks`;
  }
  courseSel && courseSel.addEventListener('change', computeEnd);
  startInput && startInput.addEventListener('change', computeEnd);
  // initial
  computeEnd();
}
