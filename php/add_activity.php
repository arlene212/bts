<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('trainer');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$trainerId = $_SESSION['user']['user_id'];

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

    // Retrieve and sanitize form data
    $topicId = $_POST['topic_id'];
    $activityTitle = trim($_POST['activity_title']);
    $activityDescription = trim($_POST['activity_description'] ?? '');
    $activityType = $_POST['activity_type'];
    $dueDate = $_POST['due_date'];
    $maxScore = $_POST['max_score'];
    $attachmentType = $_POST['activity_attachment_type'] ?? null;

    // Verify trainer has access to this topic
    $accessStmt = $pdo->prepare("
        SELECT ct.course_code 
        FROM course_topics ct
        JOIN course_assignments ca ON ct.course_code = ca.course_code
        WHERE ct.id = ? AND ca.trainer_id = ?
    ");
    $accessStmt->execute([$topicId, $trainerId]);
    if (!$accessStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Access denied to this topic']);
        exit;
    }

    $filePath = null;

    if ($attachmentType === 'file' && isset($_FILES['activity_file']) && $_FILES['activity_file']['error'] === UPLOAD_ERR_OK && !empty($_FILES['activity_file']['name'])) {
        // Validate file size (200MB max)
        if ($_FILES['activity_file']['size'] > 200 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File size must be less than 200MB.']);
            exit;
        }

        $uploadDir = '../uploads/activities/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '_' . basename($_FILES['activity_file']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['activity_file']['tmp_name'], $targetPath)) {
            $filePath = $fileName;
        } else {
            throw new Exception("Failed to upload file.");
        }
    } elseif ($attachmentType === 'link' && !empty($_POST['activity_link'])) {
        $filePath = filter_var($_POST['activity_link'], FILTER_SANITIZE_URL);
    }

    // Insert activity into the database
    $stmt = $pdo->prepare("
        INSERT INTO topic_activities (topic_id, activity_title, activity_description, activity_type, due_date, max_score, attachment_path, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$topicId, $activityTitle, $activityDescription, $activityType, $dueDate, $maxScore, $filePath, $trainerId]);

    echo json_encode(['success' => true, 'message' => 'Activity added successfully.']);

} catch (Exception $e) {
    error_log("Error in add_activity.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>