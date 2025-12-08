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
    if ($filterOnBatch) {
        $brStmt = $pdo->prepare("SELECT resource_type, resource_id FROM batch_resources WHERE course_code = ? AND batch_name = ?");
        $brStmt->execute([$courseCode, $batchName]);
        foreach ($brStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            if (($r['resource_type'] ?? '') === 'material') { $allowedMaterials[(int)$r['resource_id']] = true; }
            if (($r['resource_type'] ?? '') === 'activity') { $allowedActivities[(int)$r['resource_id']] = true; }
        }
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
            ta.id as activity_id, ta.activity_title, ta.activity_description, ta.activity_type, ta.due_date, ta.max_score
         FROM course_topics ct
         LEFT JOIN topic_materials tm ON ct.id = tm.topic_id
         LEFT JOIN topic_activities ta ON ct.id = ta.topic_id
         WHERE ct.course_code = ?
         ORDER BY ct.created_at ASC, tm.uploaded_at ASC, ta.created_at ASC"
    );
    $stmt->execute([$courseCode]);
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
        $matOk = $row['material_id'] ? (!$filterOnBatch || isset($allowedMaterials[(int)$row['material_id']])) : false;
        if ($matOk && !isset($topics[$topicId]['materials'][$row['material_id']])) {
            $topics[$topicId]['materials'][$row['material_id']] = [
                'id' => $row['material_id'],
                'title' => $row['material_title'],
                'description' => $row['material_description'],
                'file_path' => $row['material_file_path']
            ];
        }
        $actOk = $row['activity_id'] ? (!$filterOnBatch || isset($allowedActivities[(int)$row['activity_id']])) : false;
        if ($actOk && !isset($topics[$topicId]['activities'][$row['activity_id']])) {
            $topics[$topicId]['activities'][$row['activity_id']] = [
                'id' => $row['activity_id'],
                'title' => $row['activity_title'],
                'description' => $row['activity_description'],
                'type' => $row['activity_type']
            ];
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

    echo json_encode([
        'course' => $course,
        'competencies' => array_values($competencies),
        'selectedBatch' => $batchName
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_course_details_guest: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
