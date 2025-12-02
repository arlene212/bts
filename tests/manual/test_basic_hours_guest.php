<?php
require_once __DIR__ . '/../../php/DatabaseConnection.php';

echo "Testing guest basic competency hours...\n";

$db = (new DatabaseConnection())->getConnection();

$sql = "SELECT c.course_code, c.course_name, c.hours,
               COALESCE((SELECT SUM(comp.nominal_hours)
                          FROM competencies comp
                          WHERE comp.course_id = c.id
                            AND comp.status = 'active'
                            AND comp.competency_type = 'basic'), 0) AS basic_hours
        FROM courses c
        WHERE c.course_status = 'published'
        ORDER BY c.course_code";

$stmt = $db->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
  printf("%s | %s | basic_hours=%d | total_hours=%d\n",
    $row['course_code'],
    $row['course_name'],
    (int)$row['basic_hours'],
    (int)$row['hours']
  );
}

echo "Done.\n";
?>
