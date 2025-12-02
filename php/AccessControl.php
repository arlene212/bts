<?php
require_once __DIR__ . '/DatabaseConnection.php';
require_once __DIR__ . '/SessionManager.php';

class AccessControl {
    public static function requireTrainerBatchAccess(PDO $pdo, string $trainerId, string $courseCode, string $batchName): void {
        $stmt = $pdo->prepare("SELECT 1 FROM course_batches WHERE course_code = ? AND batch_name = ? AND trainer_id = ? LIMIT 1");
        $stmt->execute([$courseCode, $batchName, $trainerId]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied: trainer is not assigned to this batch']);
            exit;
        }
    }

    public static function resourceIsMappedToBatch(PDO $pdo, string $courseCode, string $batchName, string $resourceType, int $resourceId): bool {
        try {
            $tchk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'batch_resources'");
            $tchk->execute();
            if ((int)$tchk->fetchColumn() === 0) {
                return true;
            }
        } catch (Exception $__) {
            return true;
        }
        $stmt = $pdo->prepare("SELECT 1 FROM batch_resources WHERE course_code = ? AND batch_name = ? AND resource_type = ? AND resource_id = ? LIMIT 1");
        $stmt->execute([$courseCode, $batchName, $resourceType, $resourceId]);
        return (bool)$stmt->fetchColumn();
    }

    public static function mapResourceToBatch(PDO $pdo, string $courseCode, string $batchName, string $resourceType, int $resourceId, string $trainerId): void {
        try {
            $tchk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'batch_resources'");
            $tchk->execute();
            if ((int)$tchk->fetchColumn() === 0) {
                return;
            }
        } catch (Exception $__) { return; }
        $stmt = $pdo->prepare("INSERT IGNORE INTO batch_resources (course_code, batch_name, resource_type, resource_id, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$courseCode, $batchName, $resourceType, $resourceId, $trainerId]);
    }

    public static function traineeInBatch(PDO $pdo, string $traineeId, string $courseCode, string $batchName): bool {
        $stmt = $pdo->prepare("SELECT 1 FROM batch_assignments WHERE trainee_id = ? AND course_code = ? AND batch_name = ? LIMIT 1");
        $stmt->execute([$traineeId, $courseCode, $batchName]);
        return (bool)$stmt->fetchColumn();
    }

    public static function audit(PDO $pdo, array $ctx): void {
        $user = $_SESSION['user'] ?? null;
        $userId = $user['user_id'] ?? 'unknown';
        $role = $user['role'] ?? 'guest';
        $courseCode = $ctx['course_code'] ?? 'unknown';
        $batchName = $ctx['batch_name'] ?? 'unknown';
        $action = $ctx['action'] ?? 'ACCESS';
        $resourceType = $ctx['resource_type'] ?? null;
        $resourceId = $ctx['resource_id'] ?? null;
        $interface = $ctx['interface'] ?? (php_sapi_name() === 'cli' ? 'api' : 'web');
        $endpoint = $_SERVER['REQUEST_URI'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        try {
            $stmt = $pdo->prepare("INSERT INTO access_audit (user_id, role, course_code, batch_name, action, resource_type, resource_id, interface, endpoint, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $role, $courseCode, $batchName, $action, $resourceType, $resourceId, $interface, $endpoint, $ip, $ua]);
        } catch (Exception $__) {
            // swallow audit errors
        }
    }
}
?>
