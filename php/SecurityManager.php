<?php
require_once 'SessionManager.php';

/**
 * Security Manager for BTS eLMS
 * Provides comprehensive security features including input validation, XSS protection, CSRF protection, and more
 */

class SecurityManager {
    private static $instance = null;
    private $errorHandler;
    private $securityConfig;
    
    private function __construct() {
        $this->errorHandler = ErrorHandler::getInstance();
        $this->securityConfig = $this->loadSecurityConfig();
        $this->initializeSecurityHeaders();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadSecurityConfig() {
        return [
            'max_login_attempts' => 5,
            'lockout_duration' => 1800, // 30 minutes
            'password_min_length' => 8,
            'password_require_uppercase' => true,
            'password_require_lowercase' => true,
            'password_require_numbers' => true,
            'password_require_special' => true,
            'session_timeout' => 3600, // 1 hour
            'csrf_token_lifetime' => 3600,
            'max_file_upload_size' => 10 * 1024 * 1024, // 10MB
            'allowed_file_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'txt'],
            'enable_rate_limiting' => true,
            'rate_limit_window' => 3600, // 1 hour
            'rate_limit_max_requests' => 100
        ];
    }
    
    /**
     * Initialize security headers
     */
    private function initializeSecurityHeaders() {
        // Prevent XSS attacks
        header('X-XSS-Protection: 1; mode=block');
        
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enforce HTTPS
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self';");
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions Policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
    
    /**
     * Validate and sanitize input
     */
    public function sanitizeInput($input, $type = 'string', $maxLength = null) {
        if ($input === null || $input === '') {
            return null;
        }
        
        // Trim whitespace
        $input = trim($input);
        
        // Check length if specified
        if ($maxLength !== null && strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }
        
        switch ($type) {
            case 'email':
                $sanitized = filter_var($input, FILTER_SANITIZE_EMAIL);
                if (!filter_var($sanitized, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException("Invalid email format");
                }
                return $sanitized;
                
            case 'url':
                $sanitized = filter_var($input, FILTER_SANITIZE_URL);
                if (!filter_var($sanitized, FILTER_VALIDATE_URL)) {
                    throw new InvalidArgumentException("Invalid URL format");
                }
                return $sanitized;
                
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
                
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                
            case 'alphanumeric':
                return preg_replace('/[^a-zA-Z0-9]/', '', $input);
                
            case 'alpha':
                return preg_replace('/[^a-zA-Z]/', '', $input);
                
            case 'numeric':
                return preg_replace('/[^0-9]/', '', $input);
                
            case 'username':
                // Allow alphanumeric, underscore, and hyphen
                $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '', $input);
                if (strlen($sanitized) < 3 || strlen($sanitized) > 30) {
                    throw new InvalidArgumentException("Username must be between 3 and 30 characters");
                }
                return $sanitized;
                
            case 'string':
            default:
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Validate password strength
     */
    public function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < $this->securityConfig['password_min_length']) {
            $errors[] = "Password must be at least {$this->securityConfig['password_min_length']} characters long";
        }
        
        if ($this->securityConfig['password_require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if ($this->securityConfig['password_require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if ($this->securityConfig['password_require_numbers'] && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if ($this->securityConfig['password_require_special'] && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return $errors;
    }
    
    /**
     * Hash password using bcrypt
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate secure random token
     */
    public function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Validate file upload
     */
    public function validateFileUpload($file, $allowedExtensions = null, $maxSize = null) {
        $errors = [];
        
        if (!isset($file['error']) || is_array($file['error'])) {
            $errors[] = "Invalid file upload";
            return $errors;
        }
        
        // Check for upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                $errors[] = "No file was uploaded";
                return $errors;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = "File is too large";
                return $errors;
            default:
                $errors[] = "Unknown upload error";
                return $errors;
        }
        
        // Check file size
        $maxSize = $maxSize ?? $this->securityConfig['max_file_upload_size'];
        if ($file['size'] > $maxSize) {
            $maxSizeMB = $maxSize / (1024 * 1024);
            $errors[] = "File size exceeds maximum allowed size of {$maxSizeMB}MB";
        }
        
        // Check file extension
        $allowedExtensions = $allowedExtensions ?? $this->securityConfig['allowed_file_extensions'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors[] = "File type not allowed. Allowed types: " . implode(', ', $allowedExtensions);
        }
        
        // Check MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        $allowedMimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'txt' => 'text/plain'
        ];
        
        if (isset($allowedMimeTypes[$fileExtension]) && $mimeType !== $allowedMimeTypes[$fileExtension]) {
            $errors[] = "File MIME type does not match extension";
        }
        
        return $errors;
    }
    
    /**
     * Check rate limiting
     */
    public function checkRateLimit($action, $identifier = null, $limit = null, $window = null) {
        if (!$this->securityConfig['enable_rate_limiting']) {
            return true;
        }
        
        $limit = $limit ?? $this->securityConfig['rate_limit_max_requests'];
        $window = $window ?? $this->securityConfig['rate_limit_window'];
        $identifier = $identifier ?? $this->errorHandler->getClientIp();
        
        $key = 'rate_limit_' . $action . '_' . $identifier;
        $current = $_SESSION[$key] ?? 0;
        $timestamp = $_SESSION[$key . '_timestamp'] ?? time();
        
        // Reset counter if window has passed
        if (time() - $timestamp > $window) {
            $current = 0;
            $timestamp = time();
        }
        
        if ($current >= $limit) {
            $this->errorHandler->logSecurityEvent('Rate Limit Exceeded', "Action: {$action}, Identifier: {$identifier}");
            return false;
        }
        
        $_SESSION[$key] = $current + 1;
        $_SESSION[$key . '_timestamp'] = $timestamp;
        return true;
    }
    
    /**
     * Validate and sanitize SQL input
     */
    public function sanitizeSQL($input) {
        if ($input === null || $input === '') {
            return null;
        }
        
        // Remove SQL keywords and dangerous characters
        $dangerousPatterns = [
            '/\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION|SCRIPT|APPENDIX|DATABASE)\b/i',
            '/(--|\/\*|\*\/)/',
            '/[;\'"`]/',
            '/\b(OR|AND)\b.*=.*\b(OR|AND)\b/i'
        ];
        
        $sanitized = $input;
        foreach ($dangerousPatterns as $pattern) {
            $sanitized = preg_replace($pattern, '', $sanitized);
        }
        
        return $sanitized;
    }
    
    /**
     * Check for XSS attempts
     */
    public function detectXSS($input) {
        $xssPatterns = [
            '/<script[^>]*>.*?<\/script>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe[^>]*>.*?<\/iframe>/i',
            '/<object[^>]*>.*?<\/object>/i',
            '/<embed[^>]*>.*?<\/embed>/i',
            '/<form[^>]*>/i',
            '/<input[^>]*>/i',
            '/<svg[^>]*>.*?<\/svg>/i'
        ];
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log security event
     */
    public function logSecurityEvent($event, $details = '') {
        $this->errorHandler->logSecurityEvent($event, $details);
    }
    
    /**
     * Validate session security
     */
    public function validateSession() {
        if (!SessionManager::isLoggedIn()) {
            return true;
        }
        
        // Check session timeout
        $lastActivity = $_SESSION['last_activity'] ?? time();
        if (time() - $lastActivity > $this->securityConfig['session_timeout']) {
            session_destroy();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Validate session fingerprint
        $fingerprint = $this->generateSessionFingerprint();
        if (!isset($_SESSION['session_fingerprint']) || $_SESSION['session_fingerprint'] !== $fingerprint) {
            $this->logSecurityEvent('Session Hijacking Attempt', 'Fingerprint mismatch');
            session_destroy();
            return false;
        }
        
        return true;
    }
    
    /**
     * Generate session fingerprint
     */
    private function generateSessionFingerprint() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ipAddress = $this->errorHandler->getClientIp();
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        
        return md5($userAgent . $ipAddress . $acceptLanguage);
    }
    
    /**
     * Initialize secure session
     */
    public function initializeSecureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            SessionManager::startSession();
            
            // Set session fingerprint
            if (!isset($_SESSION['session_fingerprint'])) {
                $_SESSION['session_fingerprint'] = $this->generateSessionFingerprint();
            }
            
            // Set last activity
            if (!isset($_SESSION['last_activity'])) {
                $_SESSION['last_activity'] = time();
            }
        }
    }
}

// Global security functions
function sanitize_input($input, $type = 'string', $maxLength = null) {
    $securityManager = SecurityManager::getInstance();
    return $securityManager->sanitizeInput($input, $type, $maxLength);
}

function validate_password($password) {
    $securityManager = SecurityManager::getInstance();
    return $securityManager->validatePassword($password);
}

function hash_password($password) {
    $securityManager = SecurityManager::getInstance();
    return $securityManager->hashPassword($password);
}

function verify_password($password, $hash) {
    $securityManager = SecurityManager::getInstance();
    return $securityManager->verifyPassword($password, $hash);
}

function generate_secure_token($length = 32) {
    $securityManager = SecurityManager::getInstance();
    return $securityManager->generateSecureToken($length);
}

function check_rate_limit($action, $identifier = null, $limit = null, $window = null) {
    $securityManager = SecurityManager::getInstance();
    return $securityManager->checkRateLimit($action, $identifier, $limit, $window);
}

function log_security_event($event, $details = '') {
    $securityManager = SecurityManager::getInstance();
    return $securityManager->logSecurityEvent($event, $details);
}

// Initialize security on include
$securityManager = SecurityManager::getInstance();
$securityManager->initializeSecureSession();

?>