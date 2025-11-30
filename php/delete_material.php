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
$materialId = $input['material_id'] ?? null;

if (empty($materialId)) {
    echo json_encode(['success' => false, 'message' => 'material_id is required']);
    exit;
}

  $trainerId = $_SESSION['user']['user_id'];

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

    // Verify trainer owns the course that the topic belongs to and the material exists
    $ctx = $pdo->prepare("SELECT tm.uploaded_by, ct.course_code FROM topic_materials tm JOIN course_topics ct ON tm.topic_id = ct.id WHERE tm.id = ?");
    $ctx->execute([$materialId]);
    $ctxRow = $ctx->fetch(PDO::FETCH_ASSOC) ?: [];

    $verifyStmt = $pdo->prepare("SELECT tm.id as material_id, tm.file_path, ct.course_code
        FROM topic_materials tm
        JOIN course_topics ct ON tm.topic_id = ct.id
        LEFT JOIN course_assignments ca ON ct.course_code = ca.course_code AND ca.trainer_id = ?
        LEFT JOIN course_batches cb ON ct.course_code = cb.course_code AND cb.trainer_id = ?
        WHERE tm.id = ? AND (tm.uploaded_by = ? OR ca.trainer_id IS NOT NULL OR cb.trainer_id IS NOT NULL)");
    $verifyStmt->execute([$trainerId, $trainerId, $materialId, $trainerId]);
    $row = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $courseCode = $ctxRow['course_code'] ?? null;
        $uploadedBy = $ctxRow['uploaded_by'] ?? null;
        $assignCnt = 0; $batchCnt = 0;
        if ($courseCode) {
            $ac = $pdo->prepare("SELECT COUNT(*) FROM course_assignments WHERE course_code = ? AND trainer_id = ?");
            $ac->execute([$courseCode, $trainerId]);
            $assignCnt = (int)$ac->fetchColumn();
            $bc = $pdo->prepare("SELECT COUNT(*) FROM course_batches WHERE course_code = ? AND trainer_id = ?");
            $bc->execute([$courseCode, $trainerId]);
            $batchCnt = (int)$bc->fetchColumn();
        }
        echo json_encode(['success' => false, 'message' => 'Material not found or access denied', 'debug' => ['trainer_id' => $trainerId, 'material_id' => $materialId, 'course_code' => $courseCode, 'uploaded_by' => $uploadedBy, 'assignment_match' => $assignCnt, 'batch_match' => $batchCnt]]);
        exit;
    }

    $filePath = $row['file_path'];

    // Begin transaction
    $pdo->beginTransaction();

    // Delete material record
    $delStmt = $pdo->prepare("DELETE FROM topic_materials WHERE id = ?");
    $delStmt->execute([$materialId]);

    $pdo->commit();

    // Remove uploaded file if it exists and is not an external link
    if (!empty($filePath) && !filter_var($filePath, FILTER_VALIDATE_URL)) {
        $fullPath = __DIR__ . '/../uploads/courses/' . $filePath;
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Material deleted successfully', 'debug' => ['trainer_id' => $trainerId, 'material_id' => $materialId]]);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error in delete_material.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting material']);
}
?>
