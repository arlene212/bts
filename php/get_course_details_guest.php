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

    // Get the names of 'basic' competencies for this specific course from its JSON data
    $courseCompetencies = json_decode($course['competency_types'] ?? '[]', true);
    $basicCompetencyNames = [];
    foreach ($courseCompetencies as $comp) {
        if (isset($comp['type']) && $comp['type'] === 'basic' && isset($comp['name'])) {
            $basicCompetencyNames[] = $comp['name'];
        }
    }

    // Get topics, materials, and activities, but only for 'basic' competencies
    $stmt = $pdo->prepare("
        SELECT 
            ct.id as topic_id, ct.competency_id, ct.topic_name, ct.topic_description,
            tm.id as material_id, tm.material_title, tm.material_description, tm.file_path as material_file_path,
            ta.id as activity_id, ta.activity_title, ta.activity_description, ta.activity_type, ta.due_date, ta.max_score, ta.attachment_path,
            s.id as submission_id, s.score, s.submitted_at, s.file_path as submission_file_path, s.submission_text, s.feedback
        FROM course_topics ct
        LEFT JOIN topic_materials tm ON ct.id = tm.topic_id
        LEFT JOIN topic_activities ta ON ct.id = ta.topic_id
        LEFT JOIN submissions s ON ta.id = s.activity_id AND s.trainee_id = ?
        WHERE ct.course_code = ? AND ct.competency_id IN (" . implode(',', array_fill(0, count($basicCompetencyNames), '?')) . ")
        ORDER BY ct.created_at ASC, tm.uploaded_at ASC, ta.created_at ASC
    ");
    $params = array_merge([$guestId, $courseCode], $basicCompetencyNames);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process the flat results into a structured array
    $competenciesData = json_decode($course['competency_types'] ?? '[]', true);
    $competencies = [];
    
    // Filter for basic competencies only
    $basicCompetenciesData = array_filter($competenciesData, function($comp) {
        return isset($comp['type']) && $comp['type'] === 'basic';
    });

    foreach ($basicCompetenciesData as $comp) {
        $competencies[$comp['name']] = [
            'type' => $comp['type'],
            'name' => $comp['name'],
            'description' => $comp['description'],
            'topics' => []
        ];
    }

    // Group topics and materials under their respective basic competencies
    foreach ($results as $row) {
        if (isset($competencies[$row['competency_id']])) {
            // This logic can be expanded to structure topics, materials, and activities
            // For now, we'll just pass the raw results for the JS to handle.
        }
    }

    echo json_encode([
        'course' => $course,
        'content' => $results // Send the filtered content
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_course_details_guest: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>