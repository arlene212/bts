<?php
session_start();
require_once 'DatabaseConnection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$userId = $_POST['user_id'] ?? null;
$userRole = $_POST['user_role'] ?? null;

if (!$userId || !$userRole) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID and role are required']);
    exit;
}

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();
    $pdo->beginTransaction();

    // Common user data
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $contactNumber = trim($_POST['contact_number']);
    $email = trim($_POST['email']);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format provided.");
    }

    // Check if the new email is already taken by another user
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        // Email is taken, try to make it unique by adding a number
        $baseEmail = substr($email, 0, strpos($email, '@'));
        $domain = substr($email, strpos($email, '@'));
        $counter = 1;
        $newEmail = $email;
        
        do {
            $newEmail = $baseEmail . $counter . $domain;
            $stmt->execute([$newEmail, $userId]);
            $counter++;
        } while ($stmt->fetch() && $counter < 100);

        $email = $newEmail; // Use the new unique email
    }

    // Update users table, preserving the force_password_change status
    $stmt = $pdo->prepare("
        UPDATE users SET 
            first_name = ?, last_name = ?, email = ?, contact_number = ?
        WHERE user_id = ?
    ");
    $stmt->execute([$firstName, $lastName, $email, $contactNumber, $userId]);

    if ($userRole === 'trainer') {
        // Handle trainer-specific updates
        $courseCodes = $_POST['trainer_courses'] ?? [];

        // 1. Delete existing course assignments for this trainer
        $deleteStmt = $pdo->prepare("DELETE FROM course_assignments WHERE trainer_id = ?");
        $deleteStmt->execute([$userId]);

        // 2. Insert new assignments
        if (!empty($courseCodes) && is_array($courseCodes)) {
            $assignStmt = $pdo->prepare("INSERT INTO course_assignments (trainer_id, course_code, assigned_by, date_assigned) VALUES (?, ?, ?, NOW())");
            foreach ($courseCodes as $courseCode) {
                if (!empty(trim($courseCode))) {
                    $assignStmt->execute([$userId, $courseCode, $_SESSION['user']['user_id']]);
                }
            }
        }
        $message = 'Trainer updated successfully';

    } elseif ($userRole === 'trainee') {
        // Handle trainee-specific updates
        $courseCode = $_POST['course_code'] ?? '';
        $batchName = $_POST['batch_name'] ?? '';

        // 1. Delete existing enrollment and batch assignment
        $deleteEnrollmentStmt = $pdo->prepare("DELETE FROM enrollments WHERE trainee_id = ?");
        $deleteEnrollmentStmt->execute([$userId]);

        $deleteBatchStmt = $pdo->prepare("DELETE FROM batch_assignments WHERE trainee_id = ?");
        $deleteBatchStmt->execute([$userId]);

        // 2. Create new enrollment and batch assignment if a course is selected
        if (!empty($courseCode)) {
            // Get course name
            $courseStmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_code = ?");
            $courseStmt->execute([$courseCode]);
            $course = $courseStmt->fetch();

            if ($course) {
                // Create enrollment
                $enrollStmt = $pdo->prepare("INSERT INTO enrollments (trainee_id, course_code, course_name, status, batch_name, date_requested) VALUES (?, ?, ?, 'approved', ?, NOW())");
                $enrollStmt->execute([$userId, $courseCode, $course['course_name'], $batchName]);

                // Create batch assignment
                if (!empty($batchName)) {
                    $batchStmt = $pdo->prepare("INSERT INTO batch_assignments (trainee_id, course_code, batch_name, assigned_by, date_assigned) VALUES (?, ?, ?, ?, NOW())");
                    $batchStmt->execute([$userId, $courseCode, $batchName, $_SESSION['user']['user_id']]);
                }
            } else {
                throw new Exception("Selected course not found: $courseCode");
            }
        }
        $message = 'Trainee updated successfully';
    } else {
        throw new Exception("Invalid user role for update.");
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Update user error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error updating user: ' . $e->getMessage()]);
}
?>