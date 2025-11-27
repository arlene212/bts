<?php
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

function requestEnroll($db, $traineeId, $courseCode) {
  try {
    // Validate inputs
    if (empty($courseCode)) {
      return ['success' => false, 'message' => 'Course code is required'];
    }
    
    // Check if course exists and is published
    $stmt = $db->prepare("SELECT * FROM courses WHERE course_code = ? AND course_status = 'published'");
    $stmt->execute([$courseCode]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
      return ['success' => false, 'message' => 'Course not found or not available'];
    }
    
    // Check if already enrolled or has pending request
    $stmt = $db->prepare("SELECT * FROM enrollments WHERE trainee_id = ? AND course_code = ?");
    $stmt->execute([$traineeId, $courseCode]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
      if ($existing['status'] === 'approved') {
        return ['success' => false, 'message' => 'You are already enrolled in this course'];
      } elseif ($existing['status'] === 'pending') {
        return ['success' => false, 'message' => 'You already have a pending request for this course'];
      } elseif ($existing['status'] === 'rejected') {
        // Update the rejected request to pending
        $stmt = $db->prepare("UPDATE enrollments SET status = 'pending', date_requested = NOW() WHERE id = ?");
        $stmt->execute([$existing['id']]);
        return ['success' => true, 'message' => 'Enrollment request resubmitted successfully'];
      }
    }
    
    // Insert new enrollment request
    $stmt = $db->prepare("INSERT INTO enrollments (trainee_id, course_code, status, date_requested) VALUES (?, ?, 'pending', NOW())");
    $stmt->execute([$traineeId, $courseCode]);
    
    if ($stmt->rowCount() > 0) {
      return ['success' => true, 'message' => 'Enrollment request sent successfully'];
    } else {
      return ['success' => false, 'message' => 'Failed to create enrollment request'];
    }
    
  } catch (PDOException $e) {
    error_log("Enrollment request error: " . $e->getMessage());
    return ['success' => false, 'message' => 'Database error occurred'];
  }
}

/**
 * Cancel an enrollment request
 */
function cancelEnrollmentRequest($db, $traineeId, $requestId) {
  try {
    if (empty($requestId)) {
      return ['success' => false, 'message' => 'Request ID is required'];
    }
    
    // Verify the request belongs to the trainee and is pending
    $stmt = $db->prepare("SELECT * FROM enrollments WHERE id = ? AND trainee_id = ? AND status = 'pending'");
    $stmt->execute([$requestId, $traineeId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
      return ['success' => false, 'message' => 'Request not found or cannot be cancelled'];
    }
    
    // Delete the request
    $stmt = $db->prepare("DELETE FROM enrollments WHERE id = ?");
    $stmt->execute([$requestId]);
    
    if ($stmt->rowCount() > 0) {
      return ['success' => true, 'message' => 'Enrollment request cancelled successfully'];
    } else {
      return ['success' => false, 'message' => 'Failed to cancel request'];
    }
    
  } catch (PDOException $e) {
    error_log("Cancel enrollment error: " . $e->getMessage());
    return ['success' => false, 'message' => 'Database error occurred'];
  }
}
?>