<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('admin');

header('Content-Type: application/json');

if (!isset($_GET['trainee_id'])) {
    echo json_encode(['error' => 'Trainee ID required']);
    exit;
}

$database = new DatabaseConnection();
$pdo = $database->getConnection();

$stmt = $pdo->prepare("SELECT DISTINCT course_code FROM enrollments WHERE trainee_id = ? AND completion_date IS NOT NULL");
$stmt->execute([$_GET['trainee_id']]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$codes = array_map(function($r){ return $r['course_code']; }, $rows);

echo json_encode(['course_codes' => $codes]);
?>
