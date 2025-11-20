<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('admin');

header('Content-Type: application/json');

if (!isset($_GET['trainer_id'])) {
    echo json_encode([]);
    exit;
}

$database = new DatabaseConnection();
$pdo = $database->getConnection();

$trainerId = $_GET['trainer_id'];

// Get courses from course_assignments table
$stmt = $pdo->prepare("SELECT DISTINCT course_code FROM course_assignments WHERE trainer_id = ?");
$stmt->execute([$trainerId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get batches from course_batches table where trainer_id is assigned
$hasCBTrainerCol = false;
try {
    $cbColCheck = $pdo->query("SHOW COLUMNS FROM course_batches LIKE 'trainer_id'");
    if ($cbColCheck && $cbColCheck->rowCount() > 0) { $hasCBTrainerCol = true; }
} catch (Exception $__) {}

if ($hasCBTrainerCol) {
    // Get batches that this trainer is assigned to
    $batchStmt = $pdo->prepare("SELECT course_code, batch_name FROM course_batches WHERE trainer_id = ? AND batch_name IS NOT NULL AND batch_name != ''");
    $batchStmt->execute([$trainerId]);
    $batches = $batchStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine courses and batches
    $result = [];
    foreach ($courses as $course) {
        $courseCode = $course['course_code'];
        $courseBatches = [];
        foreach ($batches as $batch) {
            if ($batch['course_code'] === $courseCode) {
                $courseBatches[] = $batch['batch_name'];
            }
        }
        $result[] = [
            'course_code' => $courseCode,
            'batch_name' => !empty($courseBatches) ? implode(', ', $courseBatches) : ''
        ];
    }
    echo json_encode($result);
} else {
    // Fallback to just courses if course_batches.trainer_id doesn't exist
    echo json_encode($courses);
}
?>