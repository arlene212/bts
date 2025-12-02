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

    $materialId = $_POST['material_id'] ?? null;
    $batchName = trim($_POST['batch_name'] ?? '');
    $title = trim($_POST['material_title'] ?? '');
    $description = trim($_POST['material_description'] ?? '');

    if (empty($materialId) || empty($title)) {
        echo json_encode(['success' => false, 'message' => 'material_id and material_title are required']);
        exit;
    }
    if ($batchName === '') {
        echo json_encode(['success' => false, 'message' => 'batch_name is required']);
        exit;
    }

    // Verify access and get current file_path
    $ctx = $pdo->prepare("SELECT tm.uploaded_by, ct.course_code FROM topic_materials tm JOIN course_topics ct ON tm.topic_id = ct.id WHERE tm.id = ?");
    $ctx->execute([$materialId]);
    $ctxRow = $ctx->fetch(PDO::FETCH_ASSOC) ?: [];

    $verify = $pdo->prepare("SELECT tm.id, tm.file_path, tm.topic_id, ct.course_code
                              FROM topic_materials tm
                              JOIN course_topics ct ON tm.topic_id = ct.id
                              LEFT JOIN course_assignments ca ON ct.course_code = ca.course_code AND ca.trainer_id = ?
                              LEFT JOIN course_batches cb ON ct.course_code = cb.course_code AND cb.trainer_id = ?
                              WHERE tm.id = ? AND (tm.uploaded_by = ? OR ca.trainer_id IS NOT NULL OR cb.trainer_id IS NOT NULL)");
    $verify->execute([$trainerId, $trainerId, $materialId, $trainerId]);
    $row = $verify->fetch(PDO::FETCH_ASSOC);
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
    // Batch scoping enforcement
    $courseCode = $ctxRow['course_code'];
    AccessControl::requireTrainerBatchAccess($pdo, $trainerId, $courseCode, $batchName);
    if (!AccessControl::resourceIsMappedToBatch($pdo, $courseCode, $batchName, 'material', (int)$materialId)) {
        echo json_encode(['success' => false, 'message' => 'Access denied: material not part of this batch']);
        exit;
    }

    $currentPath = $row['file_path'];
    $newPath = $currentPath;

    $materialType = $_POST['material_type'] ?? '';
    $materialLink = trim($_POST['material_link'] ?? '');

    if ($materialType === 'link' && $materialLink !== '') {
        $newPath = filter_var($materialLink, FILTER_SANITIZE_URL);
        if (!empty($currentPath) && !filter_var($currentPath, FILTER_VALIDATE_URL)) {
            $oldFull = __DIR__ . '/../uploads/courses/' . $currentPath;
            if (file_exists($oldFull)) { @unlink($oldFull); }
        }
    } elseif (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['material_file']['size'] > 200 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File size must be less than 200MB']);
            exit;
        }
        $uploadDir = '../uploads/courses/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
        $fileName = uniqid() . '_' . basename($_FILES['material_file']['name']);
        $target = $uploadDir . $fileName;
        if (!move_uploaded_file($_FILES['material_file']['tmp_name'], $target)) {
            throw new Exception('Failed to upload file');
        }
        $newPath = $fileName;
        if (!empty($currentPath) && !filter_var($currentPath, FILTER_VALIDATE_URL)) {
            $oldFull = __DIR__ . '/../uploads/courses/' . $currentPath;
            if (file_exists($oldFull)) { @unlink($oldFull); }
        }
    }

    // Update record
    $upd = $pdo->prepare("UPDATE topic_materials SET material_title = ?, material_description = ?, file_path = ? WHERE id = ?");
    $upd->execute([$title, $description, $newPath, $materialId]);

    AccessControl::audit($pdo, [
        'course_code' => $courseCode,
        'batch_name' => $batchName,
        'action' => 'UPDATE_MATERIAL',
        'resource_type' => 'material',
        'resource_id' => (int)$materialId
    ]);
    echo json_encode(['success' => true, 'message' => 'Material updated successfully']);

} catch (Exception $e) {
    error_log('Error in update_material.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
