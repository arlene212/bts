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
    $verifyStmt = $pdo->prepare("SELECT tm.id as material_id, tm.file_path, ct.course_code
        FROM topic_materials tm
        JOIN course_topics ct ON tm.topic_id = ct.id
        JOIN course_assignments ca ON ct.course_code = ca.course_code
        WHERE tm.id = ? AND ca.trainer_id = ?");
    $verifyStmt->execute([$materialId, $trainerId]);
    $row = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Material not found or access denied']);
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

    echo json_encode(['success' => true, 'message' => 'Material deleted successfully']);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error in delete_material.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting material']);
}
?>