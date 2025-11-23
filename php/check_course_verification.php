<?php
require_once 'SessionManager.php';
require_once 'DatabaseConnection.php';

SessionManager::startSession();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$course_code = $_POST['course_code'] ?? '';
$trainee_id = $_SESSION['user']['user_id'] ?? '';

if (!$course_code || !$trainee_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit;
}

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();
    
    // Check course verification requirements
    $stmt = $pdo->prepare("SELECT require_verification, verification_type FROM courses WHERE course_code = ?");
    $stmt->execute([$course_code]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        echo json_encode(['success' => false, 'message' => 'Course not found.']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'require_verification' => $course['require_verification'] ?? false,
        'verification_type' => $course['verification_type'] ?? 'none'
    ]);
    
} catch (PDOException $e) {
    error_log("Course verification check error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>