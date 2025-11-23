<?php
require_once 'SessionManager.php';
require_once 'DatabaseConnection.php';

SessionManager::startSession();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';
$course_code = $_POST['course_code'] ?? '';
$trainee_id = $_SESSION['user']['user_id'] ?? '';
$student_id = $_POST['student_id'] ?? '';
$email_verification = $_POST['email_verification'] ?? '';

if (!$trainee_id || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'trainee') {
    echo json_encode(['success' => false, 'message' => 'Please login as a trainee to enroll.']);
    exit;
}

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();
    
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
        SELECT c.*, COUNT(e.id) as current_enrollments, c.max_capacity 
        FROM courses c 
        LEFT JOIN enrollments e ON c.course_code = e.course_code AND e.status = 'approved' 
        WHERE c.course_code = ? AND c.status = 'published' 
        GROUP BY c.course_code
    ");
    $stmt->execute([$course_code]);
    $course_details = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course_details) {
        echo json_encode(['success' => false, 'message' => 'Course not found or not available for enrollment.']);
        exit;
    }
    
    if ($course_details['current_enrollments'] >= $course_details['max_capacity']) {
        echo json_encode(['success' => false, 'message' => 'This course is currently full. Please try again later.']);
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
    $stmt = $pdo->prepare("
        INSERT INTO enrollments (trainee_id, course_code, status, date_requested, student_id_verification, email_verification) 
        VALUES (?, ?, 'pending', NOW(), ?, ?)
    ");
    $stmt->execute([$trainee_id, $course_code, $student_id, $email_verification]);
    
    // Get course trainers for notification
    $stmt = $pdo->prepare("SELECT u.email, u.first_name, u.last_name FROM users u JOIN course_assignments ca ON u.user_id = ca.trainer_id WHERE ca.course_code = ?");
    $stmt->execute([$course_code]);
    $trainers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log enrollment activity
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, target_id, details, timestamp) VALUES (?, 'enrollment_request', ?, ?, NOW())");
    $stmt->execute([$trainee_id, $course_code, json_encode(['course_code' => $course_code, 'verification_used' => $verification_type ?? 'none'])]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Your enrollment request has been submitted successfully and is pending trainer approval.',
        'verification_required' => $course['require_verification'] ?? false
    ]);
    
} catch (PDOException $e) {
    error_log("Enhanced enrollment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again later.']);
}
?>