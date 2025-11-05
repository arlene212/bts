<?php
require_once 'SessionManager.php';
require_once 'DatabaseConnection.php';

SessionManager::startSession();

// Ensure the user is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$enrollmentId = $_POST['enrollment_id'] ?? null;
$action = $_POST['action'] ?? null;
$remarks = trim($_POST['remarks'] ?? '');
$adminId = $_SESSION['user']['user_id'];

if (!$enrollmentId || !$action || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit;
}

$status = ($action === 'approve') ? 'approved' : 'rejected';

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare(
        "UPDATE enrollments 
         SET status = ?, remarks = ?, processed_date = NOW(), processed_by = ? 
         WHERE id = ? AND status = 'pending'"
    );
    
    $stmt->execute([$status, $remarks, $adminId, $enrollmentId]);

    echo json_encode(['success' => true, 'message' => "Enrollment request has been successfully " . $status . "."]);

} catch (PDOException $e) {
    error_log("Error processing enrollment: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>