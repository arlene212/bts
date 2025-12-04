<?php ?>
<section class="tab-content <?php echo $currentTab === 'activities-summary' ? 'active' : ''; ?>" id="activities-summary">
  <div class="activities-summary-container">
    <div class="summary-header">
      <h2 class="section-header">Activities Summary</h2>
      <div class="summary-controls">
        <select id="activityCourseFilter" class="form-select">
          <option value="">All Courses</option>
        </select>
      </div>
    </div>
    <div class="summary-table-wrap">
      <table class="summary-table" id="summaryTable">
        <thead>
          <tr>
            <th>Assignment</th>
            <th>Start</th>
            <th>Due</th>
            <th>% of overall</th>
            <th>Submitted</th>
            <th>Graded</th>
            <th>Score</th>
            <th>Grade</th>
          </tr>
        </thead>
        <tbody id="summaryTableBody"></tbody>
      </table>
      <div id="summaryNoData" class="no-data" style="display:none;">No activities yet.</div>
    </div>
  </div>
</section>

<style>
.activities-summary-container{ padding:1.5rem; }
.summary-header{ display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; }
.summary-controls .form-select{ background:#fff; color:var(--secondary-800); border:1px solid var(--secondary-300); border-radius:var(--radius-md); }
.summary-table-wrap{ background:#fff; border:1px solid var(--secondary-200); border-radius:var(--radius-lg); box-shadow:var(--shadow-md); overflow:hidden; }
.summary-table{ width:100%; border-collapse:collapse; }
.summary-table thead th{ background:var(--secondary-50); color:var(--secondary-800); text-align:left; padding:12px 16px; font-weight:600; border-bottom:1px solid var(--secondary-200); }
.summary-table tbody td{ color:var(--secondary-800); padding:12px 16px; border-bottom:1px solid var(--secondary-100); }
.summary-table tbody tr:hover{ background:var(--secondary-50); }
.assignment-cell{ display:flex; align-items:flex-start; gap:12px; }
.assignment-icon{ width:18px; height:18px; border-radius:4px; background:var(--secondary-100); display:flex; align-items:center; justify-content:center; color:var(--secondary-600); font-size:12px; margin-top:2px; }
.assignment-titles{ display:flex; flex-direction:column; }
.assignment-title{ color:var(--primary-600); font-weight:600; text-decoration:none; }
.assignment-subtitle{ color:var(--primary-500); font-size:13px; opacity:0.9; text-decoration:none; }
.small-gray{ color:var(--secondary-600); font-size:12px; }
.check{ color:var(--success-500); }
.flag{ color:var(--secondary-500); }
.score-main{ font-weight:600; }
.score-sub{ color:var(--secondary-600); font-size:12px; }
.grade-missing{ color:var(--secondary-500); }
.no-data{ color:var(--secondary-600); padding:16px; text-align:center; }
</style>

<script>
let actData = [];
let courseList = [];

document.addEventListener('DOMContentLoaded', function(){
  loadSummary();
  document.getElementById('activityCourseFilter').addEventListener('change', renderSummary);
});

function loadSummary(){
  fetch('../trainee/handlers/ajax_handlers.php?action=get_activity_summary')
    .then(r=>r.json())
    .then(data=>{
      if(!data.success){ console.error('Failed to load'); return; }
      actData = data.items || [];
      courseList = data.courses || [];
      populateCourseFilter();
      renderSummary();
    })
    .catch(()=>{});
}

function populateCourseFilter(){
  const sel = document.getElementById('activityCourseFilter');
  sel.innerHTML = '<option value="">All Courses</option>';
  courseList.forEach(c=>{ const o=document.createElement('option'); o.value=c.code; o.textContent=c.name; sel.appendChild(o); });
}

function renderSummary(){
  const tbody = document.getElementById('summaryTableBody');
  const noData = document.getElementById('summaryNoData');
  const course = document.getElementById('activityCourseFilter').value;
  let rows = actData.filter(i=> !course || i.course_code === course);
  if(rows.length === 0){ tbody.innerHTML=''; noData.style.display='block'; return; }
  noData.style.display='none';
  tbody.innerHTML = rows.map(i=>{
    const submittedIcon = i.submitted ? '<i class="fas fa-check check"></i>' : '<i class="fas fa-flag flag"></i>';
    const gradedIcon = i.graded ? '<i class="fas fa-check check"></i>' : '-';
    const dueText = i.due_time ? (i.due_date + '<br><span class="small-gray">' + i.due_time + '</span>') : (i.due_date || '-')
    const scoreMain = (i.score !== null) ? (i.score + '/' + i.max_score) : ('0/' + i.max_score);
    const scorePct = (i.percentage !== null) ? (i.percentage + '%') : '0%';
    const gradeText = i.grade_text || (i.graded ? (i.percentage+'%') : 'M');
    return `
      <tr>
        <td>
          <div class="assignment-cell">
            <div class="assignment-icon"><i class="fas ${i.icon}"></i></div>
            <div class="assignment-titles">
              <a href="#" class="assignment-title">${escapeHtml(i.activity_title)}</a>
              <a href="#" class="assignment-subtitle">${escapeHtml(i.topic_name || i.course_name || '')}</a>
            </div>
          </div>
        </td>
        <td>${i.start_date || '-'}</td>
        <td>${dueText}</td>
        <td>${i.weight}</td>
        <td>${submittedIcon}</td>
        <td>${gradedIcon}</td>
        <td><span class="score-main">${scoreMain}</span><br><span class="score-sub">${scorePct}</span></td>
        <td>${gradeText}</td>
      </tr>
    `;
  }).join('');
}

function escapeHtml(t){ const d=document.createElement('div'); d.textContent=t||''; return d.innerHTML; }
</script>
