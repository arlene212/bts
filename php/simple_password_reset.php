<?php
require_once 'DatabaseConnection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$newPassword = trim($_POST['new_password'] ?? '');
$confirmPassword = trim($_POST['confirm_password'] ?? '');
$otpCode = trim($_POST['otp_code'] ?? '');

// Validate input
if (empty($email) || empty($newPassword) || empty($confirmPassword) || empty($otpCode)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

if (!preg_match('/^\d{6}$/', $otpCode)) {
    echo json_encode(['success' => false, 'message' => 'Invalid OTP format']);
    exit;
}

// Check if passwords match
if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

// Validate password strength (minimum 8 characters)
if (strlen($newPassword) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
    exit;
}

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();
    
    // Check if user exists and is active
    $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, role FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'No account found with this email address']);
        exit;
    }

    if (($user['role'] ?? '') !== 'guest') {
        echo json_encode(['success' => false, 'message' => 'Forgot Password with OTP is available for guest accounts only']);
        exit;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_otps (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(50) NOT NULL,
        otp_code VARCHAR(6) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        is_used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (expires_at),
        INDEX (is_used)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $otpStmt = $pdo->prepare("SELECT id FROM password_reset_otps WHERE user_id = ? AND otp_code = ? AND is_used = 0 AND expires_at >= NOW() ORDER BY id DESC LIMIT 1");
    $otpStmt->execute([$user['user_id'], $otpCode]);
    $otp = $otpStmt->fetch();
    if (!$otp) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
        exit;
    }
    
    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update the password and set password_changed_at to current timestamp
    $updateStmt = $pdo->prepare("UPDATE users SET password = ?, password_changed_at = NOW() WHERE user_id = ?");
    $success = $updateStmt->execute([$hashedPassword, $user['user_id']]);
    
    if ($success) {
        $updOtp = $pdo->prepare("UPDATE password_reset_otps SET is_used = 1 WHERE id = ?");
        $updOtp->execute([$otp['id']]);
    }
    
    if ($success) {
        echo json_encode([
            'success' => true, 
            'message' => 'Password has been reset successfully. You can now log in with your new password.',
            'user' => [
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name']
            ]
        ]);
    } else {
        throw new Exception("Failed to update password in the database.");
    }
    
} catch (PDOException $e) {
    error_log("Password reset error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again later.']);
} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while resetting your password. Please try again.']);
}
?>
