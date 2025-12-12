<?php
function enrollGuest($db, $userId, $courseCode, $batchName = null)
{
  if (empty($batchName)) { $batchName = 'Batch 1'; }
  // Ensure batch exists; create default if missing
  try {
    $bchk = $db->prepare("SELECT 1 FROM course_batches WHERE course_code = ? AND batch_name = ?");
    $bchk->execute([$courseCode, $batchName]);
    if (!$bchk->fetchColumn()) {
      $bins = $db->prepare("INSERT INTO course_batches (course_code, batch_name, created_at) VALUES (?, ?, NOW())");
      $bins->execute([$courseCode, $batchName]);
    }
  } catch (Throwable $e) {}
  $stmt = $db->prepare("SELECT status FROM enrollments WHERE trainee_id = ? AND course_code = ? ORDER BY date_requested DESC LIMIT 1");
  $stmt->execute([$userId, $courseCode]);
  $existing = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($existing && $existing['status'] === 'approved') {
    return ['success' => false, 'message' => 'Already enrolled in this course'];
  }
  if ($existing && $existing['status'] === 'pending') {
    $u = $db->prepare("UPDATE enrollments SET status = 'approved' WHERE trainee_id = ? AND course_code = ? AND status = 'pending'");
    $u->execute([$userId, $courseCode]);
    return ['success' => true, 'message' => 'Enrollment successful. You are now enrolled.'];
  }
  $cstmt = $db->prepare("SELECT course_name FROM courses WHERE course_code = ?");
  $cstmt->execute([$courseCode]);
  $c = $cstmt->fetch(PDO::FETCH_ASSOC);
  if (!$c) { return ['success' => false, 'message' => 'Course not found']; }
  $stmt = $db->prepare("INSERT INTO enrollments (trainee_id, course_code, course_name, batch_name, status, date_requested) VALUES (?, ?, ?, ?, 'approved', NOW())");
  $stmt->execute([$userId, $courseCode, $c['course_name'], $batchName]);
  return ['success' => true, 'message' => 'Enrollment successful. You are now enrolled.'];
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

  // Build competencies and topics/materials/activities from existing structures, limited to basic competencies
  // 1) Get distinct competency codes referenced by this course's topics
  $codesStmt = $db->prepare("SELECT DISTINCT competency_id FROM course_topics WHERE course_code = ?");
  $codesStmt->execute([$courseCode]);
  $codeRows = $codesStmt->fetchAll(PDO::FETCH_ASSOC);
  $codes = array_map(function($r){ return $r['competency_id']; }, $codeRows);

  $competencies = [];

  if (!empty($codes)) {
    // 2) Fetch only basic competencies from competencies table
    $placeholders = implode(',', array_fill(0, count($codes), '?'));
    $cstmt = $db->prepare("SELECT competency_code, competency_name, module_title, competency_type, nominal_hours, description, learning_outcomes FROM competencies WHERE competency_code IN ($placeholders) AND status = 'active' AND competency_type = 'basic'");
    $cstmt->execute($codes);
    foreach ($cstmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $competencies[$row['competency_code']] = [
        'type' => $row['competency_type'],
        'name' => $row['competency_name'],
        'description' => $row['description'] ?? '',
        'topics' => []
      ];
    }
  }

  // 3) Get topics, materials, activities for the course
  $stmt = $db->prepare(
    "SELECT 
        ct.id as topic_id, ct.competency_id, ct.topic_name, ct.topic_description,
        tm.id as material_id, tm.material_title, tm.material_description, tm.file_path as material_file_path,
        ta.id as activity_id, ta.activity_title, ta.activity_description, ta.activity_type
     FROM course_topics ct
     LEFT JOIN topic_materials tm ON ct.id = tm.topic_id
     LEFT JOIN topic_activities ta ON ct.id = ta.topic_id
     WHERE ct.course_code = ?
     ORDER BY ct.created_at ASC, tm.uploaded_at ASC, ta.created_at ASC"
  );
  $stmt->execute([$courseCode]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // 4) Group into topics under basic competencies
  $topics = [];
  foreach ($rows as $row) {
    // Skip non-basic competencies
    if (!isset($competencies[$row['competency_id']])) {
      continue;
    }

    // Create topic entry
    if ($row['topic_id'] && !isset($topics[$row['topic_id']])) {
      $topics[$row['topic_id']] = [
        'id' => $row['topic_id'],
        'name' => $row['topic_name'],
        'description' => $row['topic_description'],
        'competency_id' => $row['competency_id'],
        'materials' => [],
        'activities' => []
      ];
    }

    // Add material to topic
    if ($row['material_id'] && !isset($topics[$row['topic_id']]['materials'][$row['material_id']])) {
      $topics[$row['topic_id']]['materials'][$row['material_id']] = [
        'id' => $row['material_id'],
        'title' => $row['material_title'],
        'description' => $row['material_description'],
        'file_path' => $row['material_file_path']
      ];
    }

    // Add activity to topic
    if ($row['activity_id'] && !isset($topics[$row['topic_id']]['activities'][$row['activity_id']])) {
      $topics[$row['topic_id']]['activities'][$row['activity_id']] = [
        'id' => $row['activity_id'],
        'title' => $row['activity_title'],
        'description' => $row['activity_description'],
        'type' => $row['activity_type']
      ];
    }
  }

  // 5) Assign topics to competencies and normalize arrays
  foreach ($topics as $topic) {
    if (isset($competencies[$topic['competency_id']])) {
      $topic['materials'] = array_values($topic['materials']);
      $topic['activities'] = array_values($topic['activities']);
      $competencies[$topic['competency_id']]['topics'][] = $topic;
    }
  }

  return [
    'course' => $course,
    'competencies' => array_values($competencies),
    'batches' => []
  ];
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
