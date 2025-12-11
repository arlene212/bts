<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('guest');

header('Content-Type: application/json');

if (!isset($_GET['course_code'])) {
    echo json_encode(['error' => 'Course code is required']);
    exit;
}

$courseCode = $_GET['course_code'];
$guestId = $_SESSION['user']['user_id'];

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

    // Verify guest is enrolled in this course
    $accessStmt = $pdo->prepare("
        SELECT 1 FROM enrollments 
        WHERE course_code = ? AND trainee_id = ? AND status = 'approved'
    ");
    $accessStmt->execute([$courseCode, $guestId]);

    if (!$accessStmt->fetch()) {
        echo json_encode(['error' => 'Access denied. You are not enrolled in this course.']);
        exit;
    }

    // Get course details
    $courseStmt = $pdo->prepare("SELECT * FROM courses WHERE course_code = ?");
    $courseStmt->execute([$courseCode]);
    $course = $courseStmt->fetch(PDO::FETCH_ASSOC);

    // Determine batch for guest enrollment (used to filter trainer-given resources)
    $batchName = null; $filterOnBatch = false; $allowedMaterials = []; $allowedActivities = [];
    try {
        $bnStmt = $pdo->prepare("SELECT e.batch_name FROM enrollments e WHERE e.course_code = ? AND e.trainee_id = ? LIMIT 1");
        $bnStmt->execute([$courseCode, $guestId]);
        $batchName = $bnStmt->fetchColumn() ?: null;
    } catch (Exception $__) {}
    try {
        $tchk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'batch_resources'");
        $tchk->execute();
        $hasBatchTbl = ((int)$tchk->fetchColumn() > 0);
        $filterOnBatch = $hasBatchTbl && !empty($batchName);
    } catch (Exception $__) { $filterOnBatch = false; }
    $filterMaterialsOnBatch = false; $filterActivitiesOnBatch = false;
    if ($filterOnBatch) {
        $brStmt = $pdo->prepare("SELECT resource_type, resource_id FROM batch_resources WHERE course_code = ? AND batch_name = ?");
        $brStmt->execute([$courseCode, $batchName]);
        $rows = $brStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            if (($r['resource_type'] ?? '') === 'material') { $allowedMaterials[(int)$r['resource_id']] = true; }
            if (($r['resource_type'] ?? '') === 'activity') { $allowedActivities[(int)$r['resource_id']] = true; }
        }
        // If no mapping rows exist, do not filter; show all resources
        if (empty($rows)) { $filterOnBatch = false; }
        $filterMaterialsOnBatch = $filterOnBatch && !empty($allowedMaterials);
        $filterActivitiesOnBatch = $filterOnBatch && !empty($allowedActivities);
    }

    // Build competencies list for the course using course_id (include all types)
    $competencies = [];
    $cstmt = $pdo->prepare("SELECT id, competency_code, competency_name, module_title, competency_type, nominal_hours, description, learning_outcomes FROM competencies WHERE status = 'active' AND course_id = (SELECT id FROM courses WHERE course_code = ?) ORDER BY unit_order, competency_name");
    $cstmt->execute([$courseCode]);
    foreach ($cstmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $competencies[(int)$row['id']] = [
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
    }

    // Get topics, materials, and activities for the course
    $stmt = $pdo->prepare(
        "SELECT 
            ct.id as topic_id, ct.competency_id, ct.topic_name, ct.topic_description,
            tm.id as material_id, tm.material_title, tm.material_description, tm.file_path as material_file_path,
            ta.id as activity_id, ta.activity_title, ta.activity_description, ta.activity_type, ta.due_date, ta.max_score,
            asub.id as submission_id, asub.file_path as submission_file, asub.submitted_at, asub.score, asub.feedback
         FROM course_topics ct
         LEFT JOIN topic_materials tm ON ct.id = tm.topic_id
         LEFT JOIN topic_activities ta ON ct.id = ta.topic_id
         LEFT JOIN activity_submissions asub ON ta.id = asub.activity_id AND asub.guest_id = ?
         WHERE ct.course_code = ?
         ORDER BY ct.created_at ASC, tm.uploaded_at ASC, ta.created_at ASC"
    );
    $stmt->execute([$guestId, $courseCode]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process results
    $topics = [];
    foreach ($results as $row) {
        $topicId = $row['topic_id'];
        if ($topicId && !isset($topics[$topicId])) {
            $topics[$topicId] = [
                'id' => $topicId,
                'name' => $row['topic_name'],
                'description' => $row['topic_description'],
                'competency_id' => $row['competency_id'],
                'materials' => [],
                'activities' => []
            ];
        }
        $matOk = $row['material_id'] ? (!$filterMaterialsOnBatch || isset($allowedMaterials[(int)$row['material_id']])) : false;
        if ($matOk && !isset($topics[$topicId]['materials'][$row['material_id']])) {
            $topics[$topicId]['materials'][$row['material_id']] = [
                'id' => $row['material_id'],
                'title' => $row['material_title'],
                'description' => $row['material_description'],
                'file_path' => $row['material_file_path']
            ];
        }
        $actOk = $row['activity_id'] ? (!$filterActivitiesOnBatch || isset($allowedActivities[(int)$row['activity_id']])) : false;
        if ($actOk && !isset($topics[$topicId]['activities'][$row['activity_id']])) {
            $activity = [
                'id' => $row['activity_id'],
                'title' => $row['activity_title'],
                'description' => $row['activity_description'],
                'type' => $row['activity_type'],
                'due_date' => $row['due_date'],
                'max_score' => $row['max_score'],
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
            $topics[$topicId]['activities'][$row['activity_id']] = $activity;
        }
    }

    // Assign topics to competencies by numeric id
    foreach ($topics as $topic) {
        $cid = (int)$topic['competency_id'];
        if (isset($competencies[$cid])) {
            $topic['materials'] = array_values($topic['materials']);
            $topic['activities'] = array_values($topic['activities']);
            $competencies[$cid]['topics'][] = $topic;
        }
    }

    // Also fetch course_materials grouped by competency (trainer-posted modules)
    $materialsByCompetency = [];
    try {
        $hasBatchResourcesTbl = false;
        try {
            $tchkBr = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'batch_resources'");
            $tchkBr->execute();
            $hasBatchResourcesTbl = ((int)$tchkBr->fetchColumn() > 0);
        } catch (Exception $__) {}
        if ($hasBatchResourcesTbl && !empty($batchName)) {
            $matStmt = $pdo->prepare("SELECT cm.id, cm.course_code, cm.competency_id, cm.title, cm.content_type, cm.file_path, cm.content, cm.date_created 
                                       FROM course_materials cm 
                                       JOIN batch_resources br ON br.resource_type = 'material' AND br.resource_id = cm.id AND br.course_code = cm.course_code AND br.batch_name = ? 
                                       WHERE cm.course_code = ? ORDER BY cm.date_created ASC");
            $matStmt->execute([$courseCode, $batchName]);
        } else {
            $matStmt = $pdo->prepare("SELECT cm.id, cm.course_code, cm.competency_id, cm.title, cm.content_type, cm.file_path, cm.content, cm.date_created 
                                       FROM course_materials cm 
                                       WHERE cm.course_code = ? ORDER BY cm.date_created ASC");
            $matStmt->execute([$courseCode]);
        }
        foreach ($matStmt->fetchAll(PDO::FETCH_ASSOC) as $m) {
            $cid = (int)$m['competency_id'];
            if (!isset($materialsByCompetency[$cid])) { $materialsByCompetency[$cid] = []; }
            $materialsByCompetency[$cid][] = $m;
        }
    } catch (Exception $__) {}

    echo json_encode([
        'course' => $course,
        'competencies' => array_values($competencies),
        'materialsByCompetency' => $materialsByCompetency,
        'selectedBatch' => $batchName
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_course_details_guest: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
