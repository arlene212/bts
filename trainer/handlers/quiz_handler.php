<?php
require_once __DIR__ . '/../../php/SessionManager.php';
require_once __DIR__ . '/../../php/DatabaseConnection.php';

SessionManager::startSession();
SessionManager::requireRole('trainer');

header('Content-Type: application/json');

$database = new DatabaseConnection();
$pdo = $database->getConnection();
$user = SessionManager::getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_quiz' && !empty($_POST['quiz_id'])) { $action = 'update_quiz'; }
    // Create Quiz
    if ($action === 'create_quiz') {
        $title = $_POST['quiz_title'] ?? '';
        $courseCode = $_POST['quiz_course'] ?? '';
        $description = $_POST['quiz_description'] ?? '';
        $competencyId = isset($_POST['quiz_competency']) ? intval($_POST['quiz_competency']) : 0;
        $timeLimit = $_POST['quiz_time_limit'] ? intval($_POST['quiz_time_limit']) : null;
        $maxAttempts = intval($_POST['quiz_max_attempts'] ?? 1);
        $passingScore = floatval($_POST['quiz_passing_score'] ?? 70);
        $isRandomized = isset($_POST['quiz_randomized']) ? 1 : 0;
        $showCorrectAnswers = isset($_POST['quiz_show_answers']) ? 1 : 0;
        $dueDateRaw = $_POST['quiz_due_date'] ?? '';
        
        if (empty($title) || empty($courseCode)) {
            echo json_encode(['success' => false, 'message' => 'Title and course are required']);
            exit;
        }
        
        // Verify trainer has access to this course
        $checkStmt = $pdo->prepare("SELECT id FROM course_assignments WHERE trainer_id = ? AND course_code = ?");
        $checkStmt->execute([$user['user_id'], $courseCode]);
        if (!$checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You do not have access to this course']);
            exit;
        }

        // Validate competency selection for this course
        if ($competencyId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Competency is required']);
            exit;
        }
        $cmp = $pdo->prepare("SELECT id FROM competencies WHERE id = ? AND course_id = (SELECT id FROM courses WHERE course_code = ?) LIMIT 1");
        $cmp->execute([$competencyId, $courseCode]);
        if (!$cmp->fetchColumn()) {
            echo json_encode(['success' => false, 'message' => 'Invalid competency for selected course']);
            exit;
        }
        
        // Duplicate guard: prevent rapid double-submit creating two rows
        $dup = $pdo->prepare("SELECT id FROM quizzes WHERE course_code = ? AND title = ? AND created_by = ? AND created_at >= (NOW() - INTERVAL 15 SECOND) ORDER BY id DESC LIMIT 1");
        $dup->execute([$courseCode, $title, $user['user_id']]);
        $existingId = $dup->fetchColumn();
        if ($existingId) {
            $quizId = $existingId;
        } else {
            $stmt = $pdo->prepare("INSERT INTO quizzes (course_code, competency_id, title, description, time_limit, max_attempts, passing_score, is_randomized, show_correct_answers, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$courseCode, $competencyId, $title, $description, $timeLimit, $maxAttempts, $passingScore, $isRandomized, $showCorrectAnswers, $user['user_id']]);
            $quizId = $pdo->lastInsertId();
        }
        if (!empty($dueDateRaw)) {
            $dueDate = str_replace('T', ' ', $dueDateRaw);
            if (strlen($dueDate) === 16) { $dueDate .= ':00'; }
            $ins = $pdo->prepare("INSERT INTO quiz_settings (quiz_id, setting_key, setting_value) VALUES (?, 'due_date', ?)");
            $ins->execute([$quizId, $dueDate]);
        }
        echo json_encode(['success' => true, 'message' => 'Quiz created successfully', 'quiz_id' => $quizId]);
        exit;
    }
    
    if ($action === 'update_quiz') {
        $quizId = $_POST['quiz_id'] ?? '';
        $title = $_POST['quiz_title'] ?? '';
        $courseCode = $_POST['quiz_course'] ?? '';
        $description = $_POST['quiz_description'] ?? '';
        $competencyId = isset($_POST['quiz_competency']) ? intval($_POST['quiz_competency']) : 0;
        $timeLimit = $_POST['quiz_time_limit'] ? intval($_POST['quiz_time_limit']) : null;
        $maxAttempts = intval($_POST['quiz_max_attempts'] ?? 1);
        $passingScore = floatval($_POST['quiz_passing_score'] ?? 70);
        $isRandomized = isset($_POST['quiz_randomized']) ? 1 : 0;
        $showCorrectAnswers = isset($_POST['quiz_show_answers']) ? 1 : 0;
        $dueDateRaw = $_POST['quiz_due_date'] ?? '';
        
        if (!$quizId || !$title || !$courseCode) { echo json_encode(['success'=>false,'message'=>'Missing data']); exit; }
        $own = $pdo->prepare("SELECT 1 FROM quizzes q JOIN course_assignments ca ON q.course_code = ca.course_code WHERE q.id = ? AND ca.trainer_id = ? LIMIT 1");
        $own->execute([$quizId, $user['user_id']]);
        if (!$own->fetchColumn()) { echo json_encode(['success'=>false,'message'=>'Access denied']); exit; }
        $courseOk = $pdo->prepare("SELECT 1 FROM course_assignments WHERE trainer_id = ? AND course_code = ? LIMIT 1");
        $courseOk->execute([$user['user_id'], $courseCode]);
        if (!$courseOk->fetchColumn()) { echo json_encode(['success'=>false,'message'=>'Access denied for course']); exit; }
        if ($competencyId > 0) {
            $cmp = $pdo->prepare("SELECT id FROM competencies WHERE id = ? AND course_id = (SELECT id FROM courses WHERE course_code = ?) LIMIT 1");
            $cmp->execute([$competencyId, $courseCode]);
            if (!$cmp->fetchColumn()) { echo json_encode(['success'=>false,'message'=>'Invalid competency for course']); exit; }
        } else { echo json_encode(['success'=>false,'message'=>'Competency is required']); exit; }
        $upd = $pdo->prepare("UPDATE quizzes SET course_code = ?, competency_id = ?, title = ?, description = ?, time_limit = ?, max_attempts = ?, passing_score = ?, is_randomized = ?, show_correct_answers = ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([$courseCode, $competencyId, $title, $description, $timeLimit, $maxAttempts, $passingScore, $isRandomized, $showCorrectAnswers, $quizId]);
        if (!empty($dueDateRaw)) {
            $dueDate = str_replace('T',' ',$dueDateRaw);
            if (strlen($dueDate) === 16) { $dueDate .= ':00'; }
            $exists = $pdo->prepare("SELECT id FROM quiz_settings WHERE quiz_id = ? AND setting_key = 'due_date' LIMIT 1");
            $exists->execute([$quizId]);
            if ($exists->fetchColumn()) {
                $qsUpd = $pdo->prepare("UPDATE quiz_settings SET setting_value = ? WHERE quiz_id = ? AND setting_key = 'due_date'");
                $qsUpd->execute([$dueDate, $quizId]);
            } else {
                $qsIns = $pdo->prepare("INSERT INTO quiz_settings (quiz_id, setting_key, setting_value) VALUES (?, 'due_date', ?)");
                $qsIns->execute([$quizId, $dueDate]);
            }
        } else {
            $del = $pdo->prepare("DELETE FROM quiz_settings WHERE quiz_id = ? AND setting_key = 'due_date'");
            $del->execute([$quizId]);
        }
        echo json_encode(['success'=>true]);
        exit;
    }
    
    // Add Question
    if (isset($_POST['action']) && $_POST['action'] === 'add_question') {
        $quizId = $_POST['question_quiz'] ?? '';
        $questionText = $_POST['question_text'] ?? '';
        $questionType = $_POST['question_type'] ?? 'multiple_choice';
        $points = intval($_POST['question_points'] ?? 1);
        $difficulty = $_POST['question_difficulty'] ?? 'medium';
        $explanation = $_POST['question_explanation'] ?? '';
        
        if (empty($quizId) || empty($questionText)) {
            echo json_encode(['success' => false, 'message' => 'Quiz and question text are required']);
            exit;
        }
        
        // Verify trainer has access to this quiz
        $checkStmt = $pdo->prepare("
            SELECT q.id FROM quizzes q 
            JOIN course_assignments ca ON q.course_code = ca.course_code 
            WHERE q.id = ? AND ca.trainer_id = ?
        ");
        $checkStmt->execute([$quizId, $user['user_id']]);
        if (!$checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You do not have access to this quiz']);
            exit;
        }
        
        // Process correct answer based on question type
        $correctAnswer = '';
        $options = null;
        
        switch ($questionType) {
            case 'multiple_choice':
                $options = [];
                for ($i = 1; isset($_POST["option_$i"]); $i++) {
                    $optionText = trim($_POST["option_$i"]);
                    if (!empty($optionText)) {
                        $options[] = $optionText;
                        if (isset($_POST['correct_answer']) && $_POST['correct_answer'] == $i) {
                            $correctAnswer = $optionText;
                        }
                    }
                }
                if (empty($options) || empty($correctAnswer)) {
                    echo json_encode(['success' => false, 'message' => 'Multiple choice questions require options and a correct answer']);
                    exit;
                }
                $options = json_encode($options);
                break;
                
            case 'true_false':
                $correctAnswer = $_POST['correct_answer_tf'] ?? '';
                if (empty($correctAnswer)) {
                    echo json_encode(['success' => false, 'message' => 'True/False questions require a correct answer']);
                    exit;
                }
                break;
                
            case 'short_answer':
                $correctAnswer = $_POST['correct_answer_sa'] ?? '';
                if (empty($correctAnswer)) {
                    echo json_encode(['success' => false, 'message' => 'Short answer questions require a correct answer']);
                    exit;
                }
                break;
                
            case 'essay':
                // Essay questions don't have a specific correct answer
                $correctAnswer = '';
                break;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO quiz_questions (quiz_id, course_code, question_text, question_type, options, 
                                       correct_answer, points, difficulty, explanation, created_by, created_at) 
            SELECT ?, q.course_code, ?, ?, ?, ?, ?, ?, ?, ?, NOW() 
            FROM quizzes q WHERE q.id = ?
        ");
        $stmt->execute([$quizId, $questionText, $questionType, $options, $correctAnswer, $points, 
                       $difficulty, $explanation, $user['user_id'], $quizId]);
        
        echo json_encode(['success' => true, 'message' => 'Question added successfully']);
        exit;
    }
    
    // Get Quiz Questions
    if (isset($_POST['action']) && $_POST['action'] === 'get_questions') {
        $quizId = $_POST['quiz_id'] ?? '';
        
        if (empty($quizId)) {
            echo json_encode(['success' => false, 'message' => 'Quiz ID is required']);
            exit;
        }
        
        // Verify trainer has access to this quiz
        $checkStmt = $pdo->prepare("
            SELECT q.id FROM quizzes q 
            JOIN course_assignments ca ON q.course_code = ca.course_code 
            WHERE q.id = ? AND ca.trainer_id = ?
        ");
        $checkStmt->execute([$quizId, $user['user_id']]);
        if (!$checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You do not have access to this quiz']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT qq.*, q.title as quiz_title 
            FROM quiz_questions qq
            JOIN quizzes q ON qq.quiz_id = q.id
            WHERE qq.quiz_id = ?
            ORDER BY qq.question_order, qq.id
        ");
        $stmt->execute([$quizId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'questions' => $questions]);
        exit;
    }
    
    // Get Quiz Results
    if (isset($_POST['action']) && $_POST['action'] === 'get_results') {
        $quizId = $_POST['quiz_id'] ?? '';
        
        if (empty($quizId)) {
            echo json_encode(['success' => false, 'message' => 'Quiz ID is required']);
            exit;
        }
        
        // Verify trainer has access to this quiz
        $checkStmt = $pdo->prepare("
            SELECT q.id FROM quizzes q 
            JOIN course_assignments ca ON q.course_code = ca.course_code 
            WHERE q.id = ? AND ca.trainer_id = ?
        ");
        $checkStmt->execute([$quizId, $user['user_id']]);
        if (!$checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You do not have access to this quiz']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT qa.*, u.first_name, u.last_name, u.user_id,
                   q.title as quiz_title, q.passing_score
            FROM quiz_attempts qa
            JOIN users u ON qa.trainee_id = u.user_id
            JOIN quizzes q ON qa.quiz_id = q.id
            WHERE qa.quiz_id = ?
            ORDER BY qa.completed_at DESC
        ");
        $stmt->execute([$quizId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'results' => $results]);
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'publish_quiz') {
        $quizId = $_POST['quiz_id'] ?? '';
        if (empty($quizId)) {
            echo json_encode(['success' => false, 'message' => 'Quiz ID is required']);
            exit;
        }
        $checkStmt = $pdo->prepare(
            "SELECT q.id, q.status FROM quizzes q 
             JOIN course_assignments ca ON q.course_code = ca.course_code 
             WHERE q.id = ? AND ca.trainer_id = ?"
        );
        $checkStmt->execute([$quizId, $user['user_id']]);
        $quizRow = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if (!$quizRow) {
            echo json_encode(['success' => false, 'message' => 'You do not have access to this quiz']);
            exit;
        }
        if ($quizRow['status'] !== 'draft') {
            echo json_encode(['success' => false, 'message' => 'Only draft quizzes can be published']);
            exit;
        }
        $qs = $pdo->prepare("SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = ?");
        $qs->execute([$quizId]);
        $count = (int)$qs->fetchColumn();
        if ($count === 0) {
            echo json_encode(['success' => false, 'message' => 'Add at least one question before publishing']);
            exit;
        }
        $upd = $pdo->prepare("UPDATE quizzes SET status = 'published', updated_at = NOW() WHERE id = ?");
        $upd->execute([$quizId]);
        echo json_encode(['success' => true]);
        exit;
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'archive_quiz') {
        $quizId = $_POST['quiz_id'] ?? '';
        if (empty($quizId)) { echo json_encode(['success'=>false,'message'=>'Quiz ID is required']); exit; }
        $checkStmt = $pdo->prepare("SELECT q.id FROM quizzes q JOIN course_assignments ca ON q.course_code = ca.course_code WHERE q.id = ? AND ca.trainer_id = ?");
        $checkStmt->execute([$quizId, $user['user_id']]);
        if (!$checkStmt->fetch()) { echo json_encode(['success'=>false,'message'=>'You do not have access to this quiz']); exit; }
        $upd = $pdo->prepare("UPDATE quizzes SET status = 'archived', updated_at = NOW() WHERE id = ?");
        $upd->execute([$quizId]);
        echo json_encode(['success'=>true]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    
} catch (PDOException $e) {
    error_log("Database error in quiz_handler.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error in quiz_handler.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
        if ($competencyId > 0) {
            $cmp = $pdo->prepare("SELECT id FROM competencies WHERE id = ? AND course_id = (SELECT id FROM courses WHERE course_code = ?) AND status = 'active' LIMIT 1");
            $cmp->execute([$competencyId, $courseCode]);
            if (!$cmp->fetchColumn()) {
                echo json_encode(['success' => false, 'message' => 'Invalid competency for selected course']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Competency is required']);
            exit;
        }
