<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';
SessionManager::startSession();
SessionManager::requireRole('admin');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'message' => 'Invalid method']); exit; }

try {
  $database = new DatabaseConnection();
  $pdo = $database->getConnection();
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $code = trim((string)($_POST['competency_code'] ?? ''));
  $name = trim((string)($_POST['competency_name'] ?? ''));
  $type = trim((string)($_POST['competency_type'] ?? ''));
  $desc = trim((string)($_POST['description'] ?? ''));
  $status = trim((string)($_POST['status'] ?? 'active'));
  if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Missing id']); exit; }
  if (!in_array($type, ['basic','common','core'])) { echo json_encode(['success' => false, 'message' => 'Invalid type']); exit; }
  if (!in_array($status, ['active','archived'])) { echo json_encode(['success' => false, 'message' => 'Invalid status']); exit; }
  $stmt = $pdo->prepare("UPDATE competencies SET competency_code = ?, competency_name = ?, competency_type = ?, description = ?, status = ? WHERE id = ?");
  $stmt->execute([$code, $name, $type, $desc, $status, $id]);
  echo json_encode(['success' => true]);
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
