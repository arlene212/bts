<?php
require_once 'SessionManager.php';

/**
 * Enhanced Error Handler for BTS eLMS
 * Provides comprehensive error handling, logging, and user-friendly error pages
 */

class ErrorHandler {
    private static $instance = null;
    private $logFile;
    private $debugMode;
    
    private function __construct() {
        $this->logFile = __DIR__ . '/../logs/error.log';
        $this->debugMode = (getenv('DEBUG_MODE') === 'true') || (defined('DEBUG_MODE') && constant('DEBUG_MODE'));
        $this->ensureLogDirectory();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function ensureLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Handle 404 Not Found errors
     */
    public function handle404($requestedUrl = '') {
        http_response_code(404);
        $this->logError("404 Error", "Page not found: {$requestedUrl}");
        
        if (!$this->debugMode) {
            $this->show404Page();
        } else {
            $this->showDebug404Page($requestedUrl);
        }
        exit;
    }
    
    /**
     * Handle 403 Forbidden errors
     */
    public function handle403($reason = 'Access denied') {
        http_response_code(403);
        $this->logError("403 Error", $reason);
        
        if (!$this->debugMode) {
            $this->show403Page();
        } else {
            $this->showDebug403Page($reason);
        }
        exit;
    }
    
    /**
     * Handle 500 Internal Server errors
     */
    public function handle500($error, $context = []) {
        http_response_code(500);
        $errorMessage = $error instanceof Exception ? $error->getMessage() : $error;
        $this->logError("500 Error", $errorMessage, $context);
        
        if (!$this->debugMode) {
            $this->show500Page();
        } else {
            $this->showDebug500Page($error, $context);
        }
        exit;
    }
    
    /**
     * Handle unauthorized access attempts
     */
    public function handleUnauthorized($requestedPage = '') {
        $this->logSecurityEvent("Unauthorized Access Attempt", $requestedPage);
        
        if (SessionManager::isLoggedIn()) {
            $this->handle403('Insufficient privileges to access this resource');
        } else {
            // Redirect to login with return URL
            $returnUrl = urlencode($_SERVER['REQUEST_URI']);
            header("Location: /login.php?return_url={$returnUrl}");
            exit;
        }
    }
    
    /**
     * Log errors with context
     */
    private function logError($type, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $userInfo = $this->getUserInfo();
        $ipAddress = $this->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'type' => $type,
            'message' => $message,
            'user' => $userInfo,
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'context' => $context
        ];
        
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log security events
     */
    private function logSecurityEvent($event, $details = '') {
        $securityLogFile = __DIR__ . '/../logs/security.log';
        $logDir = dirname($securityLogFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $userInfo = $this->getUserInfo();
        $ipAddress = $this->getClientIp();
        
        $logEntry = [
            'timestamp' => $timestamp,
            'event' => $event,
            'details' => $details,
            'user' => $userInfo,
            'ip' => $ipAddress,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
        
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($securityLogFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get user information for logging
     */
    private function getUserInfo() {
        if (!SessionManager::isLoggedIn()) {
            return ['status' => 'guest'];
        }
        
        $user = SessionManager::getCurrentUser();
        return [
            'status' => 'logged_in',
            'user_id' => $user['user_id'] ?? 'unknown',
            'role' => $user['role'] ?? 'unknown',
            'email' => $user['email'] ?? 'unknown'
        ];
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Show user-friendly 404 page
     */
    private function show404Page() {
        $p = __DIR__ . '/../error_pages/404.php';
        if (file_exists($p)) { include $p; } else { echo '<h1>404 Not Found</h1><p>The requested page could not be found.</p>'; }
    }
    
    /**
     * Show user-friendly 403 page
     */
    private function show403Page() {
        $p = __DIR__ . '/../error_pages/403.php';
        if (file_exists($p)) { include $p; } else { echo '<h1>403 Forbidden</h1><p>You do not have permission to access this resource.</p>'; }
    }
    
    /**
     * Show user-friendly 500 page
     */
    private function show500Page() {
        $p = __DIR__ . '/../error_pages/500.php';
        if (file_exists($p)) { include $p; } else { echo '<h1>500 Internal Server Error</h1><p>Something went wrong. Please try again later.</p>'; }
    }
    
    /**
     * Show debug 404 page
     */
    private function showDebug404Page($requestedUrl) {
        echo "<h1>404 Not Found (Debug Mode)</h1>";
        echo "<p>Requested URL: " . htmlspecialchars($requestedUrl) . "</p>";
        echo "<p>Script: " . htmlspecialchars($_SERVER['SCRIPT_NAME']) . "</p>";
        echo "<p>Request URI: " . htmlspecialchars($_SERVER['REQUEST_URI']) . "</p>";
        echo "<hr>";
        echo "<h3>Available Routes:</h3>";
        echo "<pre>";
        debug_print_backtrace();
        echo "</pre>";
    }
    
    /**
     * Show debug 403 page
     */
    private function showDebug403Page($reason) {
        echo "<h1>403 Forbidden (Debug Mode)</h1>";
        echo "<p>Reason: " . htmlspecialchars($reason) . "</p>";
        echo "<p>User: " . htmlspecialchars(json_encode($this->getUserInfo())) . "</p>";
        echo "<hr>";
        debug_print_backtrace();
    }
    
    /**
     * Show debug 500 page
     */
    private function showDebug500Page($error, $context) {
        echo "<h1>500 Internal Server Error (Debug Mode)</h1>";
        echo "<h3>Error:</h3>";
        echo "<pre>" . htmlspecialchars($error instanceof Exception ? $error->getMessage() : $error) . "</pre>";
        
        if ($error instanceof Exception) {
            echo "<h3>Stack Trace:</h3>";
            echo "<pre>" . htmlspecialchars($error->getTraceAsString()) . "</pre>";
        }
        
        if (!empty($context)) {
            echo "<h3>Context:</h3>";
            echo "<pre>" . htmlspecialchars(json_encode($context, JSON_PRETTY_PRINT)) . "</pre>";
        }
    }
    
    /**
     * Validate and sanitize input
     */
    public function sanitizeInput($input, $type = 'string') {
        if ($input === null) {
            return null;
        }
        
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'string':
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRFToken($token) {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (empty($sessionToken) || $token !== $sessionToken) {
            $this->logSecurityEvent('CSRF Token Validation Failed', "Provided: {$token}, Session: {$sessionToken}");
            return false;
        }
        return true;
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Rate limiting check
     */
    public function checkRateLimit($action, $limit = 10, $window = 3600) {
        $key = 'rate_limit_' . $action . '_' . $this->getClientIp();
        $current = $_SESSION[$key] ?? 0;
        $timestamp = $_SESSION[$key . '_timestamp'] ?? time();
        
        // Reset counter if window has passed
        if (time() - $timestamp > $window) {
            $current = 0;
            $timestamp = time();
        }
        
        if ($current >= $limit) {
            $this->logSecurityEvent('Rate Limit Exceeded', "Action: {$action}, Limit: {$limit}");
            return false;
        }
        
        $_SESSION[$key] = $current + 1;
        $_SESSION[$key . '_timestamp'] = $timestamp;
        return true;
    }
}

// Global error handler functions
function handleError($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $errorHandler = ErrorHandler::getInstance();
    $errorHandler->handle500("{$errstr} in {$errfile} on line {$errline}", [
        'type' => $errno,
        'file' => $errfile,
        'line' => $errline
    ]);
    return true;
}

function handleException($exception) {
    $errorHandler = ErrorHandler::getInstance();
    $errorHandler->handle500($exception);
}

// Set error handlers (skip for CLI)
if (php_sapi_name() !== 'cli') {
    set_error_handler('handleError');
    set_exception_handler('handleException');
    // Register shutdown function for fatal errors
    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorHandler = ErrorHandler::getInstance();
            $errorHandler->handle500("Fatal error: {$error['message']} in {$error['file']} on line {$error['line']}", $error);
        }
    });
}

?>
