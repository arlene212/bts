<?php
require_once __DIR__ . '/../DatabaseConnection.php';
$db = new DatabaseConnection();
$pdo = $db->getConnection();
$pdo->beginTransaction();
try {
  $col = $pdo->query("SHOW COLUMNS FROM courses LIKE 'competency_name'");
  if (!$col || $col->rowCount() === 0) {
    $pdo->exec("ALTER TABLE courses ADD COLUMN competency_name VARCHAR(255) NULL AFTER course_name");
  }
  $col = $pdo->query("SHOW COLUMNS FROM courses LIKE 'module_title'");
  if (!$col || $col->rowCount() === 0) {
    $pdo->exec("ALTER TABLE courses ADD COLUMN module_title VARCHAR(100) NULL AFTER competency_name");
  }
  $col = $pdo->query("SHOW COLUMNS FROM courses LIKE 'nominal_hours'");
  if (!$col || $col->rowCount() === 0) {
    $pdo->exec("ALTER TABLE courses ADD COLUMN nominal_hours INT UNSIGNED NULL AFTER hours");
  }
  $col = $pdo->query("SHOW COLUMNS FROM courses LIKE 'learning_outcomes'");
  if ($col && $col->rowCount() > 0) {
    $pdo->exec("ALTER TABLE courses MODIFY COLUMN learning_outcomes MEDIUMTEXT NULL");
  }
  $pdo->exec("UPDATE courses SET module_title = COALESCE(NULLIF(TRIM(module_title), ''), course_name)");
  $pdo->exec("UPDATE courses SET nominal_hours = COALESCE(NULLIF(nominal_hours, 0), hours)");
  $pdo->exec("UPDATE courses SET learning_outcomes = COALESCE(learning_outcomes, '')");
  $pdo->exec("UPDATE courses c JOIN (SELECT course_id, MIN(id) AS mid FROM competencies WHERE status='active' GROUP BY course_id) x ON x.course_id = c.id JOIN competencies comp ON comp.id = x.mid SET c.competency_name = COALESCE(NULLIF(TRIM(c.competency_name), ''), comp.competency_name)");
  $pdo->exec("UPDATE courses SET competency_name = COALESCE(NULLIF(TRIM(competency_name), ''), course_name)");
  $pdo->exec("ALTER TABLE courses MODIFY COLUMN competency_name VARCHAR(255) NOT NULL, MODIFY COLUMN module_title VARCHAR(100) NOT NULL, MODIFY COLUMN learning_outcomes MEDIUMTEXT NOT NULL, MODIFY COLUMN nominal_hours INT UNSIGNED NOT NULL");
  $idx = $pdo->query("SHOW INDEX FROM courses WHERE Key_name='idx_courses_module_title'");
  if (!$idx || $idx->rowCount() === 0) { $pdo->exec("ALTER TABLE courses ADD KEY idx_courses_module_title (module_title)"); }
  $idx = $pdo->query("SHOW INDEX FROM courses WHERE Key_name='idx_courses_competency_name'");
  if (!$idx || $idx->rowCount() === 0) { $pdo->exec("ALTER TABLE courses ADD KEY idx_courses_competency_name (competency_name)"); }
  $idx = $pdo->query("SHOW INDEX FROM courses WHERE Key_name='idx_courses_nominal_hours'");
  if (!$idx || $idx->rowCount() === 0) { $pdo->exec("ALTER TABLE courses ADD KEY idx_courses_nominal_hours (nominal_hours)"); }
  $pdo->commit();
  echo json_encode(['success' => true]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) { $pdo->rollBack(); }
  echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
