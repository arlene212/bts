<?php
require_once __DIR__ . '/DatabaseConnection.php';
require_once __DIR__ . '/SessionManager.php';
require_once __DIR__ . '/AccessControl.php';

SessionManager::startSession();
SessionManager::requireRole('trainer');
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $courseCode = trim($input['course_code'] ?? '');
    $batchName = trim($input['batch_name'] ?? '');
    $resourceType = trim($input['resource_type'] ?? '');
    $resourceId = (int)($input['resource_id'] ?? 0);
    $action = trim($input['action'] ?? '');
    if (!$courseCode || !$batchName || !$resourceType || !$resourceId || !in_array($action, ['give','ungive'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    if (!in_array($resourceType, ['material','activity'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid resource type']);
        exit;
    }

    $db = new DatabaseConnection();
    $pdo = $db->getConnection();
    $trainerId = $_SESSION['user']['user_id'] ?? '';
    AccessControl::requireTrainerBatchAccess($pdo, $trainerId, $courseCode, $batchName);

    if ($action === 'give') {
        AccessControl::mapResourceToBatch($pdo, $courseCode, $batchName, $resourceType, $resourceId, $trainerId);
        AccessControl::audit($pdo, ['course_code' => $courseCode, 'batch_name' => $batchName, 'action' => 'GIVE_RESOURCE', 'resource_type' => $resourceType, 'resource_id' => $resourceId]);
    } else {
        AccessControl::unmapResourceFromBatch($pdo, $courseCode, $batchName, $resourceType, $resourceId);
        AccessControl::audit($pdo, ['course_code' => $courseCode, 'batch_name' => $batchName, 'action' => 'UNGIVE_RESOURCE', 'resource_type' => $resourceType, 'resource_id' => $resourceId]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>

