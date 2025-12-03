<?php
/**
 * Security Configuration for BTS eLMS
 * Centralized security settings and configurations
 */

return [
    // Application Security Settings
    'app' => [
        'debug_mode' => false, // Set to false in production
        'maintenance_mode' => false,
        'allowed_hosts' => ['localhost', 'bts.edu.ph', 'www.bts.edu.ph'],
        'force_https' => true,
        'session_timeout' => 3600, // 1 hour
    ],
    
    // Password Policy Settings
    'password_policy' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_special_chars' => true,
        'special_chars' => '!@#$%^&*(),.?":{}|<>',
        'max_age' => 7776000, // 90 days
        'prevent_reuse' => 5, // Number of previous passwords to prevent reuse
    ],
    
    // Rate Limiting Settings
    'rate_limiting' => [
        'enabled' => true,
        'login_attempts' => 5,
        'login_lockout_duration' => 1800, // 30 minutes
        'api_requests_per_hour' => 1000,
        'password_reset_attempts' => 3,
        'password_reset_lockout_duration' => 3600, // 1 hour
    ],
    
    // File Upload Settings
    'file_uploads' => [
        'max_file_size' => 10485760, // 10MB
        'allowed_extensions' => [
            'pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx',
            'jpg', 'jpeg', 'png', 'gif', 'svg',
            'zip', 'rar'
        ],
        'allowed_mime_types' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/svg+xml',
            'application/zip',
            'application/x-rar-compressed'
        ],
        'scan_for_malware' => true,
        'quarantine_suspicious_files' => true,
    ],
    
    // Session Security Settings
    'session_security' => [
        'cookie_httponly' => true,
        'cookie_secure' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
        'regenerate_id' => true,
        'fingerprint_check' => true,
    ],
    
    // CSRF Protection Settings
    'csrf_protection' => [
        'enabled' => true,
        'token_name' => 'csrf_token',
        'token_lifetime' => 3600, // 1 hour
        'exclude_uris' => [
            'api/webhook',
            'api/external'
        ],
    ],
    
    // XSS Protection Settings
    'xss_protection' => [
        'enabled' => true,
        'sanitize_input' => true,
        'strip_tags' => true,
        'allowed_tags' => '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><a><img><table><thead><tbody><tr><th><td>',
        'content_security_policy' => true,
    ],
    
    // SQL Injection Protection
    'sql_injection_protection' => [
        'enabled' => true,
        'use_prepared_statements' => true,
        'escape_like_statements' => true,
        'validate_input_types' => true,
    ],
    
    // API Security Settings
    'api_security' => [
        'require_authentication' => true,
        'rate_limiting' => true,
        'cors_enabled' => true,
        'allowed_origins' => ['https://bts.edu.ph', 'https://www.bts.edu.ph'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
        'api_key_required' => true,
    ],
    
    // Logging and Monitoring
    'logging' => [
        'log_errors' => true,
        'log_security_events' => true,
        'log_failed_logins' => true,
        'log_file_access' => true,
        'retention_days' => 90,
        'encrypt_logs' => true,
    ],
    
    // User Account Security
    'user_security' => [
        'email_verification_required' => true,
        'two_factor_authentication' => false, // Can be enabled per user
        'account_lockout' => true,
        'max_failed_attempts' => 5,
        'lockout_duration' => 1800, // 30 minutes
        'require_strong_passwords' => true,
    ],
    
    // Admin Security Settings
    'admin_security' => [
        'restrict_by_ip' => false,
        'allowed_admin_ips' => ['127.0.0.1', '::1'], // Localhost only by default
        'require_two_factor' => true,
        'session_timeout' => 1800, // 30 minutes
        'audit_all_actions' => true,
    ],
    
    // Security Headers
    'security_headers' => [
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'strict_transport_security' => 'max-age=31536000; includeSubDomains',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'permissions_policy' => 'geolocation=(), microphone=(), camera=()',
    ],
    
    // Backup and Recovery
    'backup_security' => [
        'encrypt_backups' => true,
        'backup_retention_days' => 30,
        'test_restoration' => true,
        'backup_frequency' => 'daily',
        'secure_storage' => true,
    ],
    
    // Monitoring and Alerts
    'monitoring' => [
        'enable_intrusion_detection' => true,
        'alert_on_failed_logins' => true,
        'alert_on_suspicious_activity' => true,
        'alert_on_file_changes' => true,
        'alert_email' => 'security@bts.edu.ph',
    ],
    
    // Maintenance Mode Settings
    'maintenance_mode' => [
        'enabled' => false,
        'allowed_ips' => ['127.0.0.1', '::1'],
        'message' => 'System is currently under maintenance. Please check back later.',
        'estimated_completion' => null,
    ],
];

// Environment-specific overrides
if (file_exists(__DIR__ . '/security.local.php')) {
    $localConfig = include __DIR__ . '/security.local.php';
    $config = array_merge_recursive($config, $localConfig);
}

return $config;