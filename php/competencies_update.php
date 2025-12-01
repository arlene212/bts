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
  $moduleTitle = trim((string)($_POST['module_title'] ?? ''));
  $learningOutcomes = (string)($_POST['learning_outcomes'] ?? '');
  $nominalHours = isset($_POST['nominal_hours']) ? (int)$_POST['nominal_hours'] : null;
  $status = trim((string)($_POST['status'] ?? 'active'));
  if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Missing id']); exit; }
  if (!in_array($type, ['basic','common','core'])) { echo json_encode(['success' => false, 'message' => 'Invalid type']); exit; }
  if (!in_array($status, ['active','archived'])) { echo json_encode(['success' => false, 'message' => 'Invalid status']); exit; }
  $stmt = $pdo->prepare("UPDATE competencies SET competency_code = ?, competency_name = ?, module_title = ?, competency_type = ?, nominal_hours = ?, description = ?, learning_outcomes = ?, status = ? WHERE id = ?");
  $stmt->execute([$code, $name, $moduleTitle, $type, $nominalHours, $desc, $learningOutcomes, $status, $id]);
  // Recompute course nominal hours total
  try {
    $cidStmt = $pdo->prepare("SELECT course_id FROM competencies WHERE id = ?");
    $cidStmt->execute([$id]);
    $courseId = (int)($cidStmt->fetchColumn() ?: 0);
    if ($courseId > 0) {
      $sumStmt = $pdo->prepare("UPDATE courses SET nominal_hours = (SELECT COALESCE(SUM(nominal_hours),0) FROM competencies WHERE course_id = ? AND status = 'active') WHERE id = ?");
      $sumStmt->execute([$courseId, $courseId]);
    }
  } catch (Exception $__) {}
  echo json_encode(['success' => true]);
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
