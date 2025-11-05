<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('trainer');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$trainerId = $_SESSION['user']['user_id'];

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

    $submissionId = $_POST['submission_id'];
    $score = $_POST['score'];
    $feedback = trim($_POST['feedback'] ?? '');

    // Verify trainer has access to grade this submission
    $accessStmt = $pdo->prepare("
        SELECT asub.id FROM activity_submissions asub
        JOIN topic_activities ta ON asub.activity_id = ta.id
        JOIN course_topics ct ON ta.topic_id = ct.id
        JOIN course_assignments ca ON ct.course_code = ca.course_code
        WHERE asub.id = ? AND ca.trainer_id = ?
    ");
    $accessStmt->execute([$submissionId, $trainerId]);

    if (!$accessStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Access denied to grade this submission.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE activity_submissions SET score = ?, feedback = ?, graded_by = ?, graded_at = NOW() WHERE id = ?");
    $stmt->execute([$score, $feedback, $trainerId, $submissionId]);

    echo json_encode(['success' => true, 'message' => 'Submission graded successfully.']);

} catch (PDOException $e) {
    error_log("Error in grade_submission.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
}
?>