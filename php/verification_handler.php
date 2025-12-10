<?php
require_once '../php/SessionManager.php';
require_once '../php/DatabaseConnection.php';

SessionManager::startSession();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';
$userId = $_SESSION['user']['user_id'] ?? '';

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Please login to perform this action.']);
    exit;
}

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();
    
    switch ($action) {
        case 'send_email_verification':
            sendEmailVerification($pdo, $userId);
            break;
            
        case 'verify_email':
            $token = $_POST['token'] ?? '';
            verifyEmail($pdo, $userId, $token);
            break;
            
        case 'update_student_id':
            $studentId = $_POST['student_id'] ?? '';
            updateStudentId($pdo, $userId, $studentId);
            break;
            
        case 'send_phone_verification':
            $phone = $_POST['phone'] ?? '';
            sendPhoneVerification($pdo, $userId, $phone);
            break;
            
        case 'verify_phone':
            $token = $_POST['token'] ?? '';
            verifyPhone($pdo, $userId, $token);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Verification handler error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}

function sendEmailVerification($pdo, $userId) {
    // Get user email
    $stmt = $pdo->prepare("SELECT email, first_name FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        return;
    }
    
    // Generate verification token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Store token
    $stmt = $pdo->prepare("INSERT INTO verification_tokens (user_id, token_type, token, expires_at) VALUES (?, 'email', ?, ?)");
    $stmt->execute([$userId, $token, $expiresAt]);
    
    // In a real implementation, you would send an email here
    // For now, we'll simulate the email sending
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $verificationUrl = $scheme . '://' . $host . '/verify_email.php?token=' . $token;
    
    // Log the verification URL (in production, this would be sent via email)
    error_log("Email verification URL for " . $user['email'] . ": " . $verificationUrl);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Verification email sent! Please check your email and click the verification link.',
        'debug_url' => $verificationUrl // Remove this in production
    ]);
}

function verifyEmail($pdo, $userId, $token) {
    // Validate token
    $stmt = $pdo->prepare("SELECT id, user_id, expires_at, is_used FROM verification_tokens WHERE token = ? AND token_type = 'email' AND is_used = 0");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tokenData || $tokenData['user_id'] !== $userId) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired verification token.']);
        return;
    }
    
    if (strtotime($tokenData['expires_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Verification token has expired.']);
        return;
    }
    
    // Mark token as used
    $stmt = $pdo->prepare("UPDATE verification_tokens SET is_used = 1 WHERE id = ?");
    $stmt->execute([$tokenData['id']]);
    
    // Update user email verification status
    $stmt = $pdo->prepare("UPDATE users SET email_verified = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Log verification
    $stmt = $pdo->prepare("INSERT INTO user_verification_history (user_id, verification_type, status) VALUES (?, 'email', 'verified')");
    $stmt->execute([$userId]);
    
    echo json_encode(['success' => true, 'message' => 'Email verified successfully!']);
}

function updateStudentId($pdo, $userId, $studentId) {
    if (empty($studentId)) {
        echo json_encode(['success' => false, 'message' => 'Student ID cannot be empty.']);
        return;
    }
    
    // Check if student ID already exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE student_id = ? AND user_id != ?");
    $stmt->execute([$studentId, $userId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'This Student ID is already registered to another user.']);
        return;
    }
    
    // Update student ID
    $stmt = $pdo->prepare("UPDATE users SET student_id = ? WHERE user_id = ?");
    $stmt->execute([$studentId, $userId]);
    
    echo json_encode(['success' => true, 'message' => 'Student ID updated successfully!']);
}

function sendPhoneVerification($pdo, $userId, $phone) {
    if (empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Phone number is required.']);
        return;
    }
    
    // Validate phone format (basic validation)
    if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format. Use 09XXXXXXXXX or +639XXXXXXXXX.']);
        return;
    }
    
    // Generate verification code (6 digits)
    $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Store verification code
    $stmt = $pdo->prepare("INSERT INTO verification_tokens (user_id, token_type, token, expires_at) VALUES (?, 'phone', ?, ?)");
    $stmt->execute([$userId, $code, $expiresAt]);
    
    // Update user's phone number
    $stmt = $pdo->prepare("UPDATE users SET contact_number = ? WHERE user_id = ?");
    $stmt->execute([$phone, $userId]);
    
    // In a real implementation, you would send an SMS here
    // For now, we'll simulate the SMS sending
    error_log("Phone verification code for " . $phone . ": " . $code);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Verification code sent to your phone! Please enter the 6-digit code.',
        'debug_code' => $code // Remove this in production
    ]);
}

function verifyPhone($pdo, $userId, $code) {
    // Validate code
    $stmt = $pdo->prepare("SELECT id, user_id, expires_at, is_used FROM verification_tokens WHERE token = ? AND token_type = 'phone' AND is_used = 0");
    $stmt->execute([$code]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tokenData || $tokenData['user_id'] !== $userId) {
        echo json_encode(['success' => false, 'message' => 'Invalid verification code.']);
        return;
    }
    
    if (strtotime($tokenData['expires_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Verification code has expired.']);
        return;
    }
    
    // Mark code as used
    $stmt = $pdo->prepare("UPDATE verification_tokens SET is_used = 1 WHERE id = ?");
    $stmt->execute([$tokenData['id']]);
    
    // Update user phone verification status
    $stmt = $pdo->prepare("UPDATE users SET phone_verified = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Log verification
    $stmt = $pdo->prepare("INSERT INTO user_verification_history (user_id, verification_type, status) VALUES (?, 'phone', 'verified')");
    $stmt->execute([$userId]);
    
    echo json_encode(['success' => true, 'message' => 'Phone verified successfully!']);
}
?>
