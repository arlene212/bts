<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('admin');

header('Content-Type: application/json');

if (!isset($_GET['trainer_id'])) {
    echo json_encode([]);
    exit;
}

$database = new DatabaseConnection();
$pdo = $database->getConnection();

$stmt = $pdo->prepare("SELECT course_code FROM course_assignments WHERE trainer_id = ?");
$stmt->execute([$_GET['trainer_id']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($courses);
?>