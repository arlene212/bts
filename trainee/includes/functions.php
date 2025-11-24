<?php
function requestEnroll($db, $userId, $courseCode)
{
  $stmt = $db->prepare("SELECT status FROM enrollments WHERE trainee_id = ? AND course_code = ? ORDER BY date_requested DESC LIMIT 1");
  $stmt->execute([$userId, $courseCode]);
  $existing = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($existing && $existing['status'] === 'approved') {
    return ['success' => false, 'message' => 'Already enrolled in this course'];
  }
  if ($existing && $existing['status'] === 'pending') {
    return ['success' => false, 'message' => 'Request already pending'];
  }
  $cstmt = $db->prepare("SELECT course_name FROM courses WHERE course_code = ?");
  $cstmt->execute([$courseCode]);
  $c = $cstmt->fetch(PDO::FETCH_ASSOC);
  if (!$c) { return ['success' => false, 'message' => 'Course not found']; }
  $stmt = $db->prepare("INSERT INTO enrollments (trainee_id, course_code, course_name, status, date_requested) VALUES (?, ?, ?, 'pending', NOW())");
  $stmt->execute([$userId, $courseCode, $c['course_name']]);
  return ['success' => true, 'message' => 'Enrollment request sent'];
}

function cancelEnrollmentRequest($db, $userId, $requestId)
{
  $stmt = $db->prepare("DELETE FROM enrollments WHERE id = ? AND trainee_id = ? AND status = 'pending'");
  $stmt->execute([$requestId, $userId]);
  if ($stmt->rowCount() > 0) {
    return ['success' => true, 'message' => 'Enrollment request canceled'];
  }
  return ['success' => false, 'message' => 'No pending request found'];
}

function getCourseDetailsForTrainee($db, $courseCode, $userId)
{
  $courseStmt = $db->prepare("SELECT course_name, course_code, hours, description, image FROM courses WHERE course_code = ?");
  $courseStmt->execute([$courseCode]);
  $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
  if (!$course) { return ['error' => 'Course not found']; }
  $competenciesStmt = $db->prepare("SELECT type, name, description FROM course_competencies WHERE course_code = ? ORDER BY id ASC");
  $competenciesStmt->execute([$courseCode]);
  $competencies = $competenciesStmt->fetchAll(PDO::FETCH_ASSOC);
  return ['course' => $course, 'competencies' => $competencies];
}

function updateTraineeProfile($db, $userId, $data)
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