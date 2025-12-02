<?php
require_once 'SessionManager.php';
require_once 'DatabaseConnection.php';

SessionManager::startSession();

// Ensure the user is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$enrollmentId = $_POST['enrollment_id'] ?? null;
$action = $_POST['action'] ?? null;
$remarks = trim($_POST['remarks'] ?? '');
$adminId = $_SESSION['user']['user_id'];

if (!$enrollmentId || !$action || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit;
}

$status = ($action === 'approve') ? 'approved' : 'rejected';

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

// Update enrollment status
$stmt = $pdo->prepare(
    "UPDATE enrollments 
     SET status = ?, remarks = ?, processed_date = NOW(), processed_by = ? 
     WHERE id = ? AND status = 'pending'"
);
$stmt->execute([$status, $remarks, $adminId, $enrollmentId]);

// If approved, assign batch when a batch_name was requested
if ($status === 'approved') {
    $infoStmt = $pdo->prepare("SELECT trainee_id, course_code, batch_name FROM enrollments WHERE id = ?");
    $infoStmt->execute([$enrollmentId]);
    $enr = $infoStmt->fetch(PDO::FETCH_ASSOC);
    if ($enr && !empty($enr['batch_name'])) {
        // Resolve trainer for the batch, if any
        $trainerStmt = $pdo->prepare("SELECT trainer_id FROM course_batches WHERE course_code = ? AND batch_name = ? AND trainer_id IS NOT NULL");
        $trainerStmt->execute([$enr['course_code'], $enr['batch_name']]);
        $trainer = $trainerStmt->fetch(PDO::FETCH_ASSOC);
        $trainerId = $trainer ? $trainer['trainer_id'] : null;

        // Prevent duplicate assignment
        $checkStmt = $pdo->prepare("SELECT 1 FROM batch_assignments WHERE trainee_id = ? AND course_code = ? AND batch_name = ? LIMIT 1");
        $checkStmt->execute([$enr['trainee_id'], $enr['course_code'], $enr['batch_name']]);
        if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
            $assignStmt = $pdo->prepare(
                "INSERT INTO batch_assignments (trainee_id, trainer_id, course_code, batch_name, assigned_by, date_assigned)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $assignStmt->execute([$enr['trainee_id'], $trainerId, $enr['course_code'], $enr['batch_name'], $adminId]);
        }
    }
}

echo json_encode(['success' => true, 'message' => "Enrollment request has been successfully " . $status . "."]);

} catch (PDOException $e) {
    error_log("Error processing enrollment: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>
