<?php
require_once 'DatabaseConnection.php';
$database = new DatabaseConnection();
$pdo = $database->getConnection();

header('Content-Type: application/json');

if (isset($_GET['course_code'])) {
    $courseCode = $_GET['course_code'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT batch_name 
            FROM course_batches 
            WHERE course_code = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$courseCode]);
        $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($batches);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'No course code provided']);
}
?>