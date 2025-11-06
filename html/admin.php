<?php
// Prevent browser from caching old pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once '../php/SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('admin');

$user = SessionManager::getCurrentUser();

// Database connection
require_once '../php/DatabaseConnection.php';
$database = new DatabaseConnection();
$pdo = $database->getConnection();

// Test database connection
try {
    $pdo->query("SELECT 1");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Validate current user exists in database
try {
    $userCheck = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? AND status = 'active'");
    $userCheck->execute([$user['user_id']]);
    $validUser = $userCheck->fetch();
    
    if (!$validUser) {
        // User no longer valid - destroy session and redirect
        session_destroy();
        header("Location: ./login.php");
        exit;
    }
} catch (Exception $e) {
    error_log("User validation error: " . $e->getMessage());
}

// Cleanup inactive guest accounts (10% chance on page load)
if (rand(1, 10) === 1) {
    cleanupInactiveGuests($pdo);
}

// Ensure upload directories exist
$uploadDirs = ['../uploads/profiles/', '../uploads/courses/', '../uploads/activities/', '../uploads/submissions/'];
foreach ($uploadDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Search and Pagination Logic
$itemsPerPage = 10;

// Trainers Pagination
$trainerSearch = $_GET['trainer_search'] ?? '';
$trainerPage = max(1, intval($_GET['trainer_page'] ?? 1));
$trainerOffset = ($trainerPage - 1) * $itemsPerPage;

$activeTrainerQuery = "SELECT * FROM users WHERE role = 'trainer' AND status = 'active'";
$activeTrainerCountQuery = "SELECT COUNT(*) FROM users WHERE role = 'trainer' AND status = 'active'";

if (!empty($trainerSearch)) {
    $activeTrainerQuery .= " AND (first_name LIKE :search OR last_name LIKE :search OR user_id LIKE :search OR email LIKE :search)";
    $activeTrainerCountQuery .= " AND (first_name LIKE :search OR last_name LIKE :search OR user_id LIKE :search OR email LIKE :search)";
}

$activeTrainerQuery .= " ORDER BY date_created DESC LIMIT :limit OFFSET :offset";

// Execute active trainers query
$trainerCountStmt = $pdo->prepare($activeTrainerCountQuery);
if (!empty($trainerSearch)) {
    $trainerSearchParam = "%$trainerSearch%";
    $trainerCountStmt->bindValue(':search', $trainerSearchParam);
}
$trainerCountStmt->execute();
$totalActiveTrainers = $trainerCountStmt->fetchColumn();
$totalActiveTrainerPages = ceil($totalActiveTrainers / $itemsPerPage);

$activeTrainerStmt = $pdo->prepare($activeTrainerQuery);
if (!empty($trainerSearch)) {
    $activeTrainerStmt->bindValue(':search', $trainerSearchParam);
}
$activeTrainerStmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$activeTrainerStmt->bindValue(':offset', $trainerOffset, PDO::PARAM_INT);
$activeTrainerStmt->execute();
$activeTrainers = $activeTrainerStmt->fetchAll();

// Archived Trainers Pagination (separate query)
$archivedTrainerPage = max(1, intval($_GET['archived_trainer_page'] ?? 1));
$archivedTrainerOffset = ($archivedTrainerPage - 1) * $itemsPerPage;
$archivedTrainerQuery = "SELECT * FROM users WHERE role = 'trainer' AND status = 'archived'";
$archivedTrainerCountQuery = "SELECT COUNT(*) FROM users WHERE role = 'trainer' AND status = 'archived'";
if (!empty($trainerSearch)) {
    $archivedTrainerQuery .= " AND (first_name LIKE :search OR last_name LIKE :search OR user_id LIKE :search OR email LIKE :search)";
    $archivedTrainerCountQuery .= " AND (first_name LIKE :search OR last_name LIKE :search OR user_id LIKE :search OR email LIKE :search)";
}
$archivedTrainerQuery .= " ORDER BY date_created DESC LIMIT :limit OFFSET :offset";
$archivedTrainerCountStmt = $pdo->prepare($archivedTrainerCountQuery);
if (!empty($trainerSearch)) {
    $archivedTrainerCountStmt->bindValue(':search', $trainerSearchParam);
}
$archivedTrainerCountStmt->execute();
$totalArchivedTrainers = $archivedTrainerCountStmt->fetchColumn();
$totalArchivedTrainerPages = ceil($totalArchivedTrainers / $itemsPerPage);
$archivedTrainerStmt = $pdo->prepare($archivedTrainerQuery);
if (!empty($trainerSearch)) {
    $archivedTrainerStmt->bindValue(':search', $trainerSearchParam);
}
$archivedTrainerStmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$archivedTrainerStmt->bindValue(':offset', $archivedTrainerOffset, PDO::PARAM_INT);
$archivedTrainerStmt->execute();
$archivedTrainers = $archivedTrainerStmt->fetchAll();

// Trainees Pagination
$traineeSearch = $_GET['trainee_search'] ?? '';
$traineePage = max(1, intval($_GET['trainee_page'] ?? 1));
$traineeOffset = ($traineePage - 1) * $itemsPerPage;

$traineeQuery = "SELECT * FROM users WHERE role = 'trainee' AND status = 'active'";
$traineeCountQuery = "SELECT COUNT(*) FROM users WHERE role = 'trainee' AND status = 'active'";

if (!empty($traineeSearch)) {
    $traineeQuery .= " AND (first_name LIKE :search OR last_name LIKE :search OR user_id LIKE :search OR email LIKE :search)";
    $traineeCountQuery .= " AND (first_name LIKE :search OR last_name LIKE :search OR user_id LIKE :search OR email LIKE :search)";
}

$traineeQuery .= " ORDER BY date_created DESC LIMIT :trainee_limit OFFSET :trainee_offset";

// Execute trainees query
$traineeStmt = $pdo->prepare($traineeCountQuery);
if (!empty($traineeSearch)) {
    $traineeSearchParam = "%$traineeSearch%";
    $traineeStmt->bindValue(':search', $traineeSearchParam);
}
$traineeStmt->execute();
$totalTrainees = $traineeStmt->fetchColumn();
$totalActiveTraineePages = ceil($totalTrainees / $itemsPerPage);

$traineeStmt = $pdo->prepare($traineeQuery);
if (!empty($traineeSearch)) {
    $traineeStmt->bindValue(':search', $traineeSearchParam);
}
$traineeStmt->bindValue(':trainee_limit', $itemsPerPage, PDO::PARAM_INT);
$traineeStmt->bindValue(':trainee_offset', $traineeOffset, PDO::PARAM_INT);
$traineeStmt->execute();
$activeTrainees = $traineeStmt->fetchAll();

// Archived Trainees Pagination
$archivedTraineePage = max(1, intval($_GET['archived_trainee_page'] ?? 1));
$archivedTraineeOffset = ($archivedTraineePage - 1) * $itemsPerPage;
$archivedTraineeQuery = "SELECT * FROM users WHERE role = 'trainee' AND status = 'archived'";
$archivedTraineeCountQuery = "SELECT COUNT(*) FROM users WHERE role = 'trainee' AND status = 'archived'";
if (!empty($traineeSearch)) {
    $archivedTraineeQuery .= " AND (first_name LIKE :search OR last_name LIKE :search OR user_id LIKE :search OR email LIKE :search)";
    $archivedTraineeCountQuery .= " AND (first_name LIKE :search OR last_name LIKE :search OR user_id LIKE :search OR email LIKE :search)";
}
$archivedTraineeQuery .= " ORDER BY date_created DESC LIMIT :limit OFFSET :offset";
$archivedTraineeCountStmt = $pdo->prepare($archivedTraineeCountQuery);
if (!empty($traineeSearch)) {
    $archivedTraineeCountStmt->bindValue(':search', $traineeSearchParam);
}
$archivedTraineeCountStmt->execute();
$totalArchivedTrainees = $archivedTraineeCountStmt->fetchColumn();
$totalArchivedTraineePages = ceil($totalArchivedTrainees / $itemsPerPage);
$archivedTraineeStmt = $pdo->prepare($archivedTraineeQuery);
if (!empty($traineeSearch)) {
    $archivedTraineeStmt->bindValue(':search', $traineeSearchParam);
}
$archivedTraineeStmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$archivedTraineeStmt->bindValue(':offset', $archivedTraineeOffset, PDO::PARAM_INT);
$archivedTraineeStmt->execute();
$archivedTrainees = $archivedTraineeStmt->fetchAll();

// Guests Pagination
$guestSearch = $_GET['guest_search'] ?? '';
$guestPage = max(1, intval($_GET['guest_page'] ?? 1));
$guestOffset = ($guestPage - 1) * $itemsPerPage;

$guestQuery = "SELECT * FROM users WHERE role = 'guest'";
$guestCountQuery = "SELECT COUNT(*) FROM users WHERE role = 'guest'";

if (!empty($guestSearch)) {
    $guestQuery .= " AND (first_name LIKE :guest_search1 OR last_name LIKE :guest_search2 OR user_id LIKE :guest_search3 OR email LIKE :guest_search4)";
    $guestCountQuery .= " AND (first_name LIKE :guest_search1 OR last_name LIKE :guest_search2 OR user_id LIKE :guest_search3 OR email LIKE :guest_search4)";
}

$guestQuery .= " ORDER BY date_created DESC LIMIT :guest_limit OFFSET :guest_offset";

// Execute guests query
$guestStmt = $pdo->prepare($guestCountQuery);
if (!empty($guestSearch)) {
    $guestSearchParam = "%$guestSearch%";
    $guestStmt->bindValue(':guest_search1', $guestSearchParam);
    $guestStmt->bindValue(':guest_search2', $guestSearchParam);
    $guestStmt->bindValue(':guest_search3', $guestSearchParam);
    $guestStmt->bindValue(':guest_search4', $guestSearchParam);
}
$guestStmt->execute();
$totalGuests = $guestStmt->fetchColumn();
$totalGuestPages = ceil($totalGuests / $itemsPerPage);

$guestStmt = $pdo->prepare($guestQuery);
if (!empty($guestSearch)) {
    $guestStmt->bindValue(':guest_search1', $guestSearchParam);
    $guestStmt->bindValue(':guest_search2', $guestSearchParam);
    $guestStmt->bindValue(':guest_search3', $guestSearchParam);
    $guestStmt->bindValue(':guest_search4', $guestSearchParam);
}
$guestStmt->bindValue(':guest_limit', $itemsPerPage, PDO::PARAM_INT);
$guestStmt->bindValue(':guest_offset', $guestOffset, PDO::PARAM_INT);
$guestStmt->execute();
$guests = $guestStmt->fetchAll();

// Enrollments Pagination
$enrollmentSearch = $_GET['enrollment_search'] ?? '';
$enrollmentPage = max(1, intval($_GET['enrollment_page'] ?? 1));
$enrollmentOffset = ($enrollmentPage - 1) * $itemsPerPage;

$enrollmentQuery = "SELECT e.*, u.first_name, u.last_name, c.course_name, u.role as trainee_role
                   FROM enrollments e 
                   JOIN users u ON e.trainee_id = u.user_id 
                   JOIN courses c ON e.course_code = c.course_code
                   WHERE e.status = 'pending'";

$enrollmentCountQuery = "SELECT COUNT(*)
                        FROM enrollments e 
                        JOIN users u ON e.trainee_id = u.user_id 
                        JOIN courses c ON e.course_code = c.course_code
                        WHERE e.status = 'pending'";

if (!empty($enrollmentSearch)) {
    // The WHERE clause is already started, so we use AND
    $enrollmentQuery .= " AND (u.first_name LIKE :enrollment_search1 OR u.last_name LIKE :enrollment_search2 OR c.course_name LIKE :enrollment_search3 OR e.trainee_id LIKE :enrollment_search4)";
    $enrollmentCountQuery .= " AND (u.first_name LIKE :enrollment_search1 OR u.last_name LIKE :enrollment_search2 OR c.course_name LIKE :enrollment_search3 OR e.trainee_id LIKE :enrollment_search4)";
}

$enrollmentQuery .= " ORDER BY date_requested DESC LIMIT :enrollment_limit OFFSET :enrollment_offset";

// Execute enrollments query
$enrollmentStmt = $pdo->prepare($enrollmentCountQuery);
if (!empty($enrollmentSearch)) {
    $enrollmentSearchParam = "%$enrollmentSearch%";
    $enrollmentStmt->bindValue(':enrollment_search1', $enrollmentSearchParam);
    $enrollmentStmt->bindValue(':enrollment_search2', $enrollmentSearchParam);
    $enrollmentStmt->bindValue(':enrollment_search3', $enrollmentSearchParam);
    $enrollmentStmt->bindValue(':enrollment_search4', $enrollmentSearchParam);
}
$enrollmentStmt->execute();
$totalEnrollments = $enrollmentStmt->fetchColumn();
$totalEnrollmentPages = ceil($totalEnrollments / $itemsPerPage);

$enrollmentStmt = $pdo->prepare($enrollmentQuery);
if (!empty($enrollmentSearch)) {
    $enrollmentStmt->bindValue(':enrollment_search1', $enrollmentSearchParam);
    $enrollmentStmt->bindValue(':enrollment_search2', $enrollmentSearchParam);
    $enrollmentStmt->bindValue(':enrollment_search3', $enrollmentSearchParam);
    $enrollmentStmt->bindValue(':enrollment_search4', $enrollmentSearchParam);
}
$enrollmentStmt->bindValue(':enrollment_limit', $itemsPerPage, PDO::PARAM_INT);
$enrollmentStmt->bindValue(':enrollment_offset', $enrollmentOffset, PDO::PARAM_INT);
$enrollmentStmt->execute();
$enrollments = $enrollmentStmt->fetchAll();

// This block handles all form submissions that are not AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data received: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    // Your existing POST handling code... (This comment can be removed, it's just a placeholder)
    // Add Course with Competencies
    if (isset($_POST['add_course'])) {
        $courseName = $_POST['course_name'];
        $courseCode = $_POST['course_code'];
        $courseHours = $_POST['course_hours'];
        $courseDescription = $_POST['course_description'] ?? '';
        
        // Handle competencies
        $competencies = [];
        if (!empty($_POST['basic_competency'])) {
            foreach ($_POST['basic_competency'] as $index => $basicComp) {
                if (!empty(trim($basicComp))) {
                    $competencies[] = [
                        'type' => 'basic',
                        'name' => trim($basicComp),
                        'description' => trim($_POST['basic_competency_desc'][$index] ?? '')
                    ];
                }
            }
        }
        if (!empty($_POST['common_competency'])) {
            foreach ($_POST['common_competency'] as $index => $commonComp) {
                if (!empty(trim($commonComp))) {
                    $competencies[] = [
                        'type' => 'common',
                        'name' => trim($commonComp),
                        'description' => trim($_POST['common_competency_desc'][$index] ?? '')
                    ];
                }
            }
        }
        if (!empty($_POST['core_competency'])) {
            foreach ($_POST['core_competency'] as $index => $coreComp) {
                if (!empty(trim($coreComp))) {
                    $competencies[] = [
                        'type' => 'core',
                        'name' => trim($coreComp),
                        'description' => trim($_POST['core_competency_desc'][$index] ?? '')
                    ];
                }
            }
        }
        
        // Handle file upload
        $courseImage = '';
        if (isset($_FILES['course_image']) && $_FILES['course_image']['error'] === 0) {
            $uploadDir = '../uploads/courses/';
            $fileName = uniqid() . '_' . basename($_FILES['course_image']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['course_image']['tmp_name'], $targetPath)) {
                $courseImage = $fileName;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO courses (course_name, course_code, hours, description, image, competency_types) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$courseName, $courseCode, $courseHours, $courseDescription, $courseImage, json_encode($competencies)]);
        
        $_SESSION['success_message'] = "Course added successfully with " . count($competencies) . " competencies!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Add Course Batch
    if (isset($_POST['add_course_batch'])) {
        $batchCourseCode = $_POST['batch_course_code']; // Correctly get the course code from the form
        $batchName = $_POST['batch_name'];
        $batchDescription = $_POST['batch_description'] ?? '';
        
        // Check if batch already exists
        $checkStmt = $pdo->prepare("SELECT id FROM course_batches WHERE course_code = ? AND batch_name = ?");
        $checkStmt->execute([$batchCourseCode, $batchName]);
        
        if ($checkStmt->fetch()) {
            $_SESSION['error_message'] = "Batch '$batchName' already exists for this course!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO course_batches (course_code, batch_name, description, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$batchCourseCode, $batchName, $batchDescription, $user['user_id']]);
            $_SESSION['success_message'] = "Course batch '$batchName' created successfully!";
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "#courses");
        exit;
    }
    
    // Add Announcement - FIXED VERSION
    if (isset($_POST['add_announcement'])) {
        $title = trim($_POST['announcement_title']);
        $content = trim($_POST['announcement_content']);
        
        // Validate required fields
        if (empty($title) || empty($content)) {
            $_SESSION['error_message'] = "Please fill in both title and content.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
        
        // Use the validated admin user ID from session validation
        $posted_by = $user['user_id'];
        
        // Double check the user exists
        $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? AND status = 'active'");
        $checkStmt->execute([$posted_by]);
        
        if (!$checkStmt->fetch()) {
            // If session user doesn't exist, use default admin
            $posted_by = '1000000001';
            error_log("Session user not found, using default admin ID for announcement");
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO announcements (title, content, posted_by, date_posted) VALUES (?, ?, ?, NOW())");
            
            if ($stmt->execute([$title, $content, $posted_by])) {
                $newId = $pdo->lastInsertId();
                error_log("Announcement inserted successfully! ID: $newId, Posted by: $posted_by");
                $_SESSION['success_message'] = "Announcement posted successfully!";
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Error inserting announcement: " . $errorInfo[2]);
                $_SESSION['error_message'] = "Error posting announcement: " . $errorInfo[2];
            }
            
        } catch (PDOException $e) {
            error_log("Database error in announcement: " . $e->getMessage());
            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Archive/Unarchive actions
    if (isset($_POST['archive_user'])) {
        $userId = $_POST['user_id'];
        $stmt = $pdo->prepare("UPDATE users SET status = 'archived' WHERE user_id = ?");
        $stmt->execute([$userId]);
        $_SESSION['success_message'] = "User archived successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_POST['unarchive_user'])) {
        $userId = $_POST['user_id'];
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
        $stmt->execute([$userId]);
        $_SESSION['success_message'] = "User unarchived successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Delete user (for guests and archived users) - FIXED VERSION
    if (isset($_POST['delete_user'])) {
        $userId = $_POST['user_id'];
        
        try {
            $pdo->beginTransaction();
            
            // Delete related records first with proper table names
            $tables = [
                'course_assignments' => ['trainer_id', 'assigned_by'],
                'batch_assignments' => ['trainee_id', 'assigned_by'],
                'enrollments' => ['trainee_id', 'processed_by']
            ];
            
            foreach ($tables as $table => $columns) {
                foreach ($columns as $column) {
                    $deleteStmt = $pdo->prepare("DELETE FROM $table WHERE $column = ?");
                    $deleteStmt->execute([$userId]);
                }
            }
            
            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            $pdo->commit();
            $_SESSION['success_message'] = "User deleted successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Error deleting user: " . $e->getMessage();
            error_log("Delete user error: " . $e->getMessage());
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Delete guest account - FIXED VERSION
    if (isset($_POST['delete_guest'])) {
        $userId = $_POST['user_id'];
        
        try {
            $pdo->beginTransaction();
            
            // Delete related records first
            $deleteEnrollments = $pdo->prepare("DELETE FROM enrollments WHERE trainee_id = ?");
            $deleteEnrollments->execute([$userId]);
            
            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'guest'");
            $stmt->execute([$userId]);
            
            $pdo->commit();
            $_SESSION['success_message'] = "Guest account deleted successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Error deleting guest: " . $e->getMessage();
            error_log("Delete guest error: " . $e->getMessage());
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "#guests");
        exit;
    }
    
    // Guest self-enrollment
    if (isset($_POST['guest_enroll'])) {
        $guestId = $_POST['guest_id'];
        $courseCode = $_POST['enroll_course_code'];
        
        try {
            $courseStmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_code = ?");
            $courseStmt->execute([$courseCode]);
            $course = $courseStmt->fetch();
            
            if ($course) {
                $enrollStmt = $pdo->prepare("INSERT INTO enrollments (trainee_id, course_code, course_name, status) VALUES (?, ?, ?, 'approved')");
                $enrollStmt->execute([$guestId, $courseCode, $course['course_name']]);
                
                $_SESSION['success_message'] = "Guest enrolled in course successfully!";
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error enrolling guest: " . $e->getMessage();
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "#guests");
        exit;
    }

    // Handle enrollment actions
    if (isset($_POST['enrollment_action'])) {
        $enrollmentId = $_POST['enrollment_id'];
        $action = $_POST['action'];
        $remarks = $_POST['remarks'] ?? '';
        
        if ($action === 'approve') {
            $status = 'approved';
        } elseif ($action === 'reject') {
            $status = 'rejected';
        }
        
        $stmt = $pdo->prepare("UPDATE enrollments SET status = ?, remarks = ?, processed_date = NOW(), processed_by = ? WHERE id = ?");
        $stmt->execute([$status, $remarks, $user['user_id'], $enrollmentId]);
        
        $_SESSION['success_message'] = "Enrollment request {$action}d successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Edit Course
    if (isset($_POST['edit_course'])) {
        $courseCode = $_POST['course_code'];
        $courseName = $_POST['course_name'];
        $courseHours = $_POST['course_hours'];
        $courseDescription = $_POST['course_description'] ?? '';
        $competencies = $_POST['competencies'] ?? [];

        // Handle file upload
        $courseImage = ''; // We will handle this later

        try {
            $stmt = $pdo->prepare("UPDATE courses SET course_name = ?, hours = ?, description = ?, competency_types = ? WHERE course_code = ?");
            $stmt->execute([$courseName, $courseHours, $courseDescription, json_encode(array_values($competencies)), $courseCode]);

            $_SESSION['success_message'] = "Course '$courseName' updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error updating course: " . $e->getMessage();
        }

        header("Location: " . $_SERVER['PHP_SELF'] . "#courses");
        exit;
    }


    // Handle profile updates
    if (isset($_POST['update_profile'])) {
        $firstName = trim($_POST['first_name']);
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName = trim($_POST['last_name']);
        $suffix = trim($_POST['suffix'] ?? '');
        $contactNumber = trim($_POST['contact_number']);
        
        // Basic validation
        if (empty($firstName) || empty($lastName)) {
            $_SESSION['error_message'] = 'First name and last name are required fields.';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
        
        try {
            // Get current user data
            $currentUserStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $currentUserStmt->execute([$user['user_id']]);
            $currentUser = $currentUserStmt->fetch();
            
            if (!$currentUser) {
                throw new Exception("User not found.");
            }
            
            // Handle profile picture upload
            $profilePicture = $currentUser['profile_picture'];
            
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
                $uploadDir = '../uploads/profiles/';
                
                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $fileType = $_FILES['profile_picture']['type'];
                
                if (in_array($fileType, $allowedTypes)) {
                    // Validate file size (max 2MB)
                    if ($_FILES['profile_picture']['size'] <= 2 * 1024 * 1024) {
                        // Generate unique filename
                        $fileExtension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                        $fileName = 'profile_' . $user['user_id'] . '_' . time() . '.' . $fileExtension;
                        $targetPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath)) {
                            // Delete old profile picture if it exists and is not default
                            if (!empty($currentUser['profile_picture']) && 
                                $currentUser['profile_picture'] != 'default.png' && 
                                file_exists($uploadDir . $currentUser['profile_picture'])) {
                                unlink($uploadDir . $currentUser['profile_picture']);
                            }
                            $profilePicture = $fileName;
                        } else {
                            throw new Exception("Failed to upload profile picture.");
                        }
                    } else {
                        throw new Exception("File size must be less than 2MB.");
                    }
                } else {
                    throw new Exception("Invalid file type. Only JPG, PNG, and GIF are allowed.");
                }
            }
            
            // Update database
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, contact_number = ?, profile_picture = ? WHERE user_id = ?");
            
            if ($stmt->execute([$firstName, $middleName, $lastName, $suffix, $contactNumber, $profilePicture, $user['user_id']])) {
                
                // Update session data
                $_SESSION['user']['first_name'] = $firstName;
                $_SESSION['user']['middle_name'] = $middleName;
                $_SESSION['user']['last_name'] = $lastName;
                $_SESSION['user']['suffix'] = $suffix;
                $_SESSION['user']['contact_number'] = $contactNumber;
                $_SESSION['user']['profile_picture'] = $profilePicture;
                
                $_SESSION['success_message'] = 'Profile updated successfully!';
                
            } else {
                throw new Exception("Failed to update profile in database.");
            }
            
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            $_SESSION['error_message'] = 'Error updating profile: ' . $e->getMessage();
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Fetch data from database
try {
    // Fetch users by role
    $trainers = $pdo->query("SELECT * FROM users WHERE role = 'trainer' ORDER BY date_created DESC")->fetchAll();
    $trainees = $pdo->query("SELECT * FROM users WHERE role = 'trainee' ORDER BY date_created DESC")->fetchAll();
    $guests = $pdo->query("SELECT * FROM users WHERE role = 'guest' ORDER BY date_created DESC")->fetchAll();
    
    // Fetch courses
    $courses = $pdo->query("SELECT * FROM courses ORDER BY date_created DESC")->fetchAll();
    
    // Fetch announcements - IMPROVED QUERY
    $announcements = $pdo->query("
        SELECT a.*, u.first_name, u.last_name 
        FROM announcements a 
        JOIN users u ON a.posted_by = u.user_id 
        ORDER BY a.date_posted DESC LIMIT 5
    ")->fetchAll();
    
    // Debug announcements
    error_log("Fetched " . count($announcements) . " announcements");
    
    // Fetch enrollments
    $enrollments = $pdo->query("SELECT e.*, u.first_name, u.last_name, c.course_name, u.role as trainee_role
                               FROM enrollments e 
                               JOIN users u ON e.trainee_id = u.user_id 
                               JOIN courses c ON e.course_code = c.course_code
                               WHERE e.status = 'pending'
                               ORDER BY date_requested DESC")->fetchAll();
    
    // Fetch course batches
    $courseBatches = $pdo->query("
        SELECT cb.*, c.course_name, COUNT(ba.id) as trainee_count 
        FROM course_batches cb 
        JOIN courses c ON cb.course_code = c.course_code 
        LEFT JOIN batch_assignments ba ON cb.course_code = ba.course_code AND cb.batch_name = ba.batch_name 
        GROUP BY cb.id, cb.course_code, cb.batch_name 
        ORDER BY cb.created_at DESC
    ")->fetchAll();
    
    // Fetch admin profile
    try {
        $adminProfileStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $adminProfileStmt->execute([$user['user_id']]);
        $adminProfile = $adminProfileStmt->fetch();
        
        // Check if user was found in database
        if (!$adminProfile) {
            // If no database record found, use session data with safe defaults
            $adminProfile = [
                'first_name' => $user['first_name'] ?? 'Admin',
                'last_name' => $user['last_name'] ?? 'User', 
                'email' => $user['email'] ?? 'admin@bts.gov.ph',
                'contact_number' => $user['contact_number'] ?? 'Not set',
                'profile_picture' => $user['profile_picture'] ?? ''
            ];
        }
    } catch (Exception $e) {
        error_log("Error fetching admin profile: " . $e->getMessage());
        // Fallback to session data if query fails
        $adminProfile = [
            'first_name' => $user['first_name'] ?? 'Admin',
            'last_name' => $user['last_name'] ?? 'User',
            'email' => $user['email'] ?? 'admin@bts.gov.ph',
            'contact_number' => $user['contact_number'] ?? 'Not set',
            'profile_picture' => $user['profile_picture'] ?? ''
        ];
    }
    
    // Fetch course assignments
    $courseAssignments = $pdo->query("
        SELECT ca.*, u.first_name, u.last_name, c.course_name 
        FROM course_assignments ca 
        JOIN users u ON ca.trainer_id = u.user_id 
        JOIN courses c ON ca.course_code = c.course_code 
        ORDER BY ca.date_assigned DESC
    ")->fetchAll();
    
    // Fetch batch assignments
    $batchAssignments = $pdo->query("
        SELECT ba.*, u.first_name, u.last_name, c.course_name 
        FROM batch_assignments ba 
        JOIN users u ON ba.trainee_id = u.user_id 
        JOIN courses c ON ba.course_code = c.course_code 
        ORDER BY ba.date_assigned DESC
    ")->fetchAll();
    
    // Fetch course materials with trainer info
    $courseMaterials = $pdo->query("
        SELECT cm.*, c.course_name, u.first_name, u.last_name 
        FROM course_materials cm 
        JOIN courses c ON cm.course_code = c.course_code 
        LEFT JOIN users u ON cm.uploaded_by = u.user_id 
        ORDER BY cm.date_created DESC
    ")->fetchAll();
    
    // Fetch submissions with details
    $submissions = $pdo->query("
        SELECT s.*, u.first_name, u.last_name, cm.title as material_title, c.course_name 
        FROM submissions s 
        JOIN users u ON s.trainee_id = u.user_id 
        JOIN course_materials cm ON s.material_id = cm.id 
        JOIN courses c ON cm.course_code = c.course_code 
        ORDER BY s.submitted_at DESC
    ")->fetchAll();
    
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

function generateTempPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

function cleanupInactiveGuests($pdo) {
    try {
        $stmt = $pdo->prepare("
            DELETE FROM users 
            WHERE role = 'guest' 
            AND (last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL 6 MONTH))
            AND status = 'active'
        ");
        $stmt->execute();
        $deletedCount = $stmt->rowCount();
        if ($deletedCount > 0) {
            error_log("Cleaned up $deletedCount inactive guest accounts");
        }
    } catch (Exception $e) {
        error_log("Error cleaning up guests: " . $e->getMessage());
    }
}

// Count statistics
$totalTrainers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'trainer' AND status = 'active'")->fetchColumn();
$totalTrainees = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'trainee' AND status = 'active'")->fetchColumn();
$totalGuests = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guest'")->fetchColumn();
$totalCourses = $pdo->query("SELECT COUNT(*) FROM courses WHERE status = 'active'")->fetchColumn();
$pendingEnrollments = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'pending'")->fetchColumn() ?? 0;

// Count statistics for active/archived users
$activeTrainersCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'trainer' AND status = 'active'")->fetchColumn();
$archivedTrainersCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'trainer' AND status = 'archived'")->fetchColumn();
$activeTraineesCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'trainee' AND status = 'active'")->fetchColumn();
$archivedTraineesCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'trainee' AND status = 'archived'")->fetchColumn();
?>

<!-- THE REST OF YOUR HTML CODE REMAINS EXACTLY THE SAME -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Benguet Technical School-eLMS</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/admin.css">
  <link rel="icon" type="image/png" href="../images/school.png">
</head>

<body>
  <div class="container">
    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar">
      <div class="profile">
        <div class="user-card">
            <div class="user-card-header">
                <img src="<?php echo !empty($adminProfile['profile_picture']) ? '../uploads/profiles/' . $adminProfile['profile_picture'] . '?t=' . time() : '../images/school.png'; ?>" alt="User Avatar" class="user-avatar" id="userCardAvatar">
                <button class="edit-profile-btn" id="editProfileBtn" title="Edit Profile">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
            <div class="user-info">
                <h2 class="user-name" id="userCardName">
                    <?php 
                    echo htmlspecialchars($adminProfile['first_name'] . ' ' . $adminProfile['last_name']);
                    ?>
                </h2>
                <p class="user-email"><i class="fas fa-envelope"></i> 
                    <span id="userCardEmail"><?php echo htmlspecialchars($adminProfile['email']); ?></span>
                </p>
                <p class="user-contact"><i class="fas fa-phone"></i> 
                    <span id="userCardContact"><?php echo htmlspecialchars($adminProfile['contact_number'] ?: 'Not set'); ?></span>
                </p>
            </div>
        </div>
      </div>
      <nav class="nav">
        <a href="#" class="tab-link active" data-tab="home">Home</a>
        <a href="#" class="tab-link" data-tab="trainers">Trainers</a>
        <a href="#" class="tab-link" data-tab="trainees">Trainees</a>
        <a href="#" class="tab-link" data-tab="guests">Guests</a>
        <a href="#" class="tab-link" data-tab="courses">Courses</a>
        <a href="#" class="tab-link" data-tab="enrollments">Enrollments</a>
      </nav>
    </aside>

    <!-- ===== MAIN ===== -->
    <main class="main">
      <!-- ===== TOP BAR ===== -->
      <header class="topbar">
        <div class="hamburger" id="hamburger">
          <span></span>
          <span></span>
          <span></span>
        </div>
        
        <div class="user-info">
          <div class="notif-wrapper">
            <i class="fa-solid fa-bell notif-icon" id="notifIcon"></i>
            <div class="notif-dropdown hidden" id="notifDropdown">
              <p>No new notifications</p>
            </div>
          </div>

          <a href="../php/logout.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
          </a>
        </div>
      </header>

      <div class="sidebar-overlay" id="sidebarOverlay"></div>
      
      <!-- ===== CONTENT AREA ===== -->
      <div class="content">

        <!-- ===== HOME TAB ===== -->
        <section class="main-content tab-content active" id="home">
          <div class="home-content-wrapper">
            <div class="home-main-content">
              <div class="dashboard tab-inner active" id="dashboard">
                <h2 class="dashboard-header">User Management</h2>
                
                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
                <?php endif; ?>
                
                <div class="dashboard-cards">
                  <div class="dashboard-card" data-target="trainers">
                    <div class="number-circle"><?php echo $totalTrainers; ?></div>
                    <div class="label-text">Total Trainers</div>
                  </div>
                  <div class="dashboard-card" data-target="trainees">
                    <div class="number-circle"><?php echo $totalTrainees; ?></div>
                    <div class="label-text">Total Trainees</div>
                  </div>
                  <div class="dashboard-card" data-target="guests">
                    <div class="number-circle"><?php echo $totalGuests; ?></div>
                    <div class="label-text">Guests</div>
                  </div>
                  <div class="dashboard-card" data-target="courses">
                    <div class="number-circle"><?php echo $totalCourses; ?></div>
                    <div class="label-text">Courses Offered</div>
                  </div>
                  <div class="dashboard-card" data-target="enrollments">
                    <div class="number-circle"><?php echo $pendingEnrollments; ?></div>
                    <div class="label-text">Enrollment Requests</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ===== ANNOUNCEMENTS ===== -->
            <aside class="announcements">
              <div class="announcements-header">
                <h2>Announcements</h2>
                <button id="addAnnouncementBtn" class="add-announcement-btn">+</button>
              </div>
              <div class="announcements-list">
                <?php if (!empty($announcements)): ?>
                    <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-item">
                      <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                      <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                      <span class="announcement-date">Posted by <?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?> on <?php echo date('Y-m-d H:i', strtotime($announcement['date_posted'])); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-announcements">
                        <p>No announcements yet.</p>
                    </div>
                <?php endif; ?>
              </div>
            </aside>
          </div>
        </section>

        <!-- ===== TRAINERS TAB ===== -->
<section class="main-content tab-content" id="trainers">
  <div class="tab-header">
    <h2>Trainer Management</h2>
    <div class="tab-actions">
      <button class="create-btn" id="createTrainerBtn">+ Create Trainer</button>
    </div>
  </div>

  <!-- Google-style Search -->
  <div class="search-container">
    <div class="search-input-group">
      <i class="fas fa-search search-icon"></i>
      <input type="text" id="trainerSearchInput" class="search-input" 
             placeholder="Search trainers by name, ID, or email..." 
             value="<?php echo htmlspecialchars($trainerSearch); ?>">
      <div class="search-loading hidden" id="trainerSearchLoading">
        <i class="fas fa-spinner fa-spin"></i>
      </div>
      <?php if (!empty($trainerSearch)): ?>
        <a href="?trainer_page=1" class="clear-search" id="trainerClearSearch">Clear</a>
      <?php endif; ?>
    </div>
    <div class="search-results-dropdown" id="trainerSearchResults"></div>
    <div class="search-results-info" id="trainerSearchResultsInfo">
      <?php if (!empty($trainerSearch)): ?>
        Showing <?php echo count($activeTrainers); ?> of <?php echo $totalActiveTrainers; ?> active trainers matching "<?php echo htmlspecialchars($trainerSearch); ?>"
      <?php endif; ?>
    </div>
  </div>

  <!-- Switch Active/Archived -->
  <div class="switch-oval" style="margin-bottom: 20px;">
    <div class="switch-inner" id="trainerSwitchInner"></div>
    <button class="switch-btn active" data-tab="activeTrainers">Active (<?php echo $totalActiveTrainers; ?>)</button>
    <button class="switch-btn" data-tab="archivedTrainers">Archived (<?php echo $totalArchivedTrainers; ?>)</button>
  </div>

  <!-- Active Trainers -->
  <div class="tab-inner active" id="activeTrainers" data-tab-content>
    <h3>Active Trainers (<?php echo $activeTrainersCount; ?>)</h3>

    <div class="table-container">
      <table class="trainer-table">
        <thead>
          <tr>
            <th>User ID</th>
            <th>User Name</th>
            <th>Email</th>
            <th>Contact Number</th>
            <th>Assigned Courses</th>
            <th>Date Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($activeTrainers)): ?>
            <tr>
              <td colspan="7" class="no-data">No trainers found</td>
            </tr>
          <?php else: ?>
            <?php foreach ($activeTrainers as $trainer): ?>
              <td><?php echo htmlspecialchars($trainer['user_id']); ?></td>
              <td><?php echo htmlspecialchars($trainer['last_name'] . ', ' . $trainer['first_name'] . ($trainer['middle_name'] ? ' ' . $trainer['middle_name'] . '.' : '')); ?></td>
              <td><?php echo htmlspecialchars($trainer['email']); ?></td>
              <td><?php echo htmlspecialchars($trainer['contact_number']); ?></td>
              <td>
                <?php
                $assignedCourses = array_filter($courseAssignments, function($ca) use ($trainer) {
                    return $ca['trainer_id'] == $trainer['user_id'];
                });
                if (!empty($assignedCourses)) {
                    echo implode(', ', array_map(function($ca) {
                        return $ca['course_code'];
                    }, $assignedCourses));
                } else {
                    echo 'No courses assigned';
                }
                ?>
              </td>
              <td><?php echo date('Y-m-d', strtotime($trainer['date_created'])); ?></td>
              <td class="table-actions">
                  <button class="action-btn edit edit-trainer-btn" title="Edit"
                          data-user-id="<?php echo $trainer['user_id']; ?>"
                          data-user-data='<?php echo htmlspecialchars(json_encode($trainer), ENT_QUOTES, 'UTF-8'); ?>'>
                      <i class="fas fa-edit"></i><span class="btn-text">Edit</span>
                  </button>
                  <button class="action-btn reset reset-password-btn" title="Reset Password"
                          data-user-id="<?php echo $trainer['user_id']; ?>" 
                          data-user-name="<?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?>">
                      <i class="fas fa-key"></i><span class="btn-text">Reset</span>
                  </button>
                  <form method="POST" onsubmit="return confirm('Are you sure you want to archive this trainer?');">
                      <input type="hidden" name="user_id" value="<?php echo $trainer['user_id']; ?>">
                      <button type="submit" name="archive_user" class="action-btn archive" title="Archive"><i class="fas fa-archive"></i><span class="btn-text">Archive</span></button>
                  </form>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalActiveTrainerPages > 1): ?>
    <div class="pagination">
      <?php if ($trainerPage > 1): ?>
        <a href="?trainer_page=1&trainer_search=<?php echo urlencode($trainerSearch); ?>" class="page-link first">First</a>
        <a href="?trainer_page=<?php echo $trainerPage - 1; ?>&trainer_search=<?php echo urlencode($trainerSearch); ?>" class="page-link prev">Previous</a>
      <?php endif; ?>

      <?php for ($i = max(1, $trainerPage - 2); $i <= min($totalActiveTrainerPages, $trainerPage + 2); $i++): ?>
        <a href="?trainer_page=<?php echo $i; ?>&trainer_search=<?php echo urlencode($trainerSearch); ?>" 
           class="page-link <?php echo $i == $trainerPage ? 'active' : ''; ?>">
          <?php echo $i; ?>
        </a>
      <?php endfor; ?>

      <?php if ($trainerPage < $totalActiveTrainerPages): ?>
        <a href="?trainer_page=<?php echo $trainerPage + 1; ?>&trainer_search=<?php echo urlencode($trainerSearch); ?>" class="page-link next">Next</a>
        <a href="?trainer_page=<?php echo $totalActiveTrainerPages; ?>&trainer_search=<?php echo urlencode($trainerSearch); ?>" class="page-link last">Last</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Archived Trainers -->
  <div class="tab-inner" id="archivedTrainers" data-tab-content>
    <h3>Archived Trainers (<?php echo $archivedTrainersCount; ?>)</h3>
    <div class="table-container">
      <table class="trainer-table">
        <thead>
          <tr>
            <th>User ID</th>
            <th>User Name</th>
            <th>Email</th>
            <th>Contact Number</th>
            <th>Assigned Courses</th>
            <th>Date Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($archivedTrainers)): ?>
            <tr>
              <td colspan="7" class="no-data">No archived trainers found</td>
            </tr>
          <?php else: foreach ($archivedTrainers as $trainer): ?>
          <tr data-status="archived">
            <td><?php echo htmlspecialchars($trainer['user_id']); ?></td>
            <td><?php echo htmlspecialchars($trainer['last_name'] . ', ' . $trainer['first_name'] . ($trainer['middle_name'] ? ' ' . $trainer['middle_name'] . '.' : '')); ?></td>
            <td><?php echo htmlspecialchars($trainer['email']); ?></td>
            <td><?php echo htmlspecialchars($trainer['contact_number']); ?></td>
            <td>
              <?php
              $assignedCourses = array_filter($courseAssignments, function($ca) use ($trainer) {
                  return $ca['trainer_id'] == $trainer['user_id'];
              });
              if (!empty($assignedCourses)) {
                  echo implode(', ', array_map(function($ca) {
                      return $ca['course_code'];
                  }, $assignedCourses));
              } else {
                  echo 'No courses assigned';
              }
              ?>
            </td>
            <td><?php echo date('Y-m-d', strtotime($trainer['date_created'])); ?></td>
            <td class="table-actions">
              <form method="POST" onsubmit="return confirm('Are you sure you want to unarchive this trainer?');">
                <input type="hidden" name="user_id" value="<?php echo $trainer['user_id']; ?>">
                <button type="submit" name="unarchive_user" class="action-btn unarchive" title="Unarchive"><i class="fas fa-box-open"></i><span class="btn-text">Unarchive</span></button>
              </form>
              <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to permanently delete this trainer? This action cannot be undone.');">
                <input type="hidden" name="user_id" value="<?php echo $trainer['user_id']; ?>">
                <button type="submit" name="delete_user" class="action-btn delete" title="Delete Permanently"><i class="fas fa-trash"></i><span class="btn-text">Delete</span></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <!-- Pagination for Archived Trainers -->
    <?php if ($totalArchivedTrainerPages > 1): ?>
    <div class="pagination">
      <?php if ($archivedTrainerPage > 1): ?>
        <a href="?archived_trainer_page=1&trainer_search=<?php echo urlencode($trainerSearch); ?>" class="page-link first">First</a>
        <a href="?archived_trainer_page=<?php echo $archivedTrainerPage - 1; ?>&trainer_search=<?php echo urlencode($trainerSearch); ?>" class="page-link prev">Previous</a>
      <?php endif; ?>

      <?php for ($i = max(1, $archivedTrainerPage - 2); $i <= min($totalArchivedTrainerPages, $archivedTrainerPage + 2); $i++): ?>
        <a href="?archived_trainer_page=<?php echo $i; ?>&trainer_search=<?php echo urlencode($trainerSearch); ?>" 
           class="page-link <?php echo $i == $archivedTrainerPage ? 'active' : ''; ?>">
          <?php echo $i; ?>
        </a>
      <?php endfor; ?>

      <?php if ($archivedTrainerPage < $totalArchivedTrainerPages): ?>
        <a href="?archived_trainer_page=<?php echo $archivedTrainerPage + 1; ?>&trainer_search=<?php echo urlencode($trainerSearch); ?>" class="page-link next">Next</a>
        <a href="?archived_trainer_page=<?php echo $totalArchivedTrainerPages; ?>&trainer_search=<?php echo urlencode($trainerSearch); ?>" class="page-link last">Last</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

        <!-- ===== TRAINEES TAB ===== -->
<section class="main-content tab-content" id="trainees">
  <div class="tab-header">
    <h2>Trainee Management</h2>
    <div class="tab-actions">
      <button class="create-btn" id="createTraineeBtn">+ Create Trainee</button>
    </div>
  </div>

  <!-- Google-style Search -->
  <div class="search-container">
    <div class="search-input-group">
      <i class="fas fa-search search-icon"></i>
      <input type="text" id="traineeSearchInput" class="search-input" 
             placeholder="Search trainees by name, ID, or email..." 
             value="<?php echo htmlspecialchars($traineeSearch); ?>">
      <div class="search-loading hidden" id="traineeSearchLoading">
        <i class="fas fa-spinner fa-spin"></i>
      </div>
      <?php if (!empty($traineeSearch)): ?>
        <a href="?trainee_page=1" class="clear-search" id="traineeClearSearch">Clear</a>
      <?php endif; ?>
    </div>
    <div class="search-results-dropdown" id="traineeSearchResults"></div>
    <div class="search-results-info" id="traineeSearchResultsInfo">
      <?php if (!empty($traineeSearch)): ?>
        Showing <?php echo count($activeTrainees); ?> of <?php echo $totalTrainees; ?> active trainees matching "<?php echo htmlspecialchars($traineeSearch); ?>"
      <?php endif; ?>
    </div>
  </div>

  <!-- Switch Active/Archived -->
  <div class="switch-oval" style="margin-bottom: 20px;">
    <div class="switch-inner" id="traineeSwitchInner"></div>
    <button class="switch-btn active" data-tab="activeTrainees">Active (<?php echo $totalTrainees; ?>)</button>
    <button class="switch-btn" data-tab="archivedTrainees">Archived (<?php echo $totalArchivedTrainees; ?>)</button>
  </div>

  <!-- Active Trainees -->
  <div class="tab-inner active" id="activeTrainees" data-tab-content>
    <h3>Active Trainees (<?php echo $totalTrainees; ?>)</h3>

    <div class="table-container">
      <table class="trainee-table">
        <thead>
          <tr>
            <th>User ID</th>
            <th>User Name</th>
            <th>Email</th>
            <th>Contact Number</th>
            <th>Enrolled Courses</th>
            <th>Batch</th>
            <th>Date Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($activeTrainees)): ?>
            <tr>
              <td colspan="8" class="no-data">No active trainees found</td>
            </tr>
          <?php else: ?>
            <?php foreach ($activeTrainees as $trainee): ?>
            <tr>
              <td><?php echo htmlspecialchars($trainee['user_id']); ?></td>
              <td><?php echo htmlspecialchars($trainee['last_name'] . ', ' . $trainee['first_name'] . ($trainee['middle_name'] ? ' ' . $trainee['middle_name'] . '.' : '')); ?></td>
              <td><?php echo htmlspecialchars($trainee['email']); ?></td>
              <td><?php echo htmlspecialchars($trainee['contact_number']); ?></td>
              <td>
                <?php
                $enrolledCourses = array_filter($enrollments, function($e) use ($trainee) {
                    return $e['trainee_id'] == $trainee['user_id'] && $e['status'] == 'approved';
                });
                if (!empty($enrolledCourses)) {
                    echo implode(', ', array_map(function($e) {
                        return $e['course_code'];
                    }, $enrolledCourses));
                } else {
                    echo 'No courses enrolled';
                }
                ?>
              </td>
              <td>
                <?php
                $batchAssignments = array_filter($batchAssignments, function($ba) use ($trainee) {
                    return $ba['trainee_id'] == $trainee['user_id'];
                });
                if (!empty($batchAssignments)) {
                    echo implode(', ', array_map(function($ba) {
                        return $ba['batch_name'];
                    }, $batchAssignments));
                } else {
                    echo 'No batch assigned';
                }
                ?>
              </td>
              <td><?php echo date('Y-m-d', strtotime($trainee['date_created'])); ?></td>
              <td class="table-actions">
                  <button class="action-btn edit edit-trainee-btn" title="Edit"
                          data-user-id="<?php echo $trainee['user_id']; ?>"
                          data-user-data='<?php echo htmlspecialchars(json_encode($trainee), ENT_QUOTES, 'UTF-8'); ?>'>
                      <i class="fas fa-edit"></i><span class="btn-text">Edit</span>
                  </button>
                  <button class="action-btn reset reset-password-btn" title="Reset Password"
                          data-user-id="<?php echo $trainee['user_id']; ?>" 
                          data-user-name="<?php echo htmlspecialchars($trainee['first_name'] . ' ' . $trainee['last_name']); ?>">
                      <i class="fas fa-key"></i><span class="btn-text">Reset</span>
                  </button>
                  <form method="POST" onsubmit="return confirm('Are you sure you want to archive this trainee?');">
                      <input type="hidden" name="user_id" value="<?php echo $trainee['user_id']; ?>">
                      <button type="submit" name="archive_user" class="action-btn archive" title="Archive"><i class="fas fa-archive"></i><span class="btn-text">Archive</span></button>
                  </form>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalActiveTraineePages > 1): ?>
    <div class="pagination">
      <?php if ($traineePage > 1): ?>
        <a href="?trainee_page=1&trainee_search=<?php echo urlencode($traineeSearch); ?>" class="page-link first">First</a>
        <a href="?trainee_page=<?php echo $traineePage - 1; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>" class="page-link prev">Previous</a>
      <?php endif; ?>

      <?php for ($i = max(1, $traineePage - 2); $i <= min($totalActiveTraineePages, $traineePage + 2); $i++): ?>
        <a href="?trainee_page=<?php echo $i; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>" 
           class="page-link <?php echo $i == $traineePage ? 'active' : ''; ?>">
          <?php echo $i; ?>
        </a>
      <?php endfor; ?>

      <?php if ($traineePage < $totalActiveTraineePages): ?>
        <a href="?trainee_page=<?php echo $traineePage + 1; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>" class="page-link next">Next</a>
        <a href="?trainee_page=<?php echo $totalActiveTraineePages; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>" class="page-link last">Last</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Archived Trainees -->
  <div class="tab-inner" id="archivedTrainees" data-tab-content>
    <h3>Archived Trainees (<?php echo $archivedTraineesCount; ?>)</h3>

    <div class="table-container">
      <table class="trainee-table">
        <thead>
          <tr>
            <th>User ID</th>
            <th>User Name</th>
            <th>Email</th>
            <th>Contact Number</th>
            <th>Enrolled Courses</th>
            <th>Batch</th>
            <th>Date Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($archivedTrainees)): ?>
            <tr>
              <td colspan="8" class="no-data">No archived trainees found</td>
            </tr>
          <?php else: ?>
            <?php foreach ($archivedTrainees as $trainee): ?>
            <tr data-status="archived">
              <td><?php echo htmlspecialchars($trainee['user_id']); ?></td>
              <td><?php echo htmlspecialchars($trainee['last_name'] . ', ' . $trainee['first_name'] . ($trainee['middle_name'] ? ' ' . $trainee['middle_name'] . '.' : '')); ?></td>
              <td><?php echo htmlspecialchars($trainee['email']); ?></td>
              <td><?php echo htmlspecialchars($trainee['contact_number']); ?></td>
              <td>
                <?php
                $enrolledCourses = array_filter($enrollments, function($e) use ($trainee) {
                    return $e['trainee_id'] == $trainee['user_id'] && $e['status'] == 'approved';
                });
                if (!empty($enrolledCourses)) {
                    echo implode(', ', array_map(function($e) {
                        return $e['course_code'];
                    }, $enrolledCourses));
                } else {
                    echo 'No courses enrolled';
                }
                ?>
              </td>
              <td>
                <?php
                $batchAssignments = array_filter($batchAssignments, function($ba) use ($trainee) {
                    return $ba['trainee_id'] == $trainee['user_id'];
                });
                if (!empty($batchAssignments)) {
                    echo implode(', ', array_map(function($ba) {
                        return $ba['batch_name'];
                    }, $batchAssignments));
                } else {
                    echo 'No batch assigned';
                }
                ?>
              </td>
              <td><?php echo date('Y-m-d', strtotime($trainee['date_created'])); ?></td>
              <td class="table-actions">
                <form method="POST" onsubmit="return confirm('Are you sure you want to unarchive this trainee?');">
                  <input type="hidden" name="user_id" value="<?php echo $trainee['user_id']; ?>">
                  <button type="submit" name="unarchive_user" class="action-btn unarchive" title="Unarchive"><i class="fas fa-box-open"></i><span class="btn-text">Unarchive</span></button>
                </form>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to permanently delete this trainee? This action cannot be undone.');">
                  <input type="hidden" name="user_id" value="<?php echo $trainee['user_id']; ?>">
                  <button type="submit" name="delete_user" class="action-btn delete" title="Delete Permanently"><i class="fas fa-trash"></i><span class="btn-text">Delete</span></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <!-- Pagination for Archived Trainees -->
    <?php if ($totalArchivedTraineePages > 1): ?>
    <div class="pagination">
      <?php if ($archivedTraineePage > 1): ?>
        <a href="?archived_trainee_page=1&trainee_search=<?php echo urlencode($traineeSearch); ?>" class="page-link first">First</a>
        <a href="?archived_trainee_page=<?php echo $archivedTraineePage - 1; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>" class="page-link prev">Previous</a>
      <?php endif; ?>

      <?php for ($i = max(1, $archivedTraineePage - 2); $i <= min($totalArchivedTraineePages, $archivedTraineePage + 2); $i++): ?>
        <a href="?archived_trainee_page=<?php echo $i; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>" 
           class="page-link <?php echo $i == $archivedTraineePage ? 'active' : ''; ?>">
          <?php echo $i; ?>
        </a>
      <?php endfor; ?>

      <?php if ($archivedTraineePage < $totalArchivedTraineePages): ?>
        <a href="?archived_trainee_page=<?php echo $archivedTraineePage + 1; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>" class="page-link next">Next</a>
        <a href="?archived_trainee_page=<?php echo $totalArchivedTraineePages; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>" class="page-link last">Last</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

        <!-- ===== GUESTS TAB ===== -->
<section class="main-content tab-content" id="guests">
  <div class="tab-header">
    <h2>Guest Management</h2>
  </div>

  <!-- Google-style Search -->
  <div class="search-container">
    <div class="search-input-group">
      <i class="fas fa-search search-icon"></i>
      <input type="text" id="guestSearchInput" class="search-input" 
             placeholder="Search guests by name, ID, or email..." 
             value="<?php echo htmlspecialchars($guestSearch); ?>">
      <div class="search-loading hidden" id="guestSearchLoading">
        <i class="fas fa-spinner fa-spin"></i>
      </div>
      <?php if (!empty($guestSearch)): ?>
        <a href="?guest_page=1" class="clear-search" id="guestClearSearch">Clear</a>
      <?php endif; ?>
    </div>
    <div class="search-results-dropdown" id="guestSearchResults"></div>
    <div class="search-results-info" id="guestSearchResultsInfo">
      <?php if (!empty($guestSearch)): ?>
        Showing <?php echo count($guests); ?> of <?php echo $totalGuests; ?> guests matching "<?php echo htmlspecialchars($guestSearch); ?>"
      <?php endif; ?>
    </div>
  </div>

  <div class="table-container">
    <table class="guest-table">
      <thead>
        <tr>
          <th>User ID</th>
          <th>User Name</th>
          <th>Email</th>
          <th>Contact Number</th>
          <th>Enrolled Courses</th>
          <th>Date Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($guests)): ?>
          <tr>
            <td colspan="7" class="no-data">No guests found</td>
          </tr>
        <?php else: ?>
          <?php foreach ($guests as $guest): ?>
          <tr>
            <td><?php echo htmlspecialchars($guest['user_id']); ?></td>
            <td><?php echo htmlspecialchars($guest['last_name'] . ', ' . $guest['first_name'] . ($guest['middle_name'] ? ' ' . $guest['middle_name'] . '.' : '')); ?></td>
            <td><?php echo htmlspecialchars($guest['email']); ?></td>
            <td><?php echo htmlspecialchars($guest['contact_number']); ?></td>
            <td>
              <?php
              $guestEnrollments = array_filter($enrollments, function($e) use ($guest) {
                  return $e['trainee_id'] == $guest['user_id'] && $e['status'] == 'approved';
              });
              if (!empty($guestEnrollments)) {
                  echo implode(', ', array_map(function($e) {
                      return $e['course_code'];
                  }, $guestEnrollments));
              } else {
                  echo 'No courses enrolled';
              }
              ?>
            </td>
            <td><?php echo date('Y-m-d', strtotime($guest['date_created'])); ?></td>
            <td class="table-actions">
              <button class="action-btn enroll enroll-guest-btn" title="Enroll Guest"
                      data-user-id="<?php echo $guest['user_id']; ?>"
                      data-user-name="<?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?>">
                  <i class="fas fa-plus"></i><span class="btn-text">Enroll</span>
              </button>
              <button class="action-btn reset reset-password-btn" title="Reset Password"
                      data-user-id="<?php echo $guest['user_id']; ?>" 
                      data-user-name="<?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?>">
                  <i class="fas fa-key"></i><span class="btn-text">Reset</span>
              </button>
              <button class="action-btn delete delete-guest-btn" title="Delete Guest" data-user-id="<?php echo $guest['user_id']; ?>"><i class="fas fa-trash"></i><span class="btn-text">Delete</span></button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalGuestPages > 1): ?>
  <div class="pagination">
    <?php if ($guestPage > 1): ?>
      <a href="?guest_page=1&guest_search=<?php echo urlencode($guestSearch); ?>" class="page-link first">First</a>
      <a href="?guest_page=<?php echo $guestPage - 1; ?>&guest_search=<?php echo urlencode($guestSearch); ?>" class="page-link prev">Previous</a>
    <?php endif; ?>

    <?php for ($i = max(1, $guestPage - 2); $i <= min($totalGuestPages, $guestPage + 2); $i++): ?>
      <a href="?guest_page=<?php echo $i; ?>&guest_search=<?php echo urlencode($guestSearch); ?>" 
         class="page-link <?php echo $i == $guestPage ? 'active' : ''; ?>">
        <?php echo $i; ?>
      </a>
    <?php endfor; ?>

    <?php if ($guestPage < $totalGuestPages): ?>
      <a href="?guest_page=<?php echo $guestPage + 1; ?>&guest_search=<?php echo urlencode($guestSearch); ?>" class="page-link next">Next</a>
      <a href="?guest_page=<?php echo $totalGuestPages; ?>&guest_search=<?php echo urlencode($guestSearch); ?>" class="page-link last">Last</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</section>

        <!-- ===== COURSES TAB ===== -->
        <section class="main-content tab-content" id="courses">
          <div class="tab-header">
            <h2>Course Management</h2>
            <div class="tab-actions">
              <button class="create-btn" id="addCourseBtn">+ Add Course</button>
              <button class="create-btn" id="addBatchBtn">+ Add Course Batch</button>
            </div>
          </div>

          <div class="courses-grid">
            <?php foreach ($courses as $course): ?>
            <div class="course-card">
              <div class="course-image">
                <?php if (!empty($course['image'])): ?>
                <img src="../uploads/courses/<?php echo htmlspecialchars($course['image']); ?>" alt="<?php echo htmlspecialchars($course['course_name']); ?>">
                <?php else: ?>
                <div class="course-image-placeholder">
                  <i class="fas fa-book"></i>
                </div>
                <?php endif; ?>
              </div>
              <div class="course-info">
                <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                <p class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></p>
                <p class="course-hours"><?php echo htmlspecialchars($course['hours']); ?> hours</p>
                <p class="course-description"><?php echo htmlspecialchars($course['description']); ?></p>
                
                <!-- Course Competencies -->
                <?php 
                $competencies = json_decode($course['competency_types'] ?? '[]', true);
                if (!empty($competencies)): 
                ?>
                <div class="course-competencies">
                  <h4>Competencies:</h4>
                  <ul>
                    <?php foreach ($competencies as $competency): ?>
                    <li>
                      <strong><?php echo ucfirst($competency['type']); ?>:</strong> 
                      <?php echo htmlspecialchars($competency['name']); ?>
                      <?php if (!empty($competency['description'])): ?>
                      - <?php echo htmlspecialchars($competency['description']); ?>
                      <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                  </ul>
                </div>
                <?php endif; ?>
                
                <!-- Course Batches -->
                <?php
                $courseBatches = array_filter($courseBatches, function($batch) use ($course) {
                    return $batch['course_code'] == $course['course_code'];
                });
                ?>
                <div class="course-batches">
                  <h4>Batches (<?php echo count($courseBatches); ?>):</h4>
                  <?php if (!empty($courseBatches)): ?>
                  <ul>
                    <?php foreach ($courseBatches as $batch): ?>
                    <li>
                      <?php echo htmlspecialchars($batch['batch_name']); ?>
                      (<?php echo $batch['trainee_count']; ?> trainees)
                    </li>
                    <?php endforeach; ?>
                  </ul>
                  <?php else: ?>
                  <p>No batches created yet.</p>
                  <?php endif; ?>
                </div>
              </div>
              <!-- In the courses section, update the course-actions div -->
<div class="course-actions">
    <button class="edit-course-btn" 
            data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>"
            data-course-data='<?php echo json_encode([
                'course_name' => $course['course_name'],
                'course_code' => $course['course_code'],
                'hours' => $course['hours'],
                'description' => $course['description'],
                'image' => $course['image'],
                'competency_types' => json_decode($course['competency_types'] ?? '[]', true)
            ]); ?>'>
        Edit
    </button>
    <button class="view-details-btn" 
            data-course='<?php echo json_encode([
                'course_code' => $course['course_code'],
                'course_name' => $course['course_name'],
                'hours' => $course['hours'],
                'description' => $course['description'],
                'image' => $course['image'],
                'competency_types' => json_decode($course['competency_types'] ?? '[]', true)
            ]); ?>'>
        View Details
    </button>
</div>
            </div>
            <?php endforeach; ?>
          </div>
        </section>

        <!-- ===== ENROLLMENTS TAB ===== -->
<section class="main-content tab-content" id="enrollments">
  <div class="tab-header">
    <h2>Enrollment Management</h2>
  </div>

  <!-- Google-style Search -->
  <div class="search-container">
    <div class="search-input-group">
      <i class="fas fa-search search-icon"></i>
      <input type="text" id="enrollmentSearchInput" class="search-input" 
             placeholder="Search enrollments by trainee name, course, or ID..." 
             value="<?php echo htmlspecialchars($enrollmentSearch); ?>">
      <div class="search-loading hidden" id="enrollmentSearchLoading">
        <i class="fas fa-spinner fa-spin"></i>
      </div>
      <?php if (!empty($enrollmentSearch)): ?>
        <a href="?enrollment_page=1" class="clear-search" id="enrollmentClearSearch">Clear</a>
      <?php endif; ?>
    </div>
    <div class="search-results-dropdown" id="enrollmentSearchResults"></div>
    <div class="search-results-info" id="enrollmentSearchResultsInfo">
      <?php if (!empty($enrollmentSearch)): ?>
        Showing <?php echo count($enrollments); ?> of <?php echo $totalEnrollments; ?> enrollments matching "<?php echo htmlspecialchars($enrollmentSearch); ?>"
      <?php endif; ?>
    </div>
  </div>

  <div class="table-container">
    <table class="enrollment-table">
      <thead>
        <tr>
          <th>Enrollment ID</th>
          <th>Trainee Name</th>
          <th>Course & Trainee Type</th>
          <th>Batch</th>
          <th>Status</th>
          <th>Date Requested</th>
          <th>Remarks</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($enrollments)): ?>
          <tr>
            <td colspan="8" class="no-data">No enrollments found</td>
          </tr>
        <?php else: ?>
          <?php foreach ($enrollments as $enrollment): ?>
          <tr>
            <td><?php echo $enrollment['id']; ?></td>
            <td><?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?></td>
            <td>
                <?php echo htmlspecialchars($enrollment['course_name']); ?><br>
                <small>(<?php echo htmlspecialchars(ucfirst($enrollment['trainee_role'])); ?>)</small>
            </td>
            <td><?php echo htmlspecialchars($enrollment['batch_name'] ?? 'Not assigned'); ?></td>
            <td>
              <span class="status-badge status-<?php echo $enrollment['status']; ?>">
                <?php echo ucfirst($enrollment['status']); ?>
              </span>
            </td>
            <td><?php echo date('Y-m-d H:i', strtotime($enrollment['date_requested'])); ?></td>
            <td><?php echo htmlspecialchars($enrollment['remarks'] ?? 'No remarks'); ?></td>
            <td>
              <?php if ($enrollment['status'] == 'pending'): ?>
                <div class="table-actions enrollment-actions" data-enrollment-id="<?php echo $enrollment['id']; ?>">
                    <button class="action-btn approve" title="Approve" data-action="approve"><i class="fas fa-check"></i><span class="btn-text">Approve</span></button>
                    <button class="action-btn reject" title="Reject" title="Reject"><i class="fas fa-times"></i><span class="btn-text">Reject</span></button>
                </div>
              <?php else: ?>
              <span class="processed-text">
                Processed on <?php echo date('Y-m-d', strtotime($enrollment['processed_date'])); ?>
              </span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalEnrollmentPages > 1): ?>
  <div class="pagination">
    <?php if ($enrollmentPage > 1): ?>
      <a href="?enrollment_page=1&enrollment_search=<?php echo urlencode($enrollmentSearch); ?>" class="page-link first">First</a>
      <a href="?enrollment_page=<?php echo $enrollmentPage - 1; ?>&enrollment_search=<?php echo urlencode($enrollmentSearch); ?>" class="page-link prev">Previous</a>
    <?php endif; ?>

    <?php for ($i = max(1, $enrollmentPage - 2); $i <= min($totalEnrollmentPages, $enrollmentPage + 2); $i++): ?>
      <a href="?enrollment_page=<?php echo $i; ?>&enrollment_search=<?php echo urlencode($enrollmentSearch); ?>" 
         class="page-link <?php echo $i == $enrollmentPage ? 'active' : ''; ?>">
        <?php echo $i; ?>
      </a>
    <?php endfor; ?>

    <?php if ($enrollmentPage < $totalEnrollmentPages): ?>
      <a href="?enrollment_page=<?php echo $enrollmentPage + 1; ?>&enrollment_search=<?php echo urlencode($enrollmentSearch); ?>" class="page-link next">Next</a>
      <a href="?enrollment_page=<?php echo $totalEnrollmentPages; ?>&enrollment_search=<?php echo urlencode($enrollmentSearch); ?>" class="page-link last">Last</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</section>
      </div>
    </main>
  </div>

  <!-- ===== MODALS ===== -->

  <!-- Add Announcement Modal -->
  <div class="modal hidden" id="addAnnouncementModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Add New Announcement</h2>
        <span class="close">&times;</span>
      </div>
      <form method="POST" id="announcementForm">
        <div class="modal-body">
          <div class="form-group">
            <label for="announcement_title">Title:</label>
            <input type="text" id="announcement_title" name="announcement_title" required>
          </div>
          <div class="form-group">
            <label for="announcement_content">Content:</label>
            <textarea id="announcement_content" name="announcement_content" rows="4" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="cancel-btn">Cancel</button>
          <button type="submit" name="add_announcement" class="submit-btn">Post Announcement</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Create Trainer Modal -->
  <div class="modal hidden" id="createTrainerModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Create Trainer Account</h2>
        <span class="close">&times;</span>
      </div>
      <form method="POST" id="createTrainerForm">
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label for="trainer_first_name">First Name:</label>
              <input type="text" id="trainer_first_name" name="trainer_first_name" required>
            </div>
            <div class="form-group">
              <label for="trainer_middle_name">Middle Name:</label>
              <input type="text" id="trainer_middle_name" name="trainer_middle_name">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="trainer_last_name">Last Name:</label>
              <input type="text" id="trainer_last_name" name="trainer_last_name" required>
            </div>
            <div class="form-group">
              <label for="trainer_suffix">Suffix:</label>
              <input type="text" id="trainer_suffix" name="trainer_suffix">
            </div>
          </div>
          <div class="form-group">
            <label for="trainer_number">Contact Number:</label>
            <input type="tel" id="trainer_number" name="trainer_number" required>
          </div>
          <div class="form-group">
            <label for="trainer_courses">Assign Courses (Multiple Selection):</label>
            <select id="trainer_courses" name="trainer_courses[]" multiple style="height: 120px;">
              <?php foreach ($courses as $course): ?>
              <option value="<?php echo $course['course_code']; ?>">
                <?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?>
              </option>
              <?php endforeach; ?>
            </select>
            <small>Hold Ctrl/Cmd to select multiple courses</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="cancel-btn">Cancel</button>
          <button type="submit" class="submit-btn">Create Trainer</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Create Trainee Modal -->
  <div class="modal hidden" id="createTraineeModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Create Trainee Account</h2>
        <span class="close">&times;</span>
      </div>
      <form method="POST" id="createTraineeForm">
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label for="trainee_first_name">First Name:</label>
              <input type="text" id="trainee_first_name" name="trainee_first_name" required>
            </div>
            <div class="form-group">
              <label for="trainee_middle_name">Middle Name:</label>
              <input type="text" id="trainee_middle_name" name="trainee_middle_name">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="trainee_last_name">Last Name:</label>
              <input type="text" id="trainee_last_name" name="trainee_last_name" required>
            </div>
            <div class="form-group">
              <label for="trainee_suffix">Suffix:</label>
              <input type="text" id="trainee_suffix" name="trainee_suffix">
            </div>
          </div>
          <div class="form-group">
            <label for="trainee_number">Contact Number:</label>
            <input type="tel" id="trainee_number" name="trainee_number" required>
          </div>
          <div class="form-group">
            <label for="trainee_course">Enroll in Course (Optional):</label>
            <select id="trainee_course" name="trainee_course">
              <option value="">Select a course</option>
              <?php foreach ($courses as $course): ?>
              <option value="<?php echo $course['course_code']; ?>">
                <?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="trainee_batch">Assign to Batch (Optional):</label>
            <select id="trainee_batch" name="trainee_batch">
              <option value="">Select a batch</option>
              <!-- Batches will be populated dynamically based on course selection -->
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="cancel-btn">Cancel</button>
          <button type="submit" name="create_trainee" class="submit-btn">Create Trainee</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add Course Modal -->
  <div class="modal hidden" id="addCourseModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Add New Course</h2>
        <span class="close">&times;</span>
      </div>
      <form method="POST" enctype="multipart/form-data" id="addCourseForm">
        <div class="modal-body">
          <div class="form-group"> <!-- This div will now be a grid container -->
            <div class="form-group">
              <label for="course_name">Course Name:</label>
              <input type="text" id="course_name" name="course_name" required>
            </div>
            <div class="form-group">
              <label for="course_code">Course Code:</label>
              <input type="text" id="course_code" name="course_code" required>
            </div>
            <div class="form-group">
              <label for="course_hours">Course Hours:</label>
              <input type="number" id="course_hours" name="course_hours" required min="1">
            </div>
            <div class="form-group">
              <label for="course_image">Course Image:</label>
              <input type="file" id="course_image" name="course_image" accept="image/*">
            </div>
            <div class="form-group form-group-full">
              <label for="course_description">Description:</label>
              <textarea id="course_description" name="course_description" rows="3"></textarea>
            </div>
          </div>
          
          <!-- Competencies Section -->
          <div class="competencies-section">
            <h3>Course Competencies</h3>
            
            <!-- Basic Competencies -->
            <div class="competency-group" id="basicCompetenciesGroup">
              <div class="competency-header">
                <h4>Basic Competencies</h4>
                <button type="button" class="add-competency-btn" data-type="basic">+ Add Another</button>
              </div>
              <div class="competency-fields">
                <div class="competency-field-group">
                  <div class="form-group">
                    <label>Name:</label>
                    <input type="text" name="basic_competency[]" placeholder="Enter basic competency name">
                  </div>
                  <div class="form-group">
                    <label>Description:</label>
                    <textarea name="basic_competency_desc[]" rows="2" placeholder="Enter description (optional)"></textarea>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Common Competencies -->
            <div class="competency-group" id="commonCompetenciesGroup">
              <div class="competency-header">
                <h4>Common Competencies</h4>
                <button type="button" class="add-competency-btn" data-type="common">+ Add Another</button>
              </div>
              <div class="competency-fields">
                <div class="competency-field-group">
                  <div class="form-group">
                    <label>Name:</label>
                    <input type="text" name="common_competency[]" placeholder="Enter common competency name">
                  </div>
                  <div class="form-group">
                    <label>Description:</label>
                    <textarea name="common_competency_desc[]" rows="2" placeholder="Enter description (optional)"></textarea>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Core Competencies -->
            <div class="competency-group" id="coreCompetenciesGroup">
              <div class="competency-header">
                <h4>Core Competencies</h4>
                <button type="button" class="add-competency-btn" data-type="core">+ Add Another</button>
              </div>
              <div class="competency-fields">
                <div class="competency-field-group">
                  <div class="form-group">
                    <label>Name:</label>
                    <input type="text" name="core_competency[]" placeholder="Enter core competency name">
                  </div>
                  <div class="form-group">
                    <label>Description:</label>
                    <textarea name="core_competency_desc[]" rows="2" placeholder="Enter description (optional)"></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- End Competencies Section -->
          
        </div>
        <div class="modal-footer">
          <button type="button" class="cancel-btn">Cancel</button>
          <button type="submit" name="add_course" class="submit-btn">Add Course</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add Course Batch Modal -->
  <div class="modal hidden" id="addBatchModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Add Course Batch</h2>
        <span class="close">&times;</span>
      </div>
      <form method="POST" id="addBatchForm">
        <div class="modal-body">
          <div class="form-group">
            <label for="batch_course_code">Course:</label>
            <select id="batch_course_code" name="batch_course_code" required>
              <option value="">Select a course</option>
              <?php foreach ($courses as $course): ?>
              <option value="<?php echo $course['course_code']; ?>">
                <?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="batch_name">Batch Name:</label>
            <input type="text" id="batch_name" name="batch_name" required>
          </div>
          <div class="form-group">
            <label for="batch_description">Description:</label>
            <textarea id="batch_description" name="batch_description" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="cancel-btn">Cancel</button>
          <button type="submit" name="add_course_batch" class="submit-btn">Add Batch</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Course Modal -->
<div class="modal hidden" id="editCourseModal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h2>Edit Course</h2>
            <span class="close">&times;</span>
        </div>
        <form method="POST" enctype="multipart/form-data" id="editCourseForm">
            <input type="hidden" name="edit_course" value="1">
            <input type="hidden" id="edit_course_code" name="course_code">
            
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="form-group">
                    <label for="edit_course_name">Course Name:</label>
                    <input type="text" id="edit_course_name" name="course_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_course_code_display">Course Code:</label>
                    <input type="text" id="edit_course_code_display" readonly 
                           style="background-color: #f5f5f5; color: #666;">
                    <small>Course code cannot be changed</small>
                </div>
                
                <div class="form-group">
                    <label for="edit_course_hours">Course Hours:</label>
                    <input type="number" id="edit_course_hours" name="course_hours" required min="1">
                </div>
                
                <div class="form-group">
                    <label for="edit_course_description">Description:</label>
                    <textarea id="edit_course_description" name="course_description" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_course_image">Course Image:</label>
                    <input type="file" id="edit_course_image" name="course_image" accept="image/*">
                    <small>Leave blank to keep current image</small>
                    <div id="current_image_preview" style="margin-top: 10px;"></div>
                </div>

                <!-- Competencies Section -->
                <div class="competencies-section">
                    <h3>Course Competencies</h3>
                    
                    <div id="edit_competencies_container">
                        <!-- Competencies will be dynamically added here -->
                    </div>
                    
                    <button type="button" id="add_new_competency_btn" class="add-competency-btn" 
                            style="margin-top: 15px;">
                        + Add New Competency
                    </button>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="cancel-btn">Cancel</button>
                <button type="submit" class="submit-btn">Update Course</button>
            </div>
        </form>
    </div>
</div>

  <!-- Enhanced View Course Details Modal -->
<div class="modal hidden" id="viewCourseDetailsModal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h2>Course Details</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body" id="courseDetailsContent" style="max-height: 80vh; overflow-y: auto;">
            <!-- Course details will be populated here -->
        </div>
    </div>
</div>

  <!-- Reset Password Modal -->
  <div class="modal hidden" id="resetPasswordModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Reset Password</h2>
        <span class="close">&times;</span>
      </div>
      <form method="POST" id="resetPasswordForm">
        <div class="modal-body">
          <p>Are you sure you want to reset the password for <strong id="resetUserName"></strong>?</p>
          <p>A new temporary password will be generated and shown to you.</p>
          <input type="hidden" name="user_id" id="resetUserId">
        </div>
        <div class="modal-footer">
          <button type="button" class="cancel-btn">Cancel</button>
          <button type="submit" name="reset_password" class="submit-btn">Reset Password</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Trainer Modal -->
<div class="modal hidden" id="editTrainerModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Trainer Account</h2>
            <span class="close">&times;</span>
        </div>
        <form id="editTrainerForm">
            <input type="hidden" name="user_id" id="editTrainerId">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_trainer_first_name">First Name:</label>
                    <input type="text" id="edit_trainer_first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_trainer_last_name">Last Name:</label>
                    <input type="text" id="edit_trainer_last_name" name="last_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_trainer_email">Email:</label>
                    <input type="email" id="edit_trainer_email" name="email" required readonly style="background-color: #f5f5f5;">
                    <small class="email-preview-note">Email will automatically update based on name changes</small>
                </div>
                <div class="form-group">
                    <label for="edit_trainer_contact">Contact Number:</label>
                    <input type="tel" id="edit_trainer_contact" name="contact_number">
                </div>
                <div class="form-group">
                    <label for="edit_trainer_courses">Assigned Courses:</label>
                    <select id="edit_trainer_courses" name="trainer_courses[]" multiple style="height: 120px;">
                        <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['course_code']; ?>">
                            <?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Hold Ctrl/Cmd to select multiple courses</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn">Cancel</button>
                <button type="submit" name="edit_trainer_courses" class="submit-btn">Update Trainer</button>
            </div>
        </form>
    </div>
</div>

  <!-- Edit Trainee Modal -->
  <div class="modal hidden" id="editTraineeModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Edit Trainee Account</h2>
        <span class="close">&times;</span>
      </div>
      <form id="editTraineeForm">
        <input type="hidden" name="user_id" id="editTraineeId">
        <div class="modal-body">
          <div class="form-group">
            <label for="edit_trainee_first_name">First Name:</label>
            <input type="text" id="edit_trainee_first_name" name="first_name" required>
          </div>
          <div class="form-group">
            <label for="edit_trainee_last_name">Last Name:</label>
            <input type="text" id="edit_trainee_last_name" name="last_name" required>
          </div>
          <div class="form-group">
            <label for="edit_trainee_email">Email:</label>
            <input type="email" id="edit_trainee_email" name="email" required readonly style="background-color: #f5f5f5;">
            <small class="email-preview-note">Email will automatically update based on name changes</small>
          </div>
          <div class="form-group">
            <label for="edit_trainee_contact">Contact Number:</label>
            <input type="tel" id="edit_trainee_contact" name="contact_number">
          </div>
          <div class="form-group">
            <label for="edit_trainee_course">Course Enrollment:</label>
            <select id="edit_trainee_course" name="course_code">
              <option value="">Select Course</option>
              <?php foreach ($courses as $course): ?>
              <option value="<?php echo $course['course_code']; ?>">
                <?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="edit_trainee_batch">Batch Assignment:</label>
            <select id="edit_trainee_batch" name="batch_name">
              <option value="">Select Batch</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="cancel-btn">Cancel</button>
          <button type="submit" name="edit_trainee_enrollment" class="submit-btn">Update Trainee</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Profile Modal -->
  <div class="modal hidden" id="editProfileModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Edit Profile</h2>
        <span class="close">&times;</span>
      </div>
      <form method="POST" enctype="multipart/form-data" id="editProfileForm">
        <div class="modal-body">
          <div class="profile-picture-section">
            <div class="current-profile-picture">
              <img src="<?php echo !empty($adminProfile['profile_picture']) ? '../uploads/profiles/' . $adminProfile['profile_picture'] . '?t=' . time() : '../images/school.png'; ?>" 
                   alt="Current Profile Picture" id="currentProfilePicture">
            </div>
            <div class="form-group">
              <label for="profile_picture">Change Profile Picture:</label>
              <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
              <small>Max file size: 2MB. Supported formats: JPG, PNG, GIF</small>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="first_name">First Name:</label>
              <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($adminProfile['first_name']); ?>" required>
            </div>
            <div class="form-group">
              <label for="middle_name">Middle Name:</label>
              <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($adminProfile['middle_name'] ?? ''); ?>">
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="last_name">Last Name:</label>
              <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($adminProfile['last_name']); ?>" required>
            </div>
            <div class="form-group">
              <label for="suffix">Suffix:</label>
              <input type="text" id="suffix" name="suffix" value="<?php echo htmlspecialchars($adminProfile['suffix'] ?? ''); ?>">
            </div>
          </div>
          
          <div class="form-group">
            <label for="contact_number">Contact Number:</label>
            <input type="tel" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($adminProfile['contact_number'] ?? ''); ?>">
          </div>
          
          <div class="form-group">
            <label for="email">Email (read-only):</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($adminProfile['email']); ?>" readonly>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="cancel-btn">Cancel</button>
          <button type="submit" name="update_profile" class="submit-btn">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

 <!-- Unified Account Confirmation Modal -->
<div id="accountConfirmationModal" class="modal hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="accountConfirmationTitle">Account Creation Confirmation</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div id="accountConfirmationContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="cancel-btn">Cancel</button>
            <button id="confirmAccountBtn" class="confirm-btn">Confirm Creation</button>
        </div>
    </div>
</div>
  <!-- Guest Enrollment Modal -->
  <div class="modal hidden" id="enrollGuestModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Enroll Guest in Course</h2>
        <span class="close">&times;</span>
      </div>
      <form method="POST" id="enrollGuestForm">
        <div class="modal-body">
          <input type="hidden" name="guest_id" id="enrollGuestId">
          <p>Enrolling: <strong id="enrollGuestName"></strong></p>
          <div class="form-group">
            <label for="enroll_course_code">Select Course:</label>
            <select id="enroll_course_code" name="enroll_course_code" required>
              <option value="">Select a course</option>
              <?php foreach ($courses as $course): ?>
              <option value="<?php echo $course['course_code']; ?>">
                <?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="cancel-btn">Cancel</button>
          <button type="submit" name="guest_enroll" class="submit-btn">Enroll Guest</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Remove this entire modal -->
<div id="resetPasswordConfirmationModal" class="modal hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Password Reset Successful</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div class="confirmation-details">
                <h3>New Temporary Password:</h3>
                <div id="resetPasswordConfirmationContent"></div>
                <div class="confirmation-note">
                    <p><em>Please save this password as it will not be shown again.</em></p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="submit-btn" onclick="closeModal('resetPasswordConfirmationModal')">OK</button>
        </div>
    </div>
</div>
  
 <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_search'])) {
        $searchType = $_POST['search_type'];
        $searchQuery = $_POST['search_query'];
        $results = [];
        
        switch ($searchType) {
            case 'trainer':
                $stmt = $pdo->prepare("SELECT user_id as id, CONCAT(first_name, ' ', last_name) as name, email, 'Trainers' as section 
                                     FROM users 
                                     WHERE role = 'trainer' AND status = 'active' AND (first_name LIKE ? OR last_name LIKE ? OR user_id LIKE ? OR email LIKE ?)
                                     LIMIT 10");
                $searchParam = "%$searchQuery%";
                $stmt->execute([$searchParam, $searchParam, $searchParam, $searchParam]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'trainee':
                $stmt = $pdo->prepare("SELECT user_id as id, CONCAT(first_name, ' ', last_name) as name, email, 'Trainees' as section 
                                     FROM users 
                                     WHERE role = 'trainee' AND status = 'active' AND (first_name LIKE ? OR last_name LIKE ? OR user_id LIKE ? OR email LIKE ?)
                                     LIMIT 10");
                $searchParam = "%$searchQuery%";
                $stmt->execute([$searchParam, $searchParam, $searchParam, $searchParam]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'guest':
                $stmt = $pdo->prepare("SELECT user_id as id, CONCAT(first_name, ' ', last_name) as name, email, 'Guest Accounts' as section 
                                     FROM users 
                                     WHERE role = 'guest' AND (first_name LIKE ? OR last_name LIKE ? OR user_id LIKE ? OR email LIKE ?)
                                     LIMIT 10");
                $searchParam = "%$searchQuery%";
                $stmt->execute([$searchParam, $searchParam, $searchParam, $searchParam]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'enrollment':
                $stmt = $pdo->prepare("SELECT e.id, CONCAT(u.first_name, ' ', u.last_name) as trainee_name, c.course_name, e.status, 'Enrollments' as section 
                                     FROM enrollments e 
                                     JOIN users u ON e.trainee_id = u.user_id 
                                     JOIN courses c ON e.course_code = c.course_code 
                                     WHERE (u.first_name LIKE ? OR u.last_name LIKE ? OR c.course_name LIKE ?)
                                     LIMIT 10");
                $searchParam = "%$searchQuery%";
                $stmt->execute([$searchParam, $searchParam, $searchParam]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
        }
        
        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }

    ?>
<script src="../js/admin.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize search for each section
    initializeSearch('trainer', '<?php echo $trainerSearch; ?>');
    initializeSearch('trainee', '<?php echo $traineeSearch; ?>');
    initializeSearch('guest', '<?php echo $guestSearch; ?>');
    initializeSearch('enrollment', '<?php echo $enrollmentSearch; ?>');
});

function initializeSearch(type, currentSearch) {
    const searchInput = document.getElementById(`${type}SearchInput`);
    const searchResults = document.getElementById(`${type}SearchResults`);
    const searchLoading = document.getElementById(`${type}SearchLoading`);
    const clearSearchBtn = document.getElementById(`${type}ClearSearch`);

    if (!searchInput || !searchResults) return;

    let searchTimeout;

    // Function to perform the search via GET request
    const submitSearch = (query) => {
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = '<?php echo $_SERVER["PHP_SELF"]; ?>';
        form.style.display = 'none';

        const urlParams = new URLSearchParams(window.location.search);
        urlParams.forEach((value, key) => {
            // Exclude current search and page params for this type
            if (key !== `${type}_search` && key !== `${type}_page`) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
        });

        // Add search parameter
        const searchInputField = document.createElement('input');
        searchInputField.type = 'hidden';
        searchInputField.name = `${type}_search`;
        searchInputField.value = query;
        form.appendChild(searchInputField);

        // Reset to first page when searching
        const pageInput = document.createElement('input');
        pageInput.type = 'hidden';
        pageInput.name = `${type}_page`;
        pageInput.value = '1';
        form.appendChild(pageInput);

        document.body.appendChild(form);
        form.submit();
    };

    // Handle Enter key
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = this.value.trim();
            submitSearch(query);
        }
    });

    // Handle live search for dropdown
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        if (query.length < 2) { // Don't search for less than 2 characters
            searchResults.style.display = 'none';
            if(searchLoading) searchLoading.classList.add('hidden');
            return;
        }

        if(searchLoading) searchLoading.classList.remove('hidden');
        searchResults.innerHTML = '<div class="search-loading">Searching...</div>';
        searchResults.style.display = 'block';

        searchTimeout = setTimeout(() => {
            performAjaxSearch(type, query, searchResults, searchLoading);
        }, 300);
    });

    // Show dropdown on focus if there's text
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length > 0 && searchResults.children.length > 0) {
            searchResults.style.display = 'block';
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

    // Handle clicking a result from the dropdown
    searchResults.addEventListener('click', function(e) {
        const resultItem = e.target.closest('.search-result-item');
        if (resultItem) {
            const searchValue = resultItem.getAttribute('data-search-value');
            searchInput.value = searchValue;
            searchResults.style.display = 'none';
            submitSearch(searchValue);
        }
    });

    // Handle clear button
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', (e) => {
            e.preventDefault();
            submitSearch('');
        });
    }
}

function performAjaxSearch(type, query, resultsContainer, loadingIndicator) {
    const formData = new FormData();
    formData.append('search_type', type);
    formData.append('search_query', query);
    formData.append('ajax_search', 'true');

    fetch('<?php echo $_SERVER["PHP_SELF"]; ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        displaySearchResults(type, query, data, resultsContainer);
    })
    .catch(error => {
        console.error('Search error:', error);
        resultsContainer.innerHTML = '<div class="no-results">Search failed. Please try again.</div>';
    })
    .finally(() => {
        if(loadingIndicator) loadingIndicator.classList.add('hidden');
    });
}

function displaySearchResults(type, query, data, resultsContainer) {
    if (!data || data.length === 0) {
        resultsContainer.innerHTML = '<div class="no-results">No results found for "' + query + '"</div>';
        return;
    }

    let html = '';
    let currentSection = '';

    data.forEach(item => {
        if (item.section && item.section !== currentSection) {
            if (currentSection !== '') html += '</div>';
            html += '<div class="search-section-title">' + item.section + '</div>';
            currentSection = item.section;
        }

        let displayText = '';
        let searchValue = '';

        switch(type) {
            case 'trainer':
            case 'trainee':
            case 'guest':
                displayText = `<strong>${item.name}</strong><br><small class="text-muted">ID: ${item.id} | Email: ${item.email}</small>`;
                searchValue = item.name;
                break;
            case 'enrollment':
                displayText = `<strong>${item.trainee_name}</strong><br><small class="text-muted">Course: ${item.course_name} | Status: ${item.status}</small>`;
                searchValue = item.trainee_name;
                break;
        }

        // Highlight the query in the display text
        const regex = new RegExp(`(${query})`, 'gi');
        displayText = displayText.replace(regex, '<mark>$1</mark>');

        html += `<div class="search-result-item" data-search-value="${searchValue}">${displayText}</div>`;
    });

    resultsContainer.innerHTML = html;
}
</script>
</body>
</html>