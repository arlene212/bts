<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';
require_once 'AccessControl.php';

SessionManager::startSession();
SessionManager::requireRole('trainer');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$activityId = $input['activity_id'] ?? null;
$batchName = isset($input['batch_name']) ? trim($input['batch_name']) : '';

if (empty($activityId)) {
    echo json_encode(['success' => false, 'message' => 'activity_id is required']);
    exit;
}
if ($batchName === '') {
    echo json_encode(['success' => false, 'message' => 'batch_name is required']);
    exit;
}

  $trainerId = $_SESSION['user']['user_id'];

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

    // Verify trainer owns the course that the activity belongs to and fetch attachment path
    $ctx = $pdo->prepare("SELECT ta.created_by, ct.course_code FROM topic_activities ta JOIN course_topics ct ON ta.topic_id = ct.id WHERE ta.id = ?");
    $ctx->execute([$activityId]);
    $ctxRow = $ctx->fetch(PDO::FETCH_ASSOC) ?: [];

    $verifyStmt = $pdo->prepare("SELECT ta.id as activity_id, ta.attachment_path, ta.topic_id
        FROM topic_activities ta
        JOIN course_topics ct ON ta.topic_id = ct.id
        LEFT JOIN course_assignments ca ON ct.course_code = ca.course_code AND ca.trainer_id = ?
        LEFT JOIN course_batches cb ON ct.course_code = cb.course_code AND cb.trainer_id = ?
        WHERE ta.id = ? AND (ta.created_by = ? OR ca.trainer_id IS NOT NULL OR cb.trainer_id IS NOT NULL)");
    $verifyStmt->execute([$trainerId, $trainerId, $activityId, $trainerId]);
    $row = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
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
        echo json_encode(['success' => false, 'message' => 'Activity not found or access denied', 'debug' => ['trainer_id' => $trainerId, 'activity_id' => $activityId, 'course_code' => $courseCode, 'created_by' => $createdBy, 'assignment_match' => $assignCnt, 'batch_match' => $batchCnt]]);
        exit;
    }
    // Batch scoping enforcement
    $courseCode = $ctxRow['course_code'];
    AccessControl::requireTrainerBatchAccess($pdo, $trainerId, $courseCode, $batchName);
    if (!AccessControl::resourceIsMappedToBatch($pdo, $courseCode, $batchName, 'activity', (int)$activityId)) {
        echo json_encode(['success' => false, 'message' => 'Access denied: activity not part of this batch']);
        exit;
    }

    $attachmentPath = $row['attachment_path'];

    // Begin transaction: delete submissions, then activity
    $pdo->beginTransaction();

    // Get submissions for this activity to delete files
    $subStmt = $pdo->prepare("SELECT file_path FROM activity_submissions WHERE activity_id = ?");
    $subStmt->execute([$activityId]);
    $subs = $subStmt->fetchAll(PDO::FETCH_ASSOC);

    // Delete submissions records
    $delSubsStmt = $pdo->prepare("DELETE FROM activity_submissions WHERE activity_id = ?");
    $delSubsStmt->execute([$activityId]);

    // Delete batch mapping only if batch_resources exists
    try {
        $tchk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'batch_resources'");
        $tchk->execute();
        if ((int)$tchk->fetchColumn() > 0) {
            $pdo->prepare("DELETE FROM batch_resources WHERE resource_type = 'activity' AND resource_id = ? AND course_code = ? AND batch_name = ?")
                ->execute([$activityId, $courseCode, $batchName]);
        }
    } catch (Exception $__) {}
    $pdo->prepare("DELETE FROM topic_activities WHERE id = ?")
        ->execute([$activityId]);

    $pdo->commit();

    // Remove attachment file if exists and not an external link
    if (!empty($attachmentPath) && !filter_var($attachmentPath, FILTER_VALIDATE_URL)) {
        $fullActPath = __DIR__ . '/../uploads/activities/' . $attachmentPath;
        if (file_exists($fullActPath)) {
            @unlink($fullActPath);
        }
    }

    // Remove submission files
    if (!empty($subs)) {
        foreach ($subs as $s) {
            $sFile = $s['file_path'] ?? '';
            if (!empty($sFile) && !filter_var($sFile, FILTER_VALIDATE_URL)) {
                $fullSubPath = __DIR__ . '/../uploads/submissions/' . $sFile;
                if (file_exists($fullSubPath)) {
                    @unlink($fullSubPath);
                }
            }
        }
    }

    AccessControl::audit($pdo, [
        'course_code' => $courseCode,
        'batch_name' => $batchName,
        'action' => 'DELETE_ACTIVITY',
        'resource_type' => 'activity',
        'resource_id' => (int)$activityId
    ]);
    echo json_encode(['success' => true, 'message' => 'Activity and associated submissions deleted successfully']);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error in delete_activity.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting activity']);
}
?>
