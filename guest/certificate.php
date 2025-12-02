<?php
require_once __DIR__ . '/../php/SessionManager.php';
require_once __DIR__ . '/../php/DatabaseConnection.php';
require_once __DIR__ . '/includes/certification.php';

SessionManager::startSession();
SessionManager::requireRole('guest');
$user = SessionManager::getCurrentUser();
$courseCode = $_GET['course_code'] ?? '';
$database = new DatabaseConnection();
$pdo = $database->getConnection();
$eval = ($courseCode !== '') ? cert_evaluate($pdo, $user['user_id'], $courseCode) : ['eligible'=>false,'codes'=>['INVALID'],'details'=>[]];
if (!$eval['eligible']) { http_response_code(403); echo 'Not eligible.'; exit; }
$course = $eval['details']['course'];
$name = trim(($user['first_name'] ?? '').' '.($user['last_name'] ?? '')); 
$date = date('F j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Certificate - <?php echo htmlspecialchars($course['course_name']); ?></title>
<style>
body{font-family:Inter,Arial,sans-serif; background:#f5f7fb; margin:0; padding:40px;}
.cert{max-width:900px; margin:0 auto; background:white; box-shadow:0 10px 30px rgba(0,0,0,0.1); border:12px solid #1f2937; padding:40px;}
.cert-header{display:flex; align-items:center; justify-content:space-between;}
.brand{display:flex; align-items:center; gap:12px;}
.brand img{width:60px; height:60px;}
.title{font-size:28px; font-weight:700; color:#111827;}
.subtitle{font-size:14px; color:#6b7280;}
.cert-body{text-align:center; margin-top:40px;}
.recipient{font-size:32px; font-weight:700; color:#0f172a;}
.statement{font-size:16px; color:#374151; margin:12px 0 24px;}
.course-name{font-size:24px; font-weight:600; color:#111827;}
.meta{display:flex; justify-content:center; gap:24px; margin-top:16px; color:#6b7280;}
.sign-row{display:flex; justify-content:space-between; margin-top:60px;}
.sign{text-align:center;}
.sign .line{border-bottom:2px solid #111827; width:260px; margin:0 auto 6px;}
.actions{margin:20px auto; text-align:center;}
.btn{display:inline-block; padding:10px 16px; background:#16a34a; color:white; border:none; border-radius:6px; font-weight:600; cursor:pointer;}
@media print{ .actions{ display:none; } body{ background:white; padding:0; } .cert{ box-shadow:none; border-color:#000; } }
</style>
</head>
<body>
  <div class="actions"><button class="btn" onclick="window.print()"><i class="fas fa-print"></i> Download as PDF</button></div>
  <div class="cert">
    <div class="cert-header">
      <div class="brand"><img src="../images/school.png" alt=""><div><div class="title">Benguet Technical School</div><div class="subtitle">Certificate of Completion</div></div></div>
      <div class="subtitle">Code: <?php echo htmlspecialchars($course['course_code']); ?></div>
    </div>
    <div class="cert-body">
      <div class="recipient"><?php echo htmlspecialchars($name); ?></div>
      <div class="statement">has successfully completed Basic Competencies for the course</div>
      <div class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></div>
      <div class="meta"><span>Hours: <?php echo (int)$eval['details']['hours_completed']; ?></span><span>Date: <?php echo htmlspecialchars($date); ?></span></div>
    </div>
    <div class="sign-row">
      <div class="sign"><div class="line"></div><div class="subtitle">Trainer</div></div>
      <div class="sign"><div class="line"></div><div class="subtitle">Administrator</div></div>
    </div>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
