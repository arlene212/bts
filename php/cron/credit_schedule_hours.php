<?php
require_once __DIR__ . '/../DatabaseConnection.php';

$db = new DatabaseConnection();
$pdo = $db->getConnection();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS hour_credits (
        id INT(11) NOT NULL AUTO_INCREMENT,
        trainee_id VARCHAR(20) NOT NULL,
        course_code VARCHAR(50) NOT NULL,
        credit_date DATE NOT NULL,
        hours INT(11) NOT NULL,
        source ENUM('schedule','manual') NOT NULL DEFAULT 'schedule',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_credit (trainee_id, course_code, credit_date),
        KEY idx_credits_user_course_date (trainee_id, course_code, credit_date),
        CONSTRAINT fk_hour_credits_user FOREIGN KEY (trainee_id) REFERENCES users (user_id),
        CONSTRAINT fk_hour_credits_course FOREIGN KEY (course_code) REFERENCES courses (course_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
);

$today = date('Y-m-d');
$dayAbbr = date('D');

$coursesStmt = $pdo->prepare(
    "SELECT course_code, hours, schedule_days, session_hours
     FROM courses
     WHERE course_status = 'published'
       AND schedule_days IS NOT NULL AND TRIM(schedule_days) <> ''
       AND session_hours IS NOT NULL AND session_hours > 0"
);
$coursesStmt->execute();
$courses = $coursesStmt->fetchAll();

foreach ($courses as $course) {
    $code = $course['course_code'];
    $totalHours = (int)$course['hours'];
    $sessionHours = (int)$course['session_hours'];
    $daysStr = (string)$course['schedule_days'];
    $days = array_filter(array_map(function ($d) { return strtoupper(trim($d)); }, explode(',', $daysStr)));
    if (!in_array(strtoupper($dayAbbr), $days)) {
        continue;
    }

    $pdo->beginTransaction();
    try {
        $enrollStmt = $pdo->prepare(
            "SELECT id, trainee_id, progress_percentage
             FROM enrollments
             WHERE course_code = ? AND status = 'approved'"
        );
        $enrollStmt->execute([$code]);
        $enrollments = $enrollStmt->fetchAll();

        $checkStmt = $pdo->prepare(
            "SELECT id FROM hour_credits WHERE trainee_id = ? AND course_code = ? AND credit_date = ?"
        );
        $insertStmt = $pdo->prepare(
            "INSERT INTO hour_credits (trainee_id, course_code, credit_date, hours, source)
             VALUES (?, ?, ?, ?, 'schedule')"
        );
        $sumStmt = $pdo->prepare(
            "SELECT COALESCE(SUM(hours),0) AS total_hours FROM hour_credits WHERE trainee_id = ? AND course_code = ?"
        );
        $updateStmt = $pdo->prepare(
            "UPDATE enrollments
             SET progress_percentage = ?,
                 completion_date = CASE WHEN ? >= 100.0 AND completion_date IS NULL THEN NOW() ELSE completion_date END
             WHERE trainee_id = ? AND course_code = ?"
        );

        foreach ($enrollments as $en) {
            $userId = $en['trainee_id'];
            $checkStmt->execute([$userId, $code, $today]);
            $exists = $checkStmt->fetchColumn();
            if (!$exists) {
                $insertStmt->execute([$userId, $code, $today, $sessionHours]);
            }

            $sumStmt->execute([$userId, $code]);
            $credited = (int)$sumStmt->fetchColumn();
            $progress = $totalHours > 0 ? min(100.0, ($credited / $totalHours) * 100.0) : 0.0;
            $updateStmt->execute([number_format($progress, 2, '.', ''), $progress, $userId, $code]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('credit_schedule_hours error: ' . $e->getMessage());
    }
}

echo json_encode(['status' => 'ok', 'date' => $today]);
