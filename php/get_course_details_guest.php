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

    // Get 'basic' competencies for this course from competencies table via course_topics
    // Fetch basic competencies by course_id
  $cstmt = $pdo->prepare("SELECT competency_code, competency_name, module_title, competency_type, nominal_hours, description, learning_outcomes FROM competencies WHERE course_id = (SELECT id FROM courses WHERE course_code = ?) AND status = 'active' AND competency_type = 'basic' ORDER BY unit_order, competency_name");
    $cstmt->execute([$courseCode]);
    $basicCompetencies = $cstmt->fetchAll(PDO::FETCH_ASSOC);

    $structuredContent = [];

    if (!empty($basicCompetencies)) {
        // Get topics, materials, and activities, but only for 'basic' competencies
        $inClause = implode(',', array_fill(0, count($basicCompetencies), '?'));
        $stmt = $pdo->prepare("\n            SELECT \n                ct.id as topic_id, ct.competency_id, ct.topic_name, ct.topic_description,\n                tm.id as material_id, tm.material_title, tm.material_description, tm.file_path as material_file_path,\n                ta.id as activity_id, ta.activity_title, ta.activity_description, ta.activity_type, ta.due_date, ta.max_score, ta.attachment_path\n            FROM course_topics ct\n            LEFT JOIN topic_materials tm ON ct.id = tm.topic_id\n            LEFT JOIN topic_activities ta ON ct.id = ta.topic_id\n            WHERE ct.course_code = ? AND ct.competency_id IN ($inClause)\n            ORDER BY ct.created_at ASC, tm.uploaded_at ASC, ta.created_at ASC\n        ");
        $params = array_merge([$courseCode], array_map(function($c){ return $c['competency_code']; }, $basicCompetencies));
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process the flat results into a structured array
        $topics = [];
        foreach ($results as $row) {
            $topicId = $row['topic_id'];
            if (!isset($topics[$topicId])) {
                $topics[$topicId] = [
                    'id' => $topicId,
                    'name' => $row['topic_name'],
                    'competency_id' => $row['competency_id'],
                    'materials' => [],
                    'activities' => []
                ];
            }
            if ($row['material_id'] && !isset($topics[$topicId]['materials'][$row['material_id']])) {
                $topics[$topicId]['materials'][$row['material_id']] = [
                    'id' => $row['material_id'],
                    'title' => $row['material_title'],
                    'description' => $row['material_description'],
                    'file_path' => $row['material_file_path']
                ];
            }
            if ($row['activity_id'] && !isset($topics[$topicId]['activities'][$row['activity_id']])) {
                $topics[$topicId]['activities'][$row['activity_id']] = [
                    'id' => $row['activity_id'],
                    'title' => $row['activity_title'],
                    'type' => $row['activity_type']
                ];
            }
        }

        // Group topics under their respective basic competencies and clean up the structure
        foreach ($basicCompetencies as $comp) {
            $compTopics = [];
            foreach ($topics as $topic) {
                if ($topic['competency_id'] === $comp['competency_code']) {
                    $topic['materials'] = array_values($topic['materials']);
                    $topic['activities'] = array_values($topic['activities']);
                    $compTopics[] = $topic;
                }
            }
            $structuredContent[] = [
              'type' => $comp['competency_type'],
              'name' => $comp['competency_name'],
              'description' => $comp['description'] ?? '',
              'topics' => $compTopics
            ];
        }
    }
    
    echo json_encode([
        'course' => $course,
        'competencies' => $structuredContent // Send the structured, filtered content as 'competencies'
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_course_details_guest: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
