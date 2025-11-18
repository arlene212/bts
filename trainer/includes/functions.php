<?php
function approveEnrollment($db, $requestId, $trainerId)
{
  $stmt = $db->prepare("UPDATE enrollments e JOIN course_assignments ca ON e.course_code = ca.course_code SET e.status = 'approved', e.processed_date = NOW(), e.processed_by = ? WHERE e.id = ? AND ca.trainer_id = ?");
  $stmt->execute([$trainerId, $requestId, $trainerId]);
  return ['success' => $stmt->rowCount() > 0, 'message' => $stmt->rowCount() > 0 ? 'Enrollment approved' : 'Approval failed'];
}

function rejectEnrollment($db, $requestId, $trainerId)
{
  $stmt = $db->prepare("UPDATE enrollments e JOIN course_assignments ca ON e.course_code = ca.course_code SET e.status = 'rejected', e.processed_date = NOW(), e.processed_by = ? WHERE e.id = ? AND ca.trainer_id = ?");
  $stmt->execute([$trainerId, $requestId, $trainerId]);
  return ['success' => $stmt->rowCount() > 0, 'message' => $stmt->rowCount() > 0 ? 'Enrollment rejected' : 'Rejection failed'];
}

function updateTrainerProfile($db, $userId, $data)
{
  $set = [];
  $params = [];
  $fields = [
    'first_name' => trim($data['first_name'] ?? ''),
    'last_name' => trim($data['last_name'] ?? ''),
    'email' => trim($data['email'] ?? ''),
    'contact_number' => trim($data['contact_number'] ?? ''),
  ];
  foreach ($fields as $col => $val) { if ($val !== '') { $set[] = "$col = ?"; $params[] = $val; } }
  if (!empty($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
    $uploadDir = __DIR__ . '/../../uploads/profiles/';
    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
    $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $fileName = 'profile_' . $userId . '_' . time() . '.' . $ext;
    $targetPath = $uploadDir . $fileName;
    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath)) { $set[] = "profile_picture = ?"; $params[] = $fileName; }
  }
  if (empty($set)) { return ['success' => true, 'message' => 'No changes', 'user' => []]; }
  $sql = "UPDATE users SET " . implode(', ', $set) . " WHERE user_id = ?";
  $params[] = $userId;
  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  $stmt = $db->prepare("SELECT first_name, last_name, email, contact_number, profile_picture FROM users WHERE user_id = ?");
  $stmt->execute([$userId]);
  $updated = $stmt->fetch(PDO::FETCH_ASSOC);
  return ['success' => true, 'message' => 'Profile updated', 'user' => $updated];
}
?>