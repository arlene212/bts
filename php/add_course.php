<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Invalid request method']);
  exit;
}

try {
  $database = new DatabaseConnection();
  $pdo = $database->getConnection();

  $courseName = trim((string)($_POST['course_name'] ?? ''));
  $courseCode = trim((string)($_POST['course_code'] ?? ''));
  $courseHours = (int)($_POST['course_hours'] ?? 0);
  $courseDescription = (string)($_POST['course_description'] ?? '');
  $learningOutcomes = '';
  $courseStatus = (string)($_POST['course_status'] ?? 'published');
  $allowPreview = (int)($_POST['allow_preview'] ?? 0);
  $previewContent = (string)($_POST['course_preview_content'] ?? '');
  $requireVerification = (int)($_POST['require_verification'] ?? 0);
  $verificationType = (string)($_POST['verification_type'] ?? 'email');
  $scheduleDaysPerWeek = isset($_POST['schedule_days_per_week']) ? (int)$_POST['schedule_days_per_week'] : null;
  $scheduleDays = isset($_POST['schedule_days']) && is_array($_POST['schedule_days']) ? implode(',', array_map('trim', $_POST['schedule_days'])) : '';
  $sessionHours = isset($_POST['session_hours']) ? trim((string)$_POST['session_hours']) : null;

  if ($courseName === '' || $courseCode === '' || $courseHours <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
  }

  // Duplicate course code check
  $dup = $pdo->prepare('SELECT 1 FROM courses WHERE course_code = ? LIMIT 1');
  $dup->execute([$courseCode]);
  if ($dup->fetchColumn()) {
    echo json_encode(['success' => false, 'message' => 'Course code already exists']);
    exit;
  }

  // Handle image upload (optional)
  $courseImage = '';
  if (isset($_FILES['course_image']) && $_FILES['course_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../uploads/courses/';
    if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }
    $fileName = uniqid() . '_' . basename($_FILES['course_image']['name']);
    $targetPath = $uploadDir . $fileName;
    if (@move_uploaded_file($_FILES['course_image']['tmp_name'], $targetPath)) {
      $courseImage = $fileName;
    }
  }

  // Insert course
  $ins = $pdo->prepare('INSERT INTO courses (course_name, competency_name, module_title, course_code, hours, nominal_hours, description, learning_outcomes, course_status, allow_preview, preview_content, require_verification, verification_type, image, schedule_days_per_week, schedule_days, session_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
  $ins->execute([$courseName, $courseName, $courseName, $courseCode, $courseHours, 0, $courseDescription, $learningOutcomes, $courseStatus, $allowPreview, $previewContent, $requireVerification, $verificationType, $courseImage, $scheduleDaysPerWeek, $scheduleDays, $sessionHours]);

  // Get course id
  $cidStmt = $pdo->prepare('SELECT id FROM courses WHERE course_code = ?');
  $cidStmt->execute([$courseCode]);
  $courseRow = $cidStmt->fetch(PDO::FETCH_ASSOC);
  $courseId = $courseRow ? (int)$courseRow['id'] : 0;

  // Insert competencies
  if ($courseId > 0) {
    $insComp = $pdo->prepare('INSERT INTO competencies (course_id, unit_order, competency_code, competency_name, module_title, competency_type, nominal_hours, description, learning_outcomes, status, date_created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'active\', NOW())');
    $makeCode = function($code, $type, $num){ return $code . '-' . ucfirst($type) . '-' . $num; };

    $basicList = (array)($_POST['basic_competency'] ?? []);
    $basicDesc = (array)($_POST['basic_competency_desc'] ?? []);
    foreach ($basicList as $idx => $name) {
      $name = trim((string)$name);
      $mod = trim((string)($_POST['basic_module_title'][$idx] ?? ''));
      $hrs = isset($_POST['basic_nominal_hours'][$idx]) ? (int)$_POST['basic_nominal_hours'][$idx] : 0;
      $lo = (string)($_POST['basic_learning_outcomes'][$idx] ?? '');
      $desc = trim((string)($basicDesc[$idx] ?? ''));
      if ($name !== '' && $mod !== '' && $hrs > 0) {
        $codeVal = $makeCode($courseCode, 'basic', $idx + 1);
        $insComp->execute([$courseId, ($idx + 1), $codeVal, $name, $mod, 'basic', $hrs, $desc, $lo]);
      }
    }

    $commonList = (array)($_POST['common_competency'] ?? []);
    $commonDesc = (array)($_POST['common_competency_desc'] ?? []);
    foreach ($commonList as $idx => $name) {
      $name = trim((string)$name);
      $mod = trim((string)($_POST['common_module_title'][$idx] ?? ''));
      $hrs = isset($_POST['common_nominal_hours'][$idx]) ? (int)$_POST['common_nominal_hours'][$idx] : 0;
      $lo = (string)($_POST['common_learning_outcomes'][$idx] ?? '');
      $desc = trim((string)($commonDesc[$idx] ?? ''));
      if ($name !== '' && $mod !== '' && $hrs > 0) {
        $codeVal = $makeCode($courseCode, 'common', $idx + 1);
        $insComp->execute([$courseId, ($idx + 1), $codeVal, $name, $mod, 'common', $hrs, $desc, $lo]);
      }
    }

    $coreList = (array)($_POST['core_competency'] ?? []);
    $coreDesc = (array)($_POST['core_competency_desc'] ?? []);
    foreach ($coreList as $idx => $name) {
      $name = trim((string)$name);
      $mod = trim((string)($_POST['core_module_title'][$idx] ?? ''));
      $hrs = isset($_POST['core_nominal_hours'][$idx]) ? (int)$_POST['core_nominal_hours'][$idx] : 0;
      $lo = (string)($_POST['core_learning_outcomes'][$idx] ?? '');
      $desc = trim((string)($coreDesc[$idx] ?? ''));
      if ($name !== '' && $mod !== '' && $hrs > 0) {
        $codeVal = $makeCode($courseCode, 'core', $idx + 1);
        $insComp->execute([$courseId, ($idx + 1), $codeVal, $name, $mod, 'core', $hrs, $desc, $lo]);
      }
    }

    try {
      $sumStmt = $pdo->prepare('UPDATE courses SET nominal_hours = (SELECT COALESCE(SUM(nominal_hours),0) FROM competencies WHERE course_id = ? AND status = \'active\') WHERE id = ?');
      $sumStmt->execute([$courseId, $courseId]);
    } catch (Exception $__) {}
  }

  echo json_encode(['success' => true, 'message' => 'Course added successfully']);
} catch (PDOException $e) {
  error_log('Database error in add_course.php: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
  error_log('Error in add_course.php: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
