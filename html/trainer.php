<?php
require_once '../php/SessionManager.php';
SessionManager::startSession();
SessionManager::requireRole('trainer');

$user = SessionManager::getCurrentUser();

// Include database connection
require_once '../php/DatabaseConnection.php';

$database = new DatabaseConnection();
$db = $database->getConnection();

// Get trainer information using unified session
$trainer_id = $_SESSION['user']['user_id'];

// Initialize variables
$trainer_info = $user; // Use the session user data
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

// Trainees Pagination & Search
$traineeSearch = $_GET['trainee_search'] ?? '';
$traineePage = max(1, intval($_GET['trainee_page'] ?? 1));
$traineeOffset = ($traineePage - 1) * $itemsPerPage;

$trainees_base_query = "FROM users u 
                        JOIN enrollments e ON u.user_id = e.trainee_id
                        JOIN course_assignments ca ON e.course_code = ca.course_code
                        WHERE u.role = 'trainee' 
                        AND u.status = 'active' 
                        AND e.status = 'approved'
                        AND ca.trainer_id = :trainer_id";


try {
    // Get trainer's assigned courses
    $query = "SELECT c.* FROM courses c 
              INNER JOIN course_assignments ca ON c.course_code = ca.course_code 
              WHERE ca.trainer_id = ? AND c.status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute([$trainer_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get enrollment requests for trainer's courses only
    $query = "SELECT e.*, u.first_name, u.last_name, c.course_name
              FROM enrollments e 
              JOIN users u ON e.trainee_id = u.user_id
              JOIN courses c ON e.course_code = c.course_code
              JOIN course_assignments ca ON c.course_code = ca.course_code
              WHERE e.status = 'pending' AND ca.trainer_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$trainer_id]);
    $enrollment_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_requests = count($enrollment_requests);

    // Get total counts for dashboard using the actual tables from your schema
    // Total trainees enrolled in trainer's courses
    $query = "SELECT COUNT(DISTINCT e.trainee_id) as count 
              FROM enrollments e 
              JOIN course_assignments ca ON e.course_code = ca.course_code
              WHERE e.status = 'approved' AND ca.trainer_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$trainer_id]);
    $trainee_count = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_trainees = $trainee_count['count'];

    // Total submissions for trainer's courses
    $query = "SELECT COUNT(s.id) as count 
              FROM submissions s
              JOIN course_materials cm ON s.material_id = cm.id
              JOIN course_assignments ca ON cm.course_code = ca.course_code
              WHERE ca.trainer_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$trainer_id]);
    $submission_count = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_submissions = $submission_count['count'];

    // Total activities (course materials) for trainer's courses
    $query = "SELECT COUNT(cm.id) as count 
              FROM course_materials cm
              JOIN course_assignments ca ON cm.course_code = ca.course_code
              WHERE ca.trainer_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$trainer_id]);
    $activity_count = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_activities = $activity_count['count'];

    // Trainees query logic
    $trainees_count_query = "SELECT COUNT(DISTINCT u.user_id) " . $trainees_base_query;
    $trainees_data_query = "SELECT DISTINCT u.*, e.course_name, e.batch_name " . $trainees_base_query;

    if (!empty($traineeSearch)) {
        $search_condition = " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.user_id LIKE :search OR e.course_name LIKE :search)";
        $trainees_count_query .= $search_condition;
        $trainees_data_query .= $search_condition;
    }

    $trainees_data_query .= " ORDER BY u.date_created DESC LIMIT :limit OFFSET :offset";

    // Get total count for pagination
    $traineeCountParams = [':trainer_id' => $trainer_id];
    $traineeCountStmt = $db->prepare($trainees_count_query);
    if (!empty($traineeSearch)) {
        $traineeCountParams[':search'] = "%$traineeSearch%";
    }
    $traineeCountStmt->execute($traineeCountParams);
    $totalTraineesForTrainer = $traineeCountStmt->fetchColumn();
    $totalTraineePages = ceil($totalTraineesForTrainer / $itemsPerPage);

    // Get paginated data
    $traineesDataParams = [':trainer_id' => $trainer_id];
    $traineesStmt = $db->prepare($trainees_data_query);
    if (!empty($traineeSearch)) {
        $traineesDataParams[':search'] = "%$traineeSearch%";
    }
    $traineesDataParams[':limit'] = $itemsPerPage;
    $traineesDataParams[':offset'] = $traineeOffset;
    $traineesStmt->execute($traineesDataParams);
    $trainees = $traineesStmt->fetchAll(PDO::FETCH_ASSOC);


    // Get guests data who enrolled in trainer's courses
    $query = "SELECT DISTINCT u.* 
              FROM users u
              JOIN enrollments e ON u.user_id = e.trainee_id
              JOIN course_assignments ca ON e.course_code = ca.course_code
              WHERE u.role = 'guest' AND u.status = 'active' AND ca.trainer_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$trainer_id]);
    $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $exception) {
    echo "Error: " . $exception->getMessage();
    error_log("Trainer dashboard error: " . $exception->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benguet Technical School eLMS - Trainer</title>
    <link rel="stylesheet" href="../css/trainer.css">
    <link rel="icon" type="image/png" href="../images/school.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">

        <!-- ===== SIDEBAR ===== -->
        <aside class="sidebar">
            <div class="profile">
                <!-- User Profile Card -->
                 <div class="user-card">
                    <div class="user-card-header">
                        <img src="../images/school.png" alt="User Avatar" class="user-avatar">
                        <button class="edit-profile-btn" id="editProfileBtn" title="Edit Profile">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                    <h3 class="user-name"><?php echo htmlspecialchars($trainer_info['first_name'] . ' ' . $trainer_info['last_name']); ?></h3>
                    <p class="user-role">Trainer</p>
                    <p class="user-id"><?php echo htmlspecialchars($trainer_info['user_id']); ?></p>
                </div>
            </div>
            <nav class="nav">
                <a href="#" class="tab-link active" data-tab="home">Home</a>
                <a href="#" class="tab-link" data-tab="mycourses">My Courses</a>
                <a href="#" class="tab-link" data-tab="trainees">Trainees</a>
                <a href="#" class="tab-link" data-tab="guests">Guests</a>
                <a href="#" class="tab-link" data-tab="requests">Enrollment Request</a>
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
                    <!-- Notification bell -->
                    <div class="notif-wrapper">
                        <i class="fa-solid fa-bell notif-icon" id="notifIcon"></i>
                        <!-- Dropdown for notifications -->
                        <div class="notif-dropdown hidden" id="notifDropdown">
                            <p>No new notifications</p>
                        </div>
                    </div>

                    <!-- Logout button -->
                    <a href="../php/logout.php" class="logout-btn">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </div>
            </header>

            <!-- ===== CONTENT AREA ===== -->
            <div class="content">

                <!-- LEFT SIDE: MAIN CONTENT -->
                <div class="main-content">

                    <!-- HOME TAB -->
                    <section class="tab-content active" id="home">
                       <div class="dashboard-cards">
                            <div class="dashboard-card clickable-card" data-target="mycourses">
                                <div class="number-circle"><?php echo count($courses); ?></div>
                                <div class="label-text">My Courses</div>
                            </div>
                            <div class="dashboard-card clickable-card" data-target="trainees">
                                <div class="number-circle"><?php echo $total_trainees; ?></div>
                                <div class="label-text">Trainees</div>
                            </div>
                            <div class="dashboard-card clickable-card" data-target="guests">
                                <div class="number-circle"><?php echo count($guests); ?></div>
                                <div class="label-text">Guests</div>
                            </div>
                            <div class="dashboard-card clickable-card" data-target="requests">
                                <div class="number-circle"><?php echo $total_requests; ?></div>
                                <div class="label-text">Enrollment Request</div>
                            </div>
                        </div>
                    </section>

                    <!-- MY COURSES TAB -->
                    <section class="tab-content" id="mycourses">
                        <!-- Course List View -->
                        <div id="enrolled" class="tab-inner active">
                            <h2 class="section-header">My Assigned Courses</h2>
                            <div class="batch-list">
                                <?php if (count($courses) > 0): ?>
                                    <?php foreach ($courses as $course): ?>
                                    <div class="batch-card" 
                                         data-course="<?php echo htmlspecialchars($course['course_name']); ?>" 
                                         data-code="<?php echo htmlspecialchars($course['course_code']); ?>" 
                                         data-hours="<?php echo htmlspecialchars($course['hours']); ?>"
                                         data-course-id="<?php echo htmlspecialchars($course['id']); ?>"
                                         data-course-data='<?php echo json_encode([
                                            'competency_types' => json_decode($course['competency_types'] ?? '[]', true)
                                         ]); ?>'>
                                        <img src="<?php echo htmlspecialchars($course['image'] ?: '../images/course-placeholder.jpg'); ?>" alt="Course Image">
                                        <div class="batch-info">
                                            <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                                            <p><?php echo htmlspecialchars($course['hours']); ?> Hours</p>
                                            <p><?php echo htmlspecialchars($course['course_code']); ?></p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-courses">
                                        <p>No courses assigned to you yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Course Detail View -->
                        <div id="course-detail" class="course-detail hidden">
                            <div class="detail-header">
                                <h2 class="course-title" id="course-detail-title">Course Name</h2>
                                <button class="back-btn">← Back to Courses</button>
                            </div>
                            <div class="detail-body">
                                <div class="course-basic-info">
                                    <p><strong>Code:</strong> <span id="course-code"></span></p>
                                    <p><strong>Hours:</strong> <span id="course-hours"></span></p>
                                    <p><strong>Description:</strong> <span id="course-description"></span></p>
                                </div>
                                
                                <!-- Toggle between Batches and Competencies -->
                                <div class="view-toggle">
                                    <button class="toggle-btn active" data-view="batches">Batches</button>
                                    <button class="toggle-btn" data-view="competencies">Competencies & Topics</button>
                                </div>
                                
                                <!-- Batches Section -->
                                <div id="batches-view" class="detail-view active">
                                    <div class="batches-section">
                                        <h3>Batches</h3>
                                        <div id="batches-list">
                                            <!-- Batches will be loaded here -->
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Competencies Section -->
                                <div id="competencies-view" class="detail-view">
                                    <div class="competencies-section">
                                        <div class="section-header">
                                            <h3>Course Competencies & Topics</h3>
                                        </div>
                                        <div id="competencies-list">
                                            <!-- Competencies and topics will be loaded here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ENROLLMENT REQUEST TAB -->
                    <section class="tab-content" id="requests">
                        <h2 class="section-header">Enrollment Requests for My Courses</h2>
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Trainee Name</th>
                                    <th>Status</th>
                                    <th>Date Requested</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="requests-body">
                                <?php foreach ($enrollment_requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['course_name']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                    </td>
                                    <td>
                                        <span class="status-<?php echo htmlspecialchars($request['status']); ?>">
                                            <?php echo htmlspecialchars($request['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($request['date_requested'])); ?></td>
                                    <td class="action-buttons">
                                        <button class="approve-btn" data-request-id="<?php echo $request['id']; ?>">Approve</button>
                                        <button class="reject-btn" data-request-id="<?php echo $request['id']; ?>">Reject</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($enrollment_requests)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No pending enrollment requests.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </section>

                    <!-- TRAINEES TAB -->
                    <section class="tab-content" id="trainees">
                        <h2 class="section-header">My Trainees</h2>

                        <!-- Search Bar -->
                        <div class="search-container" style="margin-bottom: 20px;">
                            <form method="GET" action="trainer.php#trainees">
                                <input type="hidden" name="trainee_page" value="1">
                                <div class="search-input-group">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" name="trainee_search" class="search-input" 
                                           placeholder="Search trainees by name, ID, email, or course..." 
                                           value="<?php echo htmlspecialchars($traineeSearch); ?>">
                                    <button type="submit" class="search-btn">Search</button>
                                    <?php if (!empty($traineeSearch)): ?>
                                        <a href="trainer.php?trainee_page=1#trainees" class="clear-search">Clear</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <table class="trainees-table">
                            <thead>
                                <tr>
                                    <th>Trainee Name</th>
                                    <th>Email</th>
                                    <th>Contact Number</th>
                                    <th>Enrolled Course</th>
                                    <th>Batch</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trainees as $trainee): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($trainee['first_name'] . ' ' . $trainee['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($trainee['email']); ?></td>
                                        <td><?php echo htmlspecialchars($trainee['contact_number'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($trainee['course_name'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($trainee['batch_name'] ?: 'Not Assigned'); ?></td>
                                        <td>
                                            <span class="status-active"><?php echo htmlspecialchars($trainee['status']); ?></span>
                                        </td>
                                        <td><?php echo $trainee['last_login'] ? date('M d, Y H:i', strtotime($trainee['last_login'])) : 'Never'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($trainees)): ?>
                                <tr>
                                     <td colspan="7" style="text-align: center;">No trainees found in your courses</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <?php if ($totalTraineePages > 1): ?>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $totalTraineePages; $i++): ?>
                                <a href="?trainee_page=<?php echo $i; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>#trainees" 
                                   class="page-link <?php echo $i == $traineePage ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                        <?php endif; ?>
                    </section>
                    
                    <!-- GUESTS TAB -->
                    <section class="tab-content" id="guests">
                        <h2 class="section-header">Guest Users in My Courses</h2>
                        
                        <div class="table-controls">
                            <div class="search-box">
                                <input type="text" id="guestSearch" placeholder="Search guests by name, email, or phone...">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="guests-count">
                                Total Guests: <span id="totalGuests"><?php echo count($guests); ?></span>
                            </div>
                        </div>
                        
                        <table class="guests-table">
                            <thead>
                                <tr>
                                    <th>Guest Name</th>
                                    <th>Email</th>
                                    <th>Contact Number</th>
                                    <th>Course</th>
                                    <th>Registration Date</th>
                                    <th>Last Login</th>
                                </tr>
                            </thead>
                            <tbody id="guestsBody">
                                <?php 
                                // Get guests with their enrolled course information
                                $guests_with_courses_query = "SELECT DISTINCT u.*, e.course_name
                                                            FROM users u
                                                            JOIN enrollments e ON u.user_id = e.trainee_id
                                                            JOIN course_assignments ca ON e.course_code = ca.course_code
                                                            WHERE u.role = 'guest' 
                                                            AND u.status = 'active'
                                                            AND e.status = 'approved'
                                                            AND ca.trainer_id = ?";
                                $guests_stmt = $db->prepare($guests_with_courses_query);
                                $guests_stmt->execute([$trainer_id]);
                                $guests_with_courses = $guests_stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach ($guests_with_courses as $guest): 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($guest['email']); ?></td>
                                    <td><?php echo htmlspecialchars($guest['contact_number'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($guest['course_name'] ?: '-'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($guest['date_created'])); ?></td>
                                    <td><?php echo $guest['last_login'] ? date('M d, Y H:i', strtotime($guest['last_login'])) : 'Never'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($guests_with_courses)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">No guests found in your courses</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </section>
                </div>

                <!-- RIGHT SIDE: ANNOUNCEMENTS -->
                <aside class="announcements">
                    <h2>Announcements</h2>
                    <ul>
                        <?php
                        try {
                            // Get ALL announcements (including admin announcements) ordered by most recent
                            $query = "SELECT a.*, u.first_name, u.last_name, u.role 
                                     FROM announcements a 
                                     JOIN users u ON a.posted_by = u.user_id
                                     ORDER BY a.date_posted DESC LIMIT 5";
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (count($announcements) > 0) {
                                foreach ($announcements as $announcement) {
                                    $author_info = htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']);
                                    $role = htmlspecialchars($announcement['role']);
                                    
                                    echo '<li>';
                                    echo '<strong>' . htmlspecialchars($announcement['title']) . '</strong><br>';
                                    echo htmlspecialchars(substr($announcement['content'], 0, 100)) . '...<br>';
                                    echo '<small>Posted by: ' . $author_info . ' (' . $role . ')</small><br>';
                                    echo '<small>' . date('M d, Y', strtotime($announcement['date_posted'])) . '</small>';
                                    echo '</li>';
                                }
                            } else {
                                echo '<li>No announcements yet. Stay tuned!</li>';
                            }
                        } catch(PDOException $exception) {
                            echo '<li>Error loading announcements</li>';
                        }
                        ?>
                    </ul>
                </aside>
            </div>
        </main>
    </div>

    <!-- ===== PROFILE MODAL ===== -->
    <div class="modal hidden" id="profileModal">
        <div class="modal-content">
            <span class="close-btn" id="closeProfileModal">&times;</span>
            <h2>Edit Profile</h2>

            <!-- Profile Picture Section -->
            <div class="profile-wrapper">
                <img id="profilePreview" src="../images/school.png" alt="Profile">
                <button type="button" id="changeProfileBtn" class="change-profile-btn">Upload Profile</button>
                <input type="file" id="profileUpload" accept="image/*" style="display:none;">
            </div>

            <!-- Form Fields -->
            <form class="modal-form" id="profileForm" method="POST" action="../php/update_profile.php">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($trainer_info['user_id']); ?>">
                <div>
                    <label>First Name</label>
                    <input type="text" id="firstName" name="first_name" placeholder="Enter First Name" value="<?php echo htmlspecialchars($trainer_info['first_name']); ?>">
                </div>
                <div>
                    <label>Last Name</label>
                    <input type="text" id="lastName" name="last_name" placeholder="Enter Last Name" value="<?php echo htmlspecialchars($trainer_info['last_name']); ?>">
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter Email" value="<?php echo htmlspecialchars($trainer_info['email']); ?>" readonly style="background-color: #f5f5f5;">
                    <small class="email-preview-note">Email will automatically update based on name changes</small>
                </div>
                <div>
                    <label>Contact Number</label>
                    <input type="text" id="contactNumber" name="contact_number" placeholder="Enter Contact Number" value="<?php echo htmlspecialchars($trainer_info['contact_number']); ?>">
                </div>
                
                <!-- Buttons -->
                <div class="modal-buttons">
                    <button type="button" class="delete-account-btn" id="openDeleteModal">Delete Account</button>
                    <div>
                        <button type="button" class="cancel-btn" id="cancelProfileChanges">Cancel</button>
                        <button type="submit" class="primary-btn">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== ADD TOPIC MODAL ===== -->
    <div class="modal hidden" id="addTopicModal">
        <div class="modal-content">
            <span class="close-btn" id="closeTopicModal">&times;</span>
            <h2>Add Topic</h2>
            <div class="modal-info">
                <p><strong>Competency:</strong> <span id="topic_competency_name"></span></p>
                <p><strong>Course:</strong> <span id="topic_course_name"></span></p>
            </div>
            <form id="addTopicForm">
                <input type="hidden" id="topic_course_code" name="course_code">
                <input type="hidden" id="topic_competency_id" name="competency_id">
                <div class="form-group">
                    <label>Topic Name: *</label>
                    <input type="text" name="topic_name" required placeholder="Enter topic name">
                </div>
                <div class="form-group">
                    <label>Topic Description:</label>
                    <textarea name="topic_description" rows="3" placeholder="Enter topic description (optional)"></textarea>
                </div>
                <div class="form-group">
                    <label>Learning Objectives:</label>
                    <textarea name="learning_objectives" rows="3" placeholder="Enter learning objectives (optional)"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" id="cancelTopic">Cancel</button>
                    <button type="submit" class="primary-btn">Add Topic</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== ADD MATERIAL MODAL ===== -->
    <div class="modal hidden" id="addMaterialModal">
        <div class="modal-content">
            <span class="close-btn" id="closeMaterialModal">&times;</span>
            <h2>Add Material to Topic</h2>
            <form id="addMaterialForm" enctype="multipart/form-data">
                <input type="hidden" id="material_topic_id" name="topic_id">
                <div class="form-group">
                    <label>Material Title: *</label>
                    <input type="text" name="material_title" required placeholder="e.g., Introduction Slides">
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="material_description" rows="2" placeholder="A brief description of the material (optional)"></textarea>
                </div>
                <div class="form-group">
                    <label>Material Type: *</label>
                    <select name="material_type" id="material_type" required>
                        <option value="file">File Upload</option>
                        <option value="link">Web Link (URL)</option>
                    </select>
                </div>
                <div class="form-group" id="file_input_group">
                    <label>Upload File:</label>
                    <input type="file" name="material_file">
                    <small>Max file size: 200MB. Any file type is allowed.</small>
                </div>
                <div class="form-group hidden" id="link_input_group">
                    <label>Link URL:</label>
                    <input type="url" name="material_link" placeholder="https://example.com/resource">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" id="cancelMaterial">Cancel</button>
                    <button type="submit" class="primary-btn">Add Material</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== ADD ACTIVITY MODAL ===== -->
    <div class="modal hidden" id="addActivityModal">
        <div class="modal-content">
            <span class="close-btn" id="closeActivityModal">&times;</span>
            <h2>Add Activity to Topic</h2>
            <form id="addActivityForm" enctype="multipart/form-data">
                <input type="hidden" id="activity_topic_id" name="topic_id">
                <div class="form-group">
                    <label>Activity Title: *</label>
                    <input type="text" name="activity_title" required placeholder="e.g., Research Paper on Safety">
                </div>
                <div class="form-group">
                    <label>Description/Instructions:</label>
                    <textarea name="activity_description" rows="3" placeholder="Provide clear instructions for the trainee"></textarea>
                </div>
                <div class="form-group">
                    <label>Activity Type: *</label>
                    <select name="activity_type" required>
                        <option value="assignment">Assignment</option>
                        <option value="quiz">Quiz</option>
                        <option value="project">Project</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date (Optional):</label>
                        <input type="datetime-local" name="start_date">
                    </div>
                    <div class="form-group">
                        <label>Due Date: *</label>
                        <input type="datetime-local" name="due_date" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Max Score: *</label>
                    <input type="number" name="max_score" required value="100" min="1">
                </div>
                <div class="form-group">
                    <label>Attachment (Optional):</label>
                    <select name="activity_attachment_type" id="activity_attachment_type">
                        <option value="">None</option>
                        <option value="file">File Upload</option>
                        <option value="link">Web Link (URL)</option>
                    </select>
                </div>
                <div class="form-group hidden" id="activity_file_input_group">
                    <label>Upload File:</label>
                    <input type="file" name="activity_file" id="activity_file">
                    <small>Max file size: 200MB. Any file type is allowed.</small>
                </div>
                <div class="form-group hidden" id="activity_link_input_group">
                    <label>Link URL:</label>
                    <input type="url" name="activity_link" id="activity_link" placeholder="https://example.com/resource">
                </div>

                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" id="cancelActivity">Cancel</button>
                    <button type="submit" class="primary-btn">Add Activity</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== DELETE ACCOUNT MODAL ===== -->
    <div class="modal hidden" id="deleteAccountModal">
        <div class="modal-content small-modal">
            <span class="close-btn" id="closeDeleteModal">&times;</span>
            <h2>⚠️ Delete Account</h2>
             <p>Are you sure you want to permanently delete your account? <br>
                This action cannot be undone.</p>

            <div class="modal-buttons">
                <button class="cancel-btn" id="cancelDeleteBtn">Cancel</button>
                <form method="POST" action="../php/deleteAccount.php" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($trainer_info['user_id']); ?>">
                    <button type="submit" class="delete-btn">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/trainer.js"></script>
</body>
</html>
