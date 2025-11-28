<?php
// Quiz management interface for trainers
require_once __DIR__ . '/../../../php/SessionManager.php';
require_once __DIR__ . '/../../../php/DatabaseConnection.php';

SessionManager::startSession();
SessionManager::requireRole('trainer');

$user = SessionManager::getCurrentUser();
$database = new DatabaseConnection();
$pdo = $database->getConnection();

// Get trainer's courses
try {
    $trainerCoursesStmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(DISTINCT q.id) as quiz_count,
               COUNT(DISTINCT e.trainee_id) as enrolled_trainees
        FROM courses c
        LEFT JOIN course_assignments ca ON c.course_code = ca.course_code
        LEFT JOIN quizzes q ON c.course_code = q.course_code
        LEFT JOIN enrollments e ON c.course_code = e.course_code AND e.status = 'approved'
        WHERE ca.trainer_id = ?
        GROUP BY c.course_code
        ORDER BY c.course_name
    ");
    $trainerCoursesStmt->execute([$user['user_id']]);
    $trainerCourses = $trainerCoursesStmt->fetchAll();

    // Get quizzes for trainer's courses
    $quizzesStmt = $pdo->prepare("
        SELECT q.*, c.course_name, 
               COUNT(DISTINCT qq.id) as question_count,
               COUNT(DISTINCT qa.id) as attempt_count
        FROM quizzes q
        JOIN courses c ON q.course_code = c.course_code
        LEFT JOIN quiz_questions qq ON q.id = qq.quiz_id
        LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id
        WHERE c.course_code IN (SELECT course_code FROM course_assignments WHERE trainer_id = ?)
        GROUP BY q.id
        ORDER BY q.created_at DESC
    ");
    $quizzesStmt->execute([$user['user_id']]);
    $quizzes = $quizzesStmt->fetchAll();
    foreach ($quizzes as $idx => $q) {
        try {
            $ds = $pdo->prepare("SELECT setting_value FROM quiz_settings WHERE quiz_id = ? AND setting_key = 'due_date' LIMIT 1");
            $ds->execute([$q['id']]);
            $quizzes[$idx]['due_date'] = $ds->fetchColumn();
        } catch (Exception $_) {}
    }
    $activeQuizzes = array_filter($quizzes, function($q){ return ($q['status'] ?? 'draft') !== 'archived'; });
    $archivedQuizzes = array_filter($quizzes, function($q){ return ($q['status'] ?? 'draft') === 'archived'; });
} catch (PDOException $e) {
    error_log("Error loading quiz data: " . $e->getMessage());
    $trainerCourses = [];
    $quizzes = [];
}

 
?>

<section class="main-content tab-content <?php echo ($currentTab === 'quizzes' ? 'active' : ''); ?>" id="quizzes">
    <div class="tab-header">
        <div class="header-content">
            <h2><i class="fas fa-tasks"></i> Activity Management</h2>
            <p class="header-subtitle">Create and manage interactive activities for your courses</p>
        </div>
        <div class="tab-actions">
            <button class="btn btn-primary" id="addQuizBtn">
                <i class="fas fa-plus"></i> Create Activity
            </button>
            <button class="btn btn-secondary" id="addQuestionBtn">
                <i class="fas fa-check"></i> Add Task
            </button>
        </div>
    </div>

    <div class="quizzes-grid">
        <?php if (empty($activeQuizzes)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <h3>No Activities Found</h3>
                <p>Create your first activity to start assessing your students' knowledge and progress.</p>
                <div class="empty-actions">
                    <button class="btn btn-primary" onclick="openModal('addQuizModal')">
                        <i class="fas fa-plus"></i> Create Your First Activity
                    </button>
                    <button class="btn btn-outline-secondary" onclick="showHelp()">
                        <i class="fas fa-info-circle"></i> Learn More
                    </button>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($activeQuizzes as $index => $quiz): ?>
                <?php 
                $statusClass = $quiz['status'];
                $statusIcon = '';
                switch($quiz['status']) {
                    case 'active': $statusIcon = 'fa-check-circle'; break;
                    case 'published': $statusIcon = 'fa-globe'; break;
                    case 'draft': $statusIcon = 'fa-pencil'; break;
                    case 'inactive': $statusIcon = 'fa-pause'; break;
                    default: $statusIcon = 'fa-circle'; break;
                }
                ?>
                <div class="quiz-card card" style="animation-delay: <?php echo ($index * 0.1); ?>s;">
                    <div class="quiz-progress-bar">
                        <div class="progress-fill" style="width: <?php echo min(($quiz['attempt_count'] / 10) * 100, 100); ?>%"></div>
                    </div>
                    
                    <div class="quiz-header">
                        <div class="quiz-title-wrapper">
                            <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                            <div class="quiz-status <?php echo $statusClass; ?>">
                                <i class="fas <?php echo $statusIcon; ?>"></i>
                                <?php echo ucfirst($quiz['status']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="quiz-info">
                        <div class="quiz-course">
                            <i class="fas fa-graduation-cap"></i>
                            <span><?php echo htmlspecialchars($quiz['course_name']); ?></span>
                        </div>

                        <?php if (!empty($quiz['description'])): ?>
                            <p class="quiz-description"><?php echo htmlspecialchars($quiz['description']); ?></p>
                        <?php endif; ?>

                        <div class="quiz-stats">
                            <div class="stat">
                                <i class="fas fa-tasks"></i>
                                <span><?php echo $quiz['question_count']; ?> Tasks</span>
                            </div>
                            <div class="stat">
                                <i class="fas fa-clock"></i>
                                <span><?php echo $quiz['time_limit'] ? $quiz['time_limit'] . ' min' : 'No limit'; ?></span>
                            </div>
                            <div class="stat">
                                <i class="fas fa-users"></i>
                                <span><?php echo $quiz['attempt_count']; ?> Attempts</span>
                            </div>
                            <div class="stat">
                                <i class="fas fa-percentage"></i>
                                <span>Pass: <?php echo $quiz['passing_score']; ?>%</span>
                            </div>
                            <?php if (!empty($quiz['due_date'])): ?>
                            <div class="stat">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Due: <?php echo date('M j, Y g:i A', strtotime($quiz['due_date'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="quiz-meta">
                            <div class="meta-item">
                                <i class="fas fa-redo"></i>
                                <span><?php echo $quiz['is_randomized'] ? 'Randomized order' : 'Fixed order'; ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-eye"></i>
                                <span><?php echo $quiz['show_correct_answers'] ? 'Shows answers' : 'No answers shown'; ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span>Created: <?php echo date('M j, Y', strtotime($quiz['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="quiz-actions">
                        <button class="btn btn-outline-primary edit-quiz-btn"
                            data-quiz-id="<?php echo $quiz['id']; ?>"
                            data-quiz-data='<?php echo htmlspecialchars(json_encode($quiz), ENT_QUOTES, "UTF-8"); ?>'
                            title="Edit quiz settings">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-primary view-questions-btn"
                            data-quiz-id="<?php echo $quiz['id']; ?>"
                            data-quiz-title="<?php echo htmlspecialchars($quiz['title']); ?>"
                            title="Manage tasks">
                            <i class="fas fa-list"></i> Tasks
                        </button>
                        <?php if ($quiz['status'] === 'draft'): ?>
                        <button class="btn btn-success publish-quiz-btn"
                            data-quiz-id="<?php echo $quiz['id']; ?>"
                            title="Publish this activity">
                            <i class="fas fa-globe"></i> Publish
                        </button>
                        <?php endif; ?>
                        <?php if ($quiz['status'] !== 'archived'): ?>
                        <button class="btn btn-outline-secondary archive-quiz-btn"
                            data-quiz-id="<?php echo $quiz['id']; ?>"
                            title="Archive this activity">
                            <i class="fas fa-archive"></i> Archive
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-success view-results-btn"
                            data-quiz-id="<?php echo $quiz['id']; ?>"
                            title="View activity results">
                            <i class="fas fa-chart-bar"></i> Results
                        </button>
                        <button class="btn btn-info duplicate-quiz-btn"
                            data-quiz-id="<?php echo $quiz['id']; ?>"
                            title="Duplicate this activity">
                            <i class="fas fa-copy"></i> Duplicate
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="archived-section">
        <div class="tab-header" style="margin-top: 24px;">
            <div class="header-content">
                <h3><i class="fas fa-archive"></i> Archived Activities</h3>
                <p class="header-subtitle">Activities that are no longer available to trainees</p>
            </div>
        </div>
        <div class="quizzes-grid">
            <?php if (empty($archivedQuizzes)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-archive"></i>
                    </div>
                    <h3>No Archived Activities</h3>
                    <p>Archived activities will appear here for reference.</p>
                </div>
            <?php else: ?>
                <?php foreach ($archivedQuizzes as $index => $quiz): ?>
                    <?php 
                    $statusClass = $quiz['status'];
                    $statusIcon = 'fa-archive';
                    ?>
                    <div class="quiz-card card archived" style="animation-delay: <?php echo ($index * 0.1); ?>s;">
                        <div class="quiz-header">
                            <div class="quiz-title-wrapper">
                                <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                                <div class="quiz-status <?php echo $statusClass; ?>">
                                    <i class="fas <?php echo $statusIcon; ?>"></i>
                                    Archived
                                </div>
                            </div>
                        </div>

                        <div class="quiz-info">
                            <div class="quiz-course">
                                <i class="fas fa-graduation-cap"></i>
                                <span><?php echo htmlspecialchars($quiz['course_name']); ?></span>
                            </div>

                            <?php if (!empty($quiz['description'])): ?>
                                <p class="quiz-description"><?php echo htmlspecialchars($quiz['description']); ?></p>
                            <?php endif; ?>

                            <div class="quiz-stats">
                                <div class="stat">
                                    <i class="fas fa-question-circle"></i>
                                    <span><?php echo $quiz['question_count']; ?> Questions</span>
                                </div>
                                <div class="stat">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo $quiz['time_limit'] ? $quiz['time_limit'] . ' min' : 'No limit'; ?></span>
                                </div>
                                <div class="stat">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $quiz['attempt_count']; ?> Attempts</span>
                                </div>
                                <div class="stat">
                                    <i class="fas fa-percentage"></i>
                                    <span>Pass: <?php echo $quiz['passing_score']; ?>%</span>
                                </div>
                                <?php if (!empty($quiz['due_date'])): ?>
                                <div class="stat">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Due: <?php echo date('M j, Y g:i A', strtotime($quiz['due_date'])); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="quiz-actions">
                            <button class="btn btn-outline-primary edit-quiz-btn"
                                data-quiz-id="<?php echo $quiz['id']; ?>"
                                data-quiz-data='<?php echo htmlspecialchars(json_encode($quiz), ENT_QUOTES, "UTF-8"); ?>'
                                title="Edit quiz settings">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-primary view-questions-btn"
                                data-quiz-id="<?php echo $quiz['id']; ?>"
                                data-quiz-title="<?php echo htmlspecialchars($quiz['title']); ?>"
                                title="Manage questions">
                                <i class="fas fa-list"></i> Questions
                            </button>
                            <button class="btn btn-info duplicate-quiz-btn"
                                data-quiz-id="<?php echo $quiz['id']; ?>"
                                title="Duplicate this quiz">
                                <i class="fas fa-copy"></i> Duplicate
                            </button>
                            <button class="btn btn-success view-results-btn"
                                data-quiz-id="<?php echo $quiz['id']; ?>"
                                title="View student results">
                                <i class="fas fa-chart-bar"></i> Results
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for quiz action buttons
    document.querySelectorAll('.duplicate-quiz-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const quizId = this.getAttribute('data-quiz-id');
            duplicateQuiz(quizId);
        });
    });

    document.querySelectorAll('.publish-quiz-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const quizId = this.getAttribute('data-quiz-id');
            publishQuiz(quizId, this);
        });
    });

    document.querySelectorAll('.archive-quiz-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const quizId = this.getAttribute('data-quiz-id');
            archiveQuiz(quizId, this);
        });
    });

    document.querySelectorAll('.edit-quiz-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = this.getAttribute('data-quiz-data');
            const quiz = JSON.parse(data);
            const form = document.getElementById('addQuizForm');
            if (!form) return;
            form.reset();
            form.querySelector('#quiz_title').value = quiz.title || '';
            const courseSelect = form.querySelector('#quiz_course');
            if (courseSelect) courseSelect.value = quiz.course_code || '';
            const competencySelect = form.querySelector('#quiz_competency');
            form.querySelector('#quiz_description').value = quiz.description || '';
            form.querySelector('#quiz_time_limit').value = quiz.time_limit || '';
            form.querySelector('#quiz_max_attempts').value = quiz.max_attempts || 1;
            form.querySelector('#quiz_passing_score').value = quiz.passing_score || 70;
            form.querySelector('#quiz_randomized').checked = !!quiz.is_randomized;
            form.querySelector('#quiz_show_answers').checked = !!quiz.show_correct_answers;
            const dueInput = form.querySelector('#quiz_due_date');
            if (dueInput && quiz.due_date) {
                const dt = new Date(quiz.due_date.replace(' ', 'T'));
                const pad = n => String(n).padStart(2,'0');
                const v = `${dt.getFullYear()}-${pad(dt.getMonth()+1)}-${pad(dt.getDate())}T${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
                dueInput.value = v;
            }
            let idInput = form.querySelector('input[name="quiz_id"]');
            if (!idInput) {
                idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'quiz_id';
                form.appendChild(idInput);
            }
            idInput.value = quiz.id;
            const submitBtn = form.querySelector('.submit-btn');
            if (submitBtn) submitBtn.textContent = 'Save Changes';
            const modal = document.getElementById('addQuizModal');
            if (modal) { modal.classList.remove('hidden'); modal.style.display='flex'; }
            form.dataset.mode = 'edit';
            if (courseSelect && competencySelect) {
                competencySelect.disabled = true;
                competencySelect.innerHTML = '<option value="">Loading...</option>';
                fetch('../php/get_competencies.php?course_code=' + encodeURIComponent(courseSelect.value))
                  .then(r=>r.json())
                  .then(list=>{
                    const opts = Array.isArray(list) ? list : [];
                    if (opts.length === 0) {
                      competencySelect.innerHTML = '<option value="">No competencies found</option>';
                      competencySelect.disabled = true;
                    } else {
                      competencySelect.innerHTML = '<option value="">Select Competency</option>';
                      opts.forEach(c=>{
                        const opt = document.createElement('option');
                        opt.value = String(c.id);
                        opt.textContent = `${c.competency_name} (${c.competency_type})`;
                        competencySelect.appendChild(opt);
                      });
                      competencySelect.disabled = false;
                      if (quiz.competency_id) {
                        competencySelect.value = String(quiz.competency_id);
                      }
                    }
                  })
                  .catch(()=>{
                    competencySelect.innerHTML = '<option value="">Failed to load</option>';
                    competencySelect.disabled = true;
                  });
            }
        });
    });

    const addQuizForm = document.getElementById('addQuizForm');
    if (addQuizForm) {
        const courseSelectEl = addQuizForm.querySelector('#quiz_course');
        const competencySelectEl = addQuizForm.querySelector('#quiz_competency');
        const loadCompetenciesForCourse = (code) => {
            if (!competencySelectEl) return;
            competencySelectEl.innerHTML = '<option value="">Loading...</option>';
            competencySelectEl.disabled = true;
            if (!code) {
                competencySelectEl.innerHTML = '<option value="">Select Competency</option>';
                competencySelectEl.disabled = true;
                return;
            }
            fetch('../php/get_competencies.php?course_code=' + encodeURIComponent(code))
              .then(r=>r.json())
              .then(list=>{
                const opts = Array.isArray(list) ? list : [];
                if (opts.length === 0) {
                    competencySelectEl.innerHTML = '<option value="">No competencies found</option>';
                    competencySelectEl.disabled = true;
                } else {
                    competencySelectEl.innerHTML = '<option value="">Select Competency</option>';
                    opts.forEach(c=>{
                        const opt = document.createElement('option');
                        opt.value = String(c.id);
                        opt.textContent = `${c.competency_name} (${c.competency_type})`;
                        competencySelectEl.appendChild(opt);
                    });
                    competencySelectEl.disabled = false;
                }
              })
              .catch(()=>{
                competencySelectEl.innerHTML = '<option value="">Failed to load</option>';
                competencySelectEl.disabled = true;
              });
        };

        if (courseSelectEl) {
            courseSelectEl.addEventListener('change', function(){
                loadCompetenciesForCourse(courseSelectEl.value);
            });
        }
        addQuizForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (addQuizForm.dataset.submitting === '1') { return; }
            addQuizForm.dataset.submitting = '1';
            const submitBtnEl = addQuizForm.querySelector('.submit-btn');
            const originalSubmitHTML = submitBtnEl ? submitBtnEl.innerHTML : '';
            if (submitBtnEl) { submitBtnEl.disabled = true; submitBtnEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...'; }
            const fd = new FormData(addQuizForm);
            const compVal = competencySelectEl ? competencySelectEl.value : '';
            const quizIdField = addQuizForm.querySelector('input[name="quiz_id"]');
            const quizIdVal = quizIdField ? quizIdField.value : '';
            const isEdit = addQuizForm.dataset.mode === 'edit' && !!quizIdVal;
            if (fd.has('action')) { fd.delete('action'); }
            if (isEdit) {
              fd.set('action', 'update_quiz');
              fd.set('quiz_id', quizIdVal);
            } else {
              fd.set('action', 'create_quiz');
            }
            if (compVal) { fd.set('quiz_competency', compVal); }
            fetch('../trainer/handlers/quiz_handler.php', { method:'POST', body: fd })
              .then(r=>r.json())
              .then(data=>{
                if (data.success) {
                  alert(isEdit ? 'Activity updated successfully' : 'Activity created successfully');
                  location.reload();
                } else { alert(data.message||'Error'); }
              })
              .catch(()=>alert('Request failed'))
              .finally(()=>{ addQuizForm.dataset.submitting = ''; if (submitBtnEl) { submitBtnEl.disabled = false; submitBtnEl.innerHTML = originalSubmitHTML; } });
        });
    }

    document.querySelectorAll('.view-questions-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const quizId = this.getAttribute('data-quiz-id');
            const modal = document.getElementById('viewQuestionsModal');
            const body = document.getElementById('viewQuestionsBody');
            if (!modal || !body) return;
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            body.innerHTML = '<div style="padding:16px"><i class="fas fa-spinner fa-spin"></i> Loading questions...</div>';
            const fd = new URLSearchParams();
            fd.append('action','get_questions');
            fd.append('quiz_id', quizId);
            fetch('../trainer/handlers/quiz_handler.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: fd.toString() })
              .then(r=>r.json())
              .then(data=>{
                if (data.success) {
                  const q = data.questions || [];
                  let html = '<div class="questions-list" style="padding:16px">';
                  if (q.length===0) html += '<p>No questions found.</p>';
                  q.forEach((qq,i)=>{ html += `<div class="question-item" style="border:1px solid #eee;border-radius:8px;padding:12px;margin-bottom:10px"><strong>Q${i+1}.</strong> ${qq.question_text}</div>`; });
                  html += '</div>';
                  body.innerHTML = html;
                } else { body.innerHTML = '<div style="padding:16px;color:#b00">'+(data.message||'Failed to load')+'</div>'; }
              })
              .catch(()=>{ body.innerHTML = '<div style="padding:16px;color:#b00">Request failed</div>'; });
        });
    });

    document.querySelectorAll('.view-results-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const quizId = this.getAttribute('data-quiz-id');
            const modal = document.getElementById('viewResultsModal');
            const body = document.getElementById('viewResultsBody');
            if (!modal || !body) return;
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            body.innerHTML = '<div style="padding:16px"><i class="fas fa-spinner fa-spin"></i> Loading results...</div>';
            const fd = new URLSearchParams();
            fd.append('action','get_results');
            fd.append('quiz_id', quizId);
            fetch('../trainer/handlers/quiz_handler.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: fd.toString() })
              .then(r=>r.json())
              .then(data=>{
                if (data.success) {
                  const res = data.results || [];
                  let html = '<div class="results-list" style="padding:16px">';
                  if (res.length===0) html += '<p>No results found.</p>';
                  res.forEach(rw=>{ html += `<div class="result-item" style="border:1px solid #eee;border-radius:8px;padding:12px;margin-bottom:10px"><strong>${rw.last_name}, ${rw.first_name}</strong> — ${rw.score}%</div>`; });
                  html += '</div>';
                  body.innerHTML = html;
                } else { body.innerHTML = '<div style="padding:16px;color:#b00">'+(data.message||'Failed to load')+'</div>'; }
              })
              .catch(()=>{ body.innerHTML = '<div style="padding:16px;color:#b00">Request failed</div>'; });
        });
    });

    ['addQuizModal','viewQuestionsModal','viewResultsModal'].forEach(id=>{
        const modal = document.getElementById(id);
        if (!modal) return;
        const x = modal.querySelector('.close');
        const footerClose = modal.querySelector('.modal-footer .btn.btn-outline-secondary');
        const closeFn = ()=>{ modal.style.display='none'; modal.classList.add('hidden'); };
        if (x) x.addEventListener('click', closeFn);
        if (footerClose) footerClose.addEventListener('click', closeFn);
        modal.addEventListener('click', function(e){ if (e.target === modal) closeFn(); });
    });
});

function duplicateQuiz(quizId) {
    // Show confirmation dialog
    const confirmed = confirm('Are you sure you want to duplicate this quiz? This will create a copy with "(Copy)" appended to the title.');
    if (!confirmed) return;
    
    // Show loading state
    const btn = document.querySelector(`[data-quiz-id="${quizId}"].duplicate-quiz-btn`);
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Copying...';
    btn.disabled = true;
    
    fetch('../trainer/handlers/quiz_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=duplicate_quiz&quiz_id=' + quizId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Quiz duplicated successfully! The copy has been created and is now available for editing.');
            // Reload the page to show the new quiz
            location.reload();
        } else {
            alert('Error duplicating quiz: ' + data.message);
            // Reset button state
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error duplicating quiz. Please try again.');
        // Reset button state
        btn.innerHTML = originalHTML;
        btn.disabled = false;
    });
}

function publishQuiz(quizId, buttonEl) {
    const confirmed = confirm('Publish this quiz? It will become available to trainees.');
    if (!confirmed) return;
    const original = buttonEl.innerHTML;
    buttonEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';
    buttonEl.disabled = true;
    fetch('../trainer/handlers/quiz_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=publish_quiz&quiz_id=' + encodeURIComponent(quizId)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Quiz published successfully.');
            location.reload();
        } else {
            alert('Error publishing quiz: ' + data.message);
            buttonEl.innerHTML = original;
            buttonEl.disabled = false;
        }
    })
    .catch(() => {
        alert('Error publishing quiz. Please try again.');
        buttonEl.innerHTML = original;
        buttonEl.disabled = false;
    });
}

function archiveQuiz(quizId, buttonEl) {
    const confirmed = confirm('Archive this quiz? Trainees will not be able to attempt it.');
    if (!confirmed) return;
    const original = buttonEl.innerHTML;
    buttonEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Archiving...';
    buttonEl.disabled = true;
    fetch('../trainer/handlers/quiz_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=archive_quiz&quiz_id=' + encodeURIComponent(quizId)
    })
    .then(r=>r.json())
    .then(data=>{
        if (data.success) { alert('Quiz archived.'); location.reload(); }
        else { alert('Error archiving quiz: ' + data.message); buttonEl.innerHTML = original; buttonEl.disabled = false; }
    })
    .catch(()=>{ alert('Error archiving quiz.'); buttonEl.innerHTML = original; buttonEl.disabled = false; });
}

function showHelp() {
    alert('Quiz Management Help:\n\n' +
          '• Create Quiz: Build a new assessment for your course\n' +
          '• Edit: Modify quiz settings and configuration\n' +
          '• Questions: Add, edit, or remove quiz questions\n' +
          '• Results: View student performance and analytics\n' +
          '• Duplicate: Create a copy of an existing quiz\n\n' +
          'Tips:\n' +
          '• Use descriptive titles for easy identification\n' +
          '• Set appropriate time limits and passing scores\n' +
          '• Randomize questions to prevent cheating\n' +
          '• Review results regularly to improve your quizzes');
}
</script>

<!-- Add Quiz Modal -->

<div class="modal hidden" id="addQuizModal" style="position:fixed;inset:0;z-index:1000;display:none;align-items:flex-start;justify-content:center;overflow:auto;">
    <div class="modal-content modal-content-large" style="max-height:85vh;overflow:auto;margin:40px auto;width:95%;max-width:900px">
    <div class="modal-header">
            <h2>Create New Activity</h2>
            <span class="close">&times;</span>
        </div>
        <form id="addQuizForm">
            <div class="modal-body">
                <div id="quizFormErrors" class="form-errors hidden"></div>
            <div class="form-section">
                <div class="form-group">
                    <label for="quiz_title">Activity Title <span class="required">*</span></label>
                    <input type="text" id="quiz_title" name="quiz_title" required class="form-control">
                </div>

                    <div class="form-group">
                        <label for="quiz_course">Course <span class="required">*</span></label>
                        <select id="quiz_course" name="quiz_course" required class="form-control">
                            <option value="">Select Course</option>
                            <?php foreach ($trainerCourses as $course): ?>
                                <option value="<?php echo $course['course_code']; ?>">
                                    <?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quiz_competency">Competency <span class="required">*</span></label>
                        <select id="quiz_competency" name="quiz_competency" required class="form-control" disabled>
                            <option value="">Select Competency</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quiz_activity_type">Activity Type <span class="required">*</span></label>
                        <select id="quiz_activity_type" name="activity_type" required class="form-control">
                            <option value="quiz" selected>Quiz</option>
                            <option value="activity">Activity</option>
                            <option value="exam">Exam</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quiz_description">Description</label>
                        <textarea id="quiz_description" name="quiz_description" rows="3" class="form-control" placeholder="Describe the purpose and content of this activity..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="quiz_due_date">Due Date</label>
                        <input type="datetime-local" id="quiz_due_date" name="quiz_due_date" class="form-control">
                    </div>
                </div>

                <div class="form-section">
                    <h4>Activity Settings</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quiz_time_limit">Time Limit (minutes)</label>
                            <input type="number" id="quiz_time_limit" name="quiz_time_limit" min="1" max="180" class="form-control" placeholder="Leave empty for no limit">
                        </div>

                        <div class="form-group">
                            <label for="quiz_max_attempts">Max Attempts</label>
                            <input type="number" id="quiz_max_attempts" name="quiz_max_attempts" min="1" max="10" value="1" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="quiz_passing_score">Passing Score (%)</label>
                            <input type="number" id="quiz_passing_score" name="quiz_passing_score" min="0" max="100" value="70" class="form-control">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="quiz_randomized" name="quiz_randomized" value="1">
                                <span class="checkmark"></span>
                                Randomize question order
                            </label>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="quiz_show_answers" name="quiz_show_answers" value="1" checked>
                                <span class="checkmark"></span>
                                Show correct answers after completion
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary cancel-btn">Cancel</button>
                <button type="submit" class="btn btn-primary submit-btn">Create Activity</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Question Modal -->
<div class="modal hidden" id="addQuestionModal">
    <div class="modal-content modal-content-large">
    <div class="modal-header">
            <h2>Add Activity Question</h2>
            <span class="close">&times;</span>
        </div>
        <form id="addQuestionForm">
            <div class="modal-body">
                <div id="questionFormErrors" class="form-errors hidden"></div>
                <div class="form-section">
                    <div class="form-group">
                        <label for="question_quiz">Select Activity <span class="required">*</span></label>
                        <select id="question_quiz" name="question_quiz" required class="form-control">
                            <option value="">Select Quiz</option>
                            <?php foreach ($quizzes as $quiz): ?>
                                <option value="<?php echo $quiz['id']; ?>">
                                    <?php echo htmlspecialchars($quiz['title'] . ' (' . $quiz['course_name'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="question_text">Question Text <span class="required">*</span></label>
                        <textarea id="question_text" name="question_text" rows="3" required class="form-control" placeholder="Enter your question..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="question_type">Question Type <span class="required">*</span></label>
                            <select id="question_type" name="question_type" required class="form-control">
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="true_false">True/False</option>
                                <option value="short_answer">Short Answer</option>
                                <option value="essay">Essay</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="question_points">Points <span class="required">*</span></label>
                            <input type="number" id="question_points" name="question_points" min="1" max="100" value="1" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="question_difficulty">Difficulty</label>
                            <select id="question_difficulty" name="question_difficulty" class="form-control">
                                <option value="easy">Easy</option>
                                <option value="medium" selected>Medium</option>
                                <option value="hard">Hard</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section" id="question_options_section">
                    <h4>Answer Options</h4>
                    <div id="multiple_choice_options" class="question-options">
                        <div class="option-group">
                            <div class="form-group">
                                <label>Option 1 <span class="required">*</span></label>
                                <input type="text" name="option_1" class="form-control" placeholder="Enter option 1">
                                <label class="radio-label">
                                    <input type="radio" name="correct_answer" value="1" required>
                                    <span class="radio-mark"></span>
                                    Correct Answer
                                </label>
                            </div>
                        </div>
                        <div class="option-group">
                            <div class="form-group">
                                <label>Option 2 <span class="required">*</span></label>
                                <input type="text" name="option_2" class="form-control" placeholder="Enter option 2">
                                <label class="radio-label">
                                    <input type="radio" name="correct_answer" value="2" required>
                                    <span class="radio-mark"></span>
                                    Correct Answer
                                </label>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="add_option_btn" class="btn btn-outline-secondary">
                        <i class="fas fa-plus"></i> Add Option
                    </button>
                </div>

                <div class="form-section" id="true_false_section" style="display:none;">
                    <h4>True/False Answer</h4>
                    <div class="form-group">
                        <label class="radio-label">
                            <input type="radio" name="correct_answer_tf" value="true" required>
                            <span class="radio-mark"></span>
                            True
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="correct_answer_tf" value="false" required>
                            <span class="radio-mark"></span>
                            False
                        </label>
                    </div>
                </div>

                <div class="form-section" id="short_answer_section" style="display:none;">
                    <h4>Correct Answer</h4>
                    <div class="form-group">
                        <label for="correct_answer_sa">Correct Answer <span class="required">*</span></label>
                        <input type="text" id="correct_answer_sa" name="correct_answer_sa" class="form-control" placeholder="Enter the correct answer">
                    </div>
                </div>

                <div class="form-group">
                    <label for="question_explanation">Answer Explanation (Optional)</label>
                    <textarea id="question_explanation" name="question_explanation" rows="3" class="form-control" placeholder="Explain why this is the correct answer..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary cancel-btn">Cancel</button>
                <button type="submit" class="btn btn-primary submit-btn">Add Question</button>
            </div>
        </form>
    </div>
</div>

<!-- View Questions Modal -->
<div class="modal hidden" id="viewQuestionsModal" style="position:fixed;inset:0;z-index:1000;display:none;align-items:flex-start;justify-content:center;overflow:auto;background:rgba(0,0,0,0.3)">
    <div class="modal-content modal-content-extra-large" style="max-height:85vh;overflow:auto;margin:40px auto;width:95%;max-width:1100px">
        <div class="modal-header">
            <h2 id="viewQuestionsTitle">Quiz Questions</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body" id="viewQuestionsBody">
            <!-- Questions will be loaded here -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary">Close</button>
        </div>
    </div>
</div>

<!-- View Results Modal -->
<div class="modal hidden" id="viewResultsModal" style="position:fixed;inset:0;z-index:1000;display:none;align-items:flex-start;justify-content:center;overflow:auto;background:rgba(0,0,0,0.3)">
    <div class="modal-content modal-content-large" style="max-height:85vh;overflow:auto;margin:40px auto;width:95%;max-width:900px">
        <div class="modal-header">
            <h2>Quiz Results</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body" id="viewResultsBody">
            <!-- Results will be loaded here -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary">Close</button>
        </div>
    </div>
</div>
