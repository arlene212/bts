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

    // Get topics, materials, and activities
    $stmt = $pdo->prepare("
        SELECT 
            ct.id as topic_id, ct.competency_id, ct.topic_name, ct.topic_description,
            tm.id as material_id, tm.material_title, tm.material_description, tm.file_path as material_file_path,
            ta.id as activity_id, ta.activity_title, ta.activity_description, ta.activity_type, ta.due_date, ta.max_score,
            asub.id as submission_id, asub.file_path as submission_file, asub.submitted_at, asub.score, asub.feedback
        FROM course_topics ct
        LEFT JOIN topic_materials tm ON ct.id = tm.topic_id
        LEFT JOIN topic_activities ta ON ct.id = ta.topic_id
        LEFT JOIN activity_submissions asub ON ta.id = asub.activity_id AND asub.trainee_id = ?
        WHERE ct.course_code = ?
        ORDER BY ct.created_at ASC, tm.uploaded_at ASC, ta.created_at ASC
    ");
    $stmt->execute([$traineeId, $courseCode]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process the flat results into a structured array
    $competenciesData = json_decode($course['competency_types'] ?? '[]', true);
    $competencies = [];
    foreach ($competenciesData as $comp) {
        $competencies[$comp['name']] = [
            'type' => $comp['type'],
            'name' => $comp['name'],
            'description' => $comp['description'],
            'topics' => []
        ];
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

        // Group materials under topics
        if ($row['material_id'] && !isset($topics[$row['topic_id']]['materials'][$row['material_id']])) {
            $topics[$row['topic_id']]['materials'][$row['material_id']] = [
                'id' => $row['material_id'],
                'title' => $row['material_title'],
                'description' => $row['material_description'],
                'file_path' => $row['material_file_path']
            ];
        }

        // Group activities under topics and also create a flat list of activities
        if ($row['activity_id'] && !isset($topics[$row['topic_id']]['activities'][$row['activity_id']])) {
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
            $topics[$row['topic_id']]['activities'][$row['activity_id']] = $activity;
            $activities[$row['activity_id']] = $activity;
        }
    }

    // Assign topics to competencies
    foreach ($topics as $topic) {
        if (isset($competencies[$topic['competency_id']])) {
            $topic['materials'] = array_values($topic['materials']);
            $topic['activities'] = array_values($topic['activities']);
            $competencies[$topic['competency_id']]['topics'][] = $topic;
        }
    }

    echo json_encode([
        'course' => $course,
        'competencies' => array_values($competencies),
        'activities' => array_values($activities)
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_course_details_trainee: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>