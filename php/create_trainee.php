<?php
session_start();
require_once 'DatabaseConnection.php';

header('Content-Type: application/json');

// For debugging - allow errors to be shown temporarily
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log the request for debugging
error_log("Create trainee request received: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // FIX: Use DatabaseConnection instead of Database
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();
    
    // Get form data with proper validation
    $firstName = isset($_POST['trainee_first_name']) ? trim($_POST['trainee_first_name']) : '';
    $middleName = isset($_POST['trainee_middle_name']) ? trim($_POST['trainee_middle_name']) : '';
    $lastName = isset($_POST['trainee_last_name']) ? trim($_POST['trainee_last_name']) : '';
    $suffix = isset($_POST['trainee_suffix']) ? trim($_POST['trainee_suffix']) : '';
    $contactNumber = isset($_POST['trainee_number']) ? trim($_POST['trainee_number']) : '';
    $courseCode = isset($_POST['trainee_course']) ? trim($_POST['trainee_course']) : '';
    $batchName = isset($_POST['trainee_batch']) ? trim($_POST['trainee_batch']) : '';
    
    error_log("Form data - First: $firstName, Last: $lastName, Contact: $contactNumber");
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($contactNumber)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'First name, last name, and contact number are required']);
        exit;
    }
    
    // Server-side duplicate check for the same role
    $dupStmt = $pdo->prepare("SELECT user_id FROM users WHERE first_name = ? AND middle_name = ? AND last_name = ? AND role = 'trainee'");
    $dupStmt->execute([$firstName, $middleName, $lastName]);
    if ($dupStmt->fetch()) {
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'A trainee with this exact full name already exists.']);
        exit;
    }

    // Generate user ID (format: 3 + MMDD + random 3 digits)
    $datePart = date('md');
    $randomPart = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
    $userId = '3' . $datePart . $randomPart;
    
    error_log("Generated user ID: $userId");
    
    // Check if user ID already exists (try up to 10 times)
    $maxAttempts = 10;
    $attempt = 0;
    
    do {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            break; // ID is available
        }
        $randomPart = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        $userId = '3' . $datePart . $randomPart;
        $attempt++;
    } while ($attempt < $maxAttempts);
    
    if ($attempt >= $maxAttempts) {
        throw new Exception("Could not generate unique user ID after $maxAttempts attempts");
    }
    
    // Generate email
    $cleanLastName = preg_replace('/[^a-zA-Z]/', '', $lastName);
    $email = strtolower($cleanLastName . '.' . $userId) . '@bts.gov.ph';
    
    error_log("Generated email: $email");

    // Check if email already exists and make it unique if necessary
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        // If email exists, append a number to make it unique
        $counter = 1;
        $baseEmail = strtolower($cleanLastName . '.' . $userId);
        do {
            $email = $baseEmail . $counter . '@bts.gov.ph';
            $stmt->execute([$email]);
            $counter++;
        } while ($stmt->fetch() && $counter < 100); // Failsafe to prevent infinite loop
    }
    
    // Generate temporary password
    $tempPassword = generateTempPassword();
    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
    
    error_log("Password generated, starting transaction...");
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert into users table
        $stmt = $pdo->prepare("
            INSERT INTO users (user_id, role, first_name, middle_name, last_name, suffix, email, contact_number, password, status, date_created) 
            VALUES (?, 'trainee', ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        
        $success = $stmt->execute([
            $userId, 
            $firstName, 
            $middleName, 
            $lastName, 
            $suffix,
            $email,
            $contactNumber,
            $hashedPassword
        ]);
        
        if (!$success) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Failed to insert user into database: " . $errorInfo[2]);
        }
        
        error_log("User inserted successfully, user_id: $userId");
        
        // Create enrollment and batch assignment if course selected
        if (!empty($courseCode)) {
            error_log("Processing course enrollment for: $courseCode");
            
            // Get course name
            $courseStmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_code = ?");
            $courseStmt->execute([$courseCode]);
            $course = $courseStmt->fetch();
            
            if ($course) {
                // Create enrollment
                $enrollStmt = $pdo->prepare("
                    INSERT INTO enrollments (trainee_id, course_code, course_name, status, batch_name, date_requested) 
                    VALUES (?, ?, ?, 'approved', ?, NOW())
                ");
                $enrollSuccess = $enrollStmt->execute([$userId, $courseCode, $course['course_name'], $batchName]);
                
                if (!$enrollSuccess) {
                    $errorInfo = $enrollStmt->errorInfo();
                    throw new Exception("Failed to create enrollment: " . $errorInfo[2]);
                }
                
                error_log("Enrollment created successfully");
                
                // Create batch assignment
                if (!empty($batchName)) {
                    $batchStmt = $pdo->prepare("
                        INSERT INTO batch_assignments (trainee_id, course_code, batch_name, assigned_by, date_assigned) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $batchSuccess = $batchStmt->execute([$userId, $courseCode, $batchName, $_SESSION['user']['user_id']]);
                    
                    if (!$batchSuccess) {
                        $errorInfo = $batchStmt->errorInfo();
                        throw new Exception("Failed to assign batch: " . $errorInfo[2]);
                    }
                    
                    error_log("Batch assignment created successfully");
                }
            } else {
                throw new Exception("Selected course not found: $courseCode");
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        error_log("Transaction committed successfully");
        
        // Return success with credentials
        echo json_encode([
            'success' => true,
            'message' => 'Trainee created successfully',
            'credentials' => [
                'user_id' => $userId,
                'email' => $email,
                'temp_password' => $tempPassword
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Transaction rolled back: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error creating trainee: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error creating trainee: ' . $e->getMessage()]);
}

function generateTempPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    $password = '';
    $charLength = strlen($chars);
    for ($i = 0; $i < $length; $i++) $password .= $chars[random_int(0, $charLength - 1)];
    return $password;
}
?>