<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('trainee');

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

    // Check if already submitted
    $checkStmt = $pdo->prepare("SELECT id FROM activity_submissions WHERE activity_id = ? AND trainee_id = ?");
    $checkStmt->execute([$activityId, $traineeId]);
    if ($checkStmt->fetch()) {
        $response['error'] = 'You have already submitted this activity.';
        echo json_encode($response);
        exit;
    }

    // Move the uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Insert submission record into the database
        $stmt = $pdo->prepare("
            INSERT INTO activity_submissions (activity_id, trainee_id, file_path, comments, submitted_at)
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
} catch (PDOException $e) {
    error_log("Submission Error: " . $e->getMessage());
    $response['error'] = 'Database error during submission.';
}

echo json_encode($response);
?>