<?php ?>
<section class="tab-content <?php echo $currentTab === 'grades' ? 'active' : ''; ?>" id="grades">
  <div class="grades-container">
    <div class="grades-header">
      <h2 class="section-header">Grade Management</h2>
      <div class="grades-controls">
        <select id="courseFilter" class="form-select">
          <option value="">All Courses</option>
          <?php foreach ($courses as $course): ?>
            <option value="<?php echo htmlspecialchars($course['course_code']); ?>">
              <?php echo htmlspecialchars($course['course_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <select id="gradeTypeFilter" class="form-select">
          <option value="">All Types</option>
          <option value="quiz">Quizzes</option>
          <option value="assignment">Assignments</option>
          <option value="exam">Exams</option>
          <option value="project">Projects</option>
        </select>
        <button id="exportGrades" class="btn btn-outline-primary">
          <i class="fas fa-download"></i> Export Grades
        </button>
      </div>
    </div>

    <div class="grades-summary">
      <div class="summary-cards">
        <div class="summary-card">
          <div class="card-icon">
            <i class="fas fa-users"></i>
          </div>
          <div class="card-content">
            <h3 id="totalStudents">0</h3>
            <p>Total Students</p>
          </div>
        </div>
        <div class="summary-card">
          <div class="card-icon">
            <i class="fas fa-chart-line"></i>
          </div>
          <div class="card-content">
            <h3 id="averageGrade">0%</h3>
            <p>Average Grade</p>
          </div>
        </div>
        <div class="summary-card">
          <div class="card-icon">
            <i class="fas fa-trophy"></i>
          </div>
          <div class="card-content">
            <h3 id="topPerformers">0</h3>
            <p>Top Performers</p>
          </div>
        </div>
        <div class="summary-card">
          <div class="card-icon">
            <i class="fas fa-exclamation-triangle"></i>
          </div>
          <div class="card-content">
            <h3 id="failingStudents">0</h3>
            <p>Need Attention</p>
          </div>
        </div>
      </div>
    </div>

    <div class="grades-content">
      <div class="grades-table-container">
        <table class="grades-table" id="gradesTable">
          <thead>
            <tr>
              <th>Student</th>
              <th>Course</th>
              <th>Activity</th>
              <th>Type</th>
              <th>Score</th>
              <th>Grade</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="gradesTableBody">
            <!-- Grades will be populated here -->
          </tbody>
        </table>
        <div id="noGradesMessage" class="no-data-message" style="display: none;">
          <i class="fas fa-graduation-cap"></i>
          <p>No grades available yet.</p>
          <small>Grades will appear here once students complete activities and quizzes.</small>
        </div>
      </div>
    </div>

    <!-- Grade Details Modal -->
    <div class="modal hidden" id="gradeDetailsModal">
      <div class="modal-content large-modal">
        <div class="modal-header">
          <h3>Grade Details</h3>
          <button class="close-btn" id="closeGradeDetails">&times;</button>
        </div>
        <div class="modal-body">
          <div class="grade-details-content" id="gradeDetailsContent">
            <!-- Grade details will be populated here -->
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" id="cancelGradeEdit">Cancel</button>
          <button class="btn btn-primary" id="saveGradeChanges">Save Changes</button>
        </div>
      </div>
    </div>

    <!-- Bulk Grade Import Modal -->
    <div class="modal hidden" id="bulkImportModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Bulk Grade Import</h3>
          <button class="close-btn" id="closeBulkImport">&times;</button>
        </div>
        <div class="modal-body">
          <div class="import-instructions">
            <p>Upload a CSV file with the following columns:</p>
            <ul>
              <li>Student ID</li>
              <li>Activity Name</li>
              <li>Score</li>
              <li>Max Score (optional)</li>
              <li>Comments (optional)</li>
            </ul>
            <div class="file-upload-area" id="gradeFileUploadArea">
              <i class="fas fa-cloud-upload-alt"></i>
              <p>Click to upload or drag and drop</p>
              <small>Supported formats: CSV, XLSX</small>
              <input type="file" id="gradeFileInput" accept=".csv,.xlsx" style="display: none;">
            </div>
            <div id="filePreview" class="file-preview hidden"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" id="cancelBulkImport">Cancel</button>
          <button class="btn btn-primary" id="processBulkImport" disabled>Import Grades</button>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
.grades-container {
  padding: 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}

.grades-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.grades-controls {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.grades-controls .form-select {
  padding: 0.5rem 1rem;
  border: 1px solid var(--secondary-300);
  border-radius: var(--radius-md);
  background: white;
  font-size: var(--font-size-sm);
  min-width: 150px;
}

.grades-summary {
  margin-bottom: 2rem;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.summary-card {
  background: white;
  border-radius: var(--radius-lg);
  padding: 1.5rem;
  box-shadow: var(--shadow-md);
  border: 1px solid var(--secondary-200);
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.summary-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.card-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
}

.summary-card:nth-child(1) .card-icon { background: var(--info-500); }
.summary-card:nth-child(2) .card-icon { background: var(--success-500); }
.summary-card:nth-child(3) .card-icon { background: var(--warning-500); }
.summary-card:nth-child(4) .card-icon { background: var(--error-500); }

.card-content h3 {
  font-size: var(--font-size-2xl);
  font-weight: 700;
  margin: 0;
  color: var(--secondary-800);
}

.card-content p {
  margin: 0;
  color: var(--secondary-600);
  font-size: var(--font-size-sm);
}

.grades-table-container {
  background: white;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  border: 1px solid var(--secondary-200);
  overflow: hidden;
}

.grades-table {
  width: 100%;
  border-collapse: collapse;
}

.grades-table th {
  background: var(--secondary-50);
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: var(--secondary-700);
  border-bottom: 1px solid var(--secondary-200);
  font-size: var(--font-size-sm);
}

.grades-table td {
  padding: 1rem;
  border-bottom: 1px solid var(--secondary-100);
  font-size: var(--font-size-sm);
}

.grades-table tbody tr:hover {
  background: var(--secondary-50);
}

.grade-badge {
  padding: 0.25rem 0.75rem;
  border-radius: var(--radius-md);
  font-weight: 600;
  font-size: var(--font-size-xs);
  text-transform: uppercase;
}

.grade-a { background: #dcfce7; color: #166534; }
.grade-b { background: #fef3c7; color: #92400e; }
.grade-c { background: #fed7aa; color: #c2410c; }
.grade-d { background: #fecaca; color: #dc2626; }
.grade-f { background: #fee2e2; color: #991b1b; }

  font-size: var(--font-size-xs);
  font-weight: 500;
}

.status-completed { background: var(--success-500); color: white; }
.status-pending { background: var(--warning-500); color: white; }
.status-missing { background: var(--error-500); color: white; }

.no-data-message {
  text-align: center;
  padding: 3rem;
  color: var(--secondary-500);
}

.no-data-message i {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.no-data-message p {
  font-size: var(--font-size-lg);
  margin-bottom: 0.5rem;
}

.modal.large-modal .modal-content {
  max-width: 800px;
}

.file-upload-area {
  border: 2px dashed var(--secondary-300);
  border-radius: var(--radius-lg);
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: border-color var(--transition-fast);
}

.file-upload-area:hover {
  border-color: var(--primary-400);
}

.file-upload-area i {
  font-size: 2rem;
  color: var(--secondary-400);
  margin-bottom: 0.5rem;
}

.import-instructions ul {
  text-align: left;
  margin: 1rem 0;
  padding-left: 1.5rem;
}

.import-instructions li {
  margin-bottom: 0.5rem;
  color: var(--secondary-600);
}

@media (max-width: 768px) {
  .grades-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .grades-controls {
    flex-direction: column;
  }
  
  .summary-cards {
    grid-template-columns: 1fr;
  }
  
  .grades-table {
    font-size: var(--font-size-xs);
  }
  
  .grades-table th,
  .grades-table td {
    padding: 0.5rem;
  }
}
</style>

<script>
let gradesData = [];
let filteredGrades = [];

document.addEventListener('DOMContentLoaded', function() {
  loadGrades();
  setupGradeFilters();
  setupGradeActions();
});

function loadGrades() {
  fetch('../trainer/handlers/grade_handler.php?action=get_grades')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        gradesData = data.grades;
        filteredGrades = [...gradesData];
        updateGradesTable();
        updateSummaryCards();
      } else {
        showNotification('Failed to load grades', 'error');
      }
    })
    .catch(error => {
      console.error('Error loading grades:', error);
      showNotification('Error loading grades', 'error');
    });
}

function setupGradeFilters() {
  const courseFilter = document.getElementById('courseFilter');
  const gradeTypeFilter = document.getElementById('gradeTypeFilter');
  
  courseFilter.addEventListener('change', filterGrades);
  gradeTypeFilter.addEventListener('change', filterGrades);
}

function filterGrades() {
  const courseCode = document.getElementById('courseFilter').value;
  const gradeType = document.getElementById('gradeTypeFilter').value;
  
  filteredGrades = gradesData.filter(grade => {
    const courseMatch = !courseCode || grade.course_code === courseCode;
    const typeMatch = !gradeType || grade.activity_type === gradeType;
    return courseMatch && typeMatch;
  });
  
  updateGradesTable();
  updateSummaryCards();
}

function updateGradesTable() {
  const tbody = document.getElementById('gradesTableBody');
  const noGradesMessage = document.getElementById('noGradesMessage');
  
  if (filteredGrades.length === 0) {
    tbody.innerHTML = '';
    noGradesMessage.style.display = 'block';
    return;
  }
  
  noGradesMessage.style.display = 'none';
  
  tbody.innerHTML = filteredGrades.map(grade => `
    <tr>
      <td>
        <div class="student-info">
          <strong>${escapeHtml(grade.student_name)}</strong>
          <small>${escapeHtml(grade.student_id)}</small>
        </div>
      </td>
      <td>${escapeHtml(grade.course_name)}</td>
      <td>${escapeHtml(grade.activity_name)}</td>
      <td>
        <span class="activity-type-badge type-${grade.activity_type}">
          ${escapeHtml(grade.activity_type)}
        </span>
      </td>
      <td>
        <strong>${grade.score}/${grade.max_score}</strong>
        <small>(${grade.percentage}%)</small>
      </td>
      <td>
        <span class="grade-badge grade-${grade.grade.toLowerCase()}">
          ${grade.grade}
        </span>
      </td>
      <td>${formatDate(grade.submitted_date)}</td>
      <td>
        <button class="btn btn-sm btn-outline-primary" onclick="viewGradeDetails('${grade.id}')">
          <i class="fas fa-eye"></i>
        </button>
      </td>
    </tr>
  `).join('');
}

function updateSummaryCards() {
  const totalStudents = new Set(filteredGrades.map(g => g.student_id)).size;
  const averageGrade = filteredGrades.length > 0 
    ? (filteredGrades.reduce((sum, g) => sum + g.percentage, 0) / filteredGrades.length).toFixed(1)
    : 0;
  const topPerformers = filteredGrades.filter(g => g.percentage >= 90).length;
  const failingStudents = filteredGrades.filter(g => g.percentage < 60).length;
  
  document.getElementById('totalStudents').textContent = totalStudents;
  document.getElementById('averageGrade').textContent = averageGrade + '%';
  document.getElementById('topPerformers').textContent = topPerformers;
  document.getElementById('failingStudents').textContent = failingStudents;
}

function setupGradeActions() {
  document.getElementById('exportGrades')?.addEventListener('click', exportGrades);
}

function viewGradeDetails(gradeId) {
  const grade = filteredGrades.find(g => g.id === gradeId);
  if (!grade) return;
  
  const modal = document.getElementById('gradeDetailsModal');
  const content = document.getElementById('gradeDetailsContent');
  const titleEl = modal.querySelector('.modal-header h3');
  if (titleEl) titleEl.textContent = 'Grade Details';
  const saveBtn = document.getElementById('saveGradeChanges');
  const cancelBtn = document.getElementById('cancelGradeEdit');
  if (saveBtn) { saveBtn.style.display = 'none'; saveBtn.onclick = null; }
  if (cancelBtn) { cancelBtn.textContent = 'Close'; cancelBtn.style.display = 'inline-flex'; cancelBtn.onclick = function(){ closeModal(modal); }; }
  
  content.innerHTML = `
    <div class="grade-details">
      <div class="student-details">
        <h4>${escapeHtml(grade.student_name)}</h4>
        <p><strong>Student ID:</strong> ${escapeHtml(grade.student_id)}</p>
        <p><strong>Course:</strong> ${escapeHtml(grade.course_name)}</p>
        <p><strong>Activity:</strong> ${escapeHtml(grade.activity_name)}</p>
      </div>
      
      <div class="grade-breakdown">
        <h5>Grade Breakdown</h5>
        <div class="grade-info">
          <div class="grade-item">
            <span>Score:</span>
            <strong>${grade.score}/${grade.max_score}</strong>
          </div>
          <div class="grade-item">
            <span>Percentage:</span>
            <strong>${grade.percentage}%</strong>
          </div>
          <div class="grade-item">
            <span>Letter Grade:</span>
            <span class="grade-badge grade-${grade.grade.toLowerCase()}">${grade.grade}</span>
          </div>
        </div>
      </div>
      
      ${grade.feedback ? `
        <div class="feedback-section">
          <h5>Feedback</h5>
          <div class="feedback-content">${escapeHtml(grade.feedback)}</div>
        </div>
      ` : ''}
      
      ${grade.submission_details ? `
        <div class="submission-details">
          <h5>Submission Details</h5>
          <p><strong>Submitted:</strong> ${formatDateTime(grade.submitted_date)}</p>
          <p><strong>Time Taken:</strong> ${grade.time_taken || 'N/A'}</p>
          ${grade.attempts ? `<p><strong>Attempts:</strong> ${grade.attempts}</p>` : ''}
        </div>
      ` : ''}
    </div>
  `;
  
  openModal(modal);
}

function editGrade(gradeId) {
  const grade = filteredGrades.find(g => g.id === gradeId);
  if (!grade) return;
  const modal = document.getElementById('gradeDetailsModal');
  const content = document.getElementById('gradeDetailsContent');
  const titleEl = modal.querySelector('.modal-header h3');
  if (titleEl) titleEl.textContent = 'Edit Grade';
  const saveBtn = document.getElementById('saveGradeChanges');
  const cancelBtn = document.getElementById('cancelGradeEdit');
  content.innerHTML = `
    <form id="editGradeForm">
      <div class="grade-details">
        <div class="student-details">
          <h4>${escapeHtml(grade.student_name)}</h4>
          <p><strong>Student ID:</strong> ${escapeHtml(grade.student_id)}</p>
          <p><strong>Course:</strong> ${escapeHtml(grade.course_name)}</p>
          <p><strong>Activity:</strong> ${escapeHtml(grade.activity_name)}</p>
        </div>
        <div class="grade-breakdown">
          <h5>Edit Grade</h5>
          <div class="grade-info">
            <div class="grade-item">
              <label>Score:</label>
              <input type="number" name="score" class="form-control" min="0" step="0.01" value="${grade.score}">
            </div>
            <div class="grade-item">
              <label>Max Score:</label>
              <input type="number" name="max_score" class="form-control" min="1" step="1" value="${grade.max_score}">
            </div>
            <div class="grade-item">
              <label>Feedback:</label>
              <textarea name="feedback" rows="3" class="form-control">${escapeHtml(grade.feedback || '')}</textarea>
            </div>
          </div>
        </div>
        <input type="hidden" name="grade_id" value="${grade.id}">
      </div>
    </form>
  `;
  if (saveBtn) {
    saveBtn.style.display = 'inline-flex';
    saveBtn.onclick = function() {
      const form = document.getElementById('editGradeForm');
      const fd = new FormData(form);
      fd.append('action', 'update_grade');
      fetch('../trainer/handlers/grade_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            // update local data
            const idx = filteredGrades.findIndex(g => g.id === grade.id);
            if (idx !== -1) {
              filteredGrades[idx].score = parseFloat(fd.get('score'));
              filteredGrades[idx].max_score = parseFloat(fd.get('max_score'));
              filteredGrades[idx].percentage = (filteredGrades[idx].score / filteredGrades[idx].max_score * 100).toFixed(1);
              filteredGrades[idx].feedback = fd.get('feedback');
              updateGradesTable();
              updateSummaryCards();
            }
            showNotification('Grade updated successfully', 'success');
            closeModal(modal);
          } else {
            showNotification(data.message || 'Failed to update grade', 'error');
          }
        })
        .catch(() => showNotification('Request failed', 'error'));
    };
  }
  if (cancelBtn) { cancelBtn.textContent = 'Cancel'; cancelBtn.style.display = 'inline-flex'; cancelBtn.onclick = function(){ closeModal(modal); }; }
  openModal(modal);
}

function exportGrades() {
  const courseCode = document.getElementById('courseFilter').value;
  const gradeType = document.getElementById('gradeTypeFilter').value;
  
  const params = new URLSearchParams();
  if (courseCode) params.append('course_code', courseCode);
  if (gradeType) params.append('grade_type', gradeType);
  
  window.open(`../trainer/handlers/grade_handler.php?action=export&${params.toString()}`, '_blank');
}

// Utility functions
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString();
}

function formatDateTime(dateString) {
  return new Date(dateString).toLocaleString();
}

function showNotification(message, type = 'info') {
  // Implementation for showing notifications
  console.log(`[${type.toUpperCase()}] ${message}`);
}

function openModal(modal) {
  if (modal) {
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
  }
}

function closeModal(modal) {
  if (modal) {
    modal.classList.add('hidden');
    modal.style.display = 'none';
  }
}

// Close modal handlers
document.getElementById('closeGradeDetails')?.addEventListener('click', () => {
  closeModal(document.getElementById('gradeDetailsModal'));
});

document.getElementById('closeBulkImport')?.addEventListener('click', () => {
  closeModal(document.getElementById('bulkImportModal'));
});
</script>
