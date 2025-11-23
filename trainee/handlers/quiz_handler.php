<?php
require_once __DIR__ . '/../../php/SessionManager.php';
require_once __DIR__ . '/../../php/DatabaseConnection.php';

SessionManager::startSession();
SessionManager::requireRole('trainee');

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
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function startQuiz($pdo, $user) {
    $quiz_id = $_POST['quiz_id'] ?? null;
    
    if (!$quiz_id) {
        throw new Exception('Quiz ID is required');
    }

    // Verify trainee is enrolled in the course
    $stmt = $pdo->prepare("
        SELECT q.*, c.course_name 
        FROM quizzes q
        JOIN courses c ON q.course_code = c.course_code
        WHERE q.id = ? AND q.status IN ('active','published')
        AND q.course_code IN (
            SELECT course_code FROM enrollments 
            WHERE trainee_id = ? AND status = 'approved'
        )
    ");
    $stmt->execute([$quiz_id, $user['user_id']]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        throw new Exception('Quiz not found or you are not enrolled in this course');
    }

    // Check attempt count
    $stmt = $pdo->prepare("SELECT COUNT(*) as attempt_count FROM quiz_attempts WHERE quiz_id = ? AND trainee_id = ?");
    $stmt->execute([$quiz_id, $user['user_id']]);
    $attempt_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($attempt_data['attempt_count'] >= $quiz['max_attempts']) {
        throw new Exception('Maximum attempts reached for this quiz');
    }

    // Get questions for the quiz
    $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY question_order, id");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Shuffle questions if randomized
    if ($quiz['is_randomized']) {
        shuffle($questions);
    }

    echo json_encode(['success' => true, 'questions' => $questions, 'quiz_info' => $quiz]);
}

function submitQuiz($pdo, $user) {
    $quiz_id = $_POST['quiz_id'] ?? null;
    $time_spent = (int)($_POST['time_spent'] ?? 0);
    
    if (!$quiz_id) {
        throw new Exception('Quiz ID is required');
    }
    
    $stmt = $pdo->prepare("SELECT q.* FROM quizzes q WHERE q.id = ? AND q.status IN ('active','published') AND q.course_code IN (SELECT course_code FROM enrollments WHERE trainee_id = ? AND status = 'approved')");
    $stmt->execute([$quiz_id, $user['user_id']]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz) {
        throw new Exception('Quiz not found or you are not enrolled in this course');
    }
    
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
            if ($question['question_type'] === 'multiple_choice' || $question['question_type'] === 'true_false') {
                $is_correct = ($user_answer === $question['correct_answer']);
            } elseif ($question['question_type'] === 'short_answer') {
                $is_correct = (strtolower(trim($user_answer)) === strtolower(trim($question['correct_answer'])));
            }
            if ($is_correct) {
                $total_score += $question['points'];
            }
            $processed_answers[$question_id] = ['answer' => $user_answer, 'correct' => $is_correct, 'points' => $is_correct ? $question['points'] : 0];
        } else {
            $processed_answers[$question_id] = ['answer' => null, 'correct' => false, 'points' => 0];
        }
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as attempt_count FROM quiz_attempts WHERE quiz_id = ? AND trainee_id = ?");
    $stmt->execute([$quiz_id, $user['user_id']]);
    $attempt_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $attempt_number = $attempt_info['attempt_count'] + 1;
    
    $stmt = $pdo->prepare("INSERT INTO quiz_attempts (quiz_id, trainee_id, answers, score, max_score, attempt_number, time_spent) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $percentage_score = ($max_score > 0) ? round(($total_score / $max_score) * 100) : 0;
    $stmt->execute([$quiz_id, $user['user_id'], json_encode($processed_answers), $percentage_score, $max_score, $attempt_number, $time_spent]);
    $passed = ($percentage_score >= $quiz['passing_score']) ? 1 : 0;
    echo json_encode(['success' => true, 'score' => $percentage_score, 'passed' => $passed]);
}

function getQuizResults($pdo, $user) {
    $quiz_id = $_POST['quiz_id'] ?? null;
    
    if (!$quiz_id) {
        throw new Exception('Quiz ID is required');
    }

    // Verify trainee is enrolled in the course
    $stmt = $pdo->prepare("
        SELECT q.*, c.course_code 
        FROM quizzes q
        JOIN courses c ON q.course_code = c.course_code
        WHERE q.id = ?
        AND q.course_code IN (
            SELECT course_code FROM enrollments 
            WHERE trainee_id = ? AND status = 'approved'
        )
    ");
    $stmt->execute([$quiz_id, $user['user_id']]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        throw new Exception('Quiz not found or you are not enrolled in this course');
    }

    // Get all attempts for this quiz
    $stmt = $pdo->prepare("SELECT qa.*, q.passing_score, (qa.score >= q.passing_score) AS passed FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id = q.id WHERE qa.quiz_id = ? AND qa.trainee_id = ? ORDER BY qa.completed_at DESC");
    $stmt->execute([$quiz_id, $user['user_id']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'results' => $results]);
}