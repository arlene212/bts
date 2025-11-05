<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('trainer');

header('Content-Type: application/json');

if (!isset($_GET['course_code'])) {
    echo json_encode(['error' => 'Course code is required']);
    exit;
}

$courseCode = $_GET['course_code'];
$trainerId = $_SESSION['user']['user_id'];

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();
    
    // Verify trainer has access to this course
    $accessStmt = $pdo->prepare("
        SELECT 1 FROM course_assignments 
        WHERE course_code = ? AND trainer_id = ?
    ");
    $accessStmt->execute([$courseCode, $trainerId]);
    
    if (!$accessStmt->fetch()) {
        echo json_encode(['error' => 'Access denied to this course']);
        exit;
    }
    
    // Get course details
    $courseStmt = $pdo->prepare("
        SELECT * FROM courses WHERE course_code = ?
    ");
    $courseStmt->execute([$courseCode]);
    $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get batches for this course
    $batchesStmt = $pdo->prepare("
        SELECT cb.batch_name, cb.created_at as start_date, 'active' as status,
               (SELECT COUNT(DISTINCT ba.trainee_id) FROM batch_assignments ba 
                WHERE ba.course_code = cb.course_code AND ba.batch_name = cb.batch_name) as trainee_count
        FROM course_batches cb
        WHERE cb.course_code = ?
        ORDER BY cb.created_at DESC
    ");
    $batchesStmt->execute([$courseCode]);
    $batches = $batchesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get topics for this course and group them by competency
    $topicsAndMaterialsStmt = $pdo->prepare("
        SELECT 
            ct.*,
            tm.id as material_id, tm.material_title, tm.material_description, tm.file_path as material_file_path, tm.uploaded_at,
            ta.id as activity_id, ta.activity_title, ta.activity_description, ta.activity_type, ta.due_date, ta.max_score,
            asub.id as submission_id, asub.submission_text, asub.file_path as submission_file_path, asub.submitted_at, asub.score, asub.feedback,
            sub_user.first_name as trainee_first_name, sub_user.last_name as trainee_last_name
        FROM course_topics ct
        LEFT JOIN topic_materials tm ON ct.id = tm.topic_id
        LEFT JOIN topic_activities ta ON ct.id = ta.topic_id
        LEFT JOIN activity_submissions asub ON ta.id = asub.activity_id
        LEFT JOIN users sub_user ON asub.trainee_id = sub_user.user_id
        WHERE course_code = ? 
        ORDER BY 
            ct.created_at ASC, 
            tm.uploaded_at ASC, 
            ta.created_at ASC,
            asub.submitted_at ASC
    ");
    $topicsAndMaterialsStmt->execute([$courseCode]);
    $results = $topicsAndMaterialsStmt->fetchAll(PDO::FETCH_ASSOC);

    $topicsByCompetency = [];
    $tempTopics = [];

    foreach ($results as $row) {
        $topicId = $row['id'];
        if (!isset($tempTopics[$topicId])) {
            $tempTopics[$topicId] = array_intersect_key($row, array_flip(['id', 'course_code', 'competency_id', 'topic_name', 'topic_description', 'learning_objectives', 'added_by', 'created_at']));
            $tempTopics[$topicId]['materials'] = [];
            $tempTopics[$topicId]['activities'] = [];
        }

        if ($row['material_id']) {
            $materialId = $row['material_id'];
            if (!isset($tempTopics[$topicId]['materials'][$materialId])) {
                $tempTopics[$topicId]['materials'][$materialId] = array_intersect_key($row, array_flip(['material_id', 'material_title', 'material_description', 'material_file_path', 'uploaded_at']));
            }
        }

        if ($row['activity_id']) {
            $activityId = $row['activity_id'];
            if (!isset($tempTopics[$topicId]['activities'][$activityId])) {
                $tempTopics[$topicId]['activities'][$activityId] = array_intersect_key($row, array_flip(['activity_id', 'activity_title', 'activity_description', 'activity_type', 'due_date', 'max_score']));
                $tempTopics[$topicId]['activities'][$activityId]['submissions'] = [];
            }

            if ($row['submission_id']) {
                $submissionId = $row['submission_id'];
                if (!isset($tempTopics[$topicId]['activities'][$activityId]['submissions'][$submissionId])) {
                    $tempTopics[$topicId]['activities'][$activityId]['submissions'][$submissionId] = array_intersect_key($row, array_flip(['submission_id', 'submission_text', 'submission_file_path', 'submitted_at', 'score', 'feedback', 'trainee_first_name', 'trainee_last_name']));
                }
            }
        }
    }

    // Now, group the processed topics by competency
    foreach ($tempTopics as $topic) {
        $competencyId = $topic['competency_id'];
        if (!isset($topicsByCompetency[$competencyId])) {
            $topicsByCompetency[$competencyId] = [];
        }
        // Convert associative arrays to indexed arrays for JSON
        $topic['materials'] = array_values($topic['materials']);
        foreach ($topic['activities'] as &$activity) {
            $activity['submissions'] = array_values($activity['submissions']);
        }
        $topicsByCompetency[$competencyId][] = array_merge($topic, ['activities' => array_values($topic['activities'])]);
    }

    echo json_encode([
        'course' => $course,
        'batches' => $batches,
        'topicsByCompetency' => $topicsByCompetency
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_course_details_trainer: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>