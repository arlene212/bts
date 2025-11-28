<?php
require_once 'DatabaseConnection.php';
$database = new DatabaseConnection();
$pdo = $database->getConnection();

header('Content-Type: application/json');

if (isset($_GET['trainee_id'])) {
    $traineeId = $_GET['trainee_id'];
    
    try {
        // Prefer batch_assignments (latest) since batch is stored there
        $ba = $pdo->prepare("\n            SELECT course_code, batch_name\n            FROM batch_assignments\n            WHERE trainee_id = ?\n            ORDER BY date_assigned DESC\n            LIMIT 1\n        ");
        $ba->execute([$traineeId]);
        $assignment = $ba->fetch(PDO::FETCH_ASSOC);

        if ($assignment && !empty($assignment['course_code'])) {
            echo json_encode(['course_code' => $assignment['course_code'], 'batch_name' => $assignment['batch_name'] ?? '']);
        } else {
            // Fallback to enrollment (may not contain batch_name)
            $en = $pdo->prepare("\n                SELECT course_code\n                FROM enrollments\n                WHERE trainee_id = ? AND status = 'approved'\n                ORDER BY date_requested DESC\n                LIMIT 1\n            ");
            $en->execute([$traineeId]);
            $enrollment = $en->fetch(PDO::FETCH_ASSOC);
            if ($enrollment) {
                echo json_encode(['course_code' => $enrollment['course_code'], 'batch_name' => '']);
            } else {
                echo json_encode(['course_code' => '', 'batch_name' => '']);
            }
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'No trainee ID provided']);
}
?>
