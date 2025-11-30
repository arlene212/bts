<?php
require_once __DIR__ . '/../../php/DatabaseConnection.php';
require_once __DIR__ . '/../../php/SessionManager.php';

header('Content-Type: application/json');
SessionManager::startSession();
SessionManager::requireRole('trainer');

try {
  $trainerId = $_SESSION['user']['user_id'] ?? null;
  $courseCode = $_POST['course_code'] ?? '';
  $competencyId = isset($_POST['competency_id']) ? (int)$_POST['competency_id'] : 0;
  $title = trim($_POST['title'] ?? '');
  $description = trim($_POST['description'] ?? '');

  if (!$trainerId || !$courseCode || !$competencyId || !$title) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
  }

  if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'File upload failed']);
    exit;
  }

  $db = new DatabaseConnection();
  $pdo = $db->getConnection();

  $chk = $pdo->prepare("SELECT 1 FROM course_assignments WHERE course_code = ? AND trainer_id = ?");
  $chk->execute([$courseCode, $trainerId]);
  if (!$chk->fetch()) { echo json_encode(['error' => 'Access denied']); exit; }

  $compTypeStmt = $pdo->prepare("SELECT competency_type FROM competencies WHERE id = ?");
  $compTypeStmt->execute([$competencyId]);
  $compType = $compTypeStmt->fetchColumn();
  if (!$compType) { echo json_encode(['error' => 'Invalid competency']); exit; }

  $allowed = ['pdf','mp4','webm','mov','mkv','txt','md','docx','pptx'];
  $origName = $_FILES['file']['name'];
  $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
  if (!in_array($ext, $allowed, true)) { echo json_encode(['error' => 'Unsupported file type']); exit; }

  $contentType = 'pdf';
  if (in_array($ext, ['mp4','webm','mov','mkv'], true)) $contentType = 'video';
  elseif (in_array($ext, ['txt','md'], true)) $contentType = 'text';
  else $contentType = 'pdf';

  $targetDir = __DIR__ . '/../../uploads/courses/';
  if (!is_dir($targetDir)) { @mkdir($targetDir, 0775, true); }
  $safeBase = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', pathinfo($origName, PATHINFO_FILENAME));
  $filename = date('Ymd_His') . '_' . $safeBase . '.' . $ext;
  $targetPath = $targetDir . $filename;
  if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
    echo json_encode(['error' => 'Failed to move uploaded file']);
    exit;
  }

  $ins = $pdo->prepare("INSERT INTO course_materials (course_code, competency_id, title, content_type, file_path, content, competency_type, date_created) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
  $ins->execute([$courseCode, $competencyId, $title, $contentType, $filename, $description !== '' ? $description : null, $compType]);

  echo json_encode(['success' => true]);
} catch (Throwable $e) {
  error_log('module_upload error: ' . $e->getMessage());
  echo json_encode(['error' => 'Server error']);
}
