
<?php
// Prevent browser from caching old pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once '../php/SessionManager.php';
SessionManager::startSession();

// Check if user is logged in and has the correct role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'trainee') {
    header('Location: ../landingpage.php');
    exit;
}

// Check if password needs to be changed (exclude the password change page itself)
$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage !== 'force_change_password.php' && 
    isset($_SESSION['user']['password_changed_at']) && 
    $_SESSION['user']['password_changed_at'] === null) {
    header('Location: force_change_password.php');
    exit;
}

$user = SessionManager::getCurrentUser();

// Include database connection
require_once '../php/DatabaseConnection.php';

$database = new DatabaseConnection();
$db = $database->getConnection();

// Initialize variables
$enrolled_courses = [];
$offered_courses = [];
$enrollment_requests = [];
$announcements = [];

try {
    // Get trainee's enrolled courses
    $query = "SELECT e.*, c.course_name, c.course_code, c.hours, c.description, c.image 
              FROM enrollments e 
              JOIN courses c ON e.course_code = c.course_code 
              WHERE e.trainee_id = ? AND e.status = 'approved'";
    $stmt = $db->prepare($query);
    $stmt->execute([$user['user_id']]);
    $enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all available courses
    $query = "SELECT * FROM courses WHERE status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $offered_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get trainee's enrollment requests
    $query = "SELECT e.*, c.course_name 
              FROM enrollments e 
              JOIN courses c ON e.course_code = c.course_code 
              WHERE e.trainee_id = ? 
              ORDER BY e.date_requested DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$user['user_id']]);
    $enrollment_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get announcements
    $query = "SELECT * FROM announcements ORDER BY date_posted DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate dashboard stats
    $total_courses = count($enrolled_courses);
    $total_hours = 0;
    $total_submissions = 0;
    $total_activities = 0;
    
    foreach ($enrolled_courses as $course) {
        $total_hours += $course['hours'];
        
        // Count submissions for this course
        $submission_query = "SELECT COUNT(*) as count FROM submissions 
                            WHERE trainee_id = ? AND material_id IN 
                            (SELECT id FROM course_materials WHERE course_code = ?)";
        $submission_stmt = $db->prepare($submission_query);
        $submission_stmt->execute([$user['user_id'], $course['course_code']]);
        $submission_count = $submission_stmt->fetch(PDO::FETCH_ASSOC);
        $total_submissions += $submission_count['count'];

        // Count activities for this course
        $activity_query = "SELECT COUNT(ta.id) as count 
                           FROM topic_activities ta
                           JOIN course_topics ct ON ta.topic_id = ct.id
                           WHERE ct.course_code = ?";
        $activity_stmt = $db->prepare($activity_query);
        $activity_stmt->execute([$course['course_code']]);
        $activity_count = $activity_stmt->fetch(PDO::FETCH_ASSOC);
        $total_activities += $activity_count['count'];
    }
    
    $progress = $total_courses > 0 ? round(($total_submissions / ($total_courses * 5)) * 100) : 0; // Assuming 5 activities per course
    $pending_requests = count(array_filter($enrollment_requests, function($request) {
        return $request['status'] == 'pending';
    }));

} catch(PDOException $exception) {
    error_log("Trainee dashboard error: " . $exception->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benguet Technical School eLMS - Trainee</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../images/school.png">
    <link rel="stylesheet" href="../css/trainee.css">
</head>

<body>
    <div class="container">
        <!-- ===== SIDEBAR ===== -->
        <aside class="sidebar">
            <div class="profile">
                <!-- User Profile Card -->
                <div class="user-card">
                    <div class="user-card-header">
                        <img src="<?php echo !empty($user['profile_picture']) ? '../uploads/profiles/' . htmlspecialchars($user['profile_picture']) : '../images/school.png'; ?>" alt="User Avatar" class="user-avatar">
                        <button class="edit-profile-btn" id="editProfileBtn" title="Edit Profile">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                    <h3 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                    <p class="user-role">Trainee</p>
                    <p class="user-id"><?php echo htmlspecialchars($user['user_id']); ?></p>
                </div>
            </div>
            <nav class="nav">
                <a href="#" class="tab-link active" data-tab="home">Home</a>
                <a href="#" class="tab-link" data-tab="mycourses">My Courses</a>
                <a href="#" class="tab-link" data-tab="offeredCourses">Offered Courses</a>
                <a href="#" class="tab-link" data-tab="courses">Enrollment Request</a>
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
                        <div class="news-switch-wrapper">
                            <div class="switch-oval">
                                <div class="switch-inner" id="switchInner"></div>
                                <button class="switch-btn active" data-tab="dashboard">Dashboard</button>
                                <button class="switch-btn" data-tab="news">News</button>
                            </div>
                        </div>

                       <div class="dashboard tab-inner active" id="dashboard">
                            <div class="dashboard-header">Quick Overview</div>
                            <div class="dashboard-cards">
                                <div class="dashboard-card" data-target="mycourses">
                                    <div class="number-circle"><?php echo $total_courses; ?></div>
                                    <div class="label-text">My Courses</div>
                                </div>
                                <div class="dashboard-card" data-target="mycourses">
                                    <div class="number-circle"><?php echo $total_hours; ?></div>
                                    <div class="label-text">Hours Enrolled</div>
                                </div>
                                <div class="dashboard-card" data-target="mycourses">
                                    <div class="number-circle"><?php echo $total_activities; ?></div>
                                    <div class="label-text">Activities</div>
                                </div>
                                <div class="dashboard-card" data-target="courses">
                                    <div class="number-circle"><?php echo $pending_requests; ?></div>
                                    <div class="label-text">Pending Requests</div>
                                </div>
                            </div>
                        </div>

                        <!-- NEWS TAB -->
                        <div class="news tab-inner" id="news">
                            <div class="news-header">
                                <h2>Latest News & Announcements</h2>
                            </div>
                            <ul class="news-list">
                                <?php if (!empty($announcements)): ?>
                                    <?php foreach ($announcements as $announcement): ?>
                                    <li>
                                        <strong><?php echo htmlspecialchars($announcement['title']); ?></strong>
                                        <p><?php echo htmlspecialchars(substr($announcement['content'], 0, 150)); ?>...</p>
                                        <small><?php echo date('M d, Y', strtotime($announcement['date_posted'])); ?></small>
                                    </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li>No news yet. Stay tuned!</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </section>

                    <!-- MY COURSES TAB -->
                    <section class="tab-content" id="mycourses">
                        <div class="course-box">
                            <div class="news-switch-wrapper">
                                <div class="switch-oval">
                                    <div class="switch-inner" id="myCourseSwitchInner"></div>
                                    <button class="switch-btn active" data-tab="enrolled">Enrolled</button>
                                    <button class="switch-btn" data-tab="completed">Completed</button>
                                </div>
                            </div>

                            <!-- Enrolled Courses -->
                            <div id="enrolled" class="tab-inner active">
                                <h2 class="section-header">Enrolled Courses</h2>
                                <div class="course-list">
                                    <?php if (!empty($enrolled_courses)): ?>
                                        <?php foreach ($enrolled_courses as $course): ?>
                                        <div class="course-card" 
                                             data-course-id="<?php echo htmlspecialchars($course['course_code']); ?>"
                                             data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>">
                                            <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : 'https://via.placeholder.com/250x140'; ?>" alt="Course Image">
                                            <div class="course-info">
                                                <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                                                <p>Code: <?php echo htmlspecialchars($course['course_code']); ?></p>
                                                <p>Hours: <?php echo htmlspecialchars($course['hours']); ?> hrs</p>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="no-courses">
                                            <p>You are not enrolled in any courses yet.</p>
                                            <p>Check out our <a href="#" class="tab-switch" data-tab="offeredCourses">Offered Courses</a> to get started!</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Completed Courses -->
                            <div id="completed" class="tab-inner">
                                <h2 class="section-header">Completed Courses</h2>
                                <div class="course-list">
                                    <div class="no-courses">
                                        <p>No completed courses yet.</p>
                                        <p>Keep learning to complete your enrolled courses!</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Course Detail View -->
                        <div id="courseDetail" class="course-detail hidden">
                            <div class="detail-header">
                                <button class="back-btn"><i class="fas fa-arrow-left"></i> Back to My Courses</button>
                                <h2 id="course-detail-title">Course Title</h2>
                            </div>
                            <div class="course-meta">
                                <span id="course-detail-code"></span> | <span id="course-detail-hours"></span>
                            </div>
                            <p id="course-detail-description"></p>

                            <div class="news-switch-wrapper course-switch">
                                <div class="switch-oval">
                                    <div class="switch-inner"></div>
                                    <button class="switch-btn active" data-tab="modules">Modules</button>
                                    <button class="switch-btn" data-tab="activities">Activities</button>
                                </div>
                            </div>

                            <!-- Modules View (Competencies, Topics, Materials) -->
                            <div id="modules-view" class="tab-inner active">
                                <div id="competencies-list">
                                    <!-- Competencies, topics, and materials will be loaded here by JS -->
                                </div>
                            </div>

                            <!-- Activities View -->
                            <div id="activities-view" class="tab-inner">
                                <!-- Activities table will be loaded here -->
                            </div>
                        </div>
                    </section>

                    <!-- ENROLLMENT REQUEST TAB -->
                    <section class="tab-content" id="courses">
                        <h2 class="section-header">My Enrollment Requests</h2>
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Date Requested</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="requests-body">
                                <?php if (!empty($enrollment_requests)): ?>
                                    <?php foreach ($enrollment_requests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['course_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($request['date_requested'])); ?></td>
                                        <td>
                                            <span class="status-<?php echo htmlspecialchars($request['status']); ?>">
                                                <?php echo htmlspecialchars(ucfirst($request['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($request['remarks'] ?: 'No remarks'); ?></td>
                                        <td>
                                            <?php if ($request['status'] == 'pending'): ?>
                                                <button class="btn-cancel" data-request-id="<?php echo $request['id']; ?>">Cancel</button>
                                            <?php else: ?>
                                                <span class="processed">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center;">No enrollment requests found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </section>

                    <!-- OFFERED COURSES TAB -->
                    <section class="tab-content" id="offeredCourses">
                        <h2 class="section-header">Available Courses</h2>
                        <div class="course-box">
                            <div class="course-list">
                                <?php if (!empty($offered_courses)): ?>
                                    <?php foreach ($offered_courses as $course): ?>
                                    <div class="course-card">
                                        <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : 'https://via.placeholder.com/250x140'; ?>" alt="Course Image">
                                        <div class="course-info">
                                            <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                                            <p>Code: <?php echo htmlspecialchars($course['course_code']); ?></p>
                                            <p>Hours: <?php echo htmlspecialchars($course['hours']); ?> hrs</p>
                                            <p class="course-desc"><?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>...</p>
                                        </div>
                                        <?php 
                                        // Check if already enrolled
                                        $is_enrolled = false;
                                        $is_pending = false;
                                        foreach ($enrollment_requests as $request) {
                                            if ($request['course_code'] == $course['course_code']) {
                                                if ($request['status'] == 'approved') {
                                                    $is_enrolled = true;
                                                } elseif ($request['status'] == 'pending') {
                                                    $is_pending = true;
                                                }
                                            }
                                        }
                                        ?>
                                        <?php if ($is_enrolled): ?>
                                            <button class="enroll-btn enrolled" disabled>Already Enrolled</button>
                                        <?php elseif ($is_pending): ?>
                                            <button class="enroll-btn pending" disabled>Request Pending</button>
                                        <?php else: ?>
                                            <button class="enroll-btn" data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>" data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>">Enroll</button>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-courses">
                                        <p>No courses available at the moment.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- RIGHT SIDE: ANNOUNCEMENTS -->
                <aside class="announcements">
                    <h2>Announcements</h2>
                    <ul>
                        <?php if (!empty($announcements)): ?>
                            <?php foreach ($announcements as $announcement): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($announcement['title']); ?></strong><br>
                                <?php echo htmlspecialchars(substr($announcement['content'], 0, 100)); ?>...<br>
                                <small><?php echo date('M d, Y', strtotime($announcement['date_posted'])); ?></small>
                            </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>No announcements yet. Stay tuned!</li>
                        <?php endif; ?>
                    </ul>
                </aside>
            </div>
        </main>
    </div>

    <!-- ENROLL CONFIRMATION MODAL -->
    <div id="enrollModal" class="modal hidden">
        <div class="modal-content">
            <h3>⚠️ Confirmation</h3>
            <p>Are you sure you want to enroll in <strong id="enrollCourseName"></strong>?</p>
            <div class="modal-actions">
                <button id="confirmEnroll" class="confirm-btn">Yes, Enroll</button>
                <button id="cancelEnroll" class="cancel-btn">Cancel</button>
            </div>
        </div>
    </div>

    <!-- PROFILE MODAL -->
    <div class="modal hidden" id="profileModal">
        <div class="modal-content">
            <span class="close-btn" id="closeProfileModal">&times;</span>
            <h2>Edit Profile</h2>

            <!-- Profile Picture Section -->
            <div class="profile-wrapper">
                <img id="profilePreview" src="<?php echo htmlspecialchars($user['profile_picture'] ?: '../images/school.png'); ?>" alt="Profile">
                <button type="button" id="changeProfileBtn" class="change-profile-btn">Upload Profile</button>
                <input type="file" id="profileUpload" accept="image/*" style="display:none;">
            </div>

            <!-- Form Fields -->
            <form class="modal-form" id="profileForm" method="POST" action="../php/update_profile.php">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
                <div>
                    <label>First Name</label>
                    <input type="text" id="firstName" name="first_name" placeholder="Enter First Name" value="<?php echo htmlspecialchars($user['first_name']); ?>">
                </div>
                <div>
                    <label>Last Name</label>
                    <input type="text" id="lastName" name="last_name" placeholder="Enter Last Name" value="<?php echo htmlspecialchars($user['last_name']); ?>">
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter Email" value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>
                <div>
                    <label>Contact Number</label>
                    <input type="text" id="contactNumber" name="contact_number" placeholder="Enter Contact Number" value="<?php echo htmlspecialchars($user['contact_number']); ?>">
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

    <!-- DELETE ACCOUNT MODAL -->
    <div class="modal hidden" id="deleteAccountModal">
        <div class="modal-content small-modal">
            <span class="close-btn" id="closeDeleteModal">&times;</span>
            <h2>⚠️ Delete Account</h2>
            <p>Are you sure you want to permanently delete your account? <br>This action cannot be undone.</p>
            <div class="modal-buttons">
                <button class="cancel-btn" id="cancelDeleteBtn">Cancel</button>
                <form method="POST" action="../php/deleteAccount.php" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
                    <button type="submit" class="delete-btn">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ACTIVITY MODAL (keep your existing activity modal HTML) -->
    <div class="modal hidden" id="activityModal">
        <div class="modal-content activity-modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="activityModalTitle">Activity Title</h2>
                <button class="close-btn" id="closeActivityModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Activity Details Section -->
                <div class="activity-details-grid">
                    <div class="detail-item">
                        <i class="fas fa-calendar-alt"></i>
                        <div>
                            <strong>Due Date</strong>
                            <span id="activityDueDate"></span>
                        </div>
                    </div>
                    <div class="detail-item" id="activityAttachmentContainer">
                        <i class="fas fa-paperclip"></i>
                        <div>
                            <strong>Attachment</strong>
                            <a href="#" target="_blank" id="activityAttachmentLink">View Attached File</a>
                        </div>
                    </div>
                </div>

                <!-- Instructions Section -->
                <div class="activity-instructions" id="activityInstructionsSection">
                    <h4><i class="fas fa-info-circle"></i> Instructions</h4>
                    <div class="instructions-content" id="activityInstructions">
                        <!-- Instructions will be populated by JS -->
                    </div>
                </div>

                <!-- Teacher Remarks -->
                <div class="teacher-remarks hidden" id="teacherRemarksSection">
                    <h4><i class="fas fa-comment-alt"></i> Trainer's Remarks</h4>
                    <div class="remarks-content">
                        <p id="teacherRemarksContent"></p>
                        <strong id="teacherRemarksMeta"></strong>
                    </div>
                </div>

                <!-- Submission History -->
                <div class="submission-history hidden" id="submissionHistory">
                    <h4><i class="fas fa-history"></i> Submission History</h4>
                    <div id="historyContent" class="history-content">
                        <!-- Submission history will be populated by JS -->
                    </div>
                </div>
                
                <!-- File Upload Section -->
                <div class="upload-section" id="uploadSection">
                    <h4><i class="fas fa-upload"></i> Submit Your Work</h4>
                    <div class="form-group">
                        <label for="studentComment">Add a comment (optional)</label>
                        <textarea id="studentComment" placeholder="Type your comment here..."></textarea>
                    </div>
                    <div class="upload-area" id="uploadArea">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-text">Click to upload or drag and drop</div>
                        <div class="upload-hint">Max file size: 10MB | Supported: PDF, DOC, DOCX, JPG, PNG</div>
                        <input type="file" id="activityFileInput" class="file-input" style="display:none;">
                    </div>
                    <div class="file-preview hidden" id="activityFilePreview"></div>
                    <div class="modal-actions">
                        <button class="submit-btn" id="activitySubmitBtn" disabled>Submit Assignment</button>
                    </div>
                </div>
                
                <div class="submission-success hidden" id="submissionSuccess">
                    <i class="fas fa-check-circle"></i>
                    <span>Your assignment has been submitted successfully!</span>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/trainee.js"></script>
</body>
</html>