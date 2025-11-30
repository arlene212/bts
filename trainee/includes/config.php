<?php
require_once __DIR__ . '/../../php/DatabaseConnection.php';

function trainee_db_connection()
{
  static $pdo = null;
  if ($pdo === null) {
    $db = new DatabaseConnection();
    $pdo = $db->getConnection();
  }
  return $pdo;
}
?>
