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
$materialId = $input['material_id'] ?? null;
$batchName = isset($input['batch_name']) ? trim($input['batch_name']) : '';

if (empty($materialId)) {
    echo json_encode(['success' => false, 'message' => 'material_id is required']);
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

    // Verify access via course assignments
    $verifyStmt = $pdo->prepare("SELECT cm.id as material_id, cm.file_path, cm.course_code
        FROM course_materials cm
        LEFT JOIN course_assignments ca ON cm.course_code = ca.course_code AND ca.trainer_id = ?
        LEFT JOIN course_batches cb ON cm.course_code = cb.course_code AND cb.trainer_id = ?
        WHERE cm.id = ? AND (ca.trainer_id IS NOT NULL OR cb.trainer_id IS NOT NULL)");
    $verifyStmt->execute([$trainerId, $trainerId, $materialId]);
    $row = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $courseCode = null; $assignCnt = 0; $batchCnt = 0;
        $ccq = $pdo->prepare("SELECT course_code FROM course_materials WHERE id = ?");
        $ccq->execute([$materialId]);
        $courseCode = $ccq->fetchColumn();
        if ($courseCode) {
            $ac = $pdo->prepare("SELECT COUNT(*) FROM course_assignments WHERE course_code = ? AND trainer_id = ?");
            $ac->execute([$courseCode, $trainerId]);
            $assignCnt = (int)$ac->fetchColumn();
            $bc = $pdo->prepare("SELECT COUNT(*) FROM course_batches WHERE course_code = ? AND trainer_id = ?");
            $bc->execute([$courseCode, $trainerId]);
            $batchCnt = (int)$bc->fetchColumn();
        }
        echo json_encode(['success' => false, 'message' => 'Course material not found or access denied', 'debug' => ['trainer_id' => $trainerId, 'material_id' => $materialId, 'course_code' => $courseCode, 'assignment_match' => $assignCnt, 'batch_match' => $batchCnt]]);
        exit;
    }

    $filePath = $row['file_path'];
    $courseCode = $row['course_code'];
    AccessControl::requireTrainerBatchAccess($pdo, $trainerId, $courseCode, $batchName);
    if (!AccessControl::resourceIsMappedToBatch($pdo, $courseCode, $batchName, 'material', (int)$materialId)) {
        echo json_encode(['success' => false, 'message' => 'Access denied: course material not part of this batch']);
        exit;
    }

    $pdo->beginTransaction();

    // Delete batch mapping only if batch_resources exists
    try {
        $tchk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'batch_resources'");
        $tchk->execute();
        if ((int)$tchk->fetchColumn() > 0) {
            $pdo->prepare("DELETE FROM batch_resources WHERE resource_type = 'material' AND resource_id = ? AND course_code = ? AND batch_name = ?")
                ->execute([$materialId, $courseCode, $batchName]);
        }
    } catch (Exception $__) {}
    $pdo->prepare("DELETE FROM course_materials WHERE id = ?")
        ->execute([$materialId]);

    $pdo->commit();

    if (!empty($filePath) && !filter_var($filePath, FILTER_VALIDATE_URL)) {
        $fullPath = __DIR__ . '/../uploads/courses/' . $filePath;
        if (file_exists($fullPath)) { @unlink($fullPath); }
    }

    AccessControl::audit($pdo, [
        'course_code' => $courseCode,
        'batch_name' => $batchName,
        'action' => 'DELETE_COURSE_MATERIAL',
        'resource_type' => 'material',
        'resource_id' => (int)$materialId
    ]);
    echo json_encode(['success' => true, 'message' => 'Course material deleted successfully']);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('Error in delete_course_material.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting course material']);
}
?>
