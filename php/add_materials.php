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
    
    $topicId = $_POST['topic_id'];
    $materialTitle = trim($_POST['material_title']);
    $materialDescription = trim($_POST['material_description'] ?? '');
    $materialType = $_POST['material_type'];
    
    // Verify trainer has access to this topic
    $accessStmt = $pdo->prepare("
        SELECT ct.course_code 
        FROM course_topics ct
        JOIN course_assignments ca ON ct.course_code = ca.course_code
        WHERE ct.id = ? AND ca.trainer_id = ?
    ");
    $accessStmt->execute([$topicId, $trainerId]);
    $topic = $accessStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$topic) {
        echo json_encode(['success' => false, 'message' => 'Access denied to this topic']);
        exit;
    }
    
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
        $filePath = filter_var($_POST['material_link'], FILTER_SANITIZE_URL);
    }
    
    // Insert material
    $stmt = $pdo->prepare("
        INSERT INTO topic_materials (topic_id, material_title, material_description, file_path, uploaded_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$topicId, $materialTitle, $materialDescription, $filePath, $trainerId]);
    
    echo json_encode(['success' => true, 'message' => 'Material added successfully']);
    
} catch (Exception $e) {
    error_log("Error in add_material: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>