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

    // 2. Decode competencies from the course table.
    $course['competency_types'] = [];
    if (!empty($course['competency_types'])) {
        $decodedCompetencies = json_decode($course['competency_types'], true);
        if (is_array($decodedCompetencies)) {
            $course['competency_types'] = $decodedCompetencies;
        }
    }

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
    foreach ($course['competency_types'] as $competency) {
        if (empty($competency['name'])) continue;

        $competencyName = $competency['name']; // The competency name is used as the ID in the topics table.
        $competency['topics'] = [];

        // Fetch topics for this competency.
        $topicStmt = $pdo->prepare("SELECT * FROM course_topics WHERE course_code = ? AND competency_id = ? ORDER BY created_at ASC");
        $topicStmt->execute([$courseCode, $competencyName]);
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
                    JOIN users u ON s.trainee_id = u.user_id 
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
    $response = [
        'course' => $course,
        'batches' => $batches,
        'competencies' => $competenciesWithContent
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Admin - Error fetching course details: " . $e->getMessage());
    echo json_encode(['error' => 'Database error while fetching course details.']);
}
?>