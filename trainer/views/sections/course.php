<?php ?>
<section class="tab-content <?php echo $currentTab === 'courses' ? 'active' : ''; ?>" id="courses">
  <div id="enrolled" class="tab-inner active">
    <h2 class="section-header">My Assigned Courses</h2>
    <div class="batch-list">
      <?php if (count($courses) > 0): ?>
        <?php foreach ($courses as $course): ?>
          <div class="batch-card" data-course="<?php echo htmlspecialchars($course['course_name']); ?>" data-code="<?php echo htmlspecialchars($course['course_code']); ?>" data-hours="<?php echo htmlspecialchars($course['hours']); ?>" data-course-id="<?php echo htmlspecialchars($course['id']); ?>" data-course-data='<?php echo json_encode(['competency_types' => json_decode($course['competency_types'] ?? '[]', true)]); ?>'>
            <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/course-placeholder.jpg'; ?>" alt="Course Image">
            <div class="batch-info"><h3><?php echo htmlspecialchars($course['course_name']); ?></h3><p><?php echo htmlspecialchars($course['hours']); ?> Hours</p><p><?php echo htmlspecialchars($course['course_code']); ?></p></div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-courses"><p>No courses assigned to you yet.</p></div>
      <?php endif; ?>
    </div>
  </div>
  <div id="course-detail" class="course-detail hidden">
    <div class="detail-header">
      <h2 class="course-title" id="course-detail-title">Course Name</h2>
      <button class="back-btn btn-ripple">
        <i class="fas fa-arrow-left"></i> Back to Courses
      </button>
    </div>
    <div class="detail-body">
      <div class="course-basic-info"><p><strong>Code:</strong> <span id="course-code"></span></p><p><strong>Hours:</strong> <span id="course-hours"></span></p><p><strong>Description:</strong> <span id="course-description"></span></p></div>
      <div class="news-switch-wrapper course-detail-toggle">
        <div class="switch-oval">
          <div class="switch-inner"></div>
          <button class="switch-btn active" data-view="view-material">View Material</button>
          <button class="switch-btn" data-view="submissions">Submissions</button>
        </div>
      </div>
      <div id="view-material-view" class="detail-view active"><div class="competencies-section"><div class="section-header"><h3>Course Competencies & Topics</h3></div><div id="competencies-list"></div></div></div>
    </div>
  </div>
  <div id="submissions-view" class="detail-view"><div class="submissions-section-main"><h3>Activity Submissions</h3><div id="submissions-list"></div></div></div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const toggleWrapper = document.querySelector('#courses .course-detail-toggle');
  if (!toggleWrapper) return;
  const btns = toggleWrapper.querySelectorAll('.switch-btn');
  const switchInner = toggleWrapper.querySelector('.switch-inner');
  function showView(key){
    const views = document.querySelectorAll('#courses .detail-view');
    views.forEach(v => v.classList.remove('active'));
    const target = document.getElementById(key + '-view');
    if (target) target.classList.add('active');
  }
  function setActiveBtn(btn){
    btns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    if (switchInner) {
      const idx = Array.from(btns).indexOf(btn);
      switchInner.style.transform = 'translateX(' + (idx * 100) + '%)';
    }
  }
  btns.forEach(btn => {
    btn.addEventListener('click', function(e){
      e.preventDefault();
      const key = this.dataset.view;
      if (!key) return;
      setActiveBtn(this);
      showView(key);
    });
  });
});
</script>
