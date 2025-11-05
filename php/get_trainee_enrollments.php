<?php
require_once 'DatabaseConnection.php';
$database = new DatabaseConnection();
$pdo = $database->getConnection();

header('Content-Type: application/json');

if (isset($_GET['trainee_id'])) {
    $traineeId = $_GET['trainee_id'];
    
    try {
        // Get the current enrollment for the trainee
        $stmt = $pdo->prepare("
            SELECT e.course_code, e.batch_name 
            FROM enrollments e 
            WHERE e.trainee_id = ? AND e.status = 'approved'
            LIMIT 1
        ");
        $stmt->execute([$traineeId]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($enrollment) {
            echo json_encode($enrollment);
        } else {
            echo json_encode(['course_code' => '', 'batch_name' => '']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'No trainee ID provided']);
}
?>