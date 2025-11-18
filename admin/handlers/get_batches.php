<?php
require_once __DIR__ . '/../../php/DatabaseConnection.php';
header('Content-Type: application/json');
$courseCode = $_GET['course_code'] ?? $_POST['course_code'] ?? '';
try {
  $database = new DatabaseConnection();
  $pdo = $database->getConnection();
  if (!$courseCode) { echo json_encode([]); exit; }
  $stmt = $pdo->prepare("SELECT batch_name FROM course_batches WHERE course_code = ? ORDER BY batch_name ASC");
  $stmt->execute([$courseCode]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($rows);
} catch (Exception $e) {
  echo json_encode([]);
}
?>