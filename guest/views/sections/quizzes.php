<?php
// Guest quiz view section
$guest_id = $user['user_id'] ?? null;

if (!$guest_id) {
    // If no user ID found, check session directly
    $guest_id = $_SESSION['user']['user_id'] ?? null;
}

if (!$guest_id) {
    echo "<!-- Debug: No guest user ID found -->";
    $enrolledCourses = [];
    $quizzes = [];
} else {
    // Get guest's enrolled courses
    $enrolledStmt = $db->prepare("SELECT c.course_code, c.course_name FROM enrollments e JOIN courses c ON e.course_code = c.course_code WHERE e.trainee_id = ? AND e.status = 'approved'");
    $enrolledStmt->execute([$guest_id]);
    $enrolledCourses = $enrolledStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get quizzes for enrolled courses
    $quizzesStmt = $db->prepare("
        SELECT q.*, c.course_name, c.course_code,
               COUNT(DISTINCT qq.id) as question_count,
               (SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.quiz_id = q.id AND qa.trainee_id = ?) as attempt_count,
               (SELECT MAX(score) FROM quiz_attempts qa WHERE qa.quiz_id = q.id AND qa.trainee_id = ?) as best_score
        FROM quizzes q
        JOIN courses c ON q.course_code = c.course_code
        LEFT JOIN quiz_questions qq ON q.id = qq.quiz_id
        WHERE q.course_code IN (SELECT course_code FROM enrollments WHERE trainee_id = ? AND status = 'approved')
        AND q.status IN ('active', 'published')
        GROUP BY q.id
        ORDER BY q.created_at DESC
    ");
    $quizzesStmt->execute([$guest_id, $guest_id, $guest_id]);
    $quizzes = $quizzesStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<section class="quizzes-section">
    <div class="section-header">
        <h2><i class="fas fa-tasks"></i> Available Activities</h2>
        <p class="section-subtitle">Complete interactive activities for your enrolled courses</p>
    </div>

    <?php if (empty($enrolledCourses)): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <h3>No Enrolled Courses</h3>
            <p>Enroll in courses to unlock access to interactive quizzes and assessments.</p>
            <a href="?current_tab=courses" class="btn btn-primary">
                <i class="fas fa-graduation-cap"></i> Browse Available Courses
            </a>
        </div>
    <?php elseif (empty($quizzes)): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-clipboard-question"></i>
            </div>
            <h3>No Activities Available</h3>
            <p>Your enrolled courses don't have any activities available yet. Check back soon!</p>
            <div class="empty-actions">
                <a href="?current_tab=courses" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Courses
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="quizzes-grid">
            <?php foreach ($quizzes as $index => $quiz): ?>
                <?php 
                $attempts_remaining = $quiz['max_attempts'] - $quiz['attempt_count'];
                $progress_percentage = $quiz['attempt_count'] > 0 ? min(($quiz['best_score'] / 100) * 100, 100) : 0;
                ?>
                <div class="quiz-card card" style="animation-delay: <?php echo ($index * 0.1); ?>s;">
                    <div class="quiz-progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
                    </div>
                    
                    <div class="quiz-header">
                        <div class="quiz-title-wrapper">
                            <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                            <?php if ($quiz['attempt_count'] > 0): ?>
                                <span class="quiz-badge completed">
                                    <i class="fas fa-check-circle"></i> Completed
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="quiz-course">
                            <i class="fas fa-graduation-cap"></i>
                            <span><?php echo htmlspecialchars($quiz['course_name']); ?></span>
                        </div>
                    </div>

                    <div class="quiz-info">
                        <?php if (!empty($quiz['description'])): ?>
                            <p class="quiz-description"><?php echo htmlspecialchars($quiz['description']); ?></p>
                        <?php endif; ?>

                        <div class="quiz-stats">
                            <div class="stat">
                                <i class="fas fa-question-circle"></i>
                                <span><?php echo $quiz['question_count']; ?> Tasks</span>
                            </div>
                            <div class="stat">
                                <i class="fas fa-clock"></i>
                                <span><?php echo $quiz['time_limit'] ? $quiz['time_limit'] . ' min' : 'No limit'; ?></span>
                            </div>
                            <div class="stat">
                                <i class="fas fa-percentage"></i>
                                <span>Pass: <?php echo $quiz['passing_score']; ?>%</span>
                            </div>
                            <?php if ($quiz['attempt_count'] > 0): ?>
                                <div class="stat best-score">
                                    <i class="fas fa-trophy"></i>
                                    <span>Best: <?php echo $quiz['best_score']; ?>%</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="quiz-meta">
                            <div class="meta-item">
                                <i class="fas fa-redo"></i>
                                <span><?php echo $attempts_remaining; ?> attempt<?php echo $attempts_remaining !== 1 ? 's' : ''; ?> remaining</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-random"></i>
                                <span><?php echo $quiz['is_randomized'] ? 'Randomized questions' : 'Fixed order'; ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-eye"></i>
                                <span><?php echo $quiz['show_correct_answers'] ? 'Answers shown' : 'No answers shown'; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="quiz-actions">
                        <?php if ($quiz['attempt_count'] < $quiz['max_attempts']): ?>
                            <button class="btn btn-primary take-quiz-btn" data-quiz-id="<?php echo $quiz['id']; ?>">
                                <i class="fas fa-play"></i> Start Activity
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                <i class="fas fa-check-circle"></i> Max Attempts Reached
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($quiz['attempt_count'] > 0): ?>
                            <button class="btn btn-outline-primary view-results-btn" data-quiz-id="<?php echo $quiz['id']; ?>">
                                <i class="fas fa-chart-line"></i> View Activity Results
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Quiz Attempt Modal -->
<div class="modal hidden" id="quizAttemptModal">
    <div class="modal-content modal-content-large">
        <div class="modal-header">
            <h2>Activity Attempt</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body" id="quizAttemptBody">
            <!-- Quiz questions will be loaded here -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" onclick="closeModal('quizAttemptModal')">Cancel</button>
            <button type="button" class="btn btn-primary" id="submitQuizBtn">Submit Activity</button>
        </div>
    </div>
</div>

<!-- Quiz Results Modal -->
<div class="modal hidden" id="quizResultsModal">
    <div class="modal-content modal-content-large">
        <div class="modal-header">
            <h2>Activity Results</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body" id="quizResultsBody">
            <!-- Results will be loaded here -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="closeModal('quizResultsModal')">Close</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Take quiz button handlers
    document.querySelectorAll('.take-quiz-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const quizId = this.getAttribute('data-quiz-id');
            startQuiz(quizId);
        });
    });

    // View results button handlers
    document.querySelectorAll('.view-results-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const quizId = this.getAttribute('data-quiz-id');
            viewQuizResults(quizId);
        });
    });
});

function startQuiz(quizId) {
    // Show confirmation dialog
    const confirmed = confirm('Are you ready to start this quiz? You will have a limited number of attempts.');
    if (!confirmed) return;
    
    // Show loading state
    const startBtn = document.querySelector(`[data-quiz-id="${quizId}"].take-quiz-btn`);
    const originalText = startBtn.innerHTML;
    startBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting...';
    startBtn.disabled = true;
    
    fetch('../guest/handlers/quiz_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=start_quiz&quiz_id=' + quizId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayQuizQuestions(data.questions, data.quiz_info);
            openModal('quizAttemptModal');
            // Reset button state
            startBtn.innerHTML = originalText;
            startBtn.disabled = false;
        } else {
            alert('Error starting quiz: ' + data.message);
            // Reset button state
            startBtn.innerHTML = originalText;
            startBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error starting quiz. Please try again.');
        // Reset button state
        startBtn.innerHTML = originalText;
        startBtn.disabled = false;
    });
}

function displayQuizQuestions(questions, quizInfo) {
    const container = document.getElementById('quizAttemptBody');
    let html = `
        <div class="quiz-info-header">
            <h3>${quizInfo.title}</h3>
            <div class="quiz-timer">
                <i class="fas fa-clock"></i>
                <span id="quizTimer">${quizInfo.time_limit ? quizInfo.time_limit + ' min' : 'No limit'}</span>
            </div>
        </div>
        <form id="quizAttemptForm">
            <input type="hidden" name="quiz_id" value="${quizInfo.id}">
            <div class="questions-container">
    `;

    questions.forEach((question, index) => {
        html += `
            <div class="question-item" data-question-id="${question.id}">
                <div class="question-header">
                    <span class="question-number">Question ${index + 1}</span>
                    <span class="question-points">${question.points} points</span>
                </div>
                <div class="question-text">${question.question_text}</div>
                <div class="question-options">
        `;

        if (question.question_type === 'multiple_choice') {
            const options = JSON.parse(question.options);
            options.forEach((option, optIndex) => {
                html += `
                    <label class="radio-option">
                        <input type="radio" name="question_${question.id}" value="${option}" required>
                        <span class="option-text">${option}</span>
                    </label>
                `;
            });
        } else if (question.question_type === 'true_false') {
            html += `
                <label class="radio-option">
                    <input type="radio" name="question_${question.id}" value="true" required>
                    <span class="option-text">True</span>
                </label>
                <label class="radio-option">
                    <input type="radio" name="question_${question.id}" value="false" required>
                    <span class="option-text">False</span>
                </label>
            `;
        } else if (question.question_type === 'short_answer') {
            html += `
                <textarea name="question_${question.id}" class="form-control" rows="3" placeholder="Enter your answer..." required></textarea>
            `;
        } else if (question.question_type === 'essay') {
            html += `
                <textarea name="question_${question.id}" class="form-control" rows="5" placeholder="Enter your detailed answer..." required></textarea>
            `;
        }

        html += `
                </div>
            </div>
        `;
    });

    html += `
            </div>
        </form>
    `;

    container.innerHTML = html;

    const submitBtn = document.getElementById('submitQuizBtn');
    if (submitBtn) { submitBtn.onclick = function() { submitQuizAttempt(quizInfo.id); }; }
}

function submitQuizAttempt(quizId) {
    // Validate form
    const form = document.getElementById('quizAttemptForm');
    if (!form.checkValidity()) {
        alert('Please answer all questions before submitting.');
        return;
    }
    
    // Show confirmation dialog
    const confirmed = confirm('Are you sure you want to submit your quiz? This action cannot be undone.');
    if (!confirmed) return;
    
    const formData = new FormData(form);
    formData.append('action', 'submit_quiz');
    const submitBtn = document.getElementById('submitQuizBtn');
    if (submitBtn) { 
        submitBtn.disabled = true; 
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    }

    fetch('../guest/handlers/quiz_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal('quizAttemptModal');
            // Show success message
            alert('Quiz submitted successfully! Your results are being processed.');
            viewQuizResults(quizId);
        } else {
            alert('Error submitting quiz: ' + data.message);
            if (submitBtn) { 
                submitBtn.disabled = false; 
                submitBtn.innerHTML = '<i class="fas fa-check"></i> Submit Quiz';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error submitting quiz. Please try again.');
        if (submitBtn) { 
            submitBtn.disabled = false; 
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Submit Quiz';
        }
    });
}

function viewQuizResults(quizId) {
    fetch('../guest/handlers/quiz_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_results&quiz_id=' + quizId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayQuizResults(data.results);
            openModal('quizResultsModal');
        } else {
            alert('Error loading results: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading results. Please try again.');
    });
}

function displayQuizResults(results) {
    const container = document.getElementById('quizResultsBody');
    
    if (results.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-chart-bar"></i><h3>No Results Found</h3><p>You haven\'t taken this quiz yet.</p></div>';
        return;
    }

    const latestResult = results[0]; // Get the most recent attempt
    const previousResult = results[1]; // Get the previous attempt for comparison
    const hasImprovement = previousResult && latestResult.score > previousResult.score;
    const improvementAmount = hasImprovement ? (latestResult.score - previousResult.score).toFixed(1) : 0;
    
    let html = `
        <div class="quiz-results-summary">
            <div class="result-header">
                <h3>Quiz Results</h3>
                <div class="score-display ${latestResult.passed ? 'passed' : 'failed'}">
                    <span class="score-number">${latestResult.score}%</span>
                    <span class="score-label">${latestResult.passed ? 'Passed' : 'Failed'}</span>
                    ${hasImprovement ? `<span class="improvement-badge"><i class="fas fa-arrow-up"></i> +${improvementAmount}%</span>` : ''}
                </div>
            </div>
            
            <div class="result-details">
                <div class="detail-item">
                    <i class="fas fa-calendar"></i>
                    <span>Attempt Date: ${new Date(latestResult.completed_at).toLocaleDateString()}</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-clock"></i>
                    <span>Time Spent: ${formatTime(latestResult.time_spent)}</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-trophy"></i>
                    <span>Passing Score: ${latestResult.passing_score}%</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Grade: ${getGrade(parseFloat(latestResult.score))}</span>
                </div>
                ${results.length > 1 ? `
                <div class="detail-item improvement-info">
                    <i class="fas fa-chart-line"></i>
                    <span>Total Attempts: ${results.length}</span>
                </div>
                ` : ''}
            </div>
            
            <div class="attempt-history">
                <h4>Attempt History</h4>
                <div class="attempts-list">
    `;

    results.forEach((result, index) => {
        const isLatest = index === 0;
        const showImprovement = index === 0 && hasImprovement;
        
        html += `
            <div class="attempt-item ${isLatest ? 'latest-attempt' : ''}">
                <div class="attempt-info">
                    <span class="attempt-number">Attempt ${results.length - index}</span>
                    <span class="attempt-date">${new Date(result.completed_at).toLocaleDateString()}</span>
                    ${isLatest ? '<span class="latest-badge">Latest</span>' : ''}
                </div>
                <div class="attempt-score ${result.passed ? 'passed' : 'failed'}">
                    <span class="score">${result.score}%</span>
                    <span class="status">${result.passed ? 'Passed' : 'Failed'}</span>
                    <span class="grade">${getGrade(parseFloat(result.score))}</span>
                    ${showImprovement ? `<span class="improvement-indicator"><i class="fas fa-arrow-up"></i></span>` : ''}
                </div>
            </div>
        `;
    });

    html += `
                </div>
            </div>
            
            ${hasImprovement ? `
            <div class="improvement-message">
                <i class="fas fa-chart-line"></i>
                <span>Great job! You've improved by ${improvementAmount}% since your last attempt!</span>
            </div>
            ` : ''}
        </div>
    `;

    container.innerHTML = html;
}

function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}m ${remainingSeconds}s`;
}

function getGrade(score) {
    if (score >= 90) return 'A';
    if (score >= 80) return 'B';
    if (score >= 70) return 'C';
    if (score >= 60) return 'D';
    return 'F';
}
</script>
