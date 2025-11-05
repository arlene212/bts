<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'elms_bts');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('BASE_URL', 'http://localhost/bts');
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');

// Create upload directories if they don't exist
$upload_dirs = ['profiles', 'courses', 'submissions'];
foreach ($upload_dirs as $dir) {
    $path = UPLOAD_PATH . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>