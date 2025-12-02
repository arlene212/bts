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
    
    $activityId = $_POST['activity_id'];
    $batchName = trim($_POST['batch_name'] ?? '');
    $activityTitle = trim($_POST['activity_title']);
    $activityDescription = trim($_POST['activity_description'] ?? '');
    $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $dueDate = $_POST['due_date'];
    $maxAttempts = $_POST['max_attempts'] ?? 1;
    $maxScore = $_POST['max_score'] ?? 100;
    
    // Verify trainer has access to this activity
    $ctx = $pdo->prepare("SELECT ta.created_by, ct.course_code FROM topic_activities ta JOIN course_topics ct ON ta.topic_id = ct.id WHERE ta.id = ?");
    $ctx->execute([$activityId]);
    $ctxRow = $ctx->fetch(PDO::FETCH_ASSOC) ?: [];

    $accessStmt = $pdo->prepare("\n        SELECT ta.id \n        FROM topic_activities ta\n        JOIN course_topics ct ON ta.topic_id = ct.id\n        LEFT JOIN course_assignments ca ON ct.course_code = ca.course_code AND ca.trainer_id = ?\n        LEFT JOIN course_batches cb ON ct.course_code = cb.course_code AND cb.trainer_id = ?\n        WHERE ta.id = ? AND (ta.created_by = ? OR ca.trainer_id IS NOT NULL OR cb.trainer_id IS NOT NULL)\n    ");
    $accessStmt->execute([$trainerId, $trainerId, $activityId, $trainerId]);
    $activity = $accessStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activity) {
        $courseCode = $ctxRow['course_code'] ?? null;
        $createdBy = $ctxRow['created_by'] ?? null;
        $assignCnt = 0; $batchCnt = 0;
        if ($courseCode) {
            $ac = $pdo->prepare("SELECT COUNT(*) FROM course_assignments WHERE course_code = ? AND trainer_id = ?");
            $ac->execute([$courseCode, $trainerId]);
            $assignCnt = (int)$ac->fetchColumn();
            $bc = $pdo->prepare("SELECT COUNT(*) FROM course_batches WHERE course_code = ? AND trainer_id = ?");
            $bc->execute([$courseCode, $trainerId]);
            $batchCnt = (int)$bc->fetchColumn();
        }
        echo json_encode(['success' => false, 'message' => 'Access denied to this activity', 'debug' => ['trainer_id' => $trainerId, 'activity_id' => $activityId, 'course_code' => $courseCode, 'created_by' => $createdBy, 'assignment_match' => $assignCnt, 'batch_match' => $batchCnt]]);
        exit;
    }
    // Batch scoping enforcement
    $courseCode = $ctxRow['course_code'];
    if ($batchName === '') {
        echo json_encode(['success' => false, 'message' => 'batch_name is required']);
        exit;
    }
    AccessControl::requireTrainerBatchAccess($pdo, $trainerId, $courseCode, $batchName);
    if (!AccessControl::resourceIsMappedToBatch($pdo, $courseCode, $batchName, 'activity', (int)$activityId)) {
        echo json_encode(['success' => false, 'message' => 'Access denied: activity not part of this batch']);
        exit;
    }
    
    // Update activity
    $stmt = $pdo->prepare("
        UPDATE topic_activities 
        SET activity_title = ?, activity_description = ?, due_date = ?, max_score = ?, max_attempts = ?, start_date = ?
        WHERE id = ?
    ");
    $stmt->execute([$activityTitle, $activityDescription, $dueDate, $maxScore, $maxAttempts, $startDate, $activityId]);
    
    AccessControl::audit($pdo, [
        'course_code' => $courseCode,
        'batch_name' => $batchName,
        'action' => 'UPDATE_ACTIVITY',
        'resource_type' => 'activity',
        'resource_id' => (int)$activityId
    ]);
    echo json_encode(['success' => true, 'message' => 'Activity updated successfully']);
    
} catch (PDOException $e) {
    error_log("Database error in update_activity: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
