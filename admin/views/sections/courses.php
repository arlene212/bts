<?php ?>
<section class="main-content tab-content" id="courses">
  <div class="tab-header"><h2>Course Management</h2><div class="tab-actions"><button class="create-btn" id="addCourseBtn">+ Add Course</button><button class="create-btn" id="addBatchBtn">+ Add Course Batch</button></div></div>
  <div class="courses-grid row g-3">
    <?php foreach ($courses as $course): ?>
      <div class="course-card card col-md-6 col-lg-4">
        <div class="course-image">
          <?php if (!empty($course['image'])): ?>
            <img src="../uploads/courses/<?php echo htmlspecialchars($course['image']); ?>" alt="<?php echo htmlspecialchars($course['course_name']); ?>">
          <?php else: ?>
            <div class="course-image-placeholder"><i class="fas fa-book"></i></div>
          <?php endif; ?>
        </div>
        <div class="course-info card-body">
          <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
          <p class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></p>
          <p class="course-hours"><?php echo htmlspecialchars($course['hours']); ?> hours</p>
          <p class="course-description"><?php echo htmlspecialchars($course['description']); ?></p>
          <?php $competencies = json_decode($course['competency_types'] ?? '[]', true); if (!empty($competencies)): ?>
            <div class="course-competencies"><h4>Competencies:</h4><ul><?php foreach ($competencies as $competency): ?><li><strong><?php echo ucfirst($competency['type']); ?>:</strong> <?php echo htmlspecialchars($competency['name']); ?><?php if (!empty($competency['description'])): ?> - <?php echo htmlspecialchars($competency['description']); ?><?php endif; ?></li><?php endforeach; ?></ul></div>
          <?php endif; ?>
          <?php $courseSpecificBatches = is_array($courseBatches) ? array_filter($courseBatches, function ($batch) use ($course) { return $batch['course_code'] == $course['course_code']; }) : []; ?>
          <div class="course-batches"><h4>Batches (<?php echo count($courseSpecificBatches); ?>):</h4><?php if (!empty($courseSpecificBatches)): ?><ul><?php foreach ($courseSpecificBatches as $batch): ?><li><?php echo htmlspecialchars($batch['batch_name']); ?> (<?php echo $batch['trainee_count']; ?> trainees)</li><?php endforeach; ?></ul><?php else: ?><p>No batches created yet.</p><?php endif; ?></div>
        </div>
        <div class="course-actions card-footer d-flex gap-2">
          <button class="edit-course-btn btn btn-outline-primary" data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>" data-course-data='<?php echo htmlspecialchars(json_encode(['course_name' => $course['course_name'], 'course_code' => $course['course_code'], 'hours' => $course['hours'], 'description' => $course['description'], 'image' => $course['image'], 'competency_types' => json_decode($course['competency_types'] ?? '[]', true)]), ENT_QUOTES, "UTF-8"); ?>'>Edit</button>
          <button class="view-details-btn btn btn-primary" data-course='<?php echo htmlspecialchars(json_encode(['course_code' => $course['course_code'], 'course_name' => $course['course_name'], 'hours' => $course['hours'], 'description' => $course['description'], 'image' => $course['image'], 'competency_types' => json_decode($course['competency_types'] ?? '[]', true)]), ENT_QUOTES, "UTF-8"); ?>'>View Details</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div id="course-detail-view" class="hidden"></div>
</section>