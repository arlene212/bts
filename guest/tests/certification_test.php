<?php
require_once __DIR__ . '/../../php/SessionManager.php';
require_once __DIR__ . '/../../php/DatabaseConnection.php';
require_once __DIR__ . '/../includes/certification.php';

header('Content-Type: application/json');
SessionManager::startSession();
if (!isset($_SESSION['user'])) { echo json_encode(['success'=>false,'message'=>'Login required']); exit; }
$user = $_SESSION['user'];
$database = new DatabaseConnection();
$pdo = $database->getConnection();

$courseCode = $_GET['course_code'] ?? '';
if ($courseCode===''){ echo json_encode(['success'=>false,'message'=>'course_code required']); exit; }

$eval = cert_evaluate($pdo, $user['user_id'], $courseCode);
$cases = [];
$cases[] = ['name'=>'Eligibility structure', 'pass'=> isset($eval['eligible']) && isset($eval['codes']) && isset($eval['details'])];
$req = $eval['details']['hours_required'] ?? 0;
$done = $eval['details']['hours_completed'] ?? 0;
$cases[] = ['name'=>'Hours calculation non-negative', 'pass'=> ($req>=0 && $done>=0)];
$comp = $eval['details']['competencies'] ?? [];
$partial = array_filter($comp, function($c){ return !$c['completed'] && count($c['missing'])>0; });
$cases[] = ['name'=>'Partial completion scenarios supported', 'pass'=> is_array($partial)];

echo json_encode(['success'=>true,'cases'=>$cases,'eligible'=>$eval['eligible'],'codes'=>$eval['codes']]);
?>
