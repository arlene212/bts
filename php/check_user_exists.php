<?php
require_once 'DatabaseConnection.php';

header('Content-Type: application/json');

$database = new DatabaseConnection();
$pdo = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $user_id = trim($_POST['user_id'] ?? '');
    $role = trim($_POST['role'] ?? '');
    
    $exists = false;
    
    try {
        // Check if user exists with same first name and last name
        if (!empty($firstName) && !empty($lastName)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE first_name = ? AND last_name = ? AND role = ?");
            $stmt->execute([$firstName, $lastName, $role]);
            $exists = $stmt->fetchColumn() > 0;
        }
        
        // Also check if user ID already exists
        if (!$exists && !empty($user_id)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $exists = $stmt->fetchColumn() > 0;
        }
        
        echo json_encode(['exists' => $exists]);
        
    } catch (PDOException $e) {
        error_log("Error checking user existence: " . $e->getMessage());
        echo json_encode(['exists' => false, 'error' => $e->getMessage()]);
    }
}
?>