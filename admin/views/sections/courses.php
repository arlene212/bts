<?php
if (!isset($courseBatches) || !is_array($courseBatches)) { $courseBatches = []; }
?>
<section class="main-content tab-content <?php echo ($currentTab === 'courses' ? 'active' : ''); ?>" id="courses">
  <div class="tab-header"><h2>Course Management</h2><div class="tab-actions"><button class="create-btn" id="addCourseBtn">+ Add Course</button><button class="create-btn" id="addBatchBtn">+ Add Course Batch</button></div></div>
  <div class="courses-grid row g-3">
    <?php foreach ($courses as $index => $course): 
      $courseSpecificBatches = is_array($courseBatches) ? array_filter($courseBatches, function ($batch) use ($course) { return $batch['course_code'] == $course['course_code']; }) : [];
      $competencies = json_decode($course['competency_types'] ?? '[]', true);
    ?>
      <div class="course-card card" style="animation-delay: <?php echo ($index * 0.1); ?>s;">
        <div class="course-image">
          <?php if (!empty($course['image'])): ?>
            <img src="../uploads/courses/<?php echo htmlspecialchars($course['image']); ?>" alt="<?php echo htmlspecialchars($course['course_name']); ?>" loading="lazy">
          <?php else: ?>
            <div class="course-image-placeholder">
              <i class="fas fa-graduation-cap"></i>
            </div>
          <?php endif; ?>
          <div class="course-status <?php echo ($course['course_status'] ?? 'published'); ?>"><?php echo ucfirst($course['course_status'] ?? 'Published'); ?></div>
        </div>
        <div class="course-info card-body">
          <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
          <span class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></span>
          <div class="course-hours"><?php echo htmlspecialchars($course['hours']); ?> hours</div>
          <p class="course-description"><?php echo htmlspecialchars($course['description']); ?></p>
          
          <?php if (!empty($course['learning_outcomes'])): ?>
            <div class="course-learning-outcomes">
              <h4>Learning Outcomes</h4>
              <p><?php echo htmlspecialchars($course['learning_outcomes']); ?></p>
            </div>
          <?php endif; ?>
          
          <?php if (!empty($course['allow_preview'])): ?>
            <div class="course-preview-badge">
              <i class="fas fa-eye"></i> Preview Available
            </div>
          <?php endif; ?>
          
          <?php if (!empty($course['require_verification'])): ?>
            <div class="course-verification-badge">
              <i class="fas fa-shield-alt"></i> Verification Required
              <small><?php echo ucfirst($course['verification_type']); ?></small>
            </div>
          <?php endif; ?>
          
          <?php if (!empty($competencies)): ?>
            <div class="course-competencies">
              <h4>Competencies</h4>
              <ul>
                <?php foreach ($competencies as $competency): ?>
                  <li>
                    <strong><?php echo ucfirst($competency['type']); ?>:</strong> 
                    <?php echo htmlspecialchars($competency['name']); ?>
                    <?php if (!empty($competency['description'])): ?>
                      <br><small><?php echo htmlspecialchars($competency['description']); ?></small>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
          
          <div class="course-batches">
            <h4>Batches <span class="batch-count"><?php echo count($courseSpecificBatches); ?></span></h4>
            <?php if (!empty($courseSpecificBatches)): ?>
              <ul>
                <?php foreach ($courseSpecificBatches as $batch): ?>
                  <li>
                    <span><?php echo htmlspecialchars($batch['batch_name']); ?></span>
                    <span class="text-muted"><?php echo $batch['trainee_count']; ?> trainees</span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="text-muted">No batches created yet</p>
            <?php endif; ?>
          </div>
        </div>
        <div class="course-actions card-footer">
          <button class="edit-course-btn btn btn-outline-primary" 
                  data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>" 
                  data-course-data='<?php echo htmlspecialchars(json_encode([
                    'course_name' => $course['course_name'], 
                    'course_code' => $course['course_code'], 
                    'hours' => $course['hours'], 
                    'description' => $course['description'], 
                    'learning_outcomes' => $course['learning_outcomes'] ?? '',
                    'course_status' => $course['course_status'] ?? 'published',
                    'allow_preview' => $course['allow_preview'] ?? 0,
                    'preview_content' => $course['preview_content'] ?? '',
                    'require_verification' => $course['require_verification'] ?? 0,
                    'verification_type' => $course['verification_type'] ?? 'email',
                    'image' => $course['image'], 
                    'competency_types' => $competencies
                  ]), ENT_QUOTES, "UTF-8"); ?>'>
            <i class="fas fa-edit"></i> Edit
          </button>
          <button class="view-details-btn btn btn-primary" 
                  data-course='<?php echo htmlspecialchars(json_encode([
                    'course_code' => $course['course_code'], 
                    'course_name' => $course['course_name'], 
                    'hours' => $course['hours'], 
                    'description' => $course['description'], 
                    'learning_outcomes' => $course['learning_outcomes'] ?? '',
                    'course_status' => $course['course_status'] ?? 'published',
                    'allow_preview' => $course['allow_preview'] ?? 0,
                    'preview_content' => $course['preview_content'] ?? '',
                    'require_verification' => $course['require_verification'] ?? 0,
                    'verification_type' => $course['verification_type'] ?? 'email',
                    'image' => $course['image'], 
                    'competency_types' => $competencies
                  ]), ENT_QUOTES, "UTF-8"); ?>'>
            <i class="fas fa-eye"></i> View Details
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div id="course-detail-view" class="hidden"></div>
</section>