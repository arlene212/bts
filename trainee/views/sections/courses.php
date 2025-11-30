<?php
require_once __DIR__ . '/../../../php/SessionManager.php';
require_once __DIR__ . '/../../../php/DatabaseConnection.php';
SessionManager::startSession();
SessionManager::requireRole('trainee');
$user = SessionManager::getCurrentUser();
$traineeId = $user['user_id'] ?? null;
$active_courses = [];
$completed_courses = [];
try {
  $db = (new DatabaseConnection())->getConnection();
  if ($traineeId) {
    $stmt = $db->prepare(
      "SELECT e.id, e.course_code, c.course_name, c.hours, c.description, c.image
       FROM enrollments e
       JOIN courses c ON e.course_code = c.course_code
       WHERE e.trainee_id = ? AND e.status = 'approved'"
    );
    $stmt->execute([$traineeId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
      $creditStmt = $db->prepare(
        "SELECT COALESCE(SUM(hc.hours),0) FROM hour_credits hc WHERE hc.trainee_id = ? AND hc.course_code = ?"
      );
      $creditStmt->execute([$traineeId, $row['course_code']]);
      $row['credited_hours'] = (int)$creditStmt->fetchColumn();
      $totalStmt = $db->prepare(
        "SELECT COUNT(ta.id)
         FROM topic_activities ta
         JOIN course_topics ct ON ta.topic_id = ct.id
         WHERE ct.course_code = ?"
      );
      $totalStmt->execute([$row['course_code']]);
      $row['total_activities'] = (int)$totalStmt->fetchColumn();
      $completedStmt = $db->prepare(
        "SELECT COUNT(*)
         FROM activity_submissions s
         WHERE s.guest_id = ? AND s.activity_id IN (
           SELECT ta.id FROM topic_activities ta JOIN course_topics ct ON ta.topic_id = ct.id WHERE ct.course_code = ?
         )"
      );
      $completedStmt->execute([$traineeId, $row['course_code']]);
      $row['completed_activities'] = (int)$completedStmt->fetchColumn();
      $row['progress'] = $row['total_activities'] > 0 ? (int)round(($row['completed_activities'] / $row['total_activities']) * 100) : 0;
      if ($row['progress'] >= 100) { $completed_courses[] = $row; } else { $active_courses[] = $row; }
    }
  }
} catch (Throwable $e) {}
?>
<div class="mycourses tab-inner active" id="mycourses">
  <div class="course-box">
    <style>
      #mycourses .hidden { display: none !important; }
      #mycourses .course-detail { background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,.08); border: 1px solid rgba(226,232,240,.8); padding: 16px; }
      #mycourses .detail-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #e6f0ff; padding-bottom: 12px; margin-bottom: 16px; }
      #mycourses .course-card { background: #f9fbff; border-radius: 12px; padding: 12px 16px; border: 1px solid #e6f0ff; margin-bottom: 16px; }
      #mycourses .course-meta { color: #4a5568; font-size: 14px; display:flex; gap:8px; flex-wrap:wrap; }
      #mycourses .chip { background:#eef2ff; border:1px solid #e6e8ff; border-radius:999px; padding:4px 10px; font-size:12px; font-weight:700; color:#3730a3; }
      #mycourses .chip-blue { background:#dbeafe; color:#1d4ed8; border-color:#bfdbfe; }
      #mycourses .switch-oval { position: relative; display: inline-flex; background: #eef6ff; border: 1px solid #dbeafe; border-radius: 999px; padding: 4px; box-shadow: 0 2px 6px rgba(0,0,0,.06); }
      #mycourses .switch-inner { position: absolute; top: 4px; left: 4px; width: calc(50% - 8px); height: calc(100% - 8px); background: linear-gradient(135deg,#3b82f6,#2563eb); border-radius: 999px; box-shadow: 0 4px 10px rgba(37,99,235,.3); transition: transform .25s; z-index: 0; }
      #mycourses .switch-btn { position: relative; z-index: 1; border: 0; background: transparent; padding: 8px 16px; border-radius: 999px; font-weight: 600; color: #1d4ed8; cursor: pointer; }
      #mycourses .switch-btn.active { color: #fff; }
      #mycourses .modules-card, #mycourses .activities-card { background: #fff; border-radius: 16px; border: 1px solid #e6f0ff; padding: 20px; box-shadow: 0 10px 24px rgba(0,0,0,.08); }
      #mycourses .list-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; padding-bottom: 8px; border-bottom: 1px solid #e6f0ff; }
      #mycourses .list-header h3 { margin: 0; font-weight: 700; color: #1f2937; display:flex; align-items:center; gap:8px; }
      #mycourses .list-controls { display: flex; align-items: center; gap: 8px; }
      #mycourses .count-pill { background: #eef2ff; color: #3730a3; border: 1px solid #e6e8ff; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
      #mycourses .search-input { padding: 8px 12px; border: 2px solid #e5e7eb; border-radius: 999px; font-size: 12px; width: 220px; }
      #mycourses .search-input:focus { outline: none; border-color: #60a5fa; box-shadow: 0 0 0 3px rgba(96,165,250,.2); }
      #mycourses .modules-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
      #mycourses .activities-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px; }
      #mycourses .detail-tabs { position: relative; display: flex; gap: 8px; margin: 12px 0 16px; background: #eef6ff; border: 1px solid #dbeafe; border-radius: 999px; padding: 4px; }
      #mycourses .detail-tabs .tab-btn { position: relative; z-index: 1; border: 0; background: transparent; padding: 8px 16px; border-radius: 999px; font-weight: 700; color: #1d4ed8; cursor: pointer; display:flex; align-items:center; gap:8px; }
      #mycourses .detail-tabs .tab-btn.active { color: #fff; }
      #mycourses .detail-tabs .tab-indicator { position: absolute; top: 4px; left: 4px; height: calc(100% - 8px); width: calc(50% - 8px); background: linear-gradient(135deg,#3b82f6,#2563eb); border-radius: 999px; box-shadow: 0 4px 10px rgba(37,99,235,.3); transition: transform .25s ease; }
      #mycourses .tab-pane { display: none; }
      #mycourses .tab-pane.active { display: block; }
      #mycourses .module-item, #mycourses .activity-item { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; box-shadow: 0 2px 10px rgba(0,0,0,.04); transition: transform .15s, box-shadow .15s; min-height: 140px; display: flex; flex-direction: column; gap: 8px; }
      #mycourses .module-item:hover, #mycourses .activity-item:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.08); }
      #mycourses .item-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 6px; }
      #mycourses .item-title { font-weight: 700; color: #1f2937; }
      #mycourses .item-meta { display: flex; gap: 12px; color: #6b7280; font-size: 12px; }
      #mycourses .type-badge { padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; letter-spacing: .03em; }
      #mycourses .type-badge.video { background: rgba(59,130,246,.12); color: #1d4ed8; }
      #mycourses .type-badge.pdf { background: rgba(16,185,129,.12); color: #047857; }
      #mycourses .type-badge.text { background: rgba(107,114,128,.12); color: #374151; }
      #mycourses .type-badge.quiz { background: rgba(245,158,11,.12); color: #b45309; }
      #mycourses .item-actions { display: flex; gap: 8px; justify-content: flex-end; }
      #mycourses .view-link { color: #1d4ed8; text-decoration: none; font-weight: 600; border: 1px solid #bfdbfe; padding: 6px 10px; border-radius: 999px; }
      #mycourses .view-link:hover { text-decoration: underline; }
      #mycourses .empty-state { text-align: center; color: #6b7280; font-size: 14px; padding: 16px; }
      #mycourses .course-detail { max-width: 1120px; margin: 0 auto; }
      #mycourses .batch-list { display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:16px; }
      #mycourses .batch-card { background:#fff; border:1px solid #e6f0ff; border-radius:16px; box-shadow:0 8px 24px rgba(0,0,0,.06); overflow:hidden; display:flex; gap:12px; padding:12px; cursor:pointer; }
      #mycourses .batch-card .course-img { width:88px; height:88px; object-fit:cover; border-radius:12px; border:1px solid #e6f0ff; }
      #mycourses .competency-badge { background:#ecfeff; color:#0e7490; border:1px solid #a5f3fc; display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; font-weight:700; }
      #mycourses .section-header { font-size:20px; font-weight:800; color:#1f2937; margin:12px 0; }
    </style>
    <div class="news-switch-wrapper">
      <div class="switch-oval" id="courseSwitchContainer">
        <div id="myCourseSwitchInner" class="switch-inner"></div>
        <button type="button" class="switch-btn active" data-target="active">Active</button>
        <button type="button" class="switch-btn" data-target="completed">Completed</button>
      </div>
    </div>
    <div id="enrolled-active-courses" class="tab-inner active">
      <h2 class="section-header">Active Courses</h2>
      <div class="batch-list">
        <?php if (!empty($active_courses)): ?>
          <?php foreach ($active_courses as $course): ?>
            <div class="batch-card view-course-content-btn"
                 data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>"
                 data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>"
                 data-course-hours="<?php echo htmlspecialchars($course['hours']); ?>"
                 data-course-description="<?php echo htmlspecialchars($course['description']); ?>"
                 data-credited-hours="<?php echo htmlspecialchars($course['credited_hours'] ?? 0); ?>"
                 data-course-id="<?php echo htmlspecialchars($course['id']); ?>">
              <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/school.png'; ?>" alt="<?php echo htmlspecialchars($course['course_name']); ?>" class="course-img">
              <div class="batch-info">
                <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                <p class="competency-badge">Enrolled</p>
                <p>Code: <?php echo htmlspecialchars($course['course_code']); ?></p>
                <p>Hours: <?php echo htmlspecialchars($course['hours']); ?> hrs</p>
                <p>Credited: <?php echo htmlspecialchars($course['credited_hours'] ?? 0); ?> hrs</p>
                <p class="progress-indicator">Progress: <?php echo htmlspecialchars($course['progress']); ?>% (<?php echo $course['completed_activities']; ?>/<?php echo $course['total_activities']; ?>)</p>
                <?php if (!empty($course['description'])): ?>
                  <p class="course-description"><?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>...</p>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-courses">
            <p>You have no active courses.</p>
            <p>Browse <a href="#" class="tab-switch" data-tab="offered-courses">Offered Courses</a> to get started.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <div id="enrolled-completed-courses" class="tab-inner hidden">
      <h2 class="section-header">Completed Courses</h2>
      <div class="batch-list">
        <?php if (!empty($completed_courses)): ?>
          <?php foreach ($completed_courses as $course): ?>
            <div class="batch-card view-course-content-btn"
                 data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>"
                 data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>"
                 data-course-hours="<?php echo htmlspecialchars($course['hours']); ?>"
                 data-course-description="<?php echo htmlspecialchars($course['description']); ?>"
                 data-credited-hours="<?php echo htmlspecialchars($course['credited_hours'] ?? 0); ?>"
                 data-course-id="<?php echo htmlspecialchars($course['id']); ?>">
              <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/school.png'; ?>" alt="<?php echo htmlspecialchars($course['course_name']); ?>" class="course-img">
              <div class="batch-info">
                <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                <p class="competency-badge">Completed</p>
                <p>Code: <?php echo htmlspecialchars($course['course_code']); ?></p>
                <p>Hours: <?php echo htmlspecialchars($course['hours']); ?> hrs</p>
                <p>Credited: <?php echo htmlspecialchars($course['credited_hours'] ?? 0); ?> hrs</p>
                <p class="progress-indicator completed">Status: <strong>Completed</strong> (<?php echo htmlspecialchars($course['progress']); ?>%)</p>
                <?php if (!empty($course['description'])): ?>
                  <p class="course-description"><?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>...</p>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-courses">
            <p>No completed courses yet.</p>
            <p>Keep learning to complete your enrolled courses.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<div id="courseDetail" class="course-detail hidden">
  <div class="detail-header">
    <button class="back-btn btn-ripple"><i class="fas fa-arrow-left"></i> Back to My Courses</button>
    <h2 id="course-detail-title">Course Title</h2>
  </div>
  <div class="course-card">
    <div class="course-meta">
      <span id="course-detail-code" class="chip"></span>
      <span class="chip chip-blue" id="course-detail-hours"></span>
      <span class="chip" id="course-detail-credited-hours"></span>
    </div>
    <p id="course-detail-description"></p>
  </div>
  <div id="competencies-card" class="competencies-card"></div>
  <div class="detail-tabs" id="detailTabs">
    <button class="tab-btn active" data-tab="modules-pane"><i class="fas fa-book"></i> Modules <span id="modules-count" class="count-pill">0</span></button>
    <button class="tab-btn" data-tab="activities-pane"><i class="fas fa-tasks"></i> Activities <span id="activities-count" class="count-pill">0</span></button>
    <div class="tab-indicator"></div>
  </div>
  <div id="modules-pane" class="tab-pane active">
    <div class="modules-card">
      <div class="list-header">
        <h3><i class="fas fa-book"></i> Modules</h3>
        <div class="list-controls"><input id="modules-search" class="search-input" type="text" placeholder="Search modules"></div>
      </div>
      <div id="modules-list" class="modules-list"><div class="empty-state">No modules loaded yet.</div></div>
    </div>
  </div>
  <div id="activities-pane" class="tab-pane">
    <div class="activities-card">
      <div class="list-header">
        <h3><i class="fas fa-tasks"></i> Activities</h3>
        <div class="list-controls"><input id="activities-search" class="search-input" type="text" placeholder="Search activities"></div>
      </div>
      <div id="activities-list" class="activities-list"><div class="empty-state">No activities assigned yet.</div></div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const courseSwitchContainer = document.getElementById('courseSwitchContainer');
  const switchButtons = courseSwitchContainer ? courseSwitchContainer.querySelectorAll('.switch-btn') : [];
  const switchInner = document.getElementById('myCourseSwitchInner');
  const activeCoursesContent = document.getElementById('enrolled-active-courses');
  const completedCoursesContent = document.getElementById('enrolled-completed-courses');
  if (switchInner) { switchInner.style.transform = 'translateX(0%)'; switchInner.style.width = '50%'; }
  switchButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      const target = this.getAttribute('data-target');
      switchButtons.forEach(btn => btn.classList.remove('active'));
      this.classList.add('active');
      const buttonIndex = Array.from(switchButtons).indexOf(this);
      const buttonWidth = 100 / switchButtons.length;
      if (switchInner) { switchInner.style.transform = `translateX(${buttonIndex * 100}%)`; switchInner.style.width = `${buttonWidth}%`; }
      if (target === 'active') { activeCoursesContent.classList.remove('hidden'); completedCoursesContent.classList.add('hidden'); }
      else { activeCoursesContent.classList.add('hidden'); completedCoursesContent.classList.remove('hidden'); }
    });
  });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const tabs = document.getElementById('detailTabs');
  const indicator = tabs ? tabs.querySelector('.tab-indicator') : null;
  function moveIndicator(btn) {
    if (!indicator || !tabs) return;
    const btns = Array.from(tabs.querySelectorAll('.tab-btn'));
    const idx = btns.indexOf(btn);
    indicator.style.transform = `translateX(${idx * 100}%)`;
  }
  if (tabs) {
    const btns = tabs.querySelectorAll('.tab-btn');
    btns.forEach(btn => {
      btn.addEventListener('click', function(e){
        e.preventDefault();
        btns.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        moveIndicator(this);
        const tabId = this.dataset.tab;
        document.querySelectorAll('#mycourses .tab-pane').forEach(p => p.classList.remove('active'));
        const pane = document.getElementById(tabId);
        if (pane) pane.classList.add('active');
      });
    });
    const activeBtn = tabs.querySelector('.tab-btn.active');
    if (activeBtn) moveIndicator(activeBtn);
    window.addEventListener('resize', () => {
      const activeBtn2 = tabs.querySelector('.tab-btn.active');
      if (activeBtn2) moveIndicator(activeBtn2);
    });
  }
  const modulesList = document.getElementById('modules-list');
  const activitiesList = document.getElementById('activities-list');
  const modulesCount = document.getElementById('modules-count');
  const activitiesCount = document.getElementById('activities-count');
  const modulesSearch = document.getElementById('modules-search');
  const activitiesSearch = document.getElementById('activities-search');
  function updateCounts() {
    if (modulesCount && modulesList) modulesCount.textContent = modulesList.querySelectorAll('.module-item').length;
    if (activitiesCount && activitiesList) activitiesCount.textContent = activitiesList.querySelectorAll('.activity-item').length;
  }
  function attachFilter(input, list, itemClass) {
    if (!input || !list) return;
    input.addEventListener('input', function(){
      const q = this.value.toLowerCase();
      list.querySelectorAll('.' + itemClass).forEach(el => {
        const t = el.textContent.toLowerCase();
        el.style.display = t.includes(q) ? '' : 'none';
      });
      if (itemClass === 'module-item') {
        const mc = document.getElementById('modules-count');
        if (mc) mc.textContent = list.querySelectorAll('.module-item:not([style*="display: none"])').length;
      }
      if (itemClass === 'activity-item') {
        const ac = document.getElementById('activities-count');
        if (ac) ac.textContent = list.querySelectorAll('.activity-item:not([style*="display: none"])').length;
      }
    });
  }
  updateCounts();
  attachFilter(modulesSearch, modulesList, 'module-item');
  attachFilter(activitiesSearch, activitiesList, 'activity-item');
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const listView = document.getElementById('mycourses');
  const detailView = document.getElementById('courseDetail');
  const backBtn = detailView.querySelector('.back-btn');
  const titleEl = document.getElementById('course-detail-title');
  const codeEl = document.getElementById('course-detail-code');
  const hoursEl = document.getElementById('course-detail-hours');
  const creditedEl = document.getElementById('course-detail-credited-hours');
  const descEl = document.getElementById('course-detail-description');
  const modulesList = document.getElementById('modules-list');
  const activitiesList = document.getElementById('activities-list');
  const modulesCount = document.getElementById('modules-count');
  const activitiesCount = document.getElementById('activities-count');
  function clearLists() {
    modulesList.innerHTML = '<div class="empty-state">No modules loaded yet.</div>';
    activitiesList.innerHTML = '<div class="empty-state">No activities assigned yet.</div>';
    if (modulesCount) modulesCount.textContent = '0';
    if (activitiesCount) activitiesCount.textContent = '0';
  }
  function openCourseDetail(card) {
    const code = card.getAttribute('data-course-code');
    const name = card.getAttribute('data-course-name');
    const hours = card.getAttribute('data-course-hours');
    const credited = card.getAttribute('data-credited-hours');
    const desc = card.getAttribute('data-course-description') || '';
    titleEl.textContent = name || 'Course';
    codeEl.textContent = `Code: ${code}`;
    hoursEl.textContent = `${hours} hrs`;
    creditedEl.textContent = `Credited: ${credited} hrs`;
    descEl.textContent = desc;
    clearLists();
    listView.classList.add('hidden');
    detailView.classList.remove('hidden');
    loadCourseDetails(code);
  }
  function renderModules(data) {
    const items = [];
    if (Array.isArray(data.topics)) {
      data.topics.forEach(t => {
        (t.materials || []).forEach(m => {
          items.push(
            `<div class="module-item">
               <div class="item-head"><div class="item-title">${m.material_title ?? 'Material'}</div><span class="type-badge ${m.file_path ? 'pdf' : 'text'}">${m.file_path ? 'File' : 'Text'}</span></div>
               <div class="item-body">${m.material_description ?? ''}</div>
               <div class="item-meta"><span>Topic: ${t.topic_name ?? ''}</span></div>
               <div class="item-actions">${m.file_path ? `<a class=\"view-link\" href=\"${m.file_path}\" target=\"_blank\">View</a>` : ''}</div>
             </div>`
          );
        });
      });
    }
    modulesList.innerHTML = items.length ? items.join('') : '<div class="empty-state">No modules loaded yet.</div>';
    if (modulesCount) modulesCount.textContent = items.length.toString();
  }
  function renderActivities(data) {
    const items = [];
    if (Array.isArray(data.activities)) {
      data.activities.forEach(a => {
        const badge = a.activity_type ? a.activity_type.toLowerCase() : 'text';
        items.push(
          `<div class="activity-item">
             <div class="item-head"><div class="item-title">${a.activity_title ?? 'Activity'}</div><span class="type-badge ${badge}">${a.activity_type ?? 'Activity'}</span></div>
             <div class="item-body">${a.activity_description ?? ''}</div>
             <div class="item-meta"><span>Due: ${a.due_date ?? 'N/A'}</span><span>Score: ${a.max_score ?? 'N/A'}</span></div>
             <div class="item-actions">${a.attachment_path ? `<a class=\"view-link\" href=\"${a.attachment_path}\" target=\"_blank\">Attachment</a>` : ''}</div>
           </div>`
        );
      });
    }
    activitiesList.innerHTML = items.length ? items.join('') : '<div class="empty-state">No activities assigned yet.</div>';
    if (activitiesCount) activitiesCount.textContent = items.length.toString();
  }
  function loadCourseDetails(courseCode) {
    fetch(`../php/get_course_details_trainee.php?course_code=${encodeURIComponent(courseCode)}`)
      .then(r => r.json())
      .then(data => { renderModules(data); renderActivities(data); })
      .catch(() => {});
  }
  document.querySelectorAll('.view-course-content-btn').forEach(card => {
    card.addEventListener('click', function() { openCourseDetail(this); });
  });
  backBtn.addEventListener('click', function() {
    detailView.classList.add('hidden');
    listView.classList.remove('hidden');
    clearLists();
  });
});
</script>
