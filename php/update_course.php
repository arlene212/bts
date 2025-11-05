<?php
require_once 'DatabaseConnection.php';
require_once 'SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['edit_course']) || !isset($_POST['course_code'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();
    
    $courseCode = $_POST['course_code'];
    $courseName = $_POST['course_name'];
    $courseHours = $_POST['course_hours'];
    $courseDescription = $_POST['course_description'] ?? '';
    
    // Process competencies
    $competencies = [];
    if (isset($_POST['competencies']) && is_array($_POST['competencies'])) {
        foreach ($_POST['competencies'] as $comp) {
            if (!empty(trim($comp['name']))) {
                $competencies[] = [
                    'type' => $comp['type'],
                    'name' => trim($comp['name']),
                    'description' => trim($comp['description'] ?? '')
                ];
            }
        }
    }
    
    // Handle file upload
    $courseImage = null;
    if (isset($_FILES['course_image']) && $_FILES['course_image']['error'] === 0) {
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $fileType = $_FILES['course_image']['type'];
        
        if (in_array($fileType, $allowedTypes)) {
            // Get current image to delete later
            $currentStmt = $pdo->prepare("SELECT image FROM courses WHERE course_code = ?");
            $currentStmt->execute([$courseCode]);
            $currentCourse = $currentStmt->fetch();
            
            $uploadDir = '../uploads/courses/';
            $fileName = uniqid() . '_' . basename($_FILES['course_image']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['course_image']['tmp_name'], $targetPath)) {
                $courseImage = $fileName;
                
                // Delete old image if it exists and is not default
                if ($currentCourse && !empty($currentCourse['image']) && 
                    $currentCourse['image'] !== 'default.png' && 
                    file_exists($uploadDir . $currentCourse['image'])) {
                    unlink($uploadDir . $currentCourse['image']);
                }
            }
        }
    }
    
    // Build update query
    if ($courseImage) {
        $stmt = $pdo->prepare("UPDATE courses SET course_name = ?, hours = ?, description = ?, image = ?, competency_types = ? WHERE course_code = ?");
        $stmt->execute([$courseName, $courseHours, $courseDescription, $courseImage, json_encode($competencies), $courseCode]);
    } else {
        $stmt = $pdo->prepare("UPDATE courses SET course_name = ?, hours = ?, description = ?, competency_types = ? WHERE course_code = ?");
        $stmt->execute([$courseName, $courseHours, $courseDescription, json_encode($competencies), $courseCode]);
    }
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Course updated successfully']);
    } else {
        // If no rows were affected, check whether the course exists.
        $checkStmt = $pdo->prepare("SELECT * FROM courses WHERE course_code = ?");
        $checkStmt->execute([$courseCode]);
        $existingCourse = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingCourse) {
            // Course exists but nothing changed. Treat as success to avoid confusing the UI.
            echo json_encode(['success' => true, 'message' => 'No changes needed']);
        } else {
            // Course does not exist.
            echo json_encode(['success' => false, 'message' => 'Course not found']);
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error in update_course.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error in update_course.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>