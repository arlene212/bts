<?php
require_once __DIR__ . '/../php/SessionManager.php';
require_once __DIR__ . '/../php/DatabaseConnection.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

SessionManager::startSession();
SessionManager::requireRole('trainer');

$user = SessionManager::getCurrentUser();
$trainer_id = $_SESSION['user']['user_id'];
$currentTab = $_GET['current_tab'] ?? 'home';

$database = new DatabaseConnection();
$db = $database->getConnection();

$trainer_info = $user;
$courses = [];
$enrollment_requests = [];
$total_trainees = 0;
$total_submissions = 0;
$total_activities = 0;
$total_requests = 0;
$guests = [];
$itemsPerPage = 10;
$trainees = [];
$totalTraineePages = 0;
$announcements = [];

$traineeSearch = $_GET['trainee_search'] ?? '';
$traineeStatus = $_GET['trainee_status'] ?? 'active'; // Default to active trainees
$traineePage = max(1, intval($_GET['trainee_page'] ?? 1));
$traineeOffset = ($traineePage - 1) * $itemsPerPage;

// Build status filter condition
$statusCondition = '';
if ($traineeStatus === 'active') {
    $statusCondition = "AND u.status = 'active' AND e.status IN ('approved', 'pending')";
} elseif ($traineeStatus === 'dropped') {
    $statusCondition = "AND u.status = 'dropped' AND e.status IN ('approved', 'dropped')";
} elseif ($traineeStatus === 'graduated') {
    $statusCondition = "AND u.status = 'graduated' AND e.status = 'approved'";
} elseif ($traineeStatus === 'all') {
    $statusCondition = "AND u.status IN ('active', 'dropped', 'graduated')";
}

$trainees_base_query = "FROM users u JOIN enrollments e ON u.user_id = e.trainee_id JOIN course_assignments ca ON e.course_code = ca.course_code WHERE u.role = 'trainee' " . $statusCondition . " AND ca.trainer_id = :trainer_id";

try {
  $stmt = $db->prepare("SELECT c.* FROM courses c INNER JOIN course_assignments ca ON c.course_code = ca.course_code WHERE ca.trainer_id = ? AND c.status = 'active'");
  $stmt->execute([$trainer_id]);
  $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $db->prepare("SELECT e.*, u.first_name, u.last_name, c.course_name FROM enrollments e JOIN users u ON e.trainee_id = u.user_id JOIN courses c ON e.course_code = c.course_code JOIN course_assignments ca ON c.course_code = ca.course_code WHERE e.status = 'pending' AND ca.trainer_id = ?");
  $stmt->execute([$trainer_id]);
  $enrollment_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $total_requests = count($enrollment_requests);

  $stmt = $db->prepare("SELECT COUNT(DISTINCT e.trainee_id) as count FROM enrollments e JOIN course_assignments ca ON e.course_code = ca.course_code WHERE e.status = 'approved' AND ca.trainer_id = ?");
  $stmt->execute([$trainer_id]);
  $total_trainees = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

  $stmt = $db->prepare("SELECT COUNT(s.id) as count FROM submissions s JOIN course_materials cm ON s.material_id = cm.id JOIN course_assignments ca ON cm.course_code = ca.course_code WHERE ca.trainer_id = ?");
  $stmt->execute([$trainer_id]);
  $total_submissions = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

  $stmt = $db->prepare("SELECT COUNT(cm.id) as count FROM course_materials cm JOIN course_assignments ca ON cm.course_code = ca.course_code WHERE ca.trainer_id = ?");
  $stmt->execute([$trainer_id]);
  $total_activities = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

  $trainees_count_query = "SELECT COUNT(DISTINCT u.user_id) " . $trainees_base_query;
  $trainees_data_query = "SELECT DISTINCT u.*, e.course_name, e.batch_name, e.status as enrollment_status " . $trainees_base_query;
  if (!empty($traineeSearch)) {
    $search_condition = " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.user_id LIKE :search OR e.course_name LIKE :search)";
    $trainees_count_query .= $search_condition;
    $trainees_data_query .= $search_condition;
  }
  $trainees_data_query .= " ORDER BY u.date_created DESC LIMIT :limit OFFSET :offset";
  $traineeCountParams = [":trainer_id" => $trainer_id];
  $traineeCountStmt = $db->prepare($trainees_count_query);
  if (!empty($traineeSearch)) { $traineeCountParams[':search'] = "%$traineeSearch%"; }
  $traineeCountStmt->execute($traineeCountParams);
  $totalTraineesForTrainer = (int)$traineeCountStmt->fetchColumn();
  $totalTraineePages = ceil($totalTraineesForTrainer / $itemsPerPage);
  $traineesDataParams = [":trainer_id" => $trainer_id, ":limit" => $itemsPerPage, ":offset" => $traineeOffset];
  $traineesStmt = $db->prepare($trainees_data_query);
  if (!empty($traineeSearch)) { $traineesDataParams[':search'] = "%$traineeSearch%"; }
  $traineesStmt->execute($traineesDataParams);
  $trainees = $traineesStmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $db->prepare("SELECT DISTINCT u.* FROM users u JOIN enrollments e ON u.user_id = e.trainee_id JOIN course_assignments ca ON e.course_code = ca.course_code WHERE u.role = 'guest' AND u.status = 'active' AND e.status = 'approved' AND ca.trainer_id = ?");
  $stmt->execute([$trainer_id]);
  $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $db->prepare("SELECT * FROM announcements ORDER BY date_posted DESC LIMIT 5");
  $stmt->execute();
  $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
  error_log("Trainer dashboard error: " . $exception->getMessage());
}

include __DIR__ . '/views/layout.php';
?>
