<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('trainer');

header('Content-Type: application/json');

if (!isset($_GET['course_code'])) {
    echo json_encode(['success' => false, 'message' => 'course_code is required']);
    exit;
}

$courseCode = $_GET['course_code'];
$trainerId = $_SESSION['user']['user_id'];

try {
    $db = new DatabaseConnection();
    $pdo = $db->getConnection();

    $stmt = $pdo->prepare("SELECT batch_name, start_date, end_date FROM course_batches WHERE course_code = ? AND trainer_id = ? ORDER BY created_at DESC");
    $stmt->execute([$courseCode, $trainerId]);
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'batches' => $batches]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching trainer batches']);
}
?>
