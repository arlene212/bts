<?php
function enrollGuest($db, $userId, $courseCode)
{
  $stmt = $db->prepare("SELECT status FROM enrollments WHERE trainee_id = ? AND course_code = ? ORDER BY date_requested DESC LIMIT 1");
  $stmt->execute([$userId, $courseCode]);
  $existing = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($existing && $existing['status'] === 'approved') {
    return ['success' => false, 'message' => 'Already enrolled in this course'];
  }
  $cstmt = $db->prepare("SELECT course_name FROM courses WHERE course_code = ?");
  $cstmt->execute([$courseCode]);
  $c = $cstmt->fetch(PDO::FETCH_ASSOC);
  if (!$c) { return ['success' => false, 'message' => 'Course not found']; }
  $stmt = $db->prepare("INSERT INTO enrollments (trainee_id, course_code, course_name, status, date_requested) VALUES (?, ?, ?, 'pending', NOW())");
  $stmt->execute([$userId, $courseCode, $c['course_name']]);
  return ['success' => true, 'message' => 'Enrollment request sent'];
}

function unenrollGuest($db, $userId, $courseCode)
{
  $stmt = $db->prepare("DELETE FROM enrollments WHERE trainee_id = ? AND course_code = ? AND status = 'approved'");
  $stmt->execute([$userId, $courseCode]);
  if ($stmt->rowCount() > 0) {
    return ['success' => true, 'message' => 'Unenrolled successfully'];
  }
  return ['success' => false, 'message' => 'No approved enrollment found'];
}

function getCourseDetailsForGuest($db, $courseCode, $userId)
{
  $courseStmt = $db->prepare("SELECT course_name, course_code, hours, description, image FROM courses WHERE course_code = ?");
  $courseStmt->execute([$courseCode]);
  $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
  if (!$course) {
    return ['error' => 'Course not found'];
  }
  $competenciesStmt = $db->prepare("SELECT type, name, description FROM course_competencies WHERE course_code = ? ORDER BY id ASC");
  $competenciesStmt->execute([$courseCode]);
  $competencies = $competenciesStmt->fetchAll(PDO::FETCH_ASSOC);
  return ['course' => $course, 'competencies' => $competencies, 'batches' => []];
}

function updateGuestProfile($db, $userId, $data)
{
  $oldPassword = $data['old_password'] ?? '';
  $newPassword = $data['new_password'] ?? '';
  $confirmPassword = $data['confirm_password'] ?? '';
  $stmt = $db->prepare("SELECT password FROM users WHERE user_id = ?");
  $stmt->execute([$userId]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$user) { return ['success' => false, 'message' => 'User not found']; }
  if (!empty($newPassword)) {
    if (empty($oldPassword) || !password_verify($oldPassword, $user['password'])) { return ['success' => false, 'message' => 'Incorrect old password']; }
    if ($newPassword !== $confirmPassword) { return ['success' => false, 'message' => 'Passwords do not match']; }
  }
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
  if (!empty($set)) {
    $sql = "UPDATE users SET " . implode(', ', $set) . " WHERE user_id = ?";
    $params[] = $userId;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
  }
  if (!empty($newPassword)) {
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $pwdStmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $pwdStmt->execute([$hash, $userId]);
  }
  $stmt = $db->prepare("SELECT first_name, last_name, email, contact_number, profile_picture FROM users WHERE user_id = ?");
  $stmt->execute([$userId]);
  $updated = $stmt->fetch(PDO::FETCH_ASSOC);
  return ['success' => true, 'message' => 'Profile updated', 'user' => $updated];
}
?>