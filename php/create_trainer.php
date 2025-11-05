<?php
session_start();
require_once 'DatabaseConnection.php';

header('Content-Type: application/json');

// For debugging - allow errors to be shown temporarily
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log the request for debugging
error_log("Create trainer request received: " . print_r($_POST, true));

// In add_activity.php, change the INSERT statement from:
$sql = "INSERT INTO topic_activities (topic_id, activity_title, activity_description, activity_type, start_date, due_date, max_score, max_attempts, attachment_path, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

// To either remove attachment_path if you don't need it:
$sql = "INSERT INTO topic_activities (topic_id, activity_title, activity_description, activity_type, start_date, due_date, max_score, max_attempts, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

// Or if you do need attachment storage, add the column as shown above.
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
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

    // Get form data with proper validation
    $firstName = trim($_POST['trainer_first_name'] ?? '');
    $middleName = trim($_POST['trainer_middle_name'] ?? '');
    $lastName = trim($_POST['trainer_last_name'] ?? '');
    $suffix = trim($_POST['trainer_suffix'] ?? '');
    $contactNumber = trim($_POST['trainer_number'] ?? '');
    $courseCodes = $_POST['trainer_courses'] ?? [];

    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($contactNumber)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'First name, last name, and contact number are required']);
        exit;
    }

    // Generate user ID (format: 2 + MMDD + random 3 digits)
    $datePart = date('md');
    $randomPart = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
    $userId = '2' . $datePart . $randomPart;

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
        $userId = '2' . $datePart . $randomPart;
        $attempt++;
    } while ($attempt < $maxAttempts);

    if ($attempt >= $maxAttempts) {
        throw new Exception("Could not generate unique user ID after $maxAttempts attempts");
    }

    // Generate email
    $cleanLastName = preg_replace('/[^a-zA-Z]/', '', $lastName);
    $cleanFirstName = preg_replace('/[^a-zA-Z]/', '', $firstName);
    $email = strtolower($cleanLastName . '.' . $cleanFirstName) . '@bts.gov.ph';

    // Check if email already exists and make it unique if necessary
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        // If email exists, append a number to make it unique
        $counter = 1;
        $baseEmail = strtolower($cleanLastName . '.' . $cleanFirstName);
        do {
            $email = $baseEmail . $counter . '@bts.gov.ph';
            $stmt->execute([$email]);
            $counter++;
        } while ($stmt->fetch() && $counter < 100); // Failsafe to prevent infinite loop
    }

    // Generate temporary password
    $tempPassword = bin2hex(random_bytes(5)); // 10 chars
    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

    // Start transaction
    $pdo->beginTransaction();


    // Insert into users table
    $stmt = $pdo->prepare(
        "INSERT INTO users (user_id, role, first_name, middle_name, last_name, suffix, email, contact_number, password, status, date_created) 
         VALUES (?, 'trainer', ?, ?, ?, ?, ?, ?, ?, 'active', NOW())"
    );
    $stmt->execute([$userId, $firstName, $middleName, $lastName, $suffix, $email, $contactNumber, $hashedPassword]);

    // Assign courses
    if (!empty($courseCodes)) {
        $assignStmt = $pdo->prepare("INSERT INTO course_assignments (trainer_id, course_code, assigned_by) VALUES (?, ?, ?)");
        foreach ($courseCodes as $courseCode) {
            $assignStmt->execute([$userId, $courseCode, $_SESSION['user']['user_id']]);
        }
    }

    // Commit transaction
    $pdo->commit();

    // Return success with credentials
    echo json_encode([
        'success' => true,
        'message' => 'Trainer created successfully',
        'credentials' => [
            'user_id' => $userId,
            'email' => $email,
            'temp_password' => $tempPassword
        ]
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error creating trainer: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error creating trainer: ' . $e->getMessage()]);
}
?>