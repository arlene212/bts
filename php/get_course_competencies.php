<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('trainer');

header('Content-Type: application/json');

if (!isset($_GET['course_code'])) {
    echo json_encode(['error' => 'Course code is required']);
    exit;
}

$courseCode = $_GET['course_code'];
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
        echo json_encode(['error' => 'Access denied to this course']);
        exit;
    }
    
    // Get course competencies
    $competenciesStmt = $pdo->prepare("
        SELECT cc.*, u.first_name, u.last_name 
        FROM course_competencies cc
        LEFT JOIN users u ON cc.added_by = u.user_id
        WHERE cc.course_code = ?
        ORDER BY cc.competency_type, cc.competency_name
    ");
    $competenciesStmt->execute([$courseCode]);
    $competencies = $competenciesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get topics for each competency
    foreach ($competencies as &$competency) {
        $topicsStmt = $pdo->prepare("
            SELECT * FROM course_topics 
            WHERE competency_id = ? 
            ORDER BY topic_name
        ");
        $topicsStmt->execute([$competency['id']]);
        $competency['topics'] = $topicsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add full name for added_by
        $competency['added_by_name'] = $competency['first_name'] . ' ' . $competency['last_name'];
    }
    
    echo json_encode(['competencies' => $competencies]);
    
} catch (PDOException $e) {
    error_log("Database error in get_course_competencies: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>