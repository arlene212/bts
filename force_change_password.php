<?php
session_start();
require_once 'php/config.php';
require_once 'php/DatabaseConnection.php';
require_once 'php/SecurityManager.php';

// Check if user is logged in and needs to change password
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

// Check if user has already changed password
if (isset($_SESSION['user']['password_changed_at']) && $_SESSION['user']['password_changed_at'] !== null) {
    header('Location: ' . $_SESSION['user']['role'] . '/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New password and confirmation do not match.';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $error = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $error = 'Password must contain at least one number.';
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
        $error = 'Password must contain at least one special character.';
    } else {
        try {
            $db = new DatabaseConnection();
            $user_id = $_SESSION['user']['id'];
            
            // Verify current password
            $stmt = $db->getConnection()->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($current_password, $user['password'])) {
                $error = 'Current password is incorrect.';
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->getConnection()->prepare("UPDATE users SET password = ?, password_changed_at = NOW() WHERE id = ?");
                
                if ($stmt->execute([$hashed_password, $user_id])) {
                    // Update session
                    $_SESSION['user']['password_changed_at'] = date('Y-m-d H:i:s');
                    $success = 'Password changed successfully! Redirecting...';
                    
                    // Redirect after 2 seconds
                    echo '<script>setTimeout(function() { window.location.href = "' . $_SESSION['user']['role'] . '/index.php"; }, 2000);</script>';
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - BTS ELMS</title>
    <link rel="stylesheet" href="css/force_change_password.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="password-change-container">
        <div class="password-change-card">
            <div class="logo-section">
                <img src="images/school.png" alt="BTS Logo" class="logo">
                <h1>BTS ELMS</h1>
                <p>Change Your Password</p>
            </div>
            
            <div class="password-change-content">
                <h2>Security Update Required</h2>
                <p class="instruction">For security reasons, you must change your password before accessing the system.</p>
                
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="password-change-form">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="current_password" name="current_password" required>
                            <i class="fas fa-eye toggle-password" data-target="current_password"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="new_password" name="new_password" required>
                            <i class="fas fa-eye toggle-password" data-target="new_password"></i>
                        </div>
                        <div class="password-strength" id="password-strength"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <i class="fas fa-eye toggle-password" data-target="confirm_password"></i>
                        </div>
                    </div>
                    
                    <button type="submit" class="change-password-btn">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
                
                <div class="password-requirements">
                    <h4>Password Requirements:</h4>
                    <ul>
                        <li>At least 8 characters long</li>
                        <li>Contains uppercase and lowercase letters</li>
                        <li>Contains at least one number</li>
                        <li>Contains at least one special character</li>
                        <li>Cannot be the same as your current password</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Password toggle functionality
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const targetInput = document.getElementById(targetId);
                const type = targetInput.getAttribute('type') === 'password' ? 'text' : 'password';
                targetInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });
        
        // Password strength indicator
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('password-strength');
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[!@#$%^&*(),.?":{}|<>]/)) strength++;
            
            const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
            const strengthColors = ['#e74c3c', '#e67e22', '#f39c12', '#27ae60', '#2ecc71', '#27ae60'];
            
            strengthDiv.textContent = password ? `Strength: ${strengthText[strength]}` : '';
            strengthDiv.style.color = strengthColors[strength];
        });
    </script>
</body>
</html>