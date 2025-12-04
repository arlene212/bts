<?php ?>
<section class="tab-content <?php echo $currentTab === 'gradebook' ? 'active' : ''; ?>" id="gradebook">
  <div class="gradebook-container">
  <div class="gradebook-header">
    <h2 class="section-header">Grade Book</h2>
    <div class="gradebook-controls">
      <select id="gradebookCourseFilter" class="form-select">
        <option value="">All Courses</option>
        <?php foreach ($courses as $course): ?>
          <option value="<?php echo htmlspecialchars($course['course_code']); ?>">
            <?php echo htmlspecialchars($course['course_name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select id="gradebookBatchFilter" class="form-select" disabled>
        <option value="">All Batches</option>
      </select>
    </div>
  </div>

    <div class="gradebook-table-wrapper">
      <div class="gradebook-table-container">
        <table class="gradebook-table" id="gradebookTable">
          <thead id="gradebookHead"></thead>
          <tbody id="gradebookBody"></tbody>
        </table>
        <div id="gradebookNoData" class="no-data-message" style="display:none;">
          <i class="fas fa-table"></i>
          <p>No grade data available.</p>
          <small>Grades will appear once students complete activities.</small>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
.gradebook-container { padding: 1.5rem; max-width: 100%; }
.gradebook-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; gap:1rem; flex-wrap:wrap; }
.gradebook-controls { display:flex; gap:1rem; }
.gradebook-table-wrapper { background:white; border:1px solid var(--secondary-200); border-radius:var(--radius-lg); box-shadow:var(--shadow-md); overflow:auto; }
.gradebook-table-container { width:100%; overflow:auto; }
.gradebook-table { width:max-content; min-width:100%; border-collapse:collapse; }
.gradebook-table th, .gradebook-table td { border-bottom:1px solid var(--secondary-100); padding:0.75rem; font-size:var(--font-size-sm); white-space:nowrap; }
.gradebook-table thead th { position:sticky; top:0; background:var(--secondary-50); z-index:2; font-weight:600; color:var(--secondary-700); }
.gradebook-table thead th:first-child, .gradebook-table tbody td:first-child { position:sticky; left:0; background:white; z-index:3; max-width:360px; }
.activity-cell { display:flex; flex-direction:column; }
.activity-title { font-weight:600; color:var(--secondary-800); }
.activity-meta { color:var(--secondary-600); font-size:var(--font-size-xs); }
.student-header { display:flex; flex-direction:column; }
.student-name { font-weight:600; }
.student-id { color:var(--secondary-500); font-size:var(--font-size-xs); }
.score-cell { display:flex; flex-direction:column; align-items:flex-start; }
.score-main { font-weight:600; }
.score-sub { color:var(--secondary-600); font-size:var(--font-size-xs); }
.score-none { color:var(--secondary-400); }
.grade-a { background:#dcfce7; color:#166534; padding:0.15rem 0.5rem; border-radius:var(--radius-md); }
.grade-b { background:#fef3c7; color:#92400e; padding:0.15rem 0.5rem; border-radius:var(--radius-md); }
.grade-c { background:#fed7aa; color:#c2410c; padding:0.15rem 0.5rem; border-radius:var(--radius-md); }
.grade-d { background:#fecaca; color:#dc2626; padding:0.15rem 0.5rem; border-radius:var(--radius-md); }
.grade-f { background:#fee2e2; color:#991b1b; padding:0.15rem 0.5rem; border-radius:var(--radius-md); }
@media (max-width: 768px) { .gradebook-table thead th:first-child, .gradebook-table tbody td:first-child { max-width:240px; } }
</style>

<script>
let gbStudents = [];
let gbActivities = [];
let gbScores = [];
let gbScoreMap = {};
let gbEnrollments = [];
let gbBatches = {};

document.addEventListener('DOMContentLoaded', function() {
  loadGradebook();
  document.getElementById('gradebookCourseFilter').addEventListener('change', function(){ populateBatchFilter(); renderGradebook(); });
  document.getElementById('gradebookBatchFilter').addEventListener('change', renderGradebook);
});

function loadGradebook() {
  fetch('../trainer/handlers/grade_handler.php?action=get_gradebook')
    .then(r => r.json())
    .then(data => {
      if (!data.success) { showNotification('Failed to load grade book', 'error'); return; }
      gbStudents = data.students || [];
      gbActivities = data.activities || [];
      gbScores = data.scores || [];
      gbEnrollments = data.enrollments || [];
      gbBatches = data.batches || {};
      gbScoreMap = {};
      gbScores.forEach(s => { gbScoreMap[`${s.student_id}_${s.activity_id}`] = s; });
      populateBatchFilter();
      renderGradebook();
    })
    .catch(() => showNotification('Error loading grade book', 'error'));
}

function populateBatchFilter(){
  const courseCode = document.getElementById('gradebookCourseFilter').value;
  const batchSelect = document.getElementById('gradebookBatchFilter');
  batchSelect.innerHTML = '<option value="">All Batches</option>';
  if (!courseCode) { batchSelect.disabled = true; return; }
  batchSelect.disabled = false;
  const batches = gbBatches[courseCode] || [];
  batches.forEach(b => {
    const opt = document.createElement('option');
    opt.value = b;
    opt.textContent = b;
    batchSelect.appendChild(opt);
  });
}

function renderGradebook() {
  const courseCode = document.getElementById('gradebookCourseFilter').value;
  const head = document.getElementById('gradebookHead');
  const body = document.getElementById('gradebookBody');
  const noData = document.getElementById('gradebookNoData');
  let activities = gbActivities.filter(a => !courseCode || a.course_code === courseCode);
  if (gbStudents.length === 0 || activities.length === 0) {
    head.innerHTML = '';
    body.innerHTML = '';
    noData.style.display = 'block';
    return;
  }
  noData.style.display = 'none';
  head.innerHTML = '';
  const headerRow = document.createElement('tr');
  const studentTh = document.createElement('th');
  studentTh.textContent = 'Student';
  headerRow.appendChild(studentTh);
  activities.forEach(act => {
    const th = document.createElement('th');
    const acWrap = document.createElement('div');
    acWrap.className = 'activity-cell';
    const title = document.createElement('span');
    title.className = 'activity-title';
    title.textContent = act.title;
    const meta = document.createElement('small');
    meta.className = 'activity-meta';
    meta.textContent = `${act.course_name} • ${act.max_score} pts`;
    acWrap.appendChild(title);
    acWrap.appendChild(meta);
    th.appendChild(acWrap);
    headerRow.appendChild(th);
  });
  head.appendChild(headerRow);
  body.innerHTML = '';
  const batchCode = document.getElementById('gradebookBatchFilter').value;
  const eligibleSet = new Set();
  if (courseCode) {
    gbEnrollments.forEach(en => {
      if (en.course_code === courseCode && (!batchCode || (en.batch_name || '') === batchCode)) {
        eligibleSet.add(String(en.trainee_id));
      }
    });
  }

  gbStudents.filter(st => {
    if (!courseCode) return true;
    return eligibleSet.has(String(st.id));
  }).forEach(st => {
    const tr = document.createElement('tr');
    const tdStudent = document.createElement('td');
    const swrap = document.createElement('div');
    swrap.className = 'student-header';
    const nm = document.createElement('span');
    nm.className = 'student-name';
    nm.textContent = `${st.name}`;
    const id = document.createElement('small');
    id.className = 'student-id';
    id.textContent = st.id;
    swrap.appendChild(nm);
    swrap.appendChild(id);
    tdStudent.appendChild(swrap);
    tr.appendChild(tdStudent);
    activities.forEach(act => {
      const td = document.createElement('td');
      const s = gbScoreMap[`${st.id}_${act.id}`];
      const wrap = document.createElement('div');
      wrap.className = 'score-cell';
      if (s) {
        const main = document.createElement('span');
        main.className = 'score-main';
        main.textContent = `${s.points}/${s.max_score}`;
        const sub = document.createElement('small');
        sub.className = 'score-sub';
        sub.textContent = `${s.percentage}%`;
        const g = document.createElement('span');
        g.className = `grade-${(s.grade || '').toLowerCase()}`;
        g.textContent = s.grade;
        wrap.appendChild(main);
        wrap.appendChild(sub);
        wrap.appendChild(g);
      } else {
        const none = document.createElement('span');
        none.className = 'score-none';
        none.textContent = '-';
        wrap.appendChild(none);
      }
      td.appendChild(wrap);
      tr.appendChild(td);
    });
    body.appendChild(tr);
  });
}
</script>
