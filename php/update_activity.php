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
    
    $activityId = $_POST['activity_id'];
    $activityTitle = trim($_POST['activity_title']);
    $activityDescription = trim($_POST['activity_description'] ?? '');
    $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $dueDate = $_POST['due_date'];
    $maxAttempts = $_POST['max_attempts'] ?? 1;
    $maxScore = $_POST['max_score'] ?? 100;
    
    // Verify trainer has access to this activity
    $accessStmt = $pdo->prepare("
        SELECT ta.id 
        FROM topic_activities ta
        JOIN course_topics ct ON ta.topic_id = ct.id
        JOIN course_assignments ca ON ct.course_code = ca.course_code
        WHERE ta.id = ? AND ca.trainer_id = ?
    ");
    $accessStmt->execute([$activityId, $trainerId]);
    $activity = $accessStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activity) {
        echo json_encode(['success' => false, 'message' => 'Access denied to this activity']);
        exit;
    }
    
    // Update activity
    $stmt = $pdo->prepare("
        UPDATE topic_activities 
        SET activity_title = ?, activity_description = ?, due_date = ?, max_score = ?, max_attempts = ?, start_date = ?
        WHERE id = ?
    ");
    $stmt->execute([$activityTitle, $activityDescription, $dueDate, $maxScore, $maxAttempts, $startDate, $activityId]);
    
    echo json_encode(['success' => true, 'message' => 'Activity updated successfully']);
    
} catch (PDOException $e) {
    error_log("Database error in update_activity: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>