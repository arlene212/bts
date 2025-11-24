<?php
function getEnrollmentDetails($db, $requestId, $trainerId)
{
  // Trainers can only view enrollment details for their assigned courses
  $stmt = $db->prepare("SELECT e.*, u.first_name, u.last_name, u.email, c.course_name, c.course_code, ca.trainer_id 
                       FROM enrollments e 
                       JOIN users u ON e.trainee_id = u.user_id 
                       JOIN courses c ON e.course_code = c.course_code 
                       JOIN course_assignments ca ON c.course_code = ca.course_code 
                       WHERE e.id = ? AND ca.trainer_id = ?");
  $stmt->execute([$requestId, $trainerId]);
  $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if ($enrollment) {
    return ['success' => true, 'data' => $enrollment, 'message' => 'Enrollment details retrieved'];
  } else {
    return ['success' => false, 'message' => 'Enrollment not found or access denied'];
  }
}

function getEnrollmentStatus($db, $requestId, $trainerId)
{
  // Trainers can check status for their assigned courses
  $stmt = $db->prepare("SELECT e.status, e.date_requested, e.processed_date, e.remarks, c.course_name, u.first_name, u.last_name 
                       FROM enrollments e 
                       JOIN users u ON e.trainee_id = u.user_id 
                       JOIN courses c ON e.course_code = c.course_code 
                       JOIN course_assignments ca ON c.course_code = ca.course_code 
                       WHERE e.id = ? AND ca.trainer_id = ?");
  $stmt->execute([$requestId, $trainerId]);
  $status = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if ($status) {
    return ['success' => true, 'data' => $status, 'message' => 'Enrollment status retrieved'];
  } else {
    return ['success' => false, 'message' => 'Enrollment not found or access denied'];
  }
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