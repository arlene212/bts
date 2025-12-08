<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';
require_once 'AccessControl.php';

SessionManager::startSession();
SessionManager::requireRole('trainer');

header('Content-Type: application/json');

if (!isset($_GET['course_code'])) {
    echo json_encode(['error' => 'Course code is required']);
    exit;
}
if (!isset($_GET['batch_name']) || trim($_GET['batch_name']) === '') {
    echo json_encode(['error' => 'Batch name is required']);
    exit;
}

$courseCode = $_GET['course_code'];
$batchName = $_GET['batch_name'];
$trainerId = $_SESSION['user']['user_id'];

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();
    // Verify trainer has access to this batch of the course
    AccessControl::requireTrainerBatchAccess($pdo, $trainerId, $courseCode, $batchName);
    AccessControl::audit($pdo, [
        'course_code' => $courseCode,
        'batch_name' => $batchName,
        'action' => 'VIEW_COURSE_DETAILS'
    ]);
    
    // Get course details
    $courseStmt = $pdo->prepare("
        SELECT * FROM courses WHERE course_code = ?
    ");
    $courseStmt->execute([$courseCode]);
    $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get batches for this course assigned to this trainer
    $batchesStmt = $pdo->prepare("\n        SELECT cb.batch_name, COALESCE(cb.start_date, NOW()) as start_date,\n               (SELECT COUNT(DISTINCT ba.trainee_id) FROM batch_assignments ba \n                WHERE ba.course_code = cb.course_code AND ba.batch_name = cb.batch_name) as trainee_count\n        FROM course_batches cb\n        WHERE cb.course_code = ? AND cb.trainer_id = ?\n        ORDER BY cb.start_date DESC\n    ");
    $batchesStmt->execute([$courseCode, $trainerId]);
    $batches = $batchesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Determine if batch_resources table exists to scope resources per batch
    $hasBatchResourcesTbl = false;
    try {
        $tchkBr = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'batch_resources'");
        $tchkBr->execute();
        $hasBatchResourcesTbl = ((int)$tchkBr->fetchColumn() > 0);
    } catch (Exception $__) {}

    // Get topics for this course and group them by competency
    if ($hasBatchResourcesTbl) {
        $topicsAndMaterialsStmt = $pdo->prepare("
            SELECT 
                ct.*,
                tm.id as material_id, tm.material_title, tm.material_description, tm.file_path as material_file_path, tm.uploaded_at,
                br_m.id as br_material_id,
                ta.id as activity_id, ta.activity_title, ta.activity_description, ta.activity_type, ta.due_date, ta.max_score,
                br_a.id as br_activity_id,
                asub.id as submission_id, asub.submission_text, asub.file_path as submission_file_path, asub.submitted_at, asub.score, asub.feedback,
                sub_user.first_name as trainee_first_name, sub_user.last_name as trainee_last_name
            FROM course_topics ct
            LEFT JOIN topic_materials tm ON ct.id = tm.topic_id
            LEFT JOIN batch_resources br_m ON br_m.resource_type = 'material' AND br_m.resource_id = tm.id AND br_m.course_code = ct.course_code AND br_m.batch_name = ?
            LEFT JOIN topic_activities ta ON ct.id = ta.topic_id
            LEFT JOIN batch_resources br_a ON br_a.resource_type = 'activity' AND br_a.resource_id = ta.id AND br_a.course_code = ct.course_code AND br_a.batch_name = ?
            LEFT JOIN activity_submissions asub ON ta.id = asub.activity_id
            LEFT JOIN users sub_user ON asub.guest_id = sub_user.user_id
            WHERE ct.course_code = ? 
            ORDER BY 
                ct.created_at ASC, 
                tm.uploaded_at ASC, 
                ta.created_at ASC,
                asub.submitted_at ASC
        ");
        $topicsAndMaterialsStmt->execute([$batchName, $batchName, $courseCode]);
    } else {
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
            LEFT JOIN users sub_user ON asub.guest_id = sub_user.user_id
            WHERE ct.course_code = ? 
            ORDER BY 
                ct.created_at ASC, 
                tm.uploaded_at ASC, 
                ta.created_at ASC,
                asub.submitted_at ASC
        ");
        $topicsAndMaterialsStmt->execute([$courseCode]);
    }
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
                $m = array_intersect_key($row, array_flip(['material_id', 'material_title', 'material_description', 'material_file_path', 'uploaded_at']));
                $m['given'] = $hasBatchResourcesTbl ? (bool)$row['br_material_id'] : true;
                $tempTopics[$topicId]['materials'][$materialId] = $m;
            }
        }

        if ($row['activity_id']) {
            $activityId = $row['activity_id'];
            if (!isset($tempTopics[$topicId]['activities'][$activityId])) {
                $a = array_intersect_key($row, array_flip(['activity_id', 'activity_title', 'activity_description', 'activity_type', 'due_date', 'max_score']));
                $a['given'] = $hasBatchResourcesTbl ? (bool)$row['br_activity_id'] : true;
                $tempTopics[$topicId]['activities'][$activityId] = $a;
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
    // Build mapping from competency_code (string) to numeric id for consistent keys
    $codeToId = [];
    if (!empty($competencies)) {
        foreach ($competencies as $compRow) {
            if (isset($compRow['competency_code'], $compRow['id'])) {
                $codeToId[$compRow['competency_code']] = (int)$compRow['id'];
            }
        }
    }
    foreach ($tempTopics as $topic) {
        $compKey = $topic['competency_id'];
        $competencyId = isset($codeToId[$compKey]) ? $codeToId[$compKey] : $compKey;
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

    // Fetch competencies for this course via course_id (fallback if table missing)
    $hasCompetenciesTbl = false;
    try {
        $tchk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'competencies'");
        $tchk->execute();
        $hasCompetenciesTbl = ((int)$tchk->fetchColumn() > 0);
    } catch (Exception $__) {}
    $competencies = [];
    if ($hasCompetenciesTbl) {
        $compStmt = $pdo->prepare("SELECT id, unit_order, competency_code, competency_name, module_title, competency_type, nominal_hours, description, learning_outcomes, status FROM competencies WHERE course_id = ? AND status = 'active' ORDER BY competency_type, unit_order, competency_name");
        $compStmt->execute([(int)$course['id']]);
        $competencies = $compStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($hasBatchResourcesTbl) {
        $materialsStmt = $pdo->prepare("SELECT cm.id, cm.course_code, cm.competency_id, cm.title, cm.content_type, cm.file_path, cm.content, cm.date_created 
                                        FROM course_materials cm
                                        JOIN batch_resources br ON br.resource_type = 'material' AND br.resource_id = cm.id AND br.course_code = cm.course_code AND br.batch_name = ?
                                        WHERE cm.course_code = ? ORDER BY cm.date_created ASC");
        $materialsStmt->execute([$batchName, $courseCode]);
    } else {
        $materialsStmt = $pdo->prepare("SELECT cm.id, cm.course_code, cm.competency_id, cm.title, cm.content_type, cm.file_path, cm.content, cm.date_created 
                                        FROM course_materials cm
                                        WHERE cm.course_code = ? ORDER BY cm.date_created ASC");
        $materialsStmt->execute([$courseCode]);
    }
    $materials = $materialsStmt->fetchAll(PDO::FETCH_ASSOC);

    $materialsByCompetency = [];
    foreach ($materials as $m) {
        $cid = $m['competency_id'];
        if (!isset($materialsByCompetency[$cid])) $materialsByCompetency[$cid] = [];
        $materialsByCompetency[$cid][] = $m;
    }

    echo json_encode([
        'course' => $course,
        'batches' => $batches,
        'selectedBatch' => $batchName,
        'competencies' => $competencies,
        'topicsByCompetency' => $topicsByCompetency,
        'materialsByCompetency' => $materialsByCompetency
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_course_details_trainer: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
