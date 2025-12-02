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
    
    $topicId = $_POST['topic_id'];
    $batchName = trim($_POST['batch_name'] ?? '');
    $materialTitle = trim($_POST['material_title']);
    $materialDescription = trim($_POST['material_description'] ?? '');
    $materialType = $_POST['material_type'];
    
    // Verify trainer has access to this topic and batch
    $accessStmt = $pdo->prepare("\n        SELECT ct.course_code \n        FROM course_topics ct\n        JOIN course_assignments ca ON ct.course_code = ca.course_code\n        WHERE ct.id = ? AND ca.trainer_id = ?\n    ");
    $accessStmt->execute([$topicId, $trainerId]);
    $topic = $accessStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$topic) {
        echo json_encode(['success' => false, 'message' => 'Access denied to this topic']);
        exit;
    }
    if ($batchName === '') {
        echo json_encode(['success' => false, 'message' => 'Batch name is required']);
        exit;
    }
    AccessControl::requireTrainerBatchAccess($pdo, $trainerId, $topic['course_code'], $batchName);
    
    $filePath = null;
    
    if ($materialType === 'file' && isset($_FILES['material_file']) && $_FILES['material_file']['error'] === 0) {
        // Validate file size (200MB max)
        if ($_FILES['material_file']['size'] > 200 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File size must be less than 200MB']);
            exit;
        }
        
        $uploadDir = '../uploads/courses/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['material_file']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['material_file']['tmp_name'], $targetPath)) {
            $filePath = $fileName;
        } else {
            throw new Exception("Failed to upload file");
        }
    } elseif ($materialType === 'link') {
        $link = trim($_POST['material_link'] ?? '');
        if ($link === '') {
            echo json_encode(['success' => false, 'message' => 'Please provide a link URL']);
            exit;
        }
        $filePath = filter_var($link, FILTER_SANITIZE_URL);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid material type']);
        exit;
    }

    if ($materialType === 'file' && (!isset($_FILES['material_file']) || $_FILES['material_file']['error'] !== 0)) {
        echo json_encode(['success' => false, 'message' => 'Please select a file to upload']);
        exit;
    }
    
    // Insert material
    $stmt = $pdo->prepare("\n        INSERT INTO topic_materials (topic_id, material_title, material_description, file_path, uploaded_by)\n        VALUES (?, ?, ?, ?, ?)\n    ");
    $stmt->execute([$topicId, $materialTitle, $materialDescription, $filePath, $trainerId]);
    $materialId = (int)$pdo->lastInsertId();
    AccessControl::mapResourceToBatch($pdo, $topic['course_code'], $batchName, 'material', $materialId, $trainerId);
    AccessControl::audit($pdo, [
        'course_code' => $topic['course_code'],
        'batch_name' => $batchName,
        'action' => 'UPLOAD_MATERIAL',
        'resource_type' => 'material',
        'resource_id' => $materialId
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Material added successfully']);
    
} catch (Exception $e) {
    error_log("Error in add_material: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
