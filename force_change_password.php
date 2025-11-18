<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'php/SessionManager.php';
require_once 'php/DatabaseConnection.php';

SessionManager::startSession();

if (!SessionManager::isLoggedIn()) {
    header("Location: /bts/landingpage.php");
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
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE users SET password = ?, password_changed_at = NOW() WHERE user_id = ?");
            $updateStmt->execute([$hashedPassword, $_SESSION['user']['user_id']]);
            $_SESSION['user']['password_changed_at'] = date('Y-m-d H:i:s');
            $success = "Password changed successfully! Redirecting to your dashboard...";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/v4-shims.min.css">
    <link rel="stylesheet" href="css/force_change_password.css">
    <link rel="icon" type="image/png" href="images/school.png">
    <style>
      .password-change-container { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f4f6f9; }
      .password-change-card { width: 100%; max-width: 520px; background: #fff; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); overflow: hidden; }
      .logo-section { text-align: center; padding: 24px 24px 0; }
      .logo { width: 64px; height: 64px; }
      .password-change-content { padding: 24px; }
      .error-message { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 12px; }
      .success-message { background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 12px; }
      .password-change-form .form-group { margin-bottom: 16px; }
      .password-wrapper { display:flex; align-items:center; gap:8px; border:1px solid #ced4da; border-radius:8px; padding:8px; }
      .password-wrapper input { flex:1; border:none; outline:none; font-size:14px; }
      .toggle-password { cursor:pointer; color:#888; width:1.25rem; text-align:center; }
      .change-password-btn { width:100%; padding:10px 14px; border:none; border-radius:8px; background:#297acc; color:#fff; font-weight:600; cursor:pointer; }
      .change-password-btn:hover { background:#1f5f99; }
    </style>
</head>
<body>
    <div class="password-change-container">
        <div class="password-change-card">
            <div class="logo-section">
                <img src="images/school.png" alt="BTS Logo" class="logo">
                <h1>Benguet Technical School</h1>
            </div>
            <div class="password-change-content">
                <h2>Change Your Password</h2>
                <p class="instruction">For security reasons, you must change your temporary password before proceeding.</p>
                <?php if ($error): ?><div class="error-message"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <?php if ($success): ?><div class="success-message"><?php echo htmlspecialchars($success); ?></div><?php else: ?>
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
                    <ul><li>At least 8 characters long</li><li>Should not be easily guessable</li></ul>
                </div>
            </div>
        </div>
    </div>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.toggle-password').forEach(icon => {
          icon.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            if (!passwordInput) return;
            if (passwordInput.type === 'password') { passwordInput.type = 'text'; this.classList.remove('fa-eye'); this.classList.add('fa-eye-slash'); }
            else { passwordInput.type = 'password'; this.classList.remove('fa-eye-slash'); this.classList.add('fa-eye'); }
          });
        });
      });
    </script>
</body>
</html>