<?php
require_once __DIR__ . '/DatabaseConnection.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
  $db = new DatabaseConnection();
  $pdo = $db->getConnection();
  $pdo->query('SELECT 1');

  $tables = ['users','courses','enrollments'];
  $counts = [];
  foreach ($tables as $t) {
    try {
      $counts[$t] = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    } catch (Exception $__) {
      $counts[$t] = null;
    }
  }

  echo json_encode([
    'ok' => true,
    'db' => [
      'host' => DB_HOST,
      'name' => DB_NAME,
    ],
    'tables' => $counts,
  ]);
} catch (Exception $e) {
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
?>
