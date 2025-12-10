<?php
require_once __DIR__ . '/../../php/SessionManager.php';
require_once __DIR__ . '/../../php/DatabaseConnection.php';

SessionManager::startSession();
SessionManager::requireRole('trainer');

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    $database = new DatabaseConnection();
    $pdo = $database->getConnection();
    $user = SessionManager::getCurrentUser();

    $action = $_POST['action'] ?? '';

    if ($action === 'get_activity') {
        $activityId = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;
        if ($activityId <= 0) { echo json_encode(['success'=>false,'message'=>'Activity ID is required']); exit; }
        $stmt = $pdo->prepare(
            "SELECT ta.*, ct.topic_name, ct.course_code, c.course_name
             FROM topic_activities ta
             JOIN course_topics ct ON ta.topic_id = ct.id
             JOIN courses c ON ct.course_code = c.course_code
             WHERE ta.id = ?"
        );
        $stmt->execute([$activityId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { echo json_encode(['success'=>false,'message'=>'Activity not found']); exit; }
        $chk = $pdo->prepare("SELECT 1 FROM course_assignments WHERE trainer_id = ? AND course_code = ? LIMIT 1");
        $chk->execute([$user['user_id'], $row['course_code']]);
        if (!$chk->fetchColumn()) { echo json_encode(['success'=>false,'message'=>'Access denied']); exit; }
        if (strtolower($row['activity_type']) === 'quiz') { echo json_encode(['success'=>false,'message'=>'Use quiz editor']); exit; }
        echo json_encode(['success'=>true,'activity'=>$row]);
        exit;
    }

    if ($action === 'update_activity') {
        $activityId = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;
        $title = trim($_POST['activity_title'] ?? '');
        $description = trim($_POST['activity_description'] ?? '');
        $dueRaw = $_POST['due_date'] ?? '';
        $startRaw = $_POST['start_date'] ?? '';
        $maxScore = isset($_POST['max_score']) ? intval($_POST['max_score']) : null;
        if ($activityId <= 0 || $title === '') { echo json_encode(['success'=>false,'message'=>'Missing required fields']); exit; }
        $ctx = $pdo->prepare(
            "SELECT ta.id, ta.activity_type, ct.course_code
             FROM topic_activities ta
             JOIN course_topics ct ON ta.topic_id = ct.id
             WHERE ta.id = ?"
        );
        $ctx->execute([$activityId]);
        $row = $ctx->fetch(PDO::FETCH_ASSOC);
        if (!$row) { echo json_encode(['success'=>false,'message'=>'Activity not found']); exit; }
        $chk = $pdo->prepare("SELECT 1 FROM course_assignments WHERE trainer_id = ? AND course_code = ? LIMIT 1");
        $chk->execute([$user['user_id'], $row['course_code']]);
        if (!$chk->fetchColumn()) { echo json_encode(['success'=>false,'message'=>'Access denied']); exit; }
        if (strtolower($row['activity_type']) === 'quiz') { echo json_encode(['success'=>false,'message'=>'Use quiz editor']); exit; }
        $dueDate = null;
        $startDate = null;
        if (!empty($dueRaw)) {
            $dueDate = str_replace('T',' ', $dueRaw);
            if (strlen($dueDate) === 16) { $dueDate .= ':00'; }
        }
        if (!empty($startRaw)) {
            $startDate = str_replace('T',' ', $startRaw);
            if (strlen($startDate) === 16) { $startDate .= ':00'; }
        }
        $sql = "UPDATE topic_activities SET activity_title = ?, activity_description = ?, due_date = ?, start_date = ?, max_score = ? WHERE id = ?";
        $pdo->prepare($sql)->execute([$title, $description, $dueDate, $startDate, $maxScore, $activityId]);
        echo json_encode(['success'=>true]);
        exit;
    }

    echo json_encode(['success'=>false,'message'=>'Invalid action']);
} catch (PDOException $e) {
    error_log('Database error in activity_handler.php: ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Error in activity_handler.php: ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}
?>
