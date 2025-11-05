<?php
require_once 'SessionManager.php';
require_once 'DatabaseConnection.php';

SessionManager::startSession();

// Only trainees and guests can request enrollment
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['trainee', 'guest'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['course_code'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$traineeId = $_SESSION['user']['user_id'];
$courseCode = $_POST['course_code'];

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

    // Check if an enrollment request already exists for this user and course
    $checkStmt = $pdo->prepare("SELECT id FROM enrollments WHERE trainee_id = ? AND course_code = ?");
    $checkStmt->execute([$traineeId, $courseCode]);

    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You have already sent a request for this course.']);
        exit;
    }

    // Get course name
    $courseStmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_code = ?");
    $courseStmt->execute([$courseCode]);
    $course = $courseStmt->fetch();

    if (!$course) {
        echo json_encode(['success' => false, 'message' => 'Course not found.']);
        exit;
    }

    // Insert new enrollment request with 'pending' status
    $insertStmt = $pdo->prepare("INSERT INTO enrollments (trainee_id, course_code, course_name, status, date_requested) VALUES (?, ?, ?, 'pending', NOW())");
    $insertStmt->execute([$traineeId, $courseCode, $course['course_name']]);

    echo json_encode(['success' => true, 'message' => 'Enrollment request sent successfully.']);

} catch (PDOException $e) {
    error_log("Enrollment request error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>