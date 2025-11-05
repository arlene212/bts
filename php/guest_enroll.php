<?php
require_once 'SessionManager.php';
require_once 'DatabaseConnection.php';

SessionManager::startSession();

// Only guests can use this endpoint
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['course_code'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$guestId = $_SESSION['user']['user_id'];
$courseCode = $_POST['course_code'];

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

    // Check if an enrollment already exists for this guest and course
    $checkStmt = $pdo->prepare("SELECT id FROM enrollments WHERE trainee_id = ? AND course_code = ?");
    $checkStmt->execute([$guestId, $courseCode]);

    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You are already enrolled or have a pending request for this course.']);
        exit;
    }

    // Get course name
    $courseStmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_code = ?");
    $courseStmt->execute([$courseCode]);
    $course = $courseStmt->fetch();

    // Insert new enrollment with 'approved' status
    $insertStmt = $pdo->prepare("INSERT INTO enrollments (trainee_id, course_code, course_name, status, date_requested, processed_date, processed_by) VALUES (?, ?, ?, 'approved', NOW(), NOW(), ?)");
    $insertStmt->execute([$guestId, $courseCode, $course['course_name'], $guestId]); // Mark as processed by the guest themselves

    echo json_encode(['success' => true, 'message' => 'You have been successfully enrolled!']);

} catch (PDOException $e) {
    error_log("Guest enrollment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>