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

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>