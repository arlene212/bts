<?php ?>
<section class="tab-content <?php echo $currentTab === 'courses' ? 'active' : ''; ?>" id="courses">
  <style>
    #courses .batch-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 12px;
    }

    #courses .batch-card {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px;
      border: 1px solid #e2e6ea;
      border-radius: 10px;
      background: #fff;
      transition: transform .15s ease, box-shadow .15s ease;
      cursor: pointer;
    }

    #courses .batch-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(0, 0, 0, .08);
    }

    #courses .batch-card img {
      width: 88px;
      height: 66px;
      object-fit: cover;
      border-radius: 8px;
      background: #f1f3f5;
    }

    #courses .batch-info h3 {
      margin: 0 0 4px 0;
      font-size: 1rem;
    }

    #courses .batch-info p {
      margin: 0;
      color: #495057;
      font-size: .9rem;
    }

    #courses .course-detail {
      padding: 12px;
    }

    #courses .course-basic-info p {
      margin: 4px 0;
    }

    #courses .course-detail .section-header h3 {
      margin: 0;
    }

    #courses .detail-view {
      display: none;
    }

    #courses .detail-view.active {
      display: block;
    }

    #courses .switch-oval {
      position: relative;
      display: flex;
      background: #f8f9fa;
      border: 1px solid #e2e6ea;
      border-radius: 20px;
      overflow: hidden;
    }

    #courses .switch-oval .switch-inner {
      position: absolute;
      top: 0;
      left: 0;
      width: 50%;
      height: 100%;
      background: #e9f5ff;
      transition: transform .2s ease;
      z-index: 0;
    }

    #courses .switch-oval .switch-btn {
      position: relative;
      z-index: 1;
      flex: 1;
      padding: 8px 12px;
      border: none;
      background: transparent;
      cursor: pointer;
      font-size: .9rem;
    }

    #courses .news-active .switch-inner {
      background: #ffe3e3;
    }

    #courses #course-detail {
      position: fixed;
      inset: 0;
      z-index: 1000;
      background: rgba(0, 0, 0, 0.35);
      display: none;
      align-items: flex-start;
      justify-content: center;
      overflow: auto;
    }

    #courses #course-detail:not(.hidden) {
      display: flex;
    }

    #courses .course-detail-panel {
      margin: 40px auto;
      width: 96%;
      max-width: 1000px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, .15);
    }

    #courses .course-detail .detail-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 18px;
      border-bottom: 1px solid #e2e6ea;
    }

    #courses .course-detail .detail-body {
      padding: 16px 18px;
    }
  </style>
  <div id="enrolled" class="tab-inner active">
    <h2 class="section-header">My Assigned Courses</h2>
    <div class="batch-list">
      <?php if (count($courses) > 0): ?>
        <?php foreach ($courses as $course): ?>
          <div class="batch-card" data-course="<?php echo htmlspecialchars($course['course_name']); ?>" data-code="<?php echo htmlspecialchars($course['course_code']); ?>" data-hours="<?php echo htmlspecialchars($course['hours']); ?>" data-course-id="<?php echo htmlspecialchars($course['id']); ?>">
            <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/school.png'; ?>" alt="<?php echo htmlspecialchars($course['course_name']); ?>">
            <div class="batch-info">
              <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
              <p><?php echo htmlspecialchars($course['hours']); ?> Hours</p>
              <p><?php echo htmlspecialchars($course['course_code']); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-courses">
          <p>No courses assigned to you yet.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <div id="course-detail" class="course-detail hidden">
    <div class="course-detail-panel">
      <div class="detail-header">
        <h2 class="course-title" id="course-detail-title">Course Name</h2>
        <button class="back-btn btn-ripple">
          <i class="fas fa-arrow-left"></i> Back to Courses
        </button>
      </div>
      <div class="detail-body">
        <div class="course-basic-info">
          <p><strong>Code:</strong> <span id="course-code"></span></p>
          <p><strong>Hours:</strong> <span id="course-hours"></span></p>
          <p><strong>Description:</strong> <span id="course-description"></span></p>
        </div>
        <div class="news-switch-wrapper course-detail-toggle">
          <div class="switch-oval">
            <div class="switch-inner"></div>
            <button class="switch-btn active" data-view="view-material">View Material</button>
            <button class="switch-btn" data-view="submissions">Submissions</button>
          </div>
        </div>
        <div id="view-material-view" class="detail-view active">
          <div class="competencies-section">
            <div class="section-header">
              <h3>Course Competencies & Topics</h3>
            </div>
            <div id="competencies-list"></div>
          </div>
        </div>
        <div id="submissions-view" class="detail-view">
          <div class="submissions-section-main">
            <div class="section-header">
              <h3>Activity Submissions</h3>
            </div>
            <div id="submissions-list"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const toggleWrapper = document.querySelector('#courses .course-detail-toggle');
    if (!toggleWrapper) return;
    const btns = toggleWrapper.querySelectorAll('.switch-btn');
    const switchInner = toggleWrapper.querySelector('.switch-inner');

    function showView(key) {
      const views = document.querySelectorAll('#courses .detail-view');
      views.forEach(v => v.classList.remove('active'));
      const target = document.getElementById(key + '-view');
      if (target) target.classList.add('active');
    }

    function setActiveBtn(btn) {
      btns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      if (switchInner) {
        const idx = Array.from(btns).indexOf(btn);
        switchInner.style.transform = 'translateX(' + (idx * 100) + '%)';
      }
    }
    btns.forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        const key = this.dataset.view;
        if (!key) return;
        setActiveBtn(this);
        showView(key);
      });
    });

    const overlay = document.getElementById('course-detail');
    const enrolledTab = document.getElementById('enrolled');
    const backBtn = overlay ? overlay.querySelector('.back-btn') : null;
    if (backBtn) {
      backBtn.addEventListener('click', function(e) {
        e.preventDefault();
        overlay.classList.add('hidden');
        if (enrolledTab) enrolledTab.classList.remove('hidden');
      });
    }
    if (overlay) {
      overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
          overlay.classList.add('hidden');
          if (enrolledTab) enrolledTab.classList.remove('hidden');
        }
      });
    }
  });
</script>