<?php ?>
<div class="mycourses tab-inner active" id="mycourses">
  <div class="course-box">
    <div class="news-switch-wrapper">
      <div class="switch-oval" id="courseSwitchContainer">
        <button type="button" class="switch-btn active" data-target="active">Active</button>
        <button type="button" class="switch-btn" data-target="completed">Completed</button>
      </div>
    </div>
    
    <script>
    // Course switch functionality - separate from main.js to avoid conflicts
    document.addEventListener('DOMContentLoaded', function() {
      const courseSwitchContainer = document.getElementById('courseSwitchContainer');
      if (!courseSwitchContainer) return;
      
      const switchButtons = courseSwitchContainer.querySelectorAll('.switch-btn');
      const switchInner = document.getElementById('myCourseSwitchInner');
      const activeCoursesContent = document.getElementById('enrolled-active-courses');
      const completedCoursesContent = document.getElementById('enrolled-completed-courses');
      
      // Initialize switch position
      if (switchInner) {
        switchInner.style.transform = 'translateX(0%)';
        switchInner.style.width = '50%';
      }
      
      // Add click event listeners to switch buttons
      switchButtons.forEach(button => {
        button.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          
          const target = this.getAttribute('data-target');
          console.log('Course switch clicked:', target);
          
          // Update button states
          switchButtons.forEach(btn => btn.classList.remove('active'));
          this.classList.add('active');
          
          // Update switch inner position
          const buttonIndex = Array.from(switchButtons).indexOf(this);
          const buttonWidth = 100 / switchButtons.length;
          if (switchInner) {
            switchInner.style.transform = `translateX(${buttonIndex * 100}%)`;
            switchInner.style.width = `${buttonWidth}%`;
          }
          
          // Show/hide content with immediate effect
          if (target === 'active') {
            if (activeCoursesContent) {
              activeCoursesContent.classList.add('active');
              activeCoursesContent.style.display = 'block';
            }
            if (completedCoursesContent) {
              completedCoursesContent.classList.remove('active');
              completedCoursesContent.style.display = 'none';
            }
            console.log('Switched to Active courses');
          } else {
            if (activeCoursesContent) {
              activeCoursesContent.classList.remove('active');
              activeCoursesContent.style.display = 'none';
            }
            if (completedCoursesContent) {
              completedCoursesContent.classList.add('active');
              completedCoursesContent.style.display = 'block';
            }
            console.log('Switched to Completed courses');
          }
          
          // Log current states for debugging
          console.log('Active courses display:', activeCoursesContent ? activeCoursesContent.style.display : 'not found');
          console.log('Completed courses display:', completedCoursesContent ? completedCoursesContent.style.display : 'not found');
          console.log('Active courses active class:', activeCoursesContent ? activeCoursesContent.classList.contains('active') : 'not found');
          console.log('Completed courses active class:', completedCoursesContent ? completedCoursesContent.classList.contains('active') : 'not found');
        });
      });
    });
    </script>
    
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
                 data-course-id="<?php echo htmlspecialchars($course['id']); ?>">
              <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/course-placeholder.jpg'; ?>" 
                   alt="<?php echo htmlspecialchars($course['course_name']); ?>" 
                   class="course-img">
              <div class="batch-info">
                <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                <p>Code: <?php echo htmlspecialchars($course['course_code']); ?></p>
                <p>Hours: <?php echo htmlspecialchars($course['hours']); ?> hrs</p>
                <p class="progress-indicator">Progress: <?php echo htmlspecialchars($course['progress']); ?>% (<?php echo $course['completed_activities']; ?>/<?php echo $course['total_activities']; ?> activities)</p>
                <?php if (!empty($course['description'])): ?>
                  <p class="course-description"><?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>...</p>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-courses">
            <p>You have no active courses.</p>
            <p>Check out our <a href="#" class="tab-switch" data-tab="offered-courses">Offered Courses</a> to get started!</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
    
    <div id="enrolled-completed-courses" class="tab-inner">
      <h2 class="section-header">Completed Courses</h2>
      <div class="batch-list">
        <?php if (!empty($completed_courses)): ?>
          <?php foreach ($completed_courses as $course): ?>
            <div class="batch-card view-course-content-btn" 
                 data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>" 
                 data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>" 
                 data-course-hours="<?php echo htmlspecialchars($course['hours']); ?>" 
                 data-course-description="<?php echo htmlspecialchars($course['description']); ?>"
                 data-course-id="<?php echo htmlspecialchars($course['id']); ?>">
              <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/course-placeholder.jpg'; ?>" 
                   alt="<?php echo htmlspecialchars($course['course_name']); ?>" 
                   class="course-img">
              <div class="batch-info">
                <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                <p>Code: <?php echo htmlspecialchars($course['course_code']); ?></p>
                <p>Hours: <?php echo htmlspecialchars($course['hours']); ?> hrs</p>
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
            <p>Keep learning to complete your enrolled courses!</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
</div>

<script>
// Course switch functionality - moved to bottom to ensure DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  console.log('DOM loaded, initializing course switch...');
  
  const courseSwitchContainer = document.getElementById('courseSwitchContainer');
  if (!courseSwitchContainer) {
    console.error('Course switch container not found!');
    return;
  }
  
  const switchButtons = courseSwitchContainer.querySelectorAll('.switch-btn');
  const switchInner = document.getElementById('myCourseSwitchInner');
  const activeCoursesContent = document.getElementById('enrolled-active-courses');
  const completedCoursesContent = document.getElementById('enrolled-completed-courses');
  
  console.log('Found elements:', {
    switchButtons: switchButtons.length,
    switchInner: !!switchInner,
    activeContent: !!activeCoursesContent,
    completedContent: !!completedCoursesContent
  });
  
  // Initialize switch position
  if (switchInner) {
    switchInner.style.transform = 'translateX(0%)';
    switchInner.style.width = '50%';
  }
  
  // Add click event listeners to switch buttons
  switchButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const target = this.getAttribute('data-target');
      console.log('Course switch clicked:', target);
      
      // Update button states
      switchButtons.forEach(btn => btn.classList.remove('active'));
      this.classList.add('active');
      
      // Update switch inner position
      const buttonIndex = Array.from(switchButtons).indexOf(this);
      const buttonWidth = 100 / switchButtons.length;
      if (switchInner) {
        switchInner.style.transform = `translateX(${buttonIndex * 100}%)`;
        switchInner.style.width = `${buttonWidth}%`;
      }
      
      // Show/hide content with both class and inline style
      if (target === 'active') {
        if (activeCoursesContent) {
          activeCoursesContent.classList.add('active');
          activeCoursesContent.style.display = 'block';
          console.log('Showing active courses');
        }
        if (completedCoursesContent) {
          completedCoursesContent.classList.remove('active');
          completedCoursesContent.style.display = 'none';
          console.log('Hiding completed courses');
        }
      } else {
        if (activeCoursesContent) {
          activeCoursesContent.classList.remove('active');
          activeCoursesContent.style.display = 'none';
          console.log('Hiding active courses');
        }
        if (completedCoursesContent) {
          completedCoursesContent.classList.add('active');
          completedCoursesContent.style.display = 'block';
          console.log('Showing completed courses');
        }
      }
    });
  });
  
  // Test the switching functionality
  console.log('Course switch initialized successfully');
});
</script>

<div id="courseDetail" class="course-detail hidden">
    <div class="detail-header">
      <button class="back-btn btn-ripple">
        <i class="fas fa-arrow-left"></i> Back to My Courses
      </button>
      <h2 id="course-detail-title">Course Title</h2>
    </div>
    <div class="course-card">
      <div class="course-meta">
        <span id="course-detail-code"></span> | <span id="course-detail-hours"></span>
      </div>
      <p id="course-detail-description"></p>
    </div>
    <div id="competencies-card" class="competencies-card"></div>
    <div class="news-switch-wrapper course-switch">
      <div class="switch-oval">
        <div class="switch-inner"></div>
        <button class="switch-btn active" data-tab="modules-view">Modules</button>
        <button class="switch-btn" data-tab="activities-view">Activities</button>
      </div>
    </div>
    <div id="modules-view" class="tab-inner active">
      <div id="modules-list"></div>
    </div>
    <div id="activities-view" class="tab-inner"></div>
  </div>