<?php
require_once 'SessionManager.php';
require_once 'DatabaseConnection.php';

SessionManager::startSession();

header('Content-Type: application/json');

if (!SessionManager::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user = SessionManager::getCurrentUser();
$userId = $user['user_id'];

$response = ['success' => false, 'message' => 'An error occurred.'];

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();

    // Get current user data
    $currentUserStmt = $pdo->prepare("SELECT profile_picture, password FROM users WHERE user_id = ?");
    $currentUserStmt->execute([$userId]);
    $currentUserData = $currentUserStmt->fetch();

    if (!$currentUserData) {
        throw new Exception("User not found in database.");
    }

    // Prepare fields to update
    $firstName = $_POST['first_name'] ?? $user['first_name'];
    $lastName = $_POST['last_name'] ?? $user['last_name'];
    $email = $_POST['email'] ?? $user['email'];
    $contactNumber = $_POST['contact_number'] ?? $user['contact_number'];
    $profilePicture = $currentUserData['profile_picture'];

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExtension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $fileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath)) {
            // Delete old picture if it's not the default
            if (!empty($profilePicture) && $profilePicture !== 'default.png' && file_exists($uploadDir . $profilePicture)) {
                unlink($uploadDir . $profilePicture);
            }
            $profilePicture = $fileName;
            // Update the session immediately with the new picture
            $_SESSION['user']['profile_picture'] = $profilePicture;
        } else {
            throw new Exception("Failed to upload profile picture.");
        }
    }

    // Handle password change
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    if (!empty($newPassword)) {
        if (empty($oldPassword) || !password_verify($oldPassword, $currentUserData['password'])) {
            throw new Exception("Old password is incorrect.");
        }
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $passStmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $passStmt->execute([$hashedPassword, $userId]);
    }

    // Update user information
    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, contact_number = ?, profile_picture = ? WHERE user_id = ?");
    $stmt->execute([$firstName, $lastName, $email, $contactNumber, $profilePicture, $userId]);

    // Update session data
    $_SESSION['user']['first_name'] = $firstName;
    $_SESSION['user']['last_name'] = $lastName;
    $_SESSION['user']['email'] = $email;
    $_SESSION['user']['contact_number'] = $contactNumber;
    $_SESSION['user']['profile_picture'] = $profilePicture;

    $response['success'] = true;
    $response['message'] = 'Profile updated successfully!';
    $response['user'] = $_SESSION['user'];

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
    error_log("Profile update error: " . $e->getMessage());
}

echo json_encode($response);
?>