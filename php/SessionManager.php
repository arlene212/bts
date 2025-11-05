<?php
class SessionManager {
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function requireRole($requiredRole) {
        self::startSession();
        
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== $requiredRole) {
            header('Location: ../landingpage.php');
            exit;
        }
        
        // Check if password needs to be changed, but exclude the password change page itself
        $currentPage = basename($_SERVER['PHP_SELF']);
        if ($currentPage !== 'force_change_password.php' && 
            isset($_SESSION['user']['password_changed_at']) && 
            $_SESSION['user']['password_changed_at'] === null) {
            header('Location: /bts/bts/html/force_change_password.php');
            exit;
        }
    }
    
    public static function getCurrentUser() {
        self::startSession();
        return $_SESSION['user'] ?? null;
    }
    
    public static function isLoggedIn() {
        self::startSession();
        return isset($_SESSION['user']);
    }
    
    public static function logout() {
        self::startSession();
        $_SESSION = array();
        session_destroy();
        header('Location: ../landingpage.php');
        exit;
    }

    public static function loginUser($user) {
        self::startSession();
        $_SESSION['user'] = [
            'id' => $user['id'] ?? $user['user_id'],
            'user_id' => $user['user_id'],
            'role' => $user['role'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'contact_number' => $user['contact_number'] ?? '',
            'profile_picture' => $user['profile_picture'] ?? '',
            'password_changed_at' => $user['password_changed_at'] ?? null
        ];
    }
    
    public static function redirectBasedOnRole() {
        self::startSession();
        if (isset($_SESSION['user'])) {
            // Check if password needs to be changed
            if (isset($_SESSION['user']['password_changed_at']) && 
                $_SESSION['user']['password_changed_at'] === null) {
                header('Location: /bts/bts/html/force_change_password.php');
                exit;
            }
            
            switch ($_SESSION['user']['role']) {
                case 'admin': 
                    header('Location: /bts/bts/html/admin.php'); 
                    break;
                case 'trainer': 
                    header('Location: /bts/bts/html/trainer.php'); 
                    break;
                case 'trainee': 
                    header('Location: /bts/bts/html/trainee.php'); 
                    break;
                case 'guest': 
                    header('Location: /bts/bts/html/guest.php'); 
                    break;
                default: 
                    header('Location: /bts/bts/landingpage.php');
            }
            exit;
        }
    }

    public static function getRedirectUrlByRole($role) {
        switch ($role) {
            case 'admin':
                return '/bts/bts/html/admin.php';
            case 'trainer':
                return '/bts/bts/html/trainer.php';
            case 'trainee':
                return '/bts/bts/html/trainee.php';
            case 'guest':
                return '/bts/bts/html/guest.php';
            default:
                return '/bts/bts/landingpage.php';
        }
    }
    
    public static function requiresPasswordChange() {
        self::startSession();
        return isset($_SESSION['user']) && 
               isset($_SESSION['user']['password_changed_at']) && 
               $_SESSION['user']['password_changed_at'] === null;
    }
}
?>