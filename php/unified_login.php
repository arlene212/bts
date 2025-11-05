<?php
require_once 'SessionManager.php';
require_once 'DatabaseConnection.php';

SessionManager::startSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Please enter both email and password.";
        header("Location: /bts/bts/landingpage.php");
        exit();
    }
    
    try {
        $db = new DatabaseConnection();
        $pdo = $db->getConnection();
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // Check if user exists, is active, and password is correct
        if ($user && password_verify($password, $user['password'])) {
            // Update last login timestamp
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $updateStmt->execute([$user['user_id']]);
            
            // Use the correct login method
            SessionManager::loginUser($user);

            // Check if password needs to be changed
            if ($user['password_changed_at'] === null) {
                header("Location: ../html/force_change_password.php");
                exit();
            }
            
            // Redirect user to their respective dashboard
            SessionManager::redirectBasedOnRole();
        } else if ($user) {
            // User exists, but password was incorrect
            $_SESSION['login_error'] = "Invalid password.";
            header("Location: /bts/bts/landingpage.php");
            exit();
        } else {
            // User does not exist or is not active
            $_SESSION['login_error'] = "Invalid email or password.";
            header("Location: /bts/bts/landingpage.php");
            exit();
        }
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['login_error'] = "Login failed. Please try again.";
        header("Location: /bts/bts/landingpage.php");
        exit();
    }
}
?>