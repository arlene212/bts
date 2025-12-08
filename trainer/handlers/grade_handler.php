<?php
require_once __DIR__ . '/../../php/SessionManager.php';
require_once __DIR__ . '/../../php/DatabaseConnection.php';

SessionManager::startSession();
SessionManager::requireRole('trainer');

header('Content-Type: application/json');

$database = new DatabaseConnection();
$pdo = $database->getConnection();
$user = SessionManager::getCurrentUser();

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

function letterGrade($pct) {
    if ($pct >= 90) return 'A';
    if ($pct >= 80) return 'B';
    if ($pct >= 70) return 'C';
    if ($pct >= 60) return 'D';
    return 'F';
}

try {
    if ($action === 'get_grades') {
        $stmt = $pdo->prepare(
            "SELECT qa.id, qa.quiz_id, qa.trainee_id, qa.score, qa.max_score, qa.completed_at, qa.time_spent, qa.attempt_number,
                    u.first_name, u.last_name, u.user_id, q.title AS quiz_title, q.course_code, c.course_name
             FROM quiz_attempts qa
             JOIN users u ON qa.trainee_id = u.user_id
             JOIN quizzes q ON qa.quiz_id = q.id
             JOIN courses c ON q.course_code = c.course_code
             WHERE q.course_code IN (SELECT course_code FROM course_assignments WHERE trainer_id = ?)
             ORDER BY qa.completed_at DESC"
        );
        $stmt->execute([$user['user_id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $grades = [];
        foreach ($rows as $r) {
            $pct = (float)$r['score'];
            $max = (int)$r['max_score'];
            $points = $max > 0 ? round(($pct / 100) * $max) : 0;
            $grades[] = [
                'id' => (string)$r['id'],
                'student_name' => trim(($r['last_name'] ?? '') . ', ' . ($r['first_name'] ?? '')),
                'student_id' => (string)$r['user_id'],
                'course_code' => (string)$r['course_code'],
                'course_name' => (string)$r['course_name'],
                'activity_name' => (string)$r['quiz_title'],
                'activity_type' => 'quiz',
                'score' => $points,
                'max_score' => $max,
                'percentage' => round($pct, 1),
                'grade' => letterGrade($pct),
                'status' => 'completed',
                'submitted_date' => $r['completed_at'],
                'feedback' => '',
                'time_taken' => ($r['time_spent'] !== null ? ($r['time_spent'] . 's') : 'N/A'),
                'attempts' => (int)$r['attempt_number'],
                'submission_details' => true,
            ];
        }
        echo json_encode(['success' => true, 'grades' => $grades]);
        exit;
    }

  if ($action === 'export') {
        $courseCode = $_GET['course_code'] ?? '';
        $stmtSql = "SELECT qa.id, qa.quiz_id, qa.trainee_id, qa.score, qa.max_score, qa.completed_at, qa.time_spent, qa.attempt_number,
                           u.first_name, u.last_name, u.user_id, q.title AS quiz_title, q.course_code, c.course_name
                    FROM quiz_attempts qa
                    JOIN users u ON qa.trainee_id = u.user_id
                    JOIN quizzes q ON qa.quiz_id = q.id
                    JOIN courses c ON q.course_code = c.course_code
                    WHERE q.course_code IN (SELECT course_code FROM course_assignments WHERE trainer_id = ?)";
        $params = [$user['user_id']];
        if (!empty($courseCode)) { $stmtSql .= " AND q.course_code = ?"; $params[] = $courseCode; }
        $stmtSql .= " ORDER BY qa.completed_at DESC";
        $stmt = $pdo->prepare($stmtSql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="quiz_grades.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Student ID', 'Student Name', 'Course', 'Quiz Title', 'Points', 'Max Points', 'Percentage', 'Grade', 'Attempt', 'Completed At']);
        foreach ($rows as $r) {
            $pct = (float)$r['score'];
            $max = (int)$r['max_score'];
            $points = $max > 0 ? round(($pct / 100) * $max) : 0;
            fputcsv($out, [
                $r['user_id'],
                trim(($r['last_name'] ?? '') . ', ' . ($r['first_name'] ?? '')),
                $r['course_name'],
                $r['quiz_title'],
                $points,
                $max,
                round($pct, 1),
                letterGrade($pct),
                $r['attempt_number'],
                $r['completed_at'],
            ]);
        }
    fclose($out);
    exit;
  }

  if ($action === 'get_gradebook') {
    $studentsStmt = $pdo->prepare(
      "SELECT DISTINCT u.user_id, u.first_name, u.last_name
       FROM enrollments e
       JOIN users u ON e.trainee_id = u.user_id
       WHERE e.course_code IN (SELECT course_code FROM course_assignments WHERE trainer_id = ?)
         AND e.status = 'approved' AND u.role = 'trainee' AND u.status = 'active'
       ORDER BY u.last_name, u.first_name"
    );
    $studentsStmt->execute([$user['user_id']]);
    $students = [];
    foreach ($studentsStmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
      $students[] = [
        'id' => (string)$s['user_id'],
        'name' => trim(($s['last_name'] ?? '') . ', ' . ($s['first_name'] ?? '')),
      ];
    }

    $activitiesStmt = $pdo->prepare(
      "SELECT q.id, q.title, q.course_code, c.course_name
       FROM quizzes q
       JOIN courses c ON q.course_code = c.course_code
       WHERE q.course_code IN (SELECT course_code FROM course_assignments WHERE trainer_id = ?)
       ORDER BY c.course_name, q.title"
    );
    $activitiesStmt->execute([$user['user_id']]);
    $activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC);

    $maxStmt = $pdo->prepare(
      "SELECT qa.quiz_id, MAX(qa.max_score) AS max_score
       FROM quiz_attempts qa
       WHERE qa.quiz_id IN (
         SELECT id FROM quizzes WHERE course_code IN (
           SELECT course_code FROM course_assignments WHERE trainer_id = ?
         )
       )
       GROUP BY qa.quiz_id"
    );
    $maxStmt->execute([$user['user_id']]);
    $maxMap = [];
    foreach ($maxStmt->fetchAll(PDO::FETCH_ASSOC) as $m) {
      $maxMap[(string)$m['quiz_id']] = (int)$m['max_score'];
    }
    $activitiesOut = [];
    foreach ($activities as $a) {
      $activitiesOut[] = [
        'id' => (string)$a['id'],
        'title' => (string)$a['title'],
        'course_code' => (string)$a['course_code'],
        'course_name' => (string)$a['course_name'],
        'max_score' => isset($maxMap[(string)$a['id']]) ? $maxMap[(string)$a['id']] : 100,
      ];
    }

    $scoresStmt = $pdo->prepare(
      "SELECT qa.quiz_id, qa.trainee_id, MAX(qa.score) AS score, MAX(qa.max_score) AS max_score
       FROM quiz_attempts qa
       JOIN quizzes q ON qa.quiz_id = q.id
       WHERE q.course_code IN (SELECT course_code FROM course_assignments WHERE trainer_id = ?)
       GROUP BY qa.quiz_id, qa.trainee_id"
    );
    $scoresStmt->execute([$user['user_id']]);
    $scores = [];
    foreach ($scoresStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
      $pct = (float)$r['score'];
      $max = (int)($r['max_score'] ?? ($maxMap[(string)$r['quiz_id']] ?? 100));
      $points = $max > 0 ? round(($pct / 100) * $max) : 0;
      $scores[] = [
        'student_id' => (string)$r['trainee_id'],
        'activity_id' => (string)$r['quiz_id'],
        'points' => $points,
        'max_score' => $max,
        'percentage' => round($pct, 1),
        'grade' => letterGrade($pct),
      ];
    }


    // Build enrollments and batches
    $enrollStmt = $pdo->prepare(
      "SELECT e.trainee_id, e.course_code, COALESCE(ba.batch_name, e.batch_name) AS batch_name
       FROM enrollments e
       JOIN course_assignments ca ON e.course_code = ca.course_code
       LEFT JOIN batch_assignments ba ON ba.trainee_id = e.trainee_id AND ba.course_code = e.course_code AND ba.trainer_id = ca.trainer_id
       WHERE ca.trainer_id = ? AND e.status = 'approved'"
    );
    $enrollStmt->execute([$user['user_id']]);
    $enrollments = $enrollStmt->fetchAll(PDO::FETCH_ASSOC);
    $batches = [];
    foreach ($enrollments as $en) {
      $cc = (string)$en['course_code'];
      $bn = $en['batch_name'];
      if (!isset($batches[$cc])) { $batches[$cc] = []; }
      if ($bn !== null && $bn !== '') { if (!in_array($bn, $batches[$cc], true)) { $batches[$cc][] = $bn; } }
    }


    echo json_encode(['success' => true, 'students' => $students, 'activities' => $activitiesOut, 'scores' => $scores, 'enrollments' => $enrollments, 'batches' => $batches]);
    exit;
  }

  if ($action === 'update_grade') {
    $gradeId = isset($_POST['grade_id']) ? (int)$_POST['grade_id'] : 0;
    $points = isset($_POST['score']) ? (float)$_POST['score'] : 0.0;
    $max = isset($_POST['max_score']) ? (int)$_POST['max_score'] : 0;
    if ($gradeId <= 0 || $max <= 0 || $points < 0) {
      echo json_encode(['success' => false, 'message' => 'Invalid input']);
      exit;
    }
    // Ensure trainer owns the course for this attempt
    $ownStmt = $pdo->prepare("SELECT qa.id, q.course_code FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id = q.id WHERE qa.id = ? AND q.course_code IN (SELECT course_code FROM course_assignments WHERE trainer_id = ?)");
    $ownStmt->execute([$gradeId, $user['user_id']]);
    $row = $ownStmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
      echo json_encode(['success' => false, 'message' => 'Unauthorized or grade not found']);
      exit;
    }
    $pct = max(0, min(100, ($points / $max) * 100));
    $upd = $pdo->prepare("UPDATE quiz_attempts SET score = ?, max_score = ? WHERE id = ?");
    $upd->execute([$pct, $max, $gradeId]);
    echo json_encode(['success' => true, 'percentage' => round($pct, 1)]);
    exit;
  }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
