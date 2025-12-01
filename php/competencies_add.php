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
  $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
  $name = trim((string)($_POST['competency_name'] ?? ''));
  $type = trim((string)($_POST['competency_type'] ?? ''));
  $desc = trim((string)($_POST['description'] ?? ''));
  $moduleTitle = trim((string)($_POST['module_title'] ?? ''));
  $learningOutcomes = (string)($_POST['learning_outcomes'] ?? '');
  $nominalHours = isset($_POST['nominal_hours']) ? (int)$_POST['nominal_hours'] : 0;
  if ($courseId <= 0 || $name === '' || !in_array($type, ['basic','common','core'])) { echo json_encode(['success' => false, 'message' => 'Invalid fields']); exit; }
  if ($moduleTitle === '' || mb_strlen($moduleTitle) > 255) { echo json_encode(['success' => false, 'message' => 'Invalid module title']); exit; }
  if ($nominalHours <= 0) { echo json_encode(['success' => false, 'message' => 'Nominal hours must be positive']); exit; }
  // Generate competency_code: <COURSE_CODE>-<Type>-<N>
  $cstmt = $pdo->prepare("SELECT course_code FROM courses WHERE id = ?");
  $cstmt->execute([$courseId]);
  $course = $cstmt->fetch(PDO::FETCH_ASSOC);
  if (!$course) { echo json_encode(['success' => false, 'message' => 'Course not found']); exit; }
  $courseCode = $course['course_code'];
  // Determine next number for this type
  $cntStmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM competencies WHERE course_id = ? AND competency_type = ?");
  $cntStmt->execute([$courseId, $type]);
  $num = (int)$cntStmt->fetchColumn() + 1;
  $code = $courseCode . '-' . ucfirst($type) . '-' . $num;
  // Ensure uniqueness in rare race conditions
  $uniqStmt = $pdo->prepare("SELECT id FROM competencies WHERE competency_code = ?");
  while (true) {
    $uniqStmt->execute([$code]);
    if (!$uniqStmt->fetch()) break;
    $num++;
    $code = $courseCode . '-' . ucfirst($type) . '-' . $num;
  }
  $stmt = $pdo->prepare("INSERT INTO competencies (course_id, unit_order, competency_code, competency_name, module_title, competency_type, nominal_hours, description, learning_outcomes, status, date_created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
  $stmt->execute([$courseId, $num, $code, $name, $moduleTitle, $type, $nominalHours, $desc, $learningOutcomes]);
  // Recompute course nominal hours total
  try {
    $sumStmt = $pdo->prepare("UPDATE courses SET nominal_hours = (SELECT COALESCE(SUM(nominal_hours),0) FROM competencies WHERE course_id = ? AND status = 'active') WHERE id = ?");
    $sumStmt->execute([$courseId, $courseId]);
  } catch (Exception $__) {}
  echo json_encode(['success' => true]);
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
