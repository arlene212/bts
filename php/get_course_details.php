<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('admin');

header('Content-Type: application/json');

if (!isset($_GET['course_code'])) {
    echo json_encode(['error' => 'Course code is required']);
    exit;
}

$courseCode = $_GET['course_code'];
$database = new DatabaseConnection();
$pdo = $database->getConnection();

try {
    // Get course basic info
    $courseStmt = $pdo->prepare("SELECT * FROM courses WHERE course_code = ?");
    $courseStmt->execute([$courseCode]);
    $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        echo json_encode(['error' => 'Course not found']);
        exit;
    }
    
    $response = ['course' => $course];
    
    // Get batches for this course
    $batchesStmt = $pdo->prepare("
        SELECT cb.*, COUNT(ba.id) as trainee_count
        FROM course_batches cb
        LEFT JOIN batch_assignments ba ON cb.course_code = ba.course_code AND cb.batch_name = ba.batch_name
        WHERE cb.course_code = ?
        GROUP BY cb.id
        ORDER BY cb.created_at DESC
    ");
    $batchesStmt->execute([$courseCode]);
    $response['batches'] = $batchesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get competencies
    $competencies = json_decode($course['competency_types'] ?? '[]', true);
    $response['competencies'] = [];
    
    foreach ($competencies as $competency) {
        if (!isset($competency['type']) || !isset($competency['name'])) {
            continue;
        }
        
        // Get topics for this competency
        $topicsStmt = $pdo->prepare("
            SELECT * FROM course_topics 
            WHERE course_code = ? AND competency_type = ?
            ORDER BY created_at
        ");
        $topicsStmt->execute([$courseCode, $competency['type']]);
        $topics = $topicsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $competencyWithTopics = $competency;
        $competencyWithTopics['topics'] = [];
        
        foreach ($topics as $topic) {
            // Get materials for this topic
            $materialsStmt = $pdo->prepare("
                SELECT tm.*, u.first_name, u.last_name, 
                       CONCAT(u.first_name, ' ', u.last_name) as trainer_name
                FROM topic_materials tm
                LEFT JOIN users u ON tm.uploaded_by = u.user_id
                WHERE tm.topic_id = ?
                ORDER BY tm.uploaded_at
            ");
            $materialsStmt->execute([$topic['id']]);
            $materials = $materialsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get activities for this topic
            $activitiesStmt = $pdo->prepare("
                SELECT ta.*, u.first_name, u.last_name
                FROM topic_activities ta
                LEFT JOIN users u ON ta.created_by = u.user_id
                WHERE ta.topic_id = ?
                ORDER BY ta.created_at
            ");
            $activitiesStmt->execute([$topic['id']]);
            $activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get submissions for each activity
            foreach ($activities as &$activity) {
                $submissionsStmt = $pdo->prepare("
                    SELECT asub.*, u.first_name, u.last_name,
                           CONCAT(u.first_name, ' ', u.last_name) as trainee_name
                    FROM activity_submissions asub
                    LEFT JOIN users u ON asub.trainee_id = u.user_id
                    WHERE asub.activity_id = ?
                    ORDER BY asub.submitted_at DESC
                ");
                $submissionsStmt->execute([$activity['id']]);
                $activity['submissions'] = $submissionsStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $topic['materials'] = $materials;
            $topic['activities'] = $activities;
            $competencyWithTopics['topics'][] = $topic;
        }
        
        $response['competencies'][] = $competencyWithTopics;
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Database error in get_course_details: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>