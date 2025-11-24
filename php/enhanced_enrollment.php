<?php
require_once 'SessionManager.php';
require_once 'DatabaseConnection.php';

SessionManager::startSession();

header('Content-Type: application/json');

// File-based debug logging
function logEnrollmentDebug($message) {
    $logFile = __DIR__ . '/../logs/enrollment_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Create logs directory if it doesn't exist
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0777, true);
}

logEnrollmentDebug("Enhanced enrollment request received");
logEnrollmentDebug("POST data: " . print_r($_POST, true));
logEnrollmentDebug("Session data: " . print_r($_SESSION, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';
$course_code = $_POST['course_code'] ?? '';
$trainee_id = $_SESSION['user']['user_id'] ?? '';
$student_id = $_POST['student_id'] ?? '';
$email_verification = $_POST['email_verification'] ?? '';

error_log("Parsed data - course_code: $course_code, trainee_id: $trainee_id, action: $action");

if (!$trainee_id || !isset($_SESSION['user']['role']) || !in_array($_SESSION['user']['role'], ['trainee','guest'])) {
    logEnrollmentDebug("Login check failed - trainee_id: $trainee_id, role: " . ($_SESSION['user']['role'] ?? 'not set'));
    echo json_encode(['success' => false, 'message' => 'Please login to enroll.']);
    exit;
}

logEnrollmentDebug("Login check passed - proceeding with enrollment");

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();
    
    // Test database connection
    $test_stmt = $pdo->query("SELECT 1");
    logEnrollmentDebug("Database connection test: " . ($test_stmt ? 'successful' : 'failed'));
    
    // Check if user verification is required
    $stmt = $pdo->prepare("SELECT require_verification, verification_type FROM courses WHERE course_code = ?");
    $stmt->execute([$course_code]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($course && $course['require_verification']) {
        $verification_type = $course['verification_type'];
        
        if ($verification_type === 'student_id' && empty($student_id)) {
            echo json_encode(['success' => false, 'message' => 'Student ID verification is required for this course.']);
            exit;
        }
        
        if ($verification_type === 'email' && empty($email_verification)) {
            echo json_encode(['success' => false, 'message' => 'Email verification is required for this course.']);
            exit;
        }
        
        // Verify student ID if required
        if ($verification_type === 'student_id' && !empty($student_id)) {
            $stmt = $pdo->prepare("SELECT student_id FROM users WHERE user_id = ? AND student_id = ?");
            $stmt->execute([$trainee_id, $student_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Invalid Student ID. Please check your Student ID and try again.']);
                exit;
            }
        }
        
        // Verify email if required
        if ($verification_type === 'email' && !empty($email_verification)) {
            $stmt = $pdo->prepare("SELECT email FROM users WHERE user_id = ? AND email = ? AND email_verified = 1");
            $stmt->execute([$trainee_id, $email_verification]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email verification is required. Please verify your email address first.']);
                exit;
            }
        }
    }
    
    // Check course availability and prerequisites
    $stmt = $pdo->prepare("
        SELECT c.*, COUNT(e.id) as current_enrollments
        FROM courses c 
        LEFT JOIN enrollments e ON c.course_code = e.course_code AND e.status = 'approved' 
        WHERE c.course_code = ? AND c.course_status = 'published' 
        GROUP BY c.course_code
    ");
    $stmt->execute([$course_code]);
    $course_details = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course_details) {
        echo json_encode(['success' => false, 'message' => 'Course not found or not available for enrollment.']);
        exit;
    }
    
    
    
    // Check if already enrolled or has pending request
    $stmt = $pdo->prepare("SELECT status FROM enrollments WHERE trainee_id = ? AND course_code = ?");
    $stmt->execute([$trainee_id, $course_code]);
    $existing_enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_enrollment) {
        if ($existing_enrollment['status'] === 'approved') {
            echo json_encode(['success' => false, 'message' => 'You are already enrolled in this course.']);
            exit;
        } elseif ($existing_enrollment['status'] === 'pending') {
            echo json_encode(['success' => false, 'message' => 'You already have a pending enrollment request for this course.']);
            exit;
        } elseif ($existing_enrollment['status'] === 'rejected') {
            // Allow re-enrollment after rejection
            $stmt = $pdo->prepare("UPDATE enrollments SET status = 'pending', date_requested = NOW(), remarks = '' WHERE trainee_id = ? AND course_code = ?");
            $stmt->execute([$trainee_id, $course_code]);
            
            echo json_encode(['success' => true, 'message' => 'Your enrollment request has been resubmitted for review.']);
            exit;
        }
    }
    
    // Create new enrollment request
    logEnrollmentDebug("Creating new enrollment request for trainee_id: $trainee_id, course_code: $course_code");
    try {
        $stmt = $pdo->prepare("
            INSERT INTO enrollments (trainee_id, course_code, status, date_requested, student_id_verification, email_verification) 
            VALUES (?, ?, 'pending', NOW(), ?, ?)
        ");
        $result = $stmt->execute([$trainee_id, $course_code, $student_id, $email_verification]);
        logEnrollmentDebug("Enrollment insert result: " . ($result ? 'success' : 'failed'));
        
        if ($result) {
            // Get the last inserted ID for verification
            $enrollment_id = $pdo->lastInsertId();
            logEnrollmentDebug("New enrollment ID: $enrollment_id");
            
            // Verify the enrollment was created
            $verify_stmt = $pdo->prepare("SELECT id, status FROM enrollments WHERE id = ? AND trainee_id = ? AND course_code = ?");
            $verify_stmt->execute([$enrollment_id, $trainee_id, $course_code]);
            $verification = $verify_stmt->fetch(PDO::FETCH_ASSOC);
            logEnrollmentDebug("Enrollment verification: " . print_r($verification, true));
            
            $up = $pdo->prepare("UPDATE enrollments SET course_name = ? WHERE trainee_id = ? AND course_code = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
            $up_result = $up->execute([$course_details['course_name'], $trainee_id, $course_code]);
            logEnrollmentDebug("Course name update result: " . ($up_result ? 'success' : 'failed'));
        }
    } catch (PDOException $insert_error) {
        logEnrollmentDebug("Database insert error: " . $insert_error->getMessage());
        logEnrollmentDebug("Error code: " . $insert_error->getCode());
        throw $insert_error;
    }
    
    // Get course trainers for notification
    $stmt = $pdo->prepare("SELECT u.email, u.first_name, u.last_name FROM users u JOIN course_assignments ca ON u.user_id = ca.trainer_id WHERE ca.course_code = ?");
    $stmt->execute([$course_code]);
    $trainers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    
    // Final verification - check if enrollment exists
    $final_check_stmt = $pdo->prepare("SELECT COUNT(*) as enrollment_count FROM enrollments WHERE trainee_id = ? AND course_code = ? AND status = 'pending'");
    $final_check_stmt->execute([$trainee_id, $course_code]);
    $final_check = $final_check_stmt->fetch(PDO::FETCH_ASSOC);
    logEnrollmentDebug("Final enrollment check - count: " . $final_check['enrollment_count']);
    
    if ($final_check['enrollment_count'] == 0) {
        logEnrollmentDebug("ERROR: Enrollment was not created in database!");
        echo json_encode(['success' => false, 'message' => 'Enrollment failed - could not create record in database.']);
        exit;
    }
    
    $response = [
        'success' => true, 
        'message' => 'Your enrollment request has been submitted successfully and is pending trainer approval.',
        'verification_required' => $course['require_verification'] ?? false,
        'enrollment_count' => $final_check['enrollment_count']
    ];
    
    logEnrollmentDebug("Enrollment successful, sending response: " . json_encode($response));
    echo json_encode($response);
    
} catch (PDOException $e) {
    logEnrollmentDebug("Enhanced enrollment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again later.']);
}
?>