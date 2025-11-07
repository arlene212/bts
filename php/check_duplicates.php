<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$firstName = trim($_POST['first_name'] ?? '');
$middleName = trim($_POST['middle_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$contactNumber = trim($_POST['contact_number'] ?? '');
$role = trim($_POST['role'] ?? '');

if (empty($firstName) || empty($lastName) || empty($role)) {
    http_response_code(400);
    echo json_encode(['error' => 'Required fields are missing.']);
    exit;
}

$response = [
    'exact_match_same_role' => false,
    'similar_matches' => []
];

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

    // Check for exact full name match in the same role
    $exactStmt = $pdo->prepare(
        "SELECT user_id, role FROM users WHERE first_name = ? AND last_name = ? AND middle_name = ? AND role = ?"
    );
    $exactStmt->execute([$firstName, $lastName, $middleName, $role]);
    if ($exactStmt->fetch()) {
        $response['exact_match_same_role'] = true;
    }

    // Check for similar names or same contact number across all roles
    $similarQuery = "SELECT user_id, first_name, last_name, role, contact_number FROM users WHERE (first_name = ? AND last_name = ?)";
    $params = [$firstName, $lastName];

    if (!empty($contactNumber)) {
        $similarQuery .= " OR contact_number = ?";
        $params[] = $contactNumber;
    }

    $similarStmt = $pdo->prepare($similarQuery);
    $similarStmt->execute($params);
    $similarUsers = $similarStmt->fetchAll(PDO::FETCH_ASSOC);

    $response['similar_matches'] = $similarUsers;

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Duplicate check error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error during duplicate check.']);
}
?>