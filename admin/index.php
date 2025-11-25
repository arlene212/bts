<?php
require_once __DIR__ . '/../php/SessionManager.php';
require_once __DIR__ . '/../php/DatabaseConnection.php';
require_once __DIR__ . '/includes/functions.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

SessionManager::startSession();
SessionManager::requireRole('admin');

$user = SessionManager::getCurrentUser();

$database = new DatabaseConnection();
$pdo = $database->getConnection();

try {
  $pdo->query("SELECT 1");
} catch (PDOException $e) {
  error_log("Database connection failed: " . $e->getMessage());
  die("Database connection failed. Please try again later.");
}

try {
  $userCheck = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? AND status = 'active'");
  $userCheck->execute([$user['user_id']]);
  $validUser = $userCheck->fetch();
  if (!$validUser) {
    session_destroy();
    header("Location: ./login.php");
    exit;
  }
} catch (Exception $e) {
  error_log("User validation error: " . $e->getMessage());
}

try { autoArchiveEndedBatches($pdo); } catch (Exception $__) {}

if (isset($_GET['download_backup'])) {
  require_once __DIR__ . '/includes/functions.php';
  $file = $_GET['backup_file'] ?? '';
  $dir = ensureBackupDir();
  $path = realpath($dir . DIRECTORY_SEPARATOR . $file);
  if (!$path || strpos($path, $dir) !== 0 || !preg_match('/\.sql$/i', $file)) {
    header('HTTP/1.1 404 Not Found');
    exit;
  }
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="' . basename($path) . '"');
  header('Content-Length: ' . filesize($path));
  header('Cache-Control: private, max-age=0, must-revalidate');
  header('Pragma: public');
  readfile($path);
  exit;
}

if (isset($_GET['download_full_backup'])) {
  require_once __DIR__ . '/../php/config.php';
  $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
  if (!file_exists($mysqldump)) {
    $mysqldump = 'mysqldump';
  }
  $filename = DB_NAME . '_' . date('Ymd_His') . '.sql';
  header('Content-Type: application/sql');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  header('Cache-Control: private, max-age=0, must-revalidate');
  header('Pragma: public');
  @set_time_limit(0);
  if (function_exists('ob_get_level')) {
    while (ob_get_level()) {
      ob_end_clean();
    }
  }
  $cmd = '"' . $mysqldump . '"'
    . ' --host=' . escapeshellarg(DB_HOST)
    . ' --user=' . escapeshellarg(DB_USER)
    . ' --password=' . escapeshellarg(DB_PASS)
    . ' --routines --triggers --events --single-transaction --hex-blob'
    . ' --databases ' . escapeshellarg(DB_NAME);
  if (function_exists('passthru')) {
    passthru($cmd);
  } else {
    echo shell_exec($cmd . ' 2>&1');
  }
  exit;
}

if (rand(1, 10) === 1) {
  cleanupInactiveGuests($pdo);
}

$uploadDirs = [__DIR__ . '/../uploads/profiles/', __DIR__ . '/../uploads/courses/', __DIR__ . '/../uploads/activities/', __DIR__ . '/../uploads/submissions/'];
foreach ($uploadDirs as $dir) {
  if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
  }
}

$itemsPerPage = 10;

$trainerSearch = $_GET['trainer_search'] ?? '';
$trainerPage = max(1, intval($_GET['trainer_page'] ?? 1));
$trainerOffset = ($trainerPage - 1) * $itemsPerPage;
$activeTrainerQuery = "SELECT * FROM users WHERE role = 'trainer' AND status = 'active'";
$activeTrainerCountQuery = "SELECT COUNT(*) FROM users WHERE role = 'trainer' AND status = 'active'";
if (!empty($trainerSearch)) {
  $activeTrainerQuery .= " AND (first_name LIKE :search OR last_name LIKE :search OR user_id LIKE :search OR email LIKE :search)";
  $activeTrainerCountQuery .= " AND (first_name LIKE :search OR last_name LIKE :search OR user_id LIKE :search OR email LIKE :search)";
}
$activeTrainerQuery .= " ORDER BY date_created DESC LIMIT :limit OFFSET :offset";
$trainerCountStmt = $pdo->prepare($activeTrainerCountQuery);
if (!empty($trainerSearch)) {
  $trainerSearchParam = "%$trainerSearch%";
  $trainerCountStmt->bindValue(':search', $trainerSearchParam);
}
$trainerCountStmt->execute();
$totalActiveTrainers = $trainerCountStmt->fetchColumn();
$totalActiveTrainerPages = ceil($totalActiveTrainers / $itemsPerPage);
$activeTrainerStmt = $pdo->prepare($activeTrainerQuery);
if (!empty($trainerSearch)) {
  $activeTrainerStmt->bindValue(':search', $trainerSearchParam);
}
$activeTrainerStmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$activeTrainerStmt->bindValue(':offset', $trainerOffset, PDO::PARAM_INT);
$activeTrainerStmt->execute();
$activeTrainers = $activeTrainerStmt->fetchAll();

$archivedTrainerPage = max(1, intval($_GET['archived_trainer_page'] ?? 1));
$archivedTrainerOffset = ($archivedTrainerPage - 1) * $itemsPerPage;
$archivedTrainerQuery = "SELECT * FROM users WHERE role = 'trainer' AND status = 'archived'";
$archivedTrainerCountQuery = "SELECT COUNT(*) FROM users WHERE role = 'trainer' AND status = 'archived'";
if (!empty($trainerSearch)) {
  $archivedTrainerQuery .= " AND (first_name LIKE :search OR last_name LIKE :search OR user_id LIKE :search OR email LIKE :search)";
  $archivedTrainerCountQuery .= " AND (first_name LIKE :search OR last_name LIKE :search OR user_id LIKE :search OR email LIKE :search)";
}
$archivedTrainerQuery .= " ORDER BY date_created DESC LIMIT :limit OFFSET :offset";
$archivedTrainerCountStmt = $pdo->prepare($archivedTrainerCountQuery);
if (!empty($trainerSearch)) {
  $archivedTrainerCountStmt->bindValue(':search', $trainerSearchParam);
}
$archivedTrainerCountStmt->execute();
$totalArchivedTrainers = $archivedTrainerCountStmt->fetchColumn();
$totalArchivedTrainerPages = ceil($totalArchivedTrainers / $itemsPerPage);
$archivedTrainerStmt = $pdo->prepare($archivedTrainerQuery);
if (!empty($trainerSearch)) {
  $archivedTrainerStmt->bindValue(':search', $trainerSearchParam);
}
$archivedTrainerStmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$archivedTrainerStmt->bindValue(':offset', $archivedTrainerOffset, PDO::PARAM_INT);
$archivedTrainerStmt->execute();
$archivedTrainers = $archivedTrainerStmt->fetchAll();

$traineeSearch = $_GET['trainee_search'] ?? '';
$traineePage = max(1, intval($_GET['trainee_page'] ?? 1));
$traineeOffset = ($traineePage - 1) * $itemsPerPage;
$traineeQuery = "SELECT * FROM users WHERE role = 'trainee' AND status = 'active'";
$traineeCountQuery = "SELECT COUNT(*) FROM users WHERE role = 'trainee' AND status = 'active'";
if (!empty($traineeSearch)) {
  $traineeQuery .= " AND (first_name LIKE :search OR last_name LIKE :search OR user_id LIKE :search OR email LIKE :search)";
  $traineeCountQuery .= " AND (first_name LIKE :search OR last_name LIKE :search OR user_id LIKE :search OR email LIKE :search)";
}
$traineeQuery .= " ORDER BY date_created DESC LIMIT :trainee_limit OFFSET :trainee_offset";
$traineeStmt = $pdo->prepare($traineeCountQuery);
if (!empty($traineeSearch)) {
  $traineeSearchParam = "%$traineeSearch%";
  $traineeStmt->bindValue(':search', $traineeSearchParam);
}
$traineeStmt->execute();
$totalTrainees = $traineeStmt->fetchColumn();
$totalActiveTraineePages = ceil($totalTrainees / $itemsPerPage);
$traineeStmt = $pdo->prepare($traineeQuery);
if (!empty($traineeSearch)) {
  $traineeStmt->bindValue(':search', $traineeSearchParam);
}
$traineeStmt->bindValue(':trainee_limit', $itemsPerPage, PDO::PARAM_INT);
$traineeStmt->bindValue(':trainee_offset', $traineeOffset, PDO::PARAM_INT);
$traineeStmt->execute();
$activeTrainees = $traineeStmt->fetchAll();

$archivedTraineePage = max(1, intval($_GET['archived_trainee_page'] ?? 1));
$archivedTraineeOffset = ($archivedTraineePage - 1) * $itemsPerPage;
$archivedTraineeQuery = "SELECT * FROM users WHERE role = 'trainee' AND status = 'archived'";
$archivedTraineeCountQuery = "SELECT COUNT(*) FROM users WHERE role = 'trainee' AND status = 'archived'";
if (!empty($traineeSearch)) {
  $archivedTraineeQuery .= " AND (first_name LIKE :search OR last_name LIKE :search OR user_id LIKE :search OR email LIKE :search)";
  $archivedTraineeCountQuery .= " AND (first_name LIKE :search OR last_name LIKE :search OR user_id LIKE :search OR email LIKE :search)";
}
$archivedTraineeQuery .= " ORDER BY date_created DESC LIMIT :limit OFFSET :offset";
$archivedTraineeCountStmt = $pdo->prepare($archivedTraineeCountQuery);
if (!empty($traineeSearch)) {
  $archivedTraineeCountStmt->bindValue(':search', $traineeSearchParam);
}
$archivedTraineeCountStmt->execute();
$totalArchivedTrainees = $archivedTraineeCountStmt->fetchColumn();
$totalArchivedTraineePages = ceil($totalArchivedTrainees / $itemsPerPage);
$archivedTraineeStmt = $pdo->prepare($archivedTraineeQuery);
if (!empty($traineeSearch)) {
  $archivedTraineeStmt->bindValue(':search', $traineeSearchParam);
}
$archivedTraineeStmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$archivedTraineeStmt->bindValue(':offset', $archivedTraineeOffset, PDO::PARAM_INT);
$archivedTraineeStmt->execute();
$archivedTrainees = $archivedTraineeStmt->fetchAll();

$guestSearch = $_GET['guest_search'] ?? '';
$guestPage = max(1, intval($_GET['guest_page'] ?? 1));
$guestOffset = ($guestPage - 1) * $itemsPerPage;
$guestQuery = "SELECT * FROM users WHERE role = 'guest'";
$guestCountQuery = "SELECT COUNT(*) FROM users WHERE role = 'guest'";
if (!empty($guestSearch)) {
  $guestQuery .= " AND (first_name LIKE :guest_search1 OR last_name LIKE :guest_search2 OR user_id LIKE :guest_search3 OR email LIKE :guest_search4)";
  $guestCountQuery .= " AND (first_name LIKE :guest_search1 OR last_name LIKE :guest_search2 OR user_id LIKE :guest_search3 OR email LIKE :guest_search4)";
}
$guestQuery .= " ORDER BY date_created DESC LIMIT :guest_limit OFFSET :guest_offset";
$guestStmt = $pdo->prepare($guestCountQuery);
if (!empty($guestSearch)) {
  $guestSearchParam = "%$guestSearch%";
  $guestStmt->bindValue(':guest_search1', $guestSearchParam);
  $guestStmt->bindValue(':guest_search2', $guestSearchParam);
  $guestStmt->bindValue(':guest_search3', $guestSearchParam);
  $guestStmt->bindValue(':guest_search4', $guestSearchParam);
}
$guestStmt->execute();
$totalGuests = $guestStmt->fetchColumn();
$totalGuestPages = ceil($totalGuests / $itemsPerPage);
$guestStmt = $pdo->prepare($guestQuery);
if (!empty($guestSearch)) {
  $guestStmt->bindValue(':guest_search1', $guestSearchParam);
  $guestStmt->bindValue(':guest_search2', $guestSearchParam);
  $guestStmt->bindValue(':guest_search3', $guestSearchParam);
  $guestStmt->bindValue(':guest_search4', $guestSearchParam);
}
$guestStmt->bindValue(':guest_limit', $itemsPerPage, PDO::PARAM_INT);
$guestStmt->bindValue(':guest_offset', $guestOffset, PDO::PARAM_INT);
$guestStmt->execute();
$guests = $guestStmt->fetchAll();

$enrollmentSearch = $_GET['enrollment_search'] ?? '';
$enrollmentPage = max(1, intval($_GET['enrollment_page'] ?? 1));
$enrollmentOffset = ($enrollmentPage - 1) * $itemsPerPage;
$enrollmentQuery = "SELECT 
  e.id,
  e.trainee_id,
  e.course_code,
  e.course_name,
  e.status,
  e.date_requested,
  e.processed_date,
  e.processed_by,
  e.remarks,
  COALESCE(e.batch_name, (
    SELECT ba.batch_name 
    FROM batch_assignments ba 
    WHERE ba.trainee_id = e.trainee_id AND ba.course_code = e.course_code 
    ORDER BY ba.date_assigned DESC 
    LIMIT 1
  )) AS batch_name,
  u.first_name,
  u.last_name,
  u.role as trainee_role,
  c.course_name AS course_name
FROM enrollments e 
JOIN users u ON e.trainee_id = u.user_id 
JOIN courses c ON e.course_code = c.course_code 
WHERE e.status IN ('pending','approved')";
$enrollmentCountQuery = "SELECT COUNT(*) FROM enrollments e JOIN users u ON e.trainee_id = u.user_id JOIN courses c ON e.course_code = c.course_code WHERE e.status IN ('pending','approved')";
if (!empty($enrollmentSearch)) {
  $enrollmentQuery .= " AND (u.first_name LIKE :enrollment_search1 OR u.last_name LIKE :enrollment_search2 OR c.course_name LIKE :enrollment_search3 OR e.trainee_id LIKE :enrollment_search4)";
  $enrollmentCountQuery .= " AND (u.first_name LIKE :enrollment_search1 OR u.last_name LIKE :enrollment_search2 OR c.course_name LIKE :enrollment_search3 OR e.trainee_id LIKE :enrollment_search4)";
}
$enrollmentQuery .= " ORDER BY date_requested DESC LIMIT :enrollment_limit OFFSET :enrollment_offset";
$enrollmentStmt = $pdo->prepare($enrollmentCountQuery);
if (!empty($enrollmentSearch)) {
  $enrollmentSearchParam = "%$enrollmentSearch%";
  $enrollmentStmt->bindValue(':enrollment_search1', $enrollmentSearchParam);
  $enrollmentStmt->bindValue(':enrollment_search2', $enrollmentSearchParam);
  $enrollmentStmt->bindValue(':enrollment_search3', $enrollmentSearchParam);
  $enrollmentStmt->bindValue(':enrollment_search4', $enrollmentSearchParam);
}
$enrollmentStmt->execute();
$totalEnrollments = $enrollmentStmt->fetchColumn();
$totalEnrollmentPages = ceil($totalEnrollments / $itemsPerPage);
$enrollmentStmt = $pdo->prepare($enrollmentQuery);
if (!empty($enrollmentSearch)) {
  $enrollmentStmt->bindValue(':enrollment_search1', $enrollmentSearchParam);
  $enrollmentStmt->bindValue(':enrollment_search2', $enrollmentSearchParam);
  $enrollmentStmt->bindValue(':enrollment_search3', $enrollmentSearchParam);
  $enrollmentStmt->bindValue(':enrollment_search4', $enrollmentSearchParam);
}
$enrollmentStmt->bindValue(':enrollment_limit', $itemsPerPage, PDO::PARAM_INT);
$enrollmentStmt->bindValue(':enrollment_offset', $enrollmentOffset, PDO::PARAM_INT);
$enrollmentStmt->execute();
$enrollments = $enrollmentStmt->fetchAll();

$currentTab = $_GET['current_tab'] ?? $_POST['current_tab'] ?? 'home';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['add_course'])) {
    $courseName = $_POST['course_name'];
    $courseCode = $_POST['course_code'];
    $courseHours = $_POST['course_hours'];
    $courseDescription = $_POST['course_description'] ?? '';
    $learningOutcomes = $_POST['course_learning_outcomes'] ?? '';
    $courseStatus = $_POST['course_status'] ?? 'published';
    $allowPreview = $_POST['allow_preview'] ?? 0;
    $previewContent = $_POST['course_preview_content'] ?? '';
    $requireVerification = $_POST['require_verification'] ?? 0;
    $verificationType = $_POST['verification_type'] ?? 'email';
    $scheduleDaysPerWeek = $_POST['schedule_days_per_week'] ?? null;
    $scheduleDays = isset($_POST['schedule_days']) && is_array($_POST['schedule_days']) ? implode(',', $_POST['schedule_days']) : '';
    $sessionHours = $_POST['session_hours'] ?? null;

    $competencies = [];
    if (!empty($_POST['basic_competency'])) {
      foreach ($_POST['basic_competency'] as $index => $basicComp) {
        if (!empty(trim($basicComp))) {
          $competencies[] = [
            'type' => 'basic',
            'name' => trim($basicComp),
            'description' => trim($_POST['basic_competency_desc'][$index] ?? '')
          ];
        }
      }
    }
    if (!empty($_POST['common_competency'])) {
      foreach ($_POST['common_competency'] as $index => $commonComp) {
        if (!empty(trim($commonComp))) {
          $competencies[] = [
            'type' => 'common',
            'name' => trim($commonComp),
            'description' => trim($_POST['common_competency_desc'][$index] ?? '')
          ];
        }
      }
    }
    if (!empty($_POST['core_competency'])) {
      foreach ($_POST['core_competency'] as $index => $coreComp) {
        if (!empty(trim($coreComp))) {
          $competencies[] = [
            'type' => 'core',
            'name' => trim($coreComp),
            'description' => trim($_POST['core_competency_desc'][$index] ?? '')
          ];
        }
      }
    }
    $courseImage = '';
    if (isset($_FILES['course_image']) && $_FILES['course_image']['error'] === 0) {
      $uploadDir = __DIR__ . '/../uploads/courses/';
      $fileName = uniqid() . '_' . basename($_FILES['course_image']['name']);
      $targetPath = $uploadDir . $fileName;
      if (move_uploaded_file($_FILES['course_image']['tmp_name'], $targetPath)) {
        $courseImage = $fileName;
      }
    }
    $stmt = $pdo->prepare("INSERT INTO courses (course_name, course_code, hours, description, learning_outcomes, course_status, allow_preview, preview_content, require_verification, verification_type, image, competency_types) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    try {
      $stmt->execute([$courseName, $courseCode, $courseHours, $courseDescription, $learningOutcomes, $courseStatus, $allowPreview, $previewContent, $requireVerification, $verificationType, $courseImage, json_encode($competencies)]);
      // Safely update scheduling only if columns exist
      try {
        $colCheck = $pdo->query("SHOW COLUMNS FROM courses LIKE 'schedule_days_per_week'");
        if ($colCheck && $colCheck->rowCount() > 0) {
          $upd = $pdo->prepare("UPDATE courses SET schedule_days_per_week = ?, schedule_days = ?, session_hours = ? WHERE course_code = ?");
          $upd->execute([$scheduleDaysPerWeek, $scheduleDays, $sessionHours, $courseCode]);
        }
      } catch (Exception $__) {}
    } catch (PDOException $e) {
      $_SESSION['error_message'] = "Error adding course: " . $e->getMessage();
      header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=courses#courses");
      exit;
    }
    $_SESSION['success_message'] = "Course added successfully with " . count($competencies) . " competencies!";
    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=courses#courses");
    exit;
  }

  if (isset($_POST['add_course_batch'])) {
    $batchCourseCode = $_POST['batch_course_code'];
    $batchName = $_POST['batch_name'];
    $batchDescription = $_POST['batch_description'] ?? '';
    $batchStartDate = $_POST['batch_start_date'] ?? null;
    $batchEndDate = $_POST['batch_end_date'] ?? null;
    $checkStmt = $pdo->prepare("SELECT id FROM course_batches WHERE course_code = ? AND batch_name = ?");
    $checkStmt->execute([$batchCourseCode, $batchName]);
    if ($checkStmt->fetch()) {
      $_SESSION['error_message'] = "Batch '$batchName' already exists for this course!";
    } else {
      $stmt = $pdo->prepare("INSERT INTO course_batches (course_code, batch_name, description, created_by) VALUES (?, ?, ?, ?)");
      $stmt->execute([$batchCourseCode, $batchName, $batchDescription, $user['user_id']]);
      // Update start/end date if columns exist
      try {
        $colCheck = $pdo->query("SHOW COLUMNS FROM course_batches LIKE 'start_date'");
        if ($colCheck && $colCheck->rowCount() > 0) {
          $upd = $pdo->prepare("UPDATE course_batches SET start_date = ?, end_date = ? WHERE course_code = ? AND batch_name = ?");
          $upd->execute([$batchStartDate, $batchEndDate, $batchCourseCode, $batchName]);
        }
      } catch (Exception $__) {}
      // Record ACTIVE status for trainer in batch_assignment_status (if table exists)
      try {
        $cbStmt = $pdo->prepare("SELECT trainer_id, start_date, end_date FROM course_batches WHERE course_code = ? AND batch_name = ?");
        $cbStmt->execute([$batchCourseCode, $batchName]);
        $cbRow = $cbStmt->fetch();
        $trainerId = $cbRow['trainer_id'] ?? null;
        recordBatchStatus($pdo, $batchCourseCode, $batchName, $trainerId, null, 'active', ($cbRow['start_date'] ?? null), ($cbRow['end_date'] ?? null));
      } catch (Exception $__) {}
      $_SESSION['success_message'] = "Course batch '$batchName' created successfully!";
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=courses");
    exit;
  }

  if (isset($_POST['add_announcement'])) {
    $title = trim($_POST['announcement_title']);
    $content = trim($_POST['announcement_content']);
    if (empty($title) || empty($content)) {
      $_SESSION['error_message'] = "Please fill in both title and content.";
      header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=home#home");
      exit;
    }
    $posted_by = $user['user_id'];
    $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? AND status = 'active'");
    $checkStmt->execute([$posted_by]);
    if (!$checkStmt->fetch()) {
      $posted_by = '1000000001';
      error_log("Session user not found, using default admin ID for announcement");
    }
    try {
      $stmt = $pdo->prepare("INSERT INTO announcements (title, content, posted_by, date_posted) VALUES (?, ?, ?, NOW())");
      if ($stmt->execute([$title, $content, $posted_by])) {
        $_SESSION['success_message'] = "Announcement posted successfully!";
      } else {
        $errorInfo = $stmt->errorInfo();
        $_SESSION['error_message'] = "Error posting announcement: " . $errorInfo[2];
      }
    } catch (PDOException $e) {
      $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=home#home");
    exit;
  }

  if (isset($_POST['archive_user'])) {
    $userId = $_POST['user_id'];
    $currentTab = $_POST['current_tab'] ?? 'home';
    $stmt = $pdo->prepare("UPDATE users SET status = 'archived' WHERE user_id = ?");
    $stmt->execute([$userId]);
    $_SESSION['success_message'] = "User archived successfully!";
    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=" . $currentTab . "#" . $currentTab);
    exit;
  }

  if (isset($_POST['unarchive_user'])) {
    $userId = $_POST['user_id'];
    $currentTab = $_POST['current_tab'] ?? 'home';
    $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
    $stmt->execute([$userId]);
    $_SESSION['success_message'] = "User restored successfully!";
    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=" . $currentTab . "#" . $currentTab);
    exit;
  }

  if (isset($_POST['delete_user'])) {
    $userId = $_POST['user_id'];
    $currentTab = $_POST['current_tab'] ?? 'home';
    try {
      $pdo->beginTransaction();
      $tables = [
        'course_assignments' => ['trainer_id', 'assigned_by'],
        'batch_assignments' => ['trainee_id', 'assigned_by'],
        'enrollments' => ['trainee_id', 'processed_by']
      ];
      foreach ($tables as $table => $columns) {
        foreach ($columns as $column) {
          $deleteStmt = $pdo->prepare("DELETE FROM $table WHERE $column = ?");
          $deleteStmt->execute([$userId]);
        }
      }
      $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
      $stmt->execute([$userId]);
      $pdo->commit();
      $_SESSION['success_message'] = "User deleted successfully!";
    } catch (Exception $e) {
      $pdo->rollBack();
      $_SESSION['error_message'] = "Error deleting user: " . $e->getMessage();
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=" . $currentTab . "#" . $currentTab);
    exit;
  }

  if (isset($_POST['delete_guest'])) {
    $userId = $_POST['user_id'];
    try {
      $pdo->beginTransaction();
      $deleteEnrollments = $pdo->prepare("DELETE FROM enrollments WHERE trainee_id = ?");
      $deleteEnrollments->execute([$userId]);
      $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'guest'");
      $stmt->execute([$userId]);
      $pdo->commit();
      $_SESSION['success_message'] = "Guest account deleted successfully!";
    } catch (Exception $e) {
      $pdo->rollBack();
      $_SESSION['error_message'] = "Error deleting guest: " . $e->getMessage();
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=guests#guests");
    exit;
  }

  if (isset($_POST['guest_enroll'])) {
    $guestId = $_POST['guest_id'];
    $courseCode = $_POST['enroll_course_code'];
    try {
      $courseStmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_code = ?");
      $courseStmt->execute([$courseCode]);
      $course = $courseStmt->fetch();
      if ($course) {
        $enrollStmt = $pdo->prepare("INSERT INTO enrollments (trainee_id, course_code, course_name, status) VALUES (?, ?, ?, 'approved')");
        $enrollStmt->execute([$guestId, $courseCode, $course['course_name']]);
        $_SESSION['success_message'] = "Guest enrolled in course successfully!";
      }
    } catch (Exception $e) {
      $_SESSION['error_message'] = "Error enrolling guest: " . $e->getMessage();
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=guests#guests");
    exit;
  }

  if (isset($_POST['enrollment_action'])) {
    $enrollmentId = $_POST['enrollment_id'];
    $action = $_POST['action'];
    $remarks = $_POST['remarks'] ?? '';
    if ($action === 'approve') {
      $status = 'approved';
    } elseif ($action === 'reject') {
      $status = 'rejected';
    }
    $stmt = $pdo->prepare("UPDATE enrollments SET status = ?, remarks = ?, processed_date = NOW(), processed_by = ? WHERE id = ?");
    $stmt->execute([$status, $remarks, $user['user_id'], $enrollmentId]);
    $_SESSION['success_message'] = "Enrollment request {$action}d successfully!";
    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=enrollments#enrollments");
    exit;
  }

  if (isset($_POST['edit_course'])) {
    $courseCode = $_POST['course_code'];
    $courseName = $_POST['course_name'];
    $courseHours = $_POST['course_hours'];
    $courseDescription = $_POST['course_description'] ?? '';
    $learningOutcomes = $_POST['course_learning_outcomes'] ?? '';
    $courseStatus = $_POST['course_status'] ?? 'published';
    $allowPreview = $_POST['allow_preview'] ?? 0;
    $previewContent = $_POST['course_preview_content'] ?? '';
    $requireVerification = $_POST['require_verification'] ?? 0;
    $verificationType = $_POST['verification_type'] ?? 'email';
    $competencies = $_POST['competencies'] ?? [];
    try {
      $stmt = $pdo->prepare("UPDATE courses SET course_name = ?, hours = ?, description = ?, learning_outcomes = ?, course_status = ?, allow_preview = ?, preview_content = ?, require_verification = ?, verification_type = ?, competency_types = ? WHERE course_code = ?");
      $stmt->execute([$courseName, $courseHours, $courseDescription, $learningOutcomes, $courseStatus, $allowPreview, $previewContent, $requireVerification, $verificationType, json_encode(array_values($competencies)), $courseCode]);
      // Safely update scheduling only if columns exist
      try {
        $scheduleDaysPerWeek = $_POST['schedule_days_per_week'] ?? null;
        $scheduleDays = isset($_POST['schedule_days']) && is_array($_POST['schedule_days']) ? implode(',', $_POST['schedule_days']) : '';
        $sessionHours = $_POST['session_hours'] ?? null;
        $colCheck = $pdo->query("SHOW COLUMNS FROM courses LIKE 'schedule_days_per_week'");
        if ($colCheck && $colCheck->rowCount() > 0) {
          $upd = $pdo->prepare("UPDATE courses SET schedule_days_per_week = ?, schedule_days = ?, session_hours = ? WHERE course_code = ?");
          $upd->execute([$scheduleDaysPerWeek, $scheduleDays, $sessionHours, $courseCode]);
        }
      } catch (Exception $__) {}
      $_SESSION['success_message'] = "Course '$courseName' updated successfully!";
    } catch (PDOException $e) {
      $_SESSION['error_message'] = "Error updating course: " . $e->getMessage();
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=courses#courses");
    exit;
  }

  if (isset($_POST['archive_course'])) {
    $courseCode = $_POST['course_code'] ?? '';
    try {
      if ($courseCode === '') { throw new Exception('No course code provided'); }
      $stmt = $pdo->prepare("UPDATE courses SET status = 'archived', course_status = 'archived' WHERE course_code = ?");
      $stmt->execute([$courseCode]);
      $_SESSION['success_message'] = "Course archived successfully";
    } catch (Exception $e) {
      $_SESSION['error_message'] = 'Archive error: ' . $e->getMessage();
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=courses#courses");
    exit;
  }

  if (isset($_POST['restore_course'])) {
    $courseCode = $_POST['course_code'] ?? '';
    try {
      if ($courseCode === '') { throw new Exception('No course code provided'); }
      $stmt = $pdo->prepare("UPDATE courses SET status = 'active', course_status = 'published' WHERE course_code = ?");
      $stmt->execute([$courseCode]);
      $_SESSION['success_message'] = "Course restored successfully";
    } catch (Exception $e) {
      $_SESSION['error_message'] = 'Restore error: ' . $e->getMessage();
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=courses#courses");
    exit;
  }

  if (isset($_POST['create_trainer'])) {
    $firstName = trim($_POST['trainer_first_name']);
    $middleName = trim($_POST['trainer_middle_name'] ?? '');
    $lastName = trim($_POST['trainer_last_name']);
    $contactNumber = trim($_POST['trainer_number']);
    if (!validatePhilippinePhoneNumber($contactNumber)) {
      $_SESSION['error_message'] = "Please enter a valid Philippine phone number (starting with 09 or +63)";
      header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=trainers");
      exit;
    }
    $duplicateCheck = checkDuplicateUser($pdo, $firstName, $lastName, $middleName, $contactNumber);
    if ($duplicateCheck['name_duplicate']) {
      $_SESSION['error_message'] = "A user with the same name already exists!";
      header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=trainers");
      exit;
    }
    if ($duplicateCheck['contact_duplicate']) {
      $_SESSION['error_message'] = "A user with the same contact number already exists!";
      header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=trainers");
      exit;
    }
    $_SESSION['success_message'] = "Trainer created successfully!";
    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=trainers");
    exit;
  }

  if (isset($_POST['create_trainee'])) {
    $firstName = trim($_POST['trainee_first_name']);
    $middleName = trim($_POST['trainee_middle_name'] ?? '');
    $lastName = trim($_POST['trainee_last_name']);
    $contactNumber = trim($_POST['trainee_number']);
    if (!validatePhilippinePhoneNumber($contactNumber)) {
      $_SESSION['error_message'] = "Please enter a valid Philippine phone number (starting with 09 or +63)";
      header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=trainees");
      exit;
    }
    $duplicateCheck = checkDuplicateUser($pdo, $firstName, $lastName, $middleName, $contactNumber);
    if ($duplicateCheck['name_duplicate']) {
      $_SESSION['error_message'] = "A user with the same name already exists!";
      header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=trainees");
      exit;
    }
    if ($duplicateCheck['contact_duplicate']) {
      $_SESSION['error_message'] = "A user with the same contact number already exists!";
      header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=trainees");
      exit;
    }
    $_SESSION['success_message'] = "Trainee created successfully!";
    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=trainees");
    exit;
  }

  if (isset($_POST['update_profile'])) {
    $firstName = trim($_POST['first_name']);
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name']);
    $suffix = trim($_POST['suffix'] ?? '');
    $contactNumber = trim($_POST['contact_number']);
    if (empty($firstName) || empty($lastName)) {
      $_SESSION['error_message'] = 'First name and last name are required fields.';
      header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=home#home");
      exit;
    }
    try {
      $currentUserStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
      $currentUserStmt->execute([$user['user_id']]);
      $currentUser = $currentUserStmt->fetch();
      if (!$currentUser) {
        throw new Exception("User not found.");
      }
      $profilePicture = $currentUser['profile_picture'];
      if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $uploadDir = __DIR__ . '/../uploads/profiles/';
        if (!is_dir($uploadDir)) {
          mkdir($uploadDir, 0777, true);
        }
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $fileType = $_FILES['profile_picture']['type'];
        if (in_array($fileType, $allowedTypes)) {
          if ($_FILES['profile_picture']['size'] <= 2 * 1024 * 1024) {
            $fileExtension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $fileName = 'profile_' . $user['user_id'] . '_' . time() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath)) {
              if (!empty($currentUser['profile_picture']) && $currentUser['profile_picture'] != 'default.png' && file_exists($uploadDir . $currentUser['profile_picture'])) {
                unlink($uploadDir . $currentUser['profile_picture']);
              }
              $profilePicture = $fileName;
            } else {
              throw new Exception("Failed to upload profile picture.");
            }
          } else {
            throw new Exception("File size must be less than 2MB.");
          }
        } else {
          throw new Exception("Invalid file type. Only JPG, PNG, and GIF are allowed.");
        }
      }
      $stmt = $pdo->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, contact_number = ?, profile_picture = ? WHERE user_id = ?");
      if ($stmt->execute([$firstName, $middleName, $lastName, $suffix, $contactNumber, $profilePicture, $user['user_id']])) {
        $_SESSION['user']['first_name'] = $firstName;
        $_SESSION['user']['middle_name'] = $middleName;
        $_SESSION['user']['last_name'] = $lastName;
        $_SESSION['user']['suffix'] = $suffix;
        $_SESSION['user']['contact_number'] = $contactNumber;
        $_SESSION['user']['profile_picture'] = $profilePicture;
        $_SESSION['success_message'] = 'Profile updated successfully!';
      } else {
        throw new Exception("Failed to update profile in database.");
      }
    } catch (Exception $e) {
      error_log("Profile update error: " . $e->getMessage());
      $_SESSION['error_message'] = 'Error updating profile: ' . $e->getMessage();
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=home#home");
    exit;
  }

  if (isset($_POST['create_backup'])) {
    try {
      $result = createBackup();
      if ($result['success']) {
        $_SESSION['success_message'] = $result['message'] . ' (Size: ' . number_format($result['size'] / 1048576, 2) . ' MB)';
      } else {
        $_SESSION['error_message'] = $result['message'];
        if (!empty($result['details'])) {
          error_log("Backup creation failed: " . $result['details']);
        }
      }
    } catch (Exception $e) {
      $_SESSION['error_message'] = 'Backup error: ' . $e->getMessage();
      error_log("Backup exception: " . $e->getMessage());
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=backup#backup");
    exit;
  }

  // Handler: Restore from Server Backup List
  if (isset($_POST['restore_backup'])) {
    $file = $_POST['backup_file'] ?? '';

    if (empty($file)) {
      $_SESSION['error_message'] = 'No backup file specified';
    } else {
      try {
        $res = restoreBackup($file);
        if ($res['success']) {
          $_SESSION['success_message'] = $res['message'];

          // Force session reload after restore
          session_write_close();
          session_start();
        } else {
          $_SESSION['error_message'] = $res['message'];
          if (!empty($res['details'])) {
            error_log("Restore failed: " . $res['details']);
          }
        }
      } catch (Exception $e) {
        $_SESSION['error_message'] = 'Restore error: ' . $e->getMessage();
        error_log("Restore exception: " . $e->getMessage());
      }
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=backup#backup");
    exit;
  }

  // Handler: Restore from Uploaded File
  if (isset($_POST['restore_uploaded_backup'])) {
    try {
      // Validate file upload
      if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
          UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
          UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
          UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
          UPLOAD_ERR_NO_FILE => 'No file was uploaded',
          UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
          UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
          UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];

        $errorCode = $_FILES['backup_file']['error'] ?? UPLOAD_ERR_NO_FILE;
        throw new Exception($uploadErrors[$errorCode] ?? 'Unknown upload error');
      }

      $name = $_FILES['backup_file']['name'];

      // Validate file extension
      if (!preg_match('/\.sql$/i', $name)) {
        throw new Exception('Invalid file type. Only .sql files are accepted');
      }

      // Validate file size (max 50MB for uploads)
      if ($_FILES['backup_file']['size'] > 50 * 1024 * 1024) {
        throw new Exception('File too large. Maximum size is 50MB');
      }

      $dir = ensureBackupDir();
      $safeName = 'uploaded_' . date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '', basename($name));
      $target = $dir . DIRECTORY_SEPARATOR . $safeName;

      // Move uploaded file
      if (!move_uploaded_file($_FILES['backup_file']['tmp_name'], $target)) {
        throw new Exception('Failed to save uploaded file');
      }

      // Verify file was saved
      if (!file_exists($target) || filesize($target) === 0) {
        throw new Exception('Uploaded file is empty or was not saved properly');
      }

      // Restore from the uploaded file
      $res = restoreBackup($safeName);

      if ($res['success']) {
        $_SESSION['success_message'] = 'Database restored successfully from uploaded file';

        // Force session reload
        session_write_close();
        session_start();
      } else {
        // Keep the file for debugging if restore failed
        $_SESSION['error_message'] = $res['message'];
        error_log("Restore from upload failed: " . ($res['details'] ?? 'No details'));
      }
    } catch (Exception $e) {
      $_SESSION['error_message'] = 'Upload restore error: ' . $e->getMessage();
      error_log("Upload restore exception: " . $e->getMessage());
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=backup#backup");
    exit;
  }

  // Handler: Restore from Server Path
  if (isset($_POST['restore_from_path'])) {
    $path = trim($_POST['restore_path'] ?? '');

    if (empty($path)) {
      $_SESSION['error_message'] = 'No file path provided';
    } else {
      try {
        // Clean up path
        $path = str_replace(['\\\\', '//'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $path);

        $res = restoreBackupFromAbsolute($path);

        if ($res['success']) {
          $_SESSION['success_message'] = $res['message'];

          // Force session reload
          session_write_close();
          session_start();
        } else {
          $_SESSION['error_message'] = $res['message'];
          if (!empty($res['details'])) {
            error_log("Path restore failed: " . $res['details']);
          }
        }
      } catch (Exception $e) {
        $_SESSION['error_message'] = 'Path restore error: ' . $e->getMessage();
        error_log("Path restore exception: " . $e->getMessage());
      }
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=backup#backup");
    exit;
  }

  // Handler: Delete Backup
  if (isset($_POST['delete_backup'])) {
    $file = $_POST['backup_file'] ?? '';

    if (empty($file)) {
      $_SESSION['error_message'] = 'No backup file specified';
    } else {
      try {
        $res = deleteBackup($file);
        $_SESSION[$res['success'] ? 'success_message' : 'error_message'] = $res['message'];
      } catch (Exception $e) {
        $_SESSION['error_message'] = 'Delete error: ' . $e->getMessage();
        error_log("Delete backup exception: " . $e->getMessage());
      }
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=backup#backup");
    exit;
  }

  // Handler: Purge All Data (Keep Users Only)
  if (isset($_POST['purge_data'])) {
    try {
      // Extra confirmation through session flag
      if (!isset($_POST['confirm_purge'])) {
        $_POST['confirm_purge'] = true;
      }

      $res = purgeDatabaseDataExceptUsers($pdo);

      if ($res['success']) {
        $_SESSION['success_message'] = $res['message'] . '. User accounts preserved.';
      } else {
        $_SESSION['error_message'] = $res['message'];
      }
    } catch (Exception $e) {
      $_SESSION['error_message'] = 'Purge error: ' . $e->getMessage();
      error_log("Purge exception: " . $e->getMessage());
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?current_tab=backup#backup");
    exit;
  }

  // ============================================================================
  // GET HANDLERS FOR DOWNLOAD
  // ============================================================================

  // Handler: Download Existing Backup
  if (isset($_GET['download_backup'])) {
    require_once __DIR__ . '/includes/functions.php';

    $file = $_GET['backup_file'] ?? '';
    $dir = ensureBackupDir();
    $path = realpath($dir . DIRECTORY_SEPARATOR . $file);

    // Security checks
    if (!$path || strpos($path, realpath($dir)) !== 0 || !preg_match('/\.sql$/i', $file)) {
      header('HTTP/1.1 404 Not Found');
      die('Invalid backup file');
    }

    if (!file_exists($path)) {
      header('HTTP/1.1 404 Not Found');
      die('Backup file not found');
    }

    // Send file
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('X-Content-Type-Options: nosniff');

    // Use readfile for better memory efficiency
    @ob_end_clean();
    flush();
    readfile($path);
    exit;
  }

  // Handler: Create and Download Backup Directly
  if (isset($_GET['download_full_backup'])) {
    require_once __DIR__ . '/../php/config.php';

    $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
    if (!file_exists($mysqldump)) {
      $mysqldump = 'mysqldump';
    }

    $filename = DB_NAME . '_' . date('Ymd_His') . '.sql';

    // Set headers
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('X-Content-Type-Options: nosniff');

    @set_time_limit(300);
    @ini_set('memory_limit', '512M');

    // Clear any output buffers
    if (function_exists('ob_get_level')) {
      while (ob_get_level()) {
        ob_end_clean();
      }
    }

    // Build command
    $cmd = '"' . $mysqldump . '"'
      . ' --host=' . escapeshellarg(DB_HOST)
      . ' --user=' . escapeshellarg(DB_USER)
      . ' --password=' . escapeshellarg(DB_PASS)
      . ' --routines --triggers --events --single-transaction --hex-blob'
      . ' --default-character-set=utf8mb4'
      . ' --databases ' . escapeshellarg(DB_NAME);

    // Execute and stream output
    if (function_exists('passthru')) {
      passthru($cmd . ' 2>&1');
    } else {
      echo shell_exec($cmd . ' 2>&1');
    }

    exit;
  }
}

try {
  $trainers = $pdo->query("SELECT * FROM users WHERE role = 'trainer' ORDER BY date_created DESC")->fetchAll();
  $trainees = $pdo->query("SELECT * FROM users WHERE role = 'trainee' ORDER BY date_created DESC")->fetchAll();
  $guests = $pdo->query("SELECT * FROM users WHERE role = 'guest' ORDER BY date_created DESC")->fetchAll();
  $courses = $pdo->query("SELECT * FROM courses ORDER BY date_created DESC")->fetchAll();
  $announcements = $pdo->query("SELECT a.*, u.first_name, u.last_name FROM announcements a JOIN users u ON a.posted_by = u.user_id ORDER BY a.date_posted DESC LIMIT 5")->fetchAll();
  // NOTE: Enrollments for the Enrollments tab are already loaded above with pagination and search.
  // Avoid overriding $enrollments here to preserve filtering and include approved statuses.

  // Get approved enrollments for displaying trainee courses
  $approvedEnrollments = $pdo->query("SELECT e.*, u.first_name, u.last_name, c.course_name FROM enrollments e JOIN users u ON e.trainee_id = u.user_id JOIN courses c ON e.course_code = c.course_code WHERE e.status = 'approved' ORDER BY date_requested DESC")->fetchAll();

  // Get batch assignments for displaying trainee batches
  $batchAssignments = $pdo->query("SELECT ba.*, u.first_name, u.last_name FROM batch_assignments ba JOIN users u ON ba.trainee_id = u.user_id ORDER BY ba.date_assigned DESC")->fetchAll();
  $courseBatches = $pdo->query("SELECT cb.*, c.course_name, COUNT(ba.id) as trainee_count FROM course_batches cb JOIN courses c ON cb.course_code = c.course_code LEFT JOIN batch_assignments ba ON cb.course_code = ba.course_code AND cb.batch_name = ba.batch_name GROUP BY cb.id, cb.course_code, cb.batch_name ORDER BY cb.created_at DESC")->fetchAll();
  try {
    $adminProfileStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $adminProfileStmt->execute([$user['user_id']]);
    $adminProfile = $adminProfileStmt->fetch();
    if (!$adminProfile) {
      $adminProfile = [
        'first_name' => $user['first_name'] ?? 'Admin',
        'middle_name' => $user['middle_name'] ?? '',
        'last_name' => $user['last_name'] ?? 'User',
        'suffix' => $user['suffix'] ?? '',
        'email' => $user['email'] ?? 'admin@bts.gov.ph',
        'contact_number' => $user['contact_number'] ?? 'Not set',
        'profile_picture' => $user['profile_picture'] ?? ''
      ];
    }
  } catch (Exception $e) {
    $adminProfile = [
      'first_name' => $user['first_name'] ?? 'Admin',
      'middle_name' => $user['middle_name'] ?? '',
      'last_name' => $user['last_name'] ?? 'User',
      'suffix' => $user['suffix'] ?? '',
      'email' => $user['email'] ?? 'admin@bts.gov.ph',
      'contact_number' => $user['contact_number'] ?? 'Not set',
      'profile_picture' => $user['profile_picture'] ?? ''
    ];
  }
  $courseAssignments = $pdo->query("SELECT ca.*, u.first_name, u.last_name, c.course_name FROM course_assignments ca JOIN users u ON ca.trainer_id = u.user_id JOIN courses c ON ca.course_code = c.course_code ORDER BY ca.date_assigned DESC")->fetchAll();
  $batchAssignments = $pdo->query("SELECT ba.*, u.first_name, u.last_name, c.course_name FROM batch_assignments ba JOIN users u ON ba.trainee_id = u.user_id JOIN courses c ON ba.course_code = c.course_code ORDER BY ba.date_assigned DESC")->fetchAll();
  $courseMaterials = $pdo->query("SELECT cm.*, c.course_name, u.first_name, u.last_name FROM course_materials cm JOIN courses c ON cm.course_code = c.course_code LEFT JOIN users u ON cm.uploaded_by = u.user_id ORDER BY cm.date_created DESC")->fetchAll();
  $submissions = $pdo->query("SELECT s.*, u.first_name, u.last_name, cm.title as material_title, c.course_name FROM submissions s JOIN users u ON s.trainee_id = u.user_id JOIN course_materials cm ON s.material_id = cm.id JOIN courses c ON cm.course_code = c.course_code ORDER BY s.submitted_at DESC")->fetchAll();
  $backups = listBackups();
} catch (PDOException $e) {
  error_log("Database error: " . $e->getMessage());
}

if (!isset($backups) || !is_array($backups)) {
  try { $backups = listBackups(); } catch (Exception $e) { $backups = []; }
}

$totalTrainers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'trainer' AND status = 'active'")->fetchColumn();
$totalTrainees = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'trainee' AND status = 'active'")->fetchColumn();
$totalGuests = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guest'")->fetchColumn();
$totalCourses = $pdo->query("SELECT COUNT(*) FROM courses WHERE status = 'active'")->fetchColumn();
$pendingEnrollments = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'pending'")->fetchColumn() ?? 0;
$activeTrainersCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'trainer' AND status = 'active'")->fetchColumn();
$archivedTrainersCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'trainer' AND status = 'archived'")->fetchColumn();
$activeTraineesCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'trainee' AND status = 'active'")->fetchColumn();
$archivedTraineesCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'trainee' AND status = 'archived'")->fetchColumn();

include __DIR__ . '/views/layout.php';
