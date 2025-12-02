<?php
require_once 'SessionManager.php';
require_once 'DatabaseConnection.php';

SessionManager::startSession();

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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

logEnrollmentDebug("========================================");
logEnrollmentDebug("=== NEW ENROLLMENT REQUEST ===");
logEnrollmentDebug("POST data: " . print_r($_POST, true));
logEnrollmentDebug("Session user: " . print_r($_SESSION['user'] ?? 'not set', true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logEnrollmentDebug("ERROR: Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$course_code = $_POST['course_code'] ?? '';
$trainee_id = $_SESSION['user']['user_id'] ?? '';
$user_role = $_SESSION['user']['role'] ?? '';

logEnrollmentDebug("Parsed data:");
logEnrollmentDebug("  - course_code: $course_code");
logEnrollmentDebug("  - trainee_id: $trainee_id");
logEnrollmentDebug("  - user_role: $user_role");

// Validate course code
if (empty($course_code)) {
    logEnrollmentDebug("ERROR: No course code provided");
    echo json_encode(['success' => false, 'message' => 'Course code is required.']);
    exit;
}

// Validate user session
if (!$trainee_id || !in_array($user_role, ['trainee', 'guest'])) {
    logEnrollmentDebug("ERROR: Unauthorized - trainee_id: $trainee_id, role: $user_role");
    echo json_encode(['success' => false, 'message' => 'Please login to enroll.']);
    exit;
}

logEnrollmentDebug("✅ Validation passed");

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logEnrollmentDebug("✅ Database connected");
    
    // Get course details - THIS IS CRITICAL FOR course_name
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_code = ? AND course_status = 'published'");
    $stmt->execute([$course_code]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        logEnrollmentDebug("ERROR: Course not found: $course_code");
        echo json_encode(['success' => false, 'message' => 'Course not found or not available.']);
        exit;
    }
    
    logEnrollmentDebug("✅ Course found: " . $course['course_name']);
    logEnrollmentDebug("  - course_id: " . $course['id']);
    logEnrollmentDebug("  - course_status: " . $course['course_status']);
    
    // Check existing enrollment
    $stmt = $pdo->prepare("SELECT id, status FROM enrollments WHERE trainee_id = ? AND course_code = ?");
    $stmt->execute([$trainee_id, $course_code]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        logEnrollmentDebug("Existing enrollment found - ID: " . $existing['id'] . ", Status: " . $existing['status']);

        if ($existing['status'] === 'approved') {
            echo json_encode(['success' => false, 'message' => 'You are already enrolled in this course.']);
            exit;
        } elseif ($existing['status'] === 'pending') {
            echo json_encode(['success' => false, 'message' => 'You already have a pending request for this course.']);
            exit;
        } elseif ($existing['status'] === 'rejected') {
            // Resubmit rejected request for both trainees and guests
            $stmt = $pdo->prepare("UPDATE enrollments SET status = 'pending', date_requested = NOW() WHERE id = ?");
            $stmt->execute([$existing['id']]);
            logEnrollmentDebug("✅ Resubmitted rejected request");
            echo json_encode(['success' => true, 'message' => 'Request resubmitted successfully and is pending approval.']);
            exit;
        }
    }
    
    logEnrollmentDebug("Creating new enrollment...");
    
    // Build the INSERT with ALL required fields
    $sql = "INSERT INTO enrollments (
        trainee_id, 
        course_code, 
        course_name, 
        batch_name,
        status, 
        date_requested,
        student_id_verification,
        email_verification
    ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)";
    
    $values = [
        $trainee_id,
        $course_code,
        $course['course_name'],
        isset($_POST['batch_name']) ? trim((string)$_POST['batch_name']) : null,
        'pending',
        $_POST['student_id'] ?? null,
        $_POST['email_verification'] ?? null
    ];
    
    logEnrollmentDebug("SQL: $sql");
    logEnrollmentDebug("Values: " . print_r($values, true));
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($values);
    
    if ($result && $stmt->rowCount() > 0) {
        $enrollment_id = $pdo->lastInsertId();
        logEnrollmentDebug("✅✅✅ SUCCESS! Enrollment ID: $enrollment_id");
        
        // Verify it was created
        $verify = $pdo->prepare("SELECT * FROM enrollments WHERE id = ?");
        $verify->execute([$enrollment_id]);
        $created = $verify->fetch(PDO::FETCH_ASSOC);
        logEnrollmentDebug("Verification: " . print_r($created, true));
        
        echo json_encode([
            'success' => true,
            'message' => 'Enrollment request submitted. Awaiting approval.',
            'enrollment_id' => $enrollment_id
        ]);
    } else {
        logEnrollmentDebug("ERROR: INSERT returned no rows affected");
        logEnrollmentDebug("PDO Error Info: " . print_r($stmt->errorInfo(), true));
        echo json_encode(['success' => false, 'message' => 'Failed to create enrollment.']);
    }
    
} catch (PDOException $e) {
    logEnrollmentDebug("❌ DATABASE ERROR: " . $e->getMessage());
    logEnrollmentDebug("Error Code: " . $e->getCode());
    logEnrollmentDebug("Stack Trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    logEnrollmentDebug("❌ GENERAL ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>
