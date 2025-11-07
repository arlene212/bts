<?php
require_once 'SessionManager.php';
require_once 'DatabaseConnection.php';

SessionManager::startSession();

// Only guests can use this endpoint
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guest') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['course_code'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$guestId = $_SESSION['user']['user_id'];
$courseCode = $_POST['course_code'];

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

    // Delete the enrollment record for this guest and course
    $deleteStmt = $pdo->prepare("DELETE FROM enrollments WHERE trainee_id = ? AND course_code = ?");
    $deleteStmt->execute([$guestId, $courseCode]);

    echo json_encode(['success' => true, 'message' => 'You have been successfully unenrolled!']);

} catch (PDOException $e) {
    error_log("Guest unenrollment error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>