<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';
require_once 'AccessControl.php';

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
    $batchName = trim($_POST['batch_name'] ?? '');
    $score = $_POST['score'];
    $feedback = trim($_POST['feedback'] ?? '');

    // Verify trainer has access to this batch and submission belongs to mapped activity and trainee in batch
    $subCtxStmt = $pdo->prepare("SELECT asub.activity_id, asub.guest_id, ct.course_code FROM activity_submissions asub JOIN topic_activities ta ON asub.activity_id = ta.id JOIN course_topics ct ON ta.topic_id = ct.id WHERE asub.id = ?");
    $subCtxStmt->execute([$submissionId]);
    $subCtx = $subCtxStmt->fetch(PDO::FETCH_ASSOC);
    if (!$subCtx) {
        echo json_encode(['success' => false, 'message' => 'Submission not found.']);
        exit;
    }
    if ($batchName === '') {
        echo json_encode(['success' => false, 'message' => 'Batch name is required']);
        exit;
    }
    AccessControl::requireTrainerBatchAccess($pdo, $trainerId, $subCtx['course_code'], $batchName);
    if (!AccessControl::resourceIsMappedToBatch($pdo, $subCtx['course_code'], $batchName, 'activity', (int)$subCtx['activity_id'])) {
        echo json_encode(['success' => false, 'message' => 'Access denied: activity not part of this batch']);
        exit;
    }
    if (!AccessControl::traineeInBatch($pdo, $subCtx['guest_id'], $subCtx['course_code'], $batchName)) {
        echo json_encode(['success' => false, 'message' => 'Access denied: submission is not from a trainee in this batch']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE activity_submissions SET score = ?, feedback = ?, graded_by = ?, graded_at = NOW() WHERE id = ?");
    $stmt->execute([$score, $feedback, $trainerId, $submissionId]);

    AccessControl::audit($pdo, [
        'course_code' => $subCtx['course_code'],
        'batch_name' => $batchName,
        'action' => 'GRADE_SUBMISSION',
        'resource_type' => 'activity',
        'resource_id' => (int)$subCtx['activity_id']
    ]);

    echo json_encode(['success' => true, 'message' => 'Submission graded successfully.']);

} catch (PDOException $e) {
    error_log("Error in grade_submission.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
}
?>
