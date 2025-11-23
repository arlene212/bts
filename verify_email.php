<?php
require_once '../php/SessionManager.php';
require_once '../php/DatabaseConnection.php';

SessionManager::startSession();

$token = $_GET['token'] ?? '';
$message = '';
$success = false;

if ($token) {
    try {
        $database = new DatabaseConnection();
        $pdo = $database->getConnection();
        
        // Validate token
        $stmt = $pdo->prepare("SELECT id, user_id, expires_at, is_used FROM verification_tokens WHERE token = ? AND token_type = 'email' AND is_used = 0");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tokenData) {
            if (strtotime($tokenData['expires_at']) >= time()) {
                // Mark token as used
                $stmt = $pdo->prepare("UPDATE verification_tokens SET is_used = 1 WHERE id = ?");
                $stmt->execute([$tokenData['id']]);
                
                // Update user email verification status
                $stmt = $pdo->prepare("UPDATE users SET email_verified = 1 WHERE user_id = ?");
                $stmt->execute([$tokenData['user_id']]);
                
                // Log verification
                $stmt = $pdo->prepare("INSERT INTO user_verification_history (user_id, verification_type, status) VALUES (?, 'email', 'verified')");
                $stmt->execute([$tokenData['user_id']]);
                
                $message = 'Your email has been successfully verified!';
                $success = true;
            } else {
                $message = 'This verification link has expired. Please request a new one.';
            }
        } else {
            $message = 'Invalid or already used verification link.';
        }
    } catch (PDOException $e) {
        $message = 'An error occurred during verification. Please try again later.';
        error_log("Email verification error: " . $e->getMessage());
    }
} else {
    $message = 'No verification token provided.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - BTS</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .verification-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        
        .verification-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .verification-icon.success {
            color: #10b981;
        }
        
        .verification-icon.error {
            color: #ef4444;
        }
        
        .verification-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        .verification-message {
            color: #6b7280;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .verification-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .resend-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        
        .resend-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-icon <?php echo $success ? 'success' : 'error'; ?>">
            <i class="fas fa-<?php echo $success ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        </div>
        
        <h1 class="verification-title">
            <?php echo $success ? 'Email Verified!' : 'Verification Failed'; ?>
        </h1>
        
        <p class="verification-message">
            <?php echo htmlspecialchars($message); ?>
        </p>
        
        <div class="verification-actions">
            <?php if ($success): ?>
                <a href="../guest/index.php" class="btn btn-primary">Go to Dashboard</a>
            <?php else: ?>
                <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
                <a href="../guest/index.php?current_tab=home" class="btn btn-primary">Go to Dashboard</a>
            <?php endif; ?>
        </div>
        
        <?php if (!$success): ?>
            <p style="margin-top: 1rem; font-size: 0.9rem;">
                Need a new verification link? 
                <a href="../guest/index.php?current_tab=home" class="resend-link">Request one from your profile</a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>