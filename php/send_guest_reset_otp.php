<?php
require_once __DIR__ . '/DatabaseConnection.php';
require_once __DIR__ . '/Mailer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

try {
    $db = new DatabaseConnection();
    $pdo = $db->getConnection();

    $stmt = $pdo->prepare("SELECT user_id, role, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'No active account found for this email']);
        exit;
    }

    if ($user['role'] !== 'guest') {
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

    $rateStmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM password_reset_otps WHERE user_id = ? AND created_at >= (NOW() - INTERVAL 1 HOUR)");
    $rateStmt->execute([$user['user_id']]);
    $rate = (int)($rateStmt->fetch()['cnt'] ?? 0);
    if ($rate >= 5) {
        echo json_encode(['success' => false, 'message' => 'Too many OTP requests. Please try again later.']);
        exit;
    }

    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

    $ins = $pdo->prepare("INSERT INTO password_reset_otps (user_id, otp_code, expires_at, is_used) VALUES (?, ?, ?, 0)");
    $ins->execute([$user['user_id'], $otp, $expiresAt]);

    $mailer = new Mailer();
    $subject = 'Your BTS eLMS Password Reset OTP';
    $html = '<div style="font-family:Inter,Arial,sans-serif;font-size:14px;color:#222;">'
        . '<p>Dear ' . htmlspecialchars($user['user_id']) . ',</p>'
        . '<p>Use the following One-Time Password (OTP) to reset your password:</p>'
        . '<p style="font-size:24px;font-weight:bold;letter-spacing:4px">' . htmlspecialchars($otp) . '</p>'
        . '<p>This code expires at ' . htmlspecialchars($expiresAt) . '.</p>'
        . '<p>If you did not request this, you can ignore this email.</p>'
        . '<p>— Benguet Technical School eLMS</p>'
        . '</div>';
    list($sent, $msg) = $mailer->send($user['email'] ?? $email, '', $subject, $html);

    if ($sent) {
        echo json_encode(['success' => true, 'message' => 'OTP sent to your email. It will expire in 10 minutes.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email: ' . $msg]);
    }
} catch (Exception $e) {
    error_log('send_guest_reset_otp error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to send OTP. Please try again.']);
}
?>
