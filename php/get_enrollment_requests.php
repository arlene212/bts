<?php
require_once 'SessionManager.php';
require_once 'DatabaseConnection.php';

SessionManager::startSession();

$user = SessionManager::getCurrentUser();
if (!$user || !in_array($user['role'], ['trainee', 'guest'])) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("SELECT e.id, c.course_name, e.status, e.remarks, e.date_requested 
                           FROM enrollments e
                           JOIN courses c ON e.course_code = c.course_code
                           WHERE e.trainee_id = ? 
                           ORDER BY e.date_requested DESC");
    $stmt->execute([$user['user_id']]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'requests' => $requests]);
} catch (PDOException $e) {
    error_log("Get enrollment requests error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>