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

if (!isset($_POST['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

function generateTempPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

    $userId = $_POST['user_id'];
    $tempPassword = generateTempPassword();
    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

    // Set the new password and reset password_changed_at to NULL
    $stmt = $pdo->prepare("
        UPDATE users SET password = ?, password_changed_at = NULL
        WHERE user_id = ?
    ");
    $success = $stmt->execute([$hashedPassword, $userId]);

    if ($success) {
        echo json_encode(['success' => true, 'temp_password' => $tempPassword]);
    } else {
        throw new Exception("Failed to update password in the database.");
    }
} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error resetting password: ' . $e->getMessage()]);
}
?>