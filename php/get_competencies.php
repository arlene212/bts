<?php
require_once 'DatabaseConnection.php';
header('Content-Type: application/json');

try {
  $database = new DatabaseConnection();
  $pdo = $database->getConnection();
  $courseIdParam = isset($_GET['course_id']) ? trim((string)$_GET['course_id']) : '';
  $courseCodeParam = isset($_GET['course_code']) ? trim((string)$_GET['course_code']) : '';
  $status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';

  $courseId = null;
  if ($courseIdParam !== '') { $courseId = (int)$courseIdParam; }
  elseif ($courseCodeParam !== '') {
    $cs = $pdo->prepare("SELECT id FROM courses WHERE course_code = ?");
    $cs->execute([$courseCodeParam]);
    $row = $cs->fetch(PDO::FETCH_ASSOC);
    $courseId = $row ? (int)$row['id'] : null;
  }

  $sql = "SELECT id, course_id, unit_order, competency_code, competency_name, module_title, competency_type, nominal_hours, description, learning_outcomes, status, date_created FROM competencies";
  $args = [];
  $where = [];
  if ($courseId !== null) { $where[] = "course_id = ?"; $args[] = $courseId; }
  if ($status !== '') { $where[] = "status = ?"; $args[] = $status; }
  if (!empty($where)) { $sql .= " WHERE " . implode(" AND ", $where); }
  $sql .= " ORDER BY competency_type, competency_name";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($args);
  echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
  echo json_encode(['error' => 'Database error']);
}
?>
