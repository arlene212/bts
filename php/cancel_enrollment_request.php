<?php
require_once 'SessionManager.php';
require_once 'DatabaseConnection.php';

SessionManager::startSession();

// Ensure the user is a trainee
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'trainee') {
    header('Content-Type: application/json');
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['enrollment_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$traineeId = $_SESSION['user']['user_id'];
$enrollmentId = $_POST['enrollment_id'];

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

    // Delete the enrollment request only if it belongs to the current user and is pending
    $stmt = $pdo->prepare("DELETE FROM enrollments WHERE id = ? AND trainee_id = ? AND status = 'pending'");
    $stmt->execute([$enrollmentId, $traineeId]);

    echo json_encode(['success' => true, 'message' => 'Enrollment request has been cancelled.']);
} catch (PDOException $e) {
    error_log("Error cancelling enrollment request: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}