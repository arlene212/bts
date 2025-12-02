<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';
require_once 'AccessControl.php';

SessionManager::startSession();
SessionManager::requireRole('trainer');

header('Content-Type: application/json');

if (!isset($_GET['course_code']) || !isset($_GET['batch_name'])) {
    echo json_encode(['success' => false, 'message' => 'course_code and batch_name are required']);
    exit;
}

$courseCode = $_GET['course_code'];
$batchName = $_GET['batch_name'];
$trainerId = $_SESSION['user']['user_id'];

try {
    $db = new DatabaseConnection();
    $pdo = $db->getConnection();

    AccessControl::requireTrainerBatchAccess($pdo, $trainerId, $courseCode, $batchName);

    $stmt = $pdo->prepare("SELECT ba.trainee_id, u.first_name, u.last_name, u.email FROM batch_assignments ba JOIN users u ON ba.trainee_id = u.user_id WHERE ba.course_code = ? AND ba.batch_name = ? ORDER BY u.last_name, u.first_name");
    $stmt->execute([$courseCode, $batchName]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    AccessControl::audit($pdo, [
        'course_code' => $courseCode,
        'batch_name' => $batchName,
        'action' => 'LIST_BATCH_STUDENTS'
    ]);

    echo json_encode(['success' => true, 'students' => $students]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching batch students']);
}
?>
