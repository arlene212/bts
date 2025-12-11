<?php
require_once __DIR__ . '/DatabaseConnection.php';
require_once __DIR__ . '/SessionManager.php';
require_once __DIR__ . '/ErrorHandler.php';

SessionManager::startSession();
SessionManager::requireRole('guest');

function forbidden($msg) {
  ErrorHandler::getInstance()->handle403($msg);
}

$source = $_GET['source'] ?? '';
$materialId = isset($_GET['material_id']) ? (int)$_GET['material_id'] : 0;
if (!$materialId || ($source !== 'topic' && $source !== 'course')) { forbidden('Invalid request'); }

try {
  $db = (new DatabaseConnection())->getConnection();
  $guestId = $_SESSION['user']['user_id'] ?? null;
  if (!$guestId) forbidden('Unauthorized');

  $filePath = null; $courseCode = null; $resourceId = $materialId;
  if ($source === 'topic') {
    $stmt = $db->prepare("SELECT tm.file_path, ct.course_code, comp.competency_type 
                           FROM topic_materials tm 
                           JOIN course_topics ct ON tm.topic_id = ct.id 
                           LEFT JOIN competencies comp ON (comp.id = ct.competency_id OR comp.competency_code = ct.competency_id) 
                           WHERE tm.id = ? LIMIT 1");
    $stmt->execute([$materialId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
  } else {
    $stmt = $db->prepare("SELECT cm.file_path, cm.course_code, comp.competency_type 
                           FROM course_materials cm 
                           LEFT JOIN competencies comp ON comp.id = cm.competency_id 
                           WHERE cm.id = ? LIMIT 1");
    $stmt->execute([$materialId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
  }
  if (!$row) forbidden('Material not found');
  $filePath = $row['file_path'] ?? '';
  $courseCode = $row['course_code'] ?? '';
  $ctype = $row['competency_type'] ?? '';
  if (!$courseCode) forbidden('Invalid material');
  if (!empty($ctype) && strtolower($ctype) !== 'basic') forbidden('Restricted: Only basic competencies are accessible');

  $en = $db->prepare("SELECT batch_name FROM enrollments WHERE trainee_id = ? AND course_code = ? AND status = 'approved' LIMIT 1");
  $en->execute([$guestId, $courseCode]);
  $batchName = $en->fetchColumn();
  if ($batchName === false) forbidden('Access denied');

  try {
    $tchk = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'batch_resources'");
    $tchk->execute();
    $hasBr = ((int)$tchk->fetchColumn() > 0);
    if ($hasBr && !empty($batchName)) {
      // Only enforce mapping when there are any mappings for this batch/course
      $cntStmt = $db->prepare("SELECT COUNT(*) FROM batch_resources WHERE course_code = ? AND batch_name = ?");
      $cntStmt->execute([$courseCode, $batchName]);
      $hasMappings = ((int)$cntStmt->fetchColumn() > 0);
      if ($hasMappings) {
        $map = $db->prepare("SELECT 1 FROM batch_resources WHERE course_code = ? AND batch_name = ? AND resource_type = 'material' AND resource_id = ?");
        $map->execute([$courseCode, $batchName, $resourceId]);
        if (!$map->fetch()) forbidden('Access denied');
      }
    }
  } catch (Throwable $__) {}

  if (!$filePath) forbidden('File not available');
  if (preg_match('/^https?:\/\//i', $filePath)) {
    header('Location: ' . $filePath);
    exit;
  }

  $full = __DIR__ . '/../Uploads/courses/' . $filePath;
  if (!is_file($full) || !is_readable($full)) forbidden('File not found');

  $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
  $mime = $finfo ? finfo_file($finfo, $full) : 'application/octet-stream';
  if ($finfo) finfo_close($finfo);

  header('Content-Type: ' . $mime);
  header('Content-Disposition: attachment; filename="' . basename($full) . '"');
  header('Content-Length: ' . filesize($full));
  header('Cache-Control: private');
  readfile($full);
  exit;
} catch (Throwable $e) {
  forbidden('Server error');
}
?>
