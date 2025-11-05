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
    
    // Insert the new topic
    $stmt = $pdo->prepare("
        INSERT INTO course_topics (course_code, competency_id, topic_name, topic_description, learning_objectives, added_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$courseCode, $competencyId, $topicName, $topicDescription, $learningObjectives, $trainerId]);
    
    echo json_encode(['success' => true, 'message' => 'Topic added successfully']);
    
} catch (PDOException $e) {
    error_log("Database error in add_topic: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>