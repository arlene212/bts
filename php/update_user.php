<?php
session_start();
require_once 'DatabaseConnection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$userId = $_POST['user_id'] ?? null;
$userRole = $_POST['user_role'] ?? null;

if (!$userId || !$userRole) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID and role are required']);
    exit;
}

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();
    $pdo->beginTransaction();

    $userStmt = $pdo->prepare("SELECT first_name, last_name, email, contact_number FROM users WHERE user_id = ?");
    $userStmt->execute([$userId]);
    $currentUser = $userStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $firstName = isset($_POST['first_name']) ? trim((string)$_POST['first_name']) : ($currentUser['first_name'] ?? '');
    $lastName = isset($_POST['last_name']) ? trim((string)$_POST['last_name']) : ($currentUser['last_name'] ?? '');
    $contactNumber = isset($_POST['contact_number']) ? trim((string)$_POST['contact_number']) : ($currentUser['contact_number'] ?? '');
    $email = isset($_POST['email']) ? trim((string)$_POST['email']) : ($currentUser['email'] ?? '');

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format provided.");
    }

    // Check if the new email is already taken by another user
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        // Email is taken, try to make it unique by adding a number
        $baseEmail = substr($email, 0, strpos($email, '@'));
        $domain = substr($email, strpos($email, '@'));
        $counter = 1;
        $newEmail = $email;
        
        do {
            $newEmail = $baseEmail . $counter . $domain;
            $stmt->execute([$newEmail, $userId]);
            $counter++;
        } while ($stmt->fetch() && $counter < 100);

        $email = $newEmail;
    }

    $stmt = $pdo->prepare("
        UPDATE users SET 
            first_name = ?, last_name = ?, email = ?, contact_number = ?
        WHERE user_id = ?
    ");
    $stmt->execute([$firstName, $lastName, $email, $contactNumber, $userId]);

    if ($userRole === 'trainer') {
        // Handle trainer-specific updates
        $courseCodes = $_POST['trainer_courses'] ?? [];
        $trainerBatchesStr = isset($_POST['trainer_batches']) ? trim((string)$_POST['trainer_batches']) : '';
        $trainerBatchesByCourseJson = $_POST['trainer_batches_by_course'] ?? '';
        $trainerBatchesByCourse = [];
        if (!empty($trainerBatchesByCourseJson)) {
            $decoded = json_decode($trainerBatchesByCourseJson, true);
            if (is_array($decoded)) { $trainerBatchesByCourse = $decoded; }
        }
        $hasBatchColumn = false;
        try {
            $colCheck = $pdo->query("SHOW COLUMNS FROM course_assignments LIKE 'batch_name'");
            if ($colCheck && $colCheck->rowCount() > 0) { $hasBatchColumn = true; }
        } catch (Exception $__) {}

        // 1. Delete existing course assignments for this trainer
        $deleteStmt = $pdo->prepare("DELETE FROM course_assignments WHERE trainer_id = ?");
        $deleteStmt->execute([$userId]);

        // 2. Insert new assignments
        if (!empty($courseCodes) && is_array($courseCodes)) {
            // Insert course assignments with per-course batch list when available
            if ($hasBatchColumn) {
                $assignStmt = $pdo->prepare("INSERT INTO course_assignments (trainer_id, course_code, batch_name, assigned_by, date_assigned) VALUES (?, ?, ?, ?, NOW())");
                foreach ($courseCodes as $courseCode) {
                    $courseCode = trim((string)$courseCode);
                    if ($courseCode === '') continue;
                    $batchesForCourse = isset($trainerBatchesByCourse[$courseCode]) && is_array($trainerBatchesByCourse[$courseCode])
                        ? array_filter(array_map('trim', $trainerBatchesByCourse[$courseCode]))
                        : array_filter(array_map('trim', explode(',', $trainerBatchesStr)));
                    $assignStmt->execute([$userId, $courseCode, implode(', ', $batchesForCourse), $_SESSION['user']['user_id']]);
                }
            } else {
                $assignStmt = $pdo->prepare("INSERT INTO course_assignments (trainer_id, course_code, assigned_by, date_assigned) VALUES (?, ?, ?, NOW())");
                foreach ($courseCodes as $courseCode) {
                    $courseCode = trim((string)$courseCode);
                    if ($courseCode === '') continue;
                    $assignStmt->execute([$userId, $courseCode, $_SESSION['user']['user_id']]);
                }
            }

            // Update course_batches.trainer_id for selected batches per course
            $hasCBTrainerCol = false;
            try {
                $cbColCheck = $pdo->query("SHOW COLUMNS FROM course_batches LIKE 'trainer_id'");
                if ($cbColCheck && $cbColCheck->rowCount() > 0) { $hasCBTrainerCol = true; }
            } catch (Exception $___) {}
            if ($hasCBTrainerCol) {
                $clearStmt = $pdo->prepare("UPDATE course_batches SET trainer_id = NULL WHERE course_code = ? AND trainer_id = ?");
                $upd = $pdo->prepare("UPDATE course_batches SET trainer_id = ? WHERE course_code = ? AND batch_name = ?");
                $prevStmt = $pdo->prepare("SELECT DISTINCT course_code FROM course_batches WHERE trainer_id = ?");
                $prevStmt->execute([$userId]);
                $prevCourses = array_map(function($r){ return trim((string)$r['course_code']); }, $prevStmt->fetchAll(PDO::FETCH_ASSOC));
                $selectedSet = array_map(function($c){ return trim((string)$c); }, $courseCodes ?? []);
                foreach ($prevCourses as $pc) {
                    if ($pc !== '' && !in_array($pc, $selectedSet, true)) {
                        $clearStmt->execute([$pc, $userId]);
                    }
                }
                foreach ($courseCodes as $courseCode) {
                    $courseCode = trim((string)$courseCode);
                    if ($courseCode === '') continue;
                    $clearStmt->execute([$courseCode, $userId]);
                    $batchesForCourse = isset($trainerBatchesByCourse[$courseCode]) && is_array($trainerBatchesByCourse[$courseCode])
                        ? array_filter(array_map('trim', $trainerBatchesByCourse[$courseCode]))
                        : array_filter(array_map('trim', explode(',', $trainerBatchesStr)));
                    foreach ($batchesForCourse as $bn) {
                        if ($bn !== '') { $upd->execute([$userId, $courseCode, $bn]); }
                    }
                }
            }
        }
        $message = 'Trainer updated successfully';

    } elseif ($userRole === 'trainee') {
        // Handle trainee-specific updates
        $courseCode = $_POST['course_code'] ?? '';
        $batchName = $_POST['batch_name'] ?? '';
        $enrollmentStatus = $_POST['enrollment_status'] ?? 'active';

        // Write the selected status directly to users.status
        $updStatusStmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $updStatusStmt->execute([$enrollmentStatus, $userId]);

    // 1. Create new enrollment and batch assignment if a course is selected (and reset previous assignments)
    if (!empty($courseCode)) {
            // Delete existing enrollment and batch assignment only when reassigning
            $deleteEnrollmentStmt = $pdo->prepare("DELETE FROM enrollments WHERE trainee_id = ?");
            $deleteEnrollmentStmt->execute([$userId]);

            $deleteBatchStmt = $pdo->prepare("DELETE FROM batch_assignments WHERE trainee_id = ?");
            $deleteBatchStmt->execute([$userId]);
            // Get course name
            $courseStmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_code = ?");
            $courseStmt->execute([$courseCode]);
            $course = $courseStmt->fetch();

            if ($course) {
                // Create enrollment (do not store batch in enrollments; use batch_assignments)
                $enrollStmt = $pdo->prepare("INSERT INTO enrollments (trainee_id, course_code, course_name, status, date_requested) VALUES (?, ?, ?, 'approved', NOW())");
                $enrollStmt->execute([$userId, $courseCode, $course['course_name']]);

                // Apply enrollment status
                if ($enrollmentStatus === 'dropped') {
                    $upd = $pdo->prepare("UPDATE enrollments SET drop_date = NOW(), dropped_by = ? WHERE trainee_id = ? AND course_code = ?");
                    $upd->execute([$_SESSION['user']['user_id'], $userId, $courseCode]);
                } elseif ($enrollmentStatus === 'graduated') {
                    $upd = $pdo->prepare("UPDATE enrollments SET completion_date = NOW() WHERE trainee_id = ? AND course_code = ?");
                    $upd->execute([$userId, $courseCode]);
                } else {
                    // active: clear drop/graduation markers
                    $upd = $pdo->prepare("UPDATE enrollments SET drop_date = NULL, dropped_by = NULL, completion_date = NULL WHERE trainee_id = ? AND course_code = ?");
                    $upd->execute([$userId, $courseCode]);
                }

                // Create batch assignment
                if (!empty($batchName)) {
                    // Get the trainer assigned to this batch
                    $trainerStmt = $pdo->prepare("SELECT trainer_id FROM course_batches WHERE course_code = ? AND batch_name = ? AND trainer_id IS NOT NULL");
                    $trainerStmt->execute([$courseCode, $batchName]);
                    $trainer = $trainerStmt->fetch();
                    $trainerId = $trainer ? $trainer['trainer_id'] : null;
                    
                    $batchStmt = $pdo->prepare("
                        INSERT INTO batch_assignments (trainee_id, trainer_id, course_code, batch_name, assigned_by, date_assigned) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $batchStmt->execute([$userId, $trainerId, $courseCode, $batchName, $_SESSION['user']['user_id']]);
                }
            } else {
                throw new Exception("Selected course not found: $courseCode");
            }
        }
        $message = 'Trainee updated successfully';
    } else {
        throw new Exception("Invalid user role for update.");
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Update user error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error updating user: ' . $e->getMessage()]);
}
?>
