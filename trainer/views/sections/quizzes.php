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
} catch (PDOException $e) {
    error_log("Error loading quiz data: " . $e->getMessage());
    $trainerCourses = [];
    $quizzes = [];
}

$currentTab = $_GET['current_tab'] ?? 'quizzes';
?>

<section class="main-content tab-content <?php echo ($currentTab === 'quizzes' ? 'active' : ''); ?>" id="quizzes">
    <div class="tab-header">
        <div class="header-content">
            <h2><i class="fas fa-question-circle"></i> Quiz Management</h2>
            <p class="header-subtitle">Create and manage interactive quizzes for your courses</p>
        </div>
        <div class="tab-actions">
            <button class="btn btn-primary" id="addQuizBtn">
                <i class="fas fa-plus"></i> Create Quiz
            </button>
            <button class="btn btn-secondary" id="addQuestionBtn">
                <i class="fas fa-question"></i> Add Question
            </button>
        </div>
    </div>

    <div class="quizzes-grid">
        <?php if (empty($quizzes)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h3>No Quizzes Found</h3>
                <p>Create your first quiz to start assessing your students' knowledge and progress.</p>
                <div class="empty-actions">
                    <button class="btn btn-primary" onclick="openModal('addQuizModal')">
                        <i class="fas fa-plus"></i> Create Your First Quiz
                    </button>
                    <button class="btn btn-outline-secondary" onclick="showHelp()">
                        <i class="fas fa-info-circle"></i> Learn More
                    </button>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($quizzes as $index => $quiz): ?>
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
                            title="Manage questions">
                            <i class="fas fa-list"></i> Questions
                        </button>
                        <button class="btn btn-success view-results-btn"
                            data-quiz-id="<?php echo $quiz['id']; ?>"
                            title="View student results">
                            <i class="fas fa-chart-bar"></i> Results
                        </button>
                        <button class="btn btn-info duplicate-quiz-btn"
                            data-quiz-id="<?php echo $quiz['id']; ?>"
                            title="Duplicate this quiz">
                            <i class="fas fa-copy"></i> Duplicate
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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

<div class="modal hidden" id="addQuizModal">
    <div class="modal-content modal-content-large">
        <div class="modal-header">
            <h2>Create New Quiz</h2>
            <span class="close">&times;</span>
        </div>
        <form id="addQuizForm">
            <div class="modal-body">
                <div id="quizFormErrors" class="form-errors hidden"></div>
                <div class="form-section">
                    <div class="form-group">
                        <label for="quiz_title">Quiz Title <span class="required">*</span></label>
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
                        <label for="quiz_description">Description</label>
                        <textarea id="quiz_description" name="quiz_description" rows="3" class="form-control" placeholder="Describe the purpose and content of this quiz..."></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Quiz Settings</h4>
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
                <button type="submit" class="btn btn-primary submit-btn">Create Quiz</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Question Modal -->
<div class="modal hidden" id="addQuestionModal">
    <div class="modal-content modal-content-large">
        <div class="modal-header">
            <h2>Add Quiz Question</h2>
            <span class="close">&times;</span>
        </div>
        <form id="addQuestionForm">
            <div class="modal-body">
                <div id="questionFormErrors" class="form-errors hidden"></div>
                <div class="form-section">
                    <div class="form-group">
                        <label for="question_quiz">Select Quiz <span class="required">*</span></label>
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
<div class="modal hidden" id="viewQuestionsModal">
    <div class="modal-content modal-content-extra-large">
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
<div class="modal hidden" id="viewResultsModal">
    <div class="modal-content modal-content-large">
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