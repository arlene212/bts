<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

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

if (empty($activityId)) {
    echo json_encode(['success' => false, 'message' => 'activity_id is required']);
    exit;
}

$trainerId = $_SESSION['user']['user_id'];

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

    // Verify trainer owns the course that the activity belongs to and fetch attachment path
    $verifyStmt = $pdo->prepare("SELECT ta.id as activity_id, ta.attachment_path, ta.topic_id
        FROM topic_activities ta
        JOIN course_topics ct ON ta.topic_id = ct.id
        JOIN course_assignments ca ON ct.course_code = ca.course_code
        WHERE ta.id = ? AND ca.trainer_id = ?");
    $verifyStmt->execute([$activityId, $trainerId]);
    $row = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Activity not found or access denied']);
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

    // Delete the activity itself
    $delActStmt = $pdo->prepare("DELETE FROM topic_activities WHERE id = ?");
    $delActStmt->execute([$activityId]);

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