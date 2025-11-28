<?php
if (!isset($courseBatches) || !is_array($courseBatches)) { $courseBatches = []; }
?>
<section class="main-content tab-content <?php echo ($currentTab === 'courses' ? 'active' : ''); ?>" id="courses">
  <div class="tab-header"><h2>Course Management</h2><div class="tab-actions"><button class="create-btn" id="addCourseBtn">+ Add Course</button><button class="create-btn" id="addBatchBtn">+ Add Course Batch</button></div></div>
  <style>
    .courses-grid { position: relative; }
    .course-card.card { cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; overflow: visible; }
    .course-card.card.collapsed { max-height: max-content; overflow: hidden; }
    .course-card.card.expanded { transform-origin: top center; will-change: transform; }
    .course-card.card.collapsed .course-competencies, .course-card.card.collapsed .course-batches { display: none; }
    .course-card.card.expanded { position: relative; transform: scale(1.02); box-shadow: 0 8px 24px rgba(0,0,0,0.25); }
    .course-card.card .card-footer { z-index: 1; position: relative; }
    .course-batches ul { list-style: none; padding-left: 0; margin: 0; }
    .course-batches li { margin: 6px 0; }
    .batch-row { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
    .batch-name { font-weight: 600; }
    .batch-dates { color: #6c757d; font-size: 0.9em; }
    .batch-count-badge { background: #f1f3f5; color: #343a40; border: 1px solid #e2e6ea; border-radius: 12px; padding: 2px 8px; font-size: 0.85em; display: inline-flex; align-items: center; gap: 6px; }
    .batch-count-badge i { color: #495057; }
    .course-card.card .course-description, .course-card.card .course-learning-outcomes { text-align: center; overflow-wrap: anywhere; word-break: break-word; hyphens: auto; }
    .course-card.card.collapsed .course-description { max-height: 72px; overflow: hidden; }
    .course-card.card.expanded .course-description { max-height: none; overflow: visible; }
    .course-card.card.collapsed .course-learning-outcomes { max-height: 96px; overflow: hidden; }
    .course-card.card.expanded .course-learning-outcomes { max-height: none; overflow: visible; }
    .course-card.card.collapsed .course-description { max-width: 100px; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 4; line-clamp: 4; }
    .course-card.card.collapsed .course-learning-outcomes { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 6; line-clamp: 6; }
    .course-card.card.expanded .course-description, .course-card.card.expanded .course-learning-outcomes { display: block; }
    .competency-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
    .competency-chip { padding: 4px 8px; border-radius: 12px; font-size: 0.85em; border: 1px solid #e2e6ea; background: #f8f9fa; color: #343a40; }
    .competency-chip.type-basic { background: #e7f5ff; border-color: #74c0fc; color: #1c7ed6; }
    .competency-chip.type-common { background: #fff3bf; border-color: #fcc419; color: #7c5d00; }
    .competency-chip.type-core { background: #ffe3e3; border-color: #ff8787; color: #c92a2a; }
  </style>
  <div class="courses-grid row g-3">
      <?php foreach ($courses as $index => $course): 
        $courseSpecificBatches = is_array($courseBatches) ? array_filter($courseBatches, function ($batch) use ($course) { return $batch['course_code'] == $course['course_code']; }) : [];
        try {
          if (!isset($pdo) || !($pdo instanceof PDO)) { require_once __DIR__ . '/../../php/DatabaseConnection.php'; $database = new DatabaseConnection(); $pdo = $database->getConnection(); }
          $competenciesStmt = $pdo->prepare("SELECT competency_code, competency_name, competency_type, description FROM competencies WHERE course_id = ? AND status = 'active' ORDER BY competency_type, competency_name");
          $competenciesStmt->execute([(int)$course['id']]);
          $competencies = $competenciesStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $__) { $competencies = []; }
      ?>
      <div class="course-card card collapsed" style="animation-delay: <?php echo ($index * 0.1); ?>s;">
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
          <?php 
            $scheduleDaysPerWeek = $course['schedule_days_per_week'] ?? null;
            $scheduleDays = $course['schedule_days'] ?? '';
            $sessionHours = $course['session_hours'] ?? null;
            $scheduleText = '';
            if (!empty($scheduleDaysPerWeek) || !empty($scheduleDays) || !empty($sessionHours)) {
              $scheduleText = trim((($scheduleDaysPerWeek ? ($scheduleDaysPerWeek . ' days/week') : '') . ' ' . ($scheduleDays ? '(' . htmlspecialchars($scheduleDays) . ')' : '') . ' ' . ($sessionHours ? ('• ' . $sessionHours . 'h/session') : '')));
            }
          ?>
          <?php if (!empty($scheduleText)): ?>
            <div class="course-schedule"><i class="fas fa-calendar-alt"></i> <?php echo $scheduleText; ?></div>
          <?php endif; ?>
          
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
            <div class="competency-chips">
              <?php foreach ($competencies as $competency): ?>
                <span class="competency-chip type-<?php echo htmlspecialchars($competency['competency_type']); ?>" title="<?php echo htmlspecialchars(ucfirst($competency['competency_type'])); ?>">
                  <?php echo htmlspecialchars($competency['competency_name']); ?>
                </span>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
          
          <div class="course-batches">
            <h4>Batches <span class="batch-count"><?php echo count($courseSpecificBatches); ?></span></h4>
            <?php if (!empty($courseSpecificBatches)): ?>
              <ul>
                <?php foreach ($courseSpecificBatches as $batch): ?>
                  <?php
                    $hasStart = !empty($batch['start_date']);
                    $hasEnd = !empty($batch['end_date']);
                    $dateText = '';
                    if ($hasStart && $hasEnd) {
                      $startFmt = date('M d, Y', strtotime($batch['start_date']));
                      $endFmt = date('M d, Y', strtotime($batch['end_date']));
                      $dateText = "$startFmt – $endFmt";
                      try {
                        $startDt = new DateTime($batch['start_date']);
                        $endDt = new DateTime($batch['end_date']);
                        $days = $startDt->diff($endDt)->days + 1;
                        $weeks = ceil($days / 7);
                        $dateText .= " • ~{$weeks} wk";
                      } catch (Exception $__) {}
                    } elseif ($hasStart && !$hasEnd) {
                      $dateText = 'Starts ' . date('M d, Y', strtotime($batch['start_date']));
                    } elseif (!$hasStart && $hasEnd) {
                      $dateText = 'Ends ' . date('M d, Y', strtotime($batch['end_date']));
                    } else {
                      $dateText = 'Dates TBD';
                    }
                  ?>
                  <li>
                    <div class="batch-row">
                      <div>
                        <span class="batch-name"><?php echo htmlspecialchars($batch['batch_name']); ?></span><br>
                        <span class="batch-dates"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($dateText); ?></span>
                      </div>
                      <span class="batch-count-badge"><i class="fas fa-users"></i> <?php echo (int)$batch['trainee_count']; ?></span>
                    </div>
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
                    'course_id' => $course['id'],
                    'hours' => $course['hours'], 
                    'description' => $course['description'], 
                    'learning_outcomes' => $course['learning_outcomes'] ?? '',
                    'course_status' => $course['course_status'] ?? 'published',
                    'allow_preview' => $course['allow_preview'] ?? 0,
                    'preview_content' => $course['preview_content'] ?? '',
                    'require_verification' => $course['require_verification'] ?? 0,
                    'verification_type' => $course['verification_type'] ?? 'email',
                    'image' => $course['image'], 
                    'status' => $course['status'] ?? 'active',
                    'branch_id' => $course['branch_id'] ?? null,
                    'competency_types' => $competencies,
                    'schedule_days_per_week' => $course['schedule_days_per_week'] ?? null,
                    'schedule_days' => $course['schedule_days'] ?? '',
                    'session_hours' => $course['session_hours'] ?? null
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
          <?php if (($course['status'] ?? 'active') === 'archived' || ($course['course_status'] ?? '') === 'archived'): ?>
            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Restore this course?');">
              <input type="hidden" name="current_tab" value="courses">
              <input type="hidden" name="course_code" value="<?php echo htmlspecialchars($course['course_code']); ?>">
              <button type="submit" name="restore_course" class="btn btn-success">
                <i class="fas fa-box-open"></i> Restore
              </button>
            </form>
          <?php else: ?>
            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Archive this course?');">
              <input type="hidden" name="current_tab" value="courses">
              <input type="hidden" name="course_code" value="<?php echo htmlspecialchars($course['course_code']); ?>">
              <button type="submit" name="archive_course" class="btn btn-outline-danger">
                <i class="fas fa-archive"></i> Archive
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div id="course-detail-view" class="hidden"></div>
  <script>
    (function(){
      var cards = document.querySelectorAll('#courses .course-card.card');
      cards.forEach(function(card){
        card.addEventListener('click', function(e){
          if (e.target.closest('.course-actions') || e.target.closest('button') || e.target.closest('form')) { return; }
          cards.forEach(function(other){ if (other !== card) { other.classList.remove('expanded'); other.classList.add('collapsed'); } });
          if (card.classList.contains('expanded')) { card.classList.remove('expanded'); card.classList.add('collapsed'); }
          else { card.classList.remove('collapsed'); card.classList.add('expanded'); }
        });
      });
    })();
  </script>
</section>
