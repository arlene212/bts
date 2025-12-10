<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('trainee');

header('Content-Type: application/json');

if (!isset($_GET['course_code'])) {
    echo json_encode(['error' => 'Course code is required']);
    exit;
}

$courseCode = $_GET['course_code'];
$traineeId = $_SESSION['user']['user_id'];

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

    // Verify trainee is enrolled in this course
    $accessStmt = $pdo->prepare("
        SELECT 1 FROM enrollments 
        WHERE course_code = ? AND trainee_id = ? AND status = 'approved'
    ");
    $accessStmt->execute([$courseCode, $traineeId]);

    if (!$accessStmt->fetch()) {
        echo json_encode(['error' => 'Access denied. You are not enrolled in this course.']);
        exit;
    }

    // Get course details
    $courseStmt = $pdo->prepare("SELECT * FROM courses WHERE course_code = ?");
    $courseStmt->execute([$courseCode]);
    $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        echo json_encode(['error' => 'Course not found']);
        exit;
    }

    // Get topics, materials, and activities
    $stmt = $pdo->prepare("
        SELECT 
            ct.id as topic_id, ct.competency_id, ct.topic_name, ct.topic_description,
            tm.id as material_id, tm.material_title, tm.material_description, tm.file_path as material_file_path,
            ta.id as activity_id, ta.activity_title, ta.activity_description, ta.activity_type, ta.due_date, ta.max_score, ta.start_date,
            asub.id as submission_id, asub.file_path as submission_file, asub.submitted_at, asub.score, asub.feedback
        FROM course_topics ct
        LEFT JOIN topic_materials tm ON ct.id = tm.topic_id
        LEFT JOIN topic_activities ta ON ct.id = ta.topic_id
        LEFT JOIN activity_submissions asub ON ta.id = asub.activity_id AND asub.guest_id = ?
        WHERE ct.course_code = ?
        ORDER BY ct.created_at ASC, tm.uploaded_at ASC, ta.created_at ASC
    ");
    
    try {
        $stmt->execute([$traineeId, $courseCode]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Determine batch and allowed resources for filtering
        $batchName = null; $filterOnBatch = false; $allowedMaterials = []; $allowedActivities = [];
        try {
            $bnStmt = $pdo->prepare("SELECT COALESCE(ba.batch_name, e.batch_name) AS batch_name FROM enrollments e LEFT JOIN batch_assignments ba ON ba.trainee_id = e.trainee_id AND ba.course_code = e.course_code WHERE e.course_code = ? AND e.trainee_id = ? LIMIT 1");
            $bnStmt->execute([$courseCode, $traineeId]);
            $batchName = $bnStmt->fetchColumn() ?: null;
        } catch (Exception $__) {}
        try {
            $tchk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'batch_resources'");
            $tchk->execute();
            $hasBatchTbl = ((int)$tchk->fetchColumn() > 0);
            $filterOnBatch = $hasBatchTbl && !empty($batchName);
        } catch (Exception $__) { $filterOnBatch = false; }
        if ($filterOnBatch) {
            $brStmt = $pdo->prepare("SELECT resource_type, resource_id FROM batch_resources WHERE course_code = ? AND batch_name = ?");
            $brStmt->execute([$courseCode, $batchName]);
            $rows = $brStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                if (($r['resource_type'] ?? '') === 'material') { $allowedMaterials[(int)$r['resource_id']] = true; }
                if (($r['resource_type'] ?? '') === 'activity') { $allowedActivities[(int)$r['resource_id']] = true; }
            }
            // If no mapping rows exist, disable filtering to show all course resources
            if (empty($rows)) { $filterOnBatch = false; }
        }
        // Decide per-type filtering: if there are no activity mappings, do not filter activities
        $filterActivitiesOnBatch = $filterOnBatch && !empty($allowedActivities);
        $filterMaterialsOnBatch = $filterOnBatch && !empty($allowedMaterials);
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage());
        error_log("Parameters: traineeId=$traineeId, courseCode=$courseCode");
        throw $e;
    }

    // Build competencies list for the course using course_id
    $competencies = [];
    $codeToId = [];
    $cstmt = $pdo->prepare("SELECT id, competency_code, competency_name, module_title, competency_type, nominal_hours, description, learning_outcomes FROM competencies WHERE status = 'active' AND course_id = ?");
    $cstmt->execute([$course['id']]);
    foreach ($cstmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $cidNum = (int)$row['id'];
        $competencies[$cidNum] = [
            'id' => (int)$row['id'],
            'type' => $row['competency_type'],
            'name' => $row['competency_name'],
            'code' => $row['competency_code'],
            'module_title' => $row['module_title'] ?? $row['competency_name'],
            'hours' => (int)($row['nominal_hours'] ?? 0),
            'description' => $row['description'] ?? '',
            'learning_outcomes' => $row['learning_outcomes'] ?? '',
            'topics' => []
        ];
        $codeToId[$row['competency_code']] = $cidNum;
    }

    $topics = [];
    $activities = [];

    foreach ($results as $row) {
        // Group topics
        if ($row['topic_id'] && !isset($topics[$row['topic_id']])) {
            $topics[$row['topic_id']] = [
                'id' => $row['topic_id'],
                'name' => $row['topic_name'],
                'description' => $row['topic_description'],
                'competency_id' => $row['competency_id'],
                'materials' => [],
                'activities' => []
            ];
        }

        // Group materials under topics (respect batch mapping if present)
        $matMapped = $filterMaterialsOnBatch ? ($row['material_id'] && isset($allowedMaterials[(int)$row['material_id']])) : (bool)$row['material_id'];
        if ($matMapped && !isset($topics[$row['topic_id']]['materials'][$row['material_id']])) {
            $topics[$row['topic_id']]['materials'][$row['material_id']] = [
                'id' => $row['material_id'],
                'title' => $row['material_title'],
                'description' => $row['material_description'],
                'file_path' => $row['material_file_path']
            ];
        }

        // Group activities under topics and also create a flat list of activities (respect batch mapping if present)
        $actMapped = $filterActivitiesOnBatch ? ($row['activity_id'] && isset($allowedActivities[(int)$row['activity_id']])) : (bool)$row['activity_id'];
        if ($actMapped && !isset($topics[$row['topic_id']]['activities'][$row['activity_id']])) {
            $activity = [
                'id' => $row['activity_id'],
                'title' => $row['activity_title'],
                'description' => $row['activity_description'],
                'type' => $row['activity_type'],
                'due_date' => $row['due_date'],
                'max_score' => $row['max_score'],
                'start_date' => $row['start_date'], // Ensure start_date is fetched
                'competency_id' => $row['competency_id'],
                'submission' => null
            ];
            if ($row['submission_id']) {
                $activity['submission'] = [
                    'id' => $row['submission_id'],
                    'file' => $row['submission_file'],
                    'date' => $row['submitted_at'],
                    'score' => $row['score'],
                    'feedback' => $row['feedback']
                ];
            }
            $topics[$row['topic_id']]['activities'][$row['activity_id']] = $activity;
            $activities[$row['activity_id']] = $activity;
        }
    }

    // Assign topics to competencies by competency code -> numeric id mapping
    foreach ($topics as $topic) {
        $compCode = (string)$topic['competency_id'];
        $cid = isset($codeToId[$compCode]) ? (int)$codeToId[$compCode] : null;
        if ($cid !== null && isset($competencies[$cid])) {
            $topic['materials'] = array_values($topic['materials']);
            $topic['activities'] = array_values($topic['activities']);
            $competencies[$cid]['topics'][] = $topic;
        } else {
            error_log("Competency code '{$compCode}' not found in competencies for topic '{$topic['name']}'");
        }
    }

    // Fetch quizzes grouped by competency (linked via competencies.id)
    $quizzesByCompetencyId = [];
    try {
        $quizStmt = $pdo->prepare("\n            SELECT q.id, q.title, q.status, q.time_limit, q.max_attempts, q.passing_score, q.is_randomized, q.show_correct_answers, q.created_at,\n                   comp.id AS competency_id,\n                   (SELECT setting_value FROM quiz_settings qs WHERE qs.quiz_id = q.id AND qs.setting_key = 'due_date' LIMIT 1) AS due_date\n            FROM quizzes q\n            JOIN competencies comp ON q.competency_id = comp.id\n            WHERE q.course_code = ? AND q.status IN ('active','published')\n            ORDER BY q.created_at DESC\n        ");
        $quizStmt->execute([$courseCode]);
        foreach ($quizStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $cid = (int)$row['competency_id'];
            if (!isset($quizzesByCompetencyId[$cid])) { $quizzesByCompetencyId[$cid] = []; }
            $quizzesByCompetencyId[$cid][] = $row;
        }
    } catch (Exception $__) {}

    echo json_encode([
        'course' => $course,
        'competencies' => array_values($competencies),
        'activities' => array_values($activities),
        'quizzesByCompetencyId' => $quizzesByCompetencyId,
        'selectedBatch' => isset($batchName) ? $batchName : null
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_course_details_trainee: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['error' => 'Database error occurred', 'details' => $e->getMessage()]);
}
?>
