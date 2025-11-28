<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('admin');

$database = new DatabaseConnection();
$pdo = $database->getConnection();

header('Content-Type: application/json');

if (!isset($_GET['course_code'])) {
    echo json_encode(['error' => 'Course code is required']);
    exit;
}

$courseCode = $_GET['course_code'];

try {
    // 1. Fetch main course details.
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_code = ?");
    $stmt->execute([$courseCode]);
    $course = $stmt->fetch();

    if (!$course) {
        echo json_encode(['error' => 'Course not found']);
        exit;
    }

    // 2. Fetch competencies for this course using course_id
    $competenciesStmt = $pdo->prepare("SELECT id, competency_code, competency_name, competency_type, description, status FROM competencies WHERE course_id = ? AND status = 'active' ORDER BY competency_type, competency_name");
    $competenciesStmt->execute([(int)$course['id']]);
    $competencies = $competenciesStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch course batches.
    $batchStmt = $pdo->prepare("
        SELECT cb.*, COUNT(ba.id) as trainee_count
        FROM course_batches cb
        LEFT JOIN batch_assignments ba ON cb.course_code = ba.course_code AND cb.batch_name = ba.batch_name
        WHERE cb.course_code = ?
        GROUP BY cb.id
        ORDER BY cb.created_at DESC
    ");
    $batchStmt->execute([$courseCode]);
    $batches = $batchStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Fetch all content (topics, materials, activities, submissions) for the course.
    $competenciesWithContent = [];
    foreach ($competencies as &$competency) {
        $competencyCode = $competency['competency_code'];
        $competency['topics'] = [];

        // Fetch topics for this competency.
        $topicStmt = $pdo->prepare("SELECT * FROM course_topics WHERE course_code = ? AND competency_id = ? ORDER BY created_at ASC");
        $topicStmt->execute([$courseCode, $competencyCode]);
        $topics = $topicStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($topics as $topic) {
            $topicId = $topic['id'];
            $topic['materials'] = [];
            $topic['activities'] = [];

            // Fetch materials for this topic.
            $materialStmt = $pdo->prepare("SELECT tm.*, u.first_name, u.last_name FROM topic_materials tm LEFT JOIN users u ON tm.uploaded_by = u.user_id WHERE tm.topic_id = ? ORDER BY tm.uploaded_at ASC");
            $materialStmt->execute([$topicId]);
            $topic['materials'] = $materialStmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch activities for this topic.
            $activityStmt = $pdo->prepare("SELECT * FROM topic_activities WHERE topic_id = ? ORDER BY created_at ASC");
            $activityStmt->execute([$topicId]);
            $activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($activities as $activity) {
                $activityId = $activity['id'];
                
                // Fetch submissions for this activity.
                $submissionStmt = $pdo->prepare("
                    SELECT s.*, u.first_name, u.last_name
                    FROM activity_submissions s
                    JOIN users u ON s.guest_id = u.user_id 
                    WHERE s.activity_id = ? 
                    ORDER BY s.submitted_at DESC
                ");
                $submissionStmt->execute([$activityId]);
                $activity['submissions'] = $submissionStmt->fetchAll(PDO::FETCH_ASSOC);

                $topic['activities'][] = $activity;
            }
            $competency['topics'][] = $topic;
        }
        $competenciesWithContent[] = $competency;
    }

    // 5. Assemble the final JSON response.
    // Fetch course materials grouped by competency (modules)
    $materialsByCompetency = [];
    try {
        $matStmt = $pdo->prepare("SELECT cm.*, comp.competency_code FROM course_materials cm JOIN competencies comp ON cm.competency_id = comp.id WHERE cm.course_code = ? ORDER BY cm.date_created ASC");
        $matStmt->execute([$courseCode]);
        foreach ($matStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $code = $row['competency_code'];
            if (!isset($materialsByCompetency[$code])) { $materialsByCompetency[$code] = []; }
            $materialsByCompetency[$code][] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'content_type' => $row['content_type'],
                'file_path' => $row['file_path'],
                'content' => $row['content'],
                'date_created' => $row['date_created']
            ];
        }
    } catch (Exception $__) {}

    // 6. Fetch quizzes grouped by competency (linked via competencies.id)
    $quizzesByCompetency = [];
    try {
        $quizStmt = $pdo->prepare("\n            SELECT q.id, q.title, q.status, q.time_limit, q.max_attempts, q.passing_score, q.is_randomized, q.show_correct_answers, q.created_at,\n                   comp.competency_code, comp.id AS competency_id,\n                   (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.id) AS question_count,\n                   (SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.quiz_id = q.id) AS attempt_count,\n                   (SELECT setting_value FROM quiz_settings qs WHERE qs.quiz_id = q.id AND qs.setting_key = 'due_date' LIMIT 1) AS due_date\n            FROM quizzes q\n            JOIN competencies comp ON q.competency_id = comp.id\n            WHERE q.course_code = ?\n            ORDER BY q.created_at DESC\n        ");
        $quizStmt->execute([$courseCode]);
        foreach ($quizStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $code = $row['competency_code'];
            if (!isset($quizzesByCompetency[$code])) { $quizzesByCompetency[$code] = []; }
            $quizzesByCompetency[$code][] = $row;
        }
    } catch (Exception $__) {}

    $response = [
        'course' => $course,
        'batches' => $batches,
        'competencies' => $competenciesWithContent,
        'materialsByCompetency' => $materialsByCompetency,
        'quizzesByCompetency' => $quizzesByCompetency
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Admin - Error fetching course details: " . $e->getMessage());
    echo json_encode(['error' => 'Database error while fetching course details.']);
}
?>
