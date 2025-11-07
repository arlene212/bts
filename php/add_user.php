<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('admin');

$database = new DatabaseConnection();
$pdo = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name']);
    $suffix = trim($_POST['suffix'] ?? '');
    $email = trim($_POST['email']);
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $role = $_POST['role'];
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email) || empty($role)) {
        $_SESSION['error_message'] = 'Please fill in all required fields.';
        header("Location: ../admin/admin.php");
        exit;
    }
    
    // Validate contact number format
    if (!empty($contactNumber) && !preg_match('/^(09\d{9}|\+63\d{10})$/', $contactNumber)) {
        $_SESSION['error_message'] = 'Please enter a valid Philippine mobile number (09XXXXXXXXX or +63XXXXXXXXX).';
        header("Location: ../admin/admin.php");
        exit;
    }
    
    try {
        // Check if user already exists with same name or email
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (first_name = ? AND last_name = ?) OR email = ?");
        $checkStmt->execute([$firstName, $lastName, $email]);
        $userExists = $checkStmt->fetchColumn();
        
        if ($userExists > 0) {
            $_SESSION['error_message'] = 'A user with the same name or email already exists!';
            header("Location: ../admin/admin.php");
            exit;
        }
        
        // Generate user ID
        $prefix = '';
        switch ($role) {
            case 'admin': $prefix = '1000'; break;
            case 'trainer': $prefix = '2000'; break;
            case 'trainee': $prefix = '3000'; break;
            case 'guest': $prefix = '4000'; break;
            default: $prefix = '0000';
        }
        
        // Get the highest existing user ID for this role
        $stmt = $pdo->prepare("SELECT MAX(user_id) FROM users WHERE user_id LIKE ?");
        $likePattern = $prefix . '%';
        $stmt->execute([$likePattern]);
        $maxId = $stmt->fetchColumn();
        
        if ($maxId) {
            $nextNumber = intval(substr($maxId, -6)) + 1;
        } else {
            $nextNumber = 1;
        }
        
        $userId = $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        
        // Generate random password
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ($i = 0; $i < 8; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $pdo->prepare("INSERT INTO users (user_id, first_name, middle_name, last_name, suffix, email, contact_number, password, role, status, date_created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
        
        if ($stmt->execute([$userId, $firstName, $middleName, $lastName, $suffix, $email, $contactNumber, $hashedPassword, $role])) {
            // In a real application, you would send the credentials via email
            $_SESSION['success_message'] = "{$role} added successfully! User ID: {$userId}, Temporary Password: {$password}";
        } else {
            $_SESSION['error_message'] = "Error adding {$role}. Please try again.";
        }
        
    } catch (PDOException $e) {
        error_log("Error adding user: " . $e->getMessage());
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    }
    
    header("Location: ../admin/admin.php");
    exit;
}
?>