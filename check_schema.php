<?php
// Check database schema to see what columns already exist
require_once 'php/DatabaseConnection.php';
require_once 'php/config.php';

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();
    
    echo "=== DATABASE SCHEMA CHECK ===<br><br>";
    
    // Check courses table structure
    echo "COURSES TABLE COLUMNS:<br>";
    $stmt = $pdo->query("DESCRIBE courses");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})<br>";
    }
    
    echo "<br>=== EXISTING COLUMNS CHECK ===<br><br>";
    
    // Check for specific columns that might already exist
    $checkColumns = [
        'learning_outcomes',
        'course_status', 
        'allow_preview',
        'preview_content',
        'require_verification',
        'verification_type',
        'branch_id'
    ];
    
    $existingColumns = array_column($columns, 'Field');
    
    foreach ($checkColumns as $column) {
        if (in_array($column, $existingColumns)) {
            echo "✓ Column '$column' already exists<br>";
        } else {
            echo "✗ Column '$column' needs to be added<br>";
        }
    }
    
    echo "<br>=== USERS TABLE CHECK ===<br><br>";
    
    // Check users table
    $stmt = $pdo->query("DESCRIBE users");
    $userColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($userColumns as $column) {
        echo "- {$column['Field']} ({$column['Type']})<br>";
    }
    
    echo "<br>=== ENROLLMENTS TABLE CHECK ===<br><br>";
    
    // Check enrollments table
    $stmt = $pdo->query("DESCRIBE enrollments");
    $enrollmentColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($enrollmentColumns as $column) {
        echo "- {$column['Field']} ({$column['Type']})<br>";
    }
    
} catch (Exception $e) {
    echo "Database connection failed!<br>";
    echo "Error: " . $e->getMessage() . "<br>";
}