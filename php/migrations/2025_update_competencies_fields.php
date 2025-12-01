<?php
require_once __DIR__ . '/../DatabaseConnection.php';
$db = new DatabaseConnection();
$pdo = $db->getConnection();
$pdo->beginTransaction();
try {
  $col = $pdo->query("SHOW COLUMNS FROM competencies LIKE 'module_title'");
  if (!$col || $col->rowCount() === 0) { $pdo->exec("ALTER TABLE competencies ADD COLUMN module_title VARCHAR(255) NULL AFTER competency_name"); }
  $col = $pdo->query("SHOW COLUMNS FROM competencies LIKE 'learning_outcomes'");
  if (!$col || $col->rowCount() === 0) { $pdo->exec("ALTER TABLE competencies ADD COLUMN learning_outcomes MEDIUMTEXT NULL AFTER description"); }
  $col = $pdo->query("SHOW COLUMNS FROM competencies LIKE 'nominal_hours'");
  if (!$col || $col->rowCount() === 0) { $pdo->exec("ALTER TABLE competencies ADD COLUMN nominal_hours INT UNSIGNED NULL AFTER competency_type"); }
  $col = $pdo->query("SHOW COLUMNS FROM competencies LIKE 'unit_order'");
  if (!$col || $col->rowCount() === 0) { $pdo->exec("ALTER TABLE competencies ADD COLUMN unit_order INT UNSIGNED NULL AFTER competency_code"); }
  $idx = $pdo->query("SHOW INDEX FROM competencies WHERE Key_name='idx_comp_course_type_order'");
  if (!$idx || $idx->rowCount() === 0) { $pdo->exec("ALTER TABLE competencies ADD KEY idx_comp_course_type_order (course_id, competency_type, unit_order)"); }
  $pdo->exec("UPDATE competencies SET module_title = COALESCE(NULLIF(TRIM(module_title), ''), competency_name)");
  $pdo->exec("UPDATE competencies SET learning_outcomes = COALESCE(learning_outcomes, '')");
  $pdo->exec("UPDATE competencies SET nominal_hours = COALESCE(nominal_hours, 0)");
  $pdo->commit();
  echo json_encode(['success' => true]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) { $pdo->rollBack(); }
  echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

