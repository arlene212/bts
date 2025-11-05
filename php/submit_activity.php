<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

date_default_timezone_set('UTC'); // Set default timezone to UTC

SessionManager::startSession();
// Allow both trainees and guests to submit activities.
$user = SessionManager::getCurrentUser();
if (!$user || !in_array($user['role'], ['trainee', 'guest'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Unauthorized.']));
}

header('Content-Type: application/json');

$response = ['success' => false, 'error' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['error'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$traineeId = $_SESSION['user']['user_id'];
$activityId = $_POST['activity_id'] ?? null;
$comment = $_POST['comment'] ?? '';

if (empty($activityId)) {
    $response['error'] = 'Activity ID is missing.';
    echo json_encode($response);
    exit;
}

if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
    $response['error'] = 'File upload error. Code: ' . ($_FILES['submission_file']['error'] ?? 'N/A');
    echo json_encode($response);
    exit;
}

$file = $_FILES['submission_file'];
$uploadDir = '../uploads/submissions/';

// Ensure the upload directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate a unique filename to prevent overwrites
$fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
$uniqueFilename = 'sub_' . $activityId . '_' . $traineeId . '_' . time() . '.' . $fileExtension;
$targetPath = $uploadDir . $uniqueFilename;

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

    // === VALIDATION CHECKS ===
    // 1. Check if the activity exists and is valid for submission
    $activityStmt = $pdo->prepare("
        SELECT start_date, due_date FROM topic_activities 
        WHERE id = ?
    ");
    $activityStmt->execute([$activityId]);
    $activity = $activityStmt->fetch();

    if (!$activity) {
        throw new Exception("Activity not found.");
    }

    // 2. Check if the submission is within the allowed time frame
    $now = new DateTime(); // Will be in UTC due to date_default_timezone_set
    $startDate = $activity['start_date'] ? new DateTime($activity['start_date']) : null;
    $dueDate = new DateTime($activity['due_date']);
    if (($startDate && $now < $startDate) || $now > $dueDate) {
        throw new Exception("Submission is not allowed at this time. The activity might not have started or is past the due date.");
    }

    // Check if already submitted
    $checkStmt = $pdo->prepare("SELECT id FROM activity_submissions WHERE activity_id = ? AND trainee_id = ?");
    $checkStmt->execute([$activityId, $traineeId]);
    if ($checkStmt->fetch()) {
        // Correctly throw an exception to be caught and sent as JSON
        throw new Exception('You have already submitted this activity.');
    }

    // Move the uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Insert submission record into the database
        $stmt = $pdo->prepare("
            INSERT INTO activity_submissions (activity_id, trainee_id, submission_file_path, comments, submitted_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$activityId, $traineeId, $uniqueFilename, $comment])) {
            $response['success'] = true;
            unset($response['error']);
        } else {
            $response['error'] = 'Failed to save submission record to the database.';
        }
    } else {
        $response['error'] = 'Failed to move uploaded file.';
    }
} catch (Exception $e) { // Catch both PDOException and general Exception
    error_log("Submission Error: " . $e->getMessage());
    // Use the specific exception message for better feedback
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>