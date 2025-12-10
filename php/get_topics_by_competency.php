<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('trainer');

header('Content-Type: application/json');

$courseCode = $_GET['course_code'] ?? '';
$competencyId = isset($_GET['competency_id']) ? intval($_GET['competency_id']) : 0;
$trainerId = $_SESSION['user']['user_id'] ?? 0;

if (!$courseCode || $competencyId <= 0) { echo json_encode([]); exit; }

try {
  $pdo = (new DatabaseConnection())->getConnection();
  $chk = $pdo->prepare("SELECT 1 FROM course_assignments WHERE trainer_id = ? AND course_code = ? LIMIT 1");
  $chk->execute([$trainerId, $courseCode]);
  if (!$chk->fetchColumn()) { echo json_encode([]); exit; }
  $stmt = $pdo->prepare("SELECT id, topic_name AS name, topic_name FROM course_topics WHERE course_code = ? AND competency_id = ? ORDER BY created_at ASC");
  $stmt->execute([$courseCode, $competencyId]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($rows ?: []);
} catch (Throwable $e) {
  echo json_encode([]);
}
?>
