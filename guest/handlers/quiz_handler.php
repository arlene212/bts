<?php
require_once __DIR__ . '/../../php/SessionManager.php';
require_once __DIR__ . '/../../php/DatabaseConnection.php';

SessionManager::startSession();

// Check if user is logged in and has guest role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied. Please log in as a guest.']);
    exit;
}

$database = new DatabaseConnection();
$pdo = $database->getConnection();
$user = SessionManager::getCurrentUser();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'start_quiz':
            startQuiz($pdo, $user);
            break;
        case 'submit_quiz':
            submitQuiz($pdo, $user);
            break;
        case 'get_results':
            getQuizResults($pdo, $user);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function startQuiz($pdo, $user) {
    $quiz_id = $_POST['quiz_id'] ?? null;
    $trainee_id = $user['user_id'];
    
    if (!$quiz_id) {
        echo json_encode(['success' => false, 'message' => 'Quiz ID is required']);
        return;
    }
    
    // Verify enrollment and get quiz details
    $stmt = $pdo->prepare("
        SELECT q.*, c.course_name 
        FROM quizzes q 
        JOIN courses c ON q.course_code = c.course_code 
        WHERE q.id = ? AND q.status IN ('active', 'published')
        AND q.course_code IN (
            SELECT course_code FROM enrollments 
            WHERE trainee_id = ? AND status = 'approved'
        )
    ");
    $stmt->execute([$quiz_id, $trainee_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz) {
        echo json_encode(['success' => false, 'message' => 'Quiz not found or you are not enrolled in this course']);
        return;
    }
    $ds = $pdo->prepare("SELECT setting_value FROM quiz_settings WHERE quiz_id = ? AND setting_key = 'due_date' LIMIT 1");
    $ds->execute([$quiz_id]);
    $due = $ds->fetchColumn();
    if (!empty($due)) {
        $now = new DateTime('now');
        $dueDt = new DateTime($due);
        if ($now > $dueDt) {
            echo json_encode(['success' => false, 'message' => 'This quiz is past its due date and can no longer be attempted']);
            return;
        }
    }
    
    // Check attempt limit
    $stmt = $pdo->prepare("SELECT COUNT(*) as attempt_count FROM quiz_attempts WHERE quiz_id = ? AND trainee_id = ?");
    $stmt->execute([$quiz_id, $trainee_id]);
    $attempt_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($quiz['max_attempts'] && $attempt_info['attempt_count'] >= $quiz['max_attempts']) {
        echo json_encode(['success' => false, 'message' => 'You have reached the maximum number of attempts for this quiz']);
        return;
    }
    
    // Get questions
    $stmt = $pdo->prepare("
        SELECT * FROM quiz_questions 
        WHERE quiz_id = ? 
        ORDER BY question_order, id
    ");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'quiz_info' => $quiz,
        'questions' => $questions,
        'current_attempt' => $attempt_info['attempt_count'] + 1
    ]);
}

function submitQuiz($pdo, $user) {
    $quiz_id = $_POST['quiz_id'] ?? null;
    $time_spent = (int)($_POST['time_spent'] ?? 0);
    $trainee_id = $user['user_id'];
    
    if (!$quiz_id) {
        echo json_encode(['success' => false, 'message' => 'Quiz ID is required']);
        return;
    }
    
    // Verify enrollment
    $stmt = $pdo->prepare("
        SELECT q.* 
        FROM quizzes q 
        WHERE q.id = ? AND q.status IN ('active', 'published')
        AND q.course_code IN (
            SELECT course_code FROM enrollments 
            WHERE trainee_id = ? AND status = 'approved'
        )
    ");
    $stmt->execute([$quiz_id, $trainee_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz) {
        echo json_encode(['success' => false, 'message' => 'Quiz not found or you are not enrolled in this course']);
        return;
    }
    
    // Get questions and calculate score from posted form fields (question_<id>)
    $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY question_order, id");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_score = 0;
    $max_score = 0;
    $processed_answers = [];
    
    foreach ($questions as $question) {
        $max_score += $question['points'];
        $question_id = $question['id'];
        $user_answer = $_POST['question_' . $question_id] ?? null;
        
        if ($user_answer !== null) {
            $is_correct = false;
            
            if ($question['question_type'] === 'multiple_choice') {
                $is_correct = ($user_answer === $question['correct_answer']);
            } elseif ($question['question_type'] === 'true_false') {
                $is_correct = ($user_answer === $question['correct_answer']);
            } elseif ($question['question_type'] === 'short_answer') {
                // For short answer, check if the answer contains key terms
                $correct_answer = strtolower($question['correct_answer']);
                $user_answer_lower = strtolower($user_answer);
                $is_correct = (strpos($user_answer_lower, $correct_answer) !== false);
            }
            
            if ($is_correct) {
                $total_score += $question['points'];
            }
            
            $processed_answers[$question_id] = [
                'answer' => $user_answer,
                'correct' => $is_correct,
                'points' => $is_correct ? $question['points'] : 0
            ];
        } else {
            $processed_answers[$question_id] = [
                'answer' => null,
                'correct' => false,
                'points' => 0
            ];
        }
    }
    
    // Get current attempt number
    $stmt = $pdo->prepare("SELECT COUNT(*) as attempt_count FROM quiz_attempts WHERE quiz_id = ? AND trainee_id = ?");
    $stmt->execute([$quiz_id, $trainee_id]);
    $attempt_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $attempt_number = $attempt_info['attempt_count'] + 1;
    
    // Save attempt (store percentage score in `score` for UI display)
    $stmt = $pdo->prepare("
        INSERT INTO quiz_attempts (quiz_id, trainee_id, answers, score, max_score, attempt_number, time_spent) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $percentage_score = ($max_score > 0) ? round(($total_score / $max_score) * 100) : 0;
    $stmt->execute([
        $quiz_id,
        $trainee_id,
        json_encode($processed_answers),
        $percentage_score,
        $max_score,
        $attempt_number,
        $time_spent
    ]);
    
    $passed = ($percentage_score >= $quiz['passing_score']) ? 1 : 0;
    
    echo json_encode([
        'success' => true,
        'score' => $percentage_score,
        'max_score' => $max_score,
        'passed' => $passed,
        'attempt_id' => $pdo->lastInsertId()
    ]);
}

function getQuizResults($pdo, $user) {
    $quiz_id = $_POST['quiz_id'] ?? null;
    $trainee_id = $user['user_id'];
    
    if (!$quiz_id) {
        echo json_encode(['success' => false, 'message' => 'Quiz ID is required']);
        return;
    }
    
    // Get all attempts for this quiz, include passing_score for UI
    $stmt = $pdo->prepare("
        SELECT qa.*, q.passing_score, (qa.score >= q.passing_score) AS passed
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        WHERE qa.quiz_id = ? AND qa.trainee_id = ?
        ORDER BY qa.completed_at DESC
    ");
    $stmt->execute([$quiz_id, $trainee_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
}