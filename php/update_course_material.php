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

    $materialId = $_POST['material_id'] ?? null;
    $title = trim($_POST['material_title'] ?? '');
    $description = trim($_POST['material_description'] ?? '');

    if (empty($materialId) || empty($title)) {
        echo json_encode(['success' => false, 'message' => 'material_id and material_title are required']);
        exit;
    }

    // Verify access via course assignments
    $verify = $pdo->prepare("SELECT cm.id, cm.file_path, cm.course_code
                              FROM course_materials cm
                              LEFT JOIN course_assignments ca ON cm.course_code = ca.course_code AND ca.trainer_id = ?
                              LEFT JOIN course_batches cb ON cm.course_code = cb.course_code AND cb.trainer_id = ?
                              WHERE cm.id = ? AND (ca.trainer_id IS NOT NULL OR cb.trainer_id IS NOT NULL)");
    $verify->execute([$trainerId, $trainerId, $materialId]);
    $row = $verify->fetch(PDO::FETCH_ASSOC);
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

    $currentPath = $row['file_path'];
    $newPath = $currentPath;

    // Optional new file
    if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
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

    // Update course material; use content field for description
    $upd = $pdo->prepare("UPDATE course_materials SET title = ?, content = ?, file_path = ? WHERE id = ?");
    $upd->execute([$title, $description, $newPath, $materialId]);

    echo json_encode(['success' => true, 'message' => 'Course material updated successfully', 'debug' => ['trainer_id' => $trainerId, 'material_id' => $materialId]]);

} catch (Exception $e) {
    error_log('Error in update_course_material.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
