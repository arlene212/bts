<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('trainer');

header('Content-Type: application/json');

$database = new DatabaseConnection();
$db = $database->getConnection();
$trainer_id = $_SESSION['user']['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$course_code = $_POST['course_code'] ?? '';
$competency_type = $_POST['competency_type'] ?? '';
$competency_name = $_POST['competency_name'] ?? '';
$competency_description = $_POST['competency_description'] ?? '';

if (empty($course_code) || empty($competency_type) || empty($competency_name)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit;
}

try {
    // Verify the trainer is assigned to this course
    $verify_query = "SELECT ca.* FROM course_assignments ca 
                    WHERE ca.trainer_id = ? AND ca.course_code = ?";
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->execute([$trainer_id, $course_code]);
    $course_assignment = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course_assignment) {
        echo json_encode(['success' => false, 'message' => 'You are not assigned to this course']);
        exit;
    }
    
    // Insert competency
    $insert_query = "INSERT INTO competencies (course_code, competency_type, competency_name, competency_description, created_by) 
                    VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->execute([
        $course_code,
        $competency_type,
        $competency_name,
        $competency_description,
        $trainer_id
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Competency added successfully']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>