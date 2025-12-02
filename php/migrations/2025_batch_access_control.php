<?php
require_once __DIR__ . '/../DatabaseConnection.php';

try {
    $db = new DatabaseConnection();
    $pdo = $db->getConnection();

    // Helper to check table existence
    $hasTable = function($name) use ($pdo) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $stmt->execute([$name]);
        return (int)$stmt->fetchColumn() > 0;
    };

    // Ensure unique keys on course_batches for composite references
    if ($hasTable('course_batches')) {
        $idxExists = function($table, $index) use ($pdo) {
            $q = $pdo->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?");
            $q->execute([$table, $index]);
            return (int)$q->fetchColumn() > 0;
        };
        if (!$idxExists('course_batches', 'uniq_course_batch')) {
            $pdo->exec("ALTER TABLE course_batches ADD UNIQUE KEY uniq_course_batch (course_code, batch_name)");
        }
        if (!$idxExists('course_batches', 'idx_course_batch_trainer')) {
            $pdo->exec("ALTER TABLE course_batches ADD INDEX idx_course_batch_trainer (course_code, batch_name, trainer_id)");
        }
    }

    // Strengthen batch_assignments FK to the existing course+batch
    if ($hasTable('batch_assignments') && $hasTable('course_batches')) {
        // Drop existing FK if any with same name to avoid conflicts
        try { $pdo->exec("ALTER TABLE batch_assignments DROP FOREIGN KEY fk_batch_assign_course_batch"); } catch (Exception $__) {}
        try {
            $pdo->exec("ALTER TABLE batch_assignments ADD CONSTRAINT fk_batch_assign_course_batch FOREIGN KEY (course_code, batch_name) REFERENCES course_batches (course_code, batch_name) ON UPDATE CASCADE ON DELETE RESTRICT");
        } catch (Exception $__) {}
    }

    // Create batch_resources mapping table to scope resources to batches
    if (!$hasTable('batch_resources')) {
        $pdo->exec("CREATE TABLE batch_resources (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_code VARCHAR(50) NOT NULL,
            batch_name VARCHAR(100) NOT NULL,
            resource_type ENUM('material','activity') NOT NULL,
            resource_id INT NOT NULL,
            created_by VARCHAR(50) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_batch_resource (resource_type, resource_id, course_code, batch_name),
            KEY idx_batch_resources_course_batch (course_code, batch_name),
            CONSTRAINT fk_batch_resources_course_batch FOREIGN KEY (course_code, batch_name) REFERENCES course_batches (course_code, batch_name) ON UPDATE CASCADE ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // Create access audit table for trainer interactions
    if (!$hasTable('access_audit')) {
        $pdo->exec("CREATE TABLE access_audit (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(50) NOT NULL,
            role ENUM('admin','trainer','trainee','guest') NOT NULL,
            course_code VARCHAR(50) NOT NULL,
            batch_name VARCHAR(100) NOT NULL,
            action VARCHAR(64) NOT NULL,
            resource_type VARCHAR(32) DEFAULT NULL,
            resource_id INT DEFAULT NULL,
            interface VARCHAR(16) NOT NULL DEFAULT 'web',
            endpoint VARCHAR(255) DEFAULT NULL,
            ip_address VARCHAR(64) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_access_audit_user (user_id, role),
            KEY idx_access_audit_course_batch (course_code, batch_name),
            KEY idx_access_audit_action (action)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    echo json_encode(['success' => true, 'message' => 'Batch access control migration applied']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Migration failed', 'details' => $e->getMessage()]);
}
?>
