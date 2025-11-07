<?php
// Prevent browser from caching old pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once '../php/SessionManager.php';
require_once '../php/DatabaseConnection.php';

SessionManager::startSession();

// Redirect if not logged in or if password already changed
if (!SessionManager::isLoggedIn()) {
    header("Location: ../landingpage.php");
    exit();
}

if (isset($_SESSION['user']['password_changed_at']) && $_SESSION['user']['password_changed_at'] !== null) {
    SessionManager::redirectBasedOnRole();
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate passwords
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = "Please fill in all fields.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($newPassword) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        try {
            $db = new DatabaseConnection();
            $pdo = $db->getConnection();
            
            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password and set password_changed_at timestamp
            $updateStmt = $pdo->prepare("UPDATE users SET password = ?, password_changed_at = NOW() WHERE user_id = ?");
            $updateStmt->execute([$hashedPassword, $_SESSION['user']['user_id']]);
            
            // Update session
            $_SESSION['user']['password_changed_at'] = date('Y-m-d H:i:s');
            
            $success = "Password changed successfully! Redirecting to your dashboard...";
            
            // Redirect after 2 seconds
            $redirectUrl = SessionManager::getRedirectUrlByRole($_SESSION['user']['role']);
            header("refresh:2;url=" . $redirectUrl);
            
        } catch (PDOException $e) {
            $error = "Error changing password: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Benguet Technical School</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/force_change_password.css">
    <link rel="icon" type="image/png" href="../images/school.png">
</head>
<body>
    <div class="password-change-container">
        <div class="password-change-card">
            <div class="logo-section">
                <img src="../images/school.png" alt="BTS Logo" class="logo">
                <h1>Benguet Technical School</h1>
            </div>
            
            <div class="password-change-content">
                <h2>Change Your Password</h2>
                <p class="instruction">For security reasons, you must change your temporary password before proceeding.</p>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                <?php else: ?>
                <form method="POST" class="password-change-form">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="new_password" name="new_password" required placeholder="Enter new password (min. 8 characters)">
                            <i class="fa fa-eye toggle-password" data-target="new_password"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your new password">
                            <i class="fa fa-eye toggle-password" data-target="confirm_password"></i>
                        </div>
                    </div>
                    
                    <button type="submit" name="change_password" class="change-password-btn">Change Password</button>
                </form>
                <?php endif; ?>
                
                <div class="password-requirements">
                    <h4>Password Requirements:</h4>
                    <ul>
                        <li>At least 8 characters long</li>
                        <li>Should not be easily guessable</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePasswordIcons = document.querySelectorAll('.toggle-password');
        togglePasswordIcons.forEach(icon => {
            icon.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);

                if (passwordInput) {
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        this.classList.remove('fa-eye');
                        this.classList.add('fa-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        this.classList.remove('fa-eye-slash');
                        this.classList.add('fa-eye');
                    }
                }
            });
        });
    });
    </script>
</body>
</html>