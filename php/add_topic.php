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

if (!isset($_POST['course_code']) || !isset($_POST['competency_id']) || !isset($_POST['topic_name'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$courseCode = $_POST['course_code'];
$competencyId = $_POST['competency_id'];
$topicName = trim($_POST['topic_name']);
$topicDescription = trim($_POST['topic_description'] ?? '');
$learningObjectives = trim($_POST['learning_objectives'] ?? '');
$trainerId = $_SESSION['user']['user_id'];

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();
    
    // Verify trainer has access to this course
    $accessStmt = $pdo->prepare("
        SELECT 1 FROM course_assignments
        WHERE course_code = ? AND trainer_id = ?
    ");
    $accessStmt->execute([$courseCode, $trainerId]);
    
    if (!$accessStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Access denied to add a topic for this course.']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO course_topics (course_code, competency_id, topic_name, topic_description, learning_objectives, added_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$courseCode, $competencyId, $topicName, $topicDescription, $learningObjectives, $trainerId]);
    $newTopicId = $pdo->lastInsertId();

    $materialTitle = isset($_POST['material_title']) ? trim($_POST['material_title']) : '';
    $materialDescription = isset($_POST['material_description']) ? trim($_POST['material_description']) : '';
    $materialType = $_POST['material_type'] ?? null;
    if ($materialTitle !== '' && $materialType) {
        $filePath = null;
        if ($materialType === 'file' && isset($_FILES['material_file']) && $_FILES['material_file']['error'] === 0) {
            if ($_FILES['material_file']['size'] > 200 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'File size must be less than 200MB']);
                exit;
            }
            $uploadDir = '../uploads/courses/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
            $fileName = uniqid() . '_' . basename($_FILES['material_file']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['material_file']['tmp_name'], $targetPath)) {
                $filePath = $fileName;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
                exit;
            }
        } elseif ($materialType === 'link') {
            $filePath = filter_var($_POST['material_link'] ?? '', FILTER_SANITIZE_URL);
        }
        $matStmt = $pdo->prepare("
            INSERT INTO topic_materials (topic_id, material_title, material_description, file_path, uploaded_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $matStmt->execute([$newTopicId, $materialTitle, $materialDescription, $filePath, $trainerId]);
    }
    echo json_encode(['success' => true, 'message' => 'Topic added successfully']);
    
} catch (PDOException $e) {
    error_log("Database error in add_topic: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
