<?php
// Prevent browser from caching old pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once '../php/SessionManager.php';

SessionManager::startSession();
SessionManager::requireRole('guest');

$user = SessionManager::getCurrentUser();

// Include database connection
require_once '../php/DatabaseConnection.php';

$database = new DatabaseConnection();
$db = $database->getConnection();

// Initialize variables
$offered_courses = [];
$enrolled_courses = [];
$enrollment_requests = [];

try {
    // Get all available courses
    $query = "SELECT * FROM courses WHERE status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $offered_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get guest's enrolled courses (approved enrollments)
    $query = "SELECT e.*, c.course_name, c.course_code, c.hours, c.description, c.image 
              FROM enrollments e 
              JOIN courses c ON e.course_code = c.course_code 
              WHERE e.trainee_id = ? AND e.status = 'approved'";
    $stmt = $db->prepare($query);
    $stmt->execute([$user['user_id']]);
    $enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get guest's enrollment requests
    $query = "SELECT e.*, c.course_name 
              FROM enrollments e 
              JOIN courses c ON e.course_code = c.course_code 
              WHERE e.trainee_id = ? 
              ORDER BY e.date_requested DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$user['user_id']]);
    $enrollment_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $exception) {
    error_log("Guest dashboard error: " . $exception->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benguet Technical School eLMS - Guest</title>
    <link rel ="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/guest.css">
    <link rel="icon" type="image/png" href="../images/school.png">
</head>
<body>
    <div class="container">
        <!-- ===== SIDEBAR ===== -->
        <aside class="sidebar">
            <div class="profile">
                <div class="user-card">
                    <div class="user-card-header"> 
                        <img src="<?php echo htmlspecialchars($user['profile_picture'] ?: '../images/school.png'); ?>" alt="User Avatar" class="user-avatar">
                        <button class="edit-profile-btn" id="editProfileBtn" title="Edit Profile">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                    <h3 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                    <p class="user-role">Guest</p>
                    <p class="user-id"><?php echo htmlspecialchars($user['user_id']); ?></p>
                </div>
            </div>
            <nav class="nav">
                <a href="#" class="tab-link active" data-tab="home">Home</a>
                <a href="#" class="tab-link" data-tab="courses">Offered Courses</a>
                <a href="#" class="tab-link" data-tab="enrolled">Enrolled Courses</a>
                <a href="#" class="tab-link" data-tab="aboutUs">About US</a>
            </nav>
        </aside>

        <!-- ===== MAIN ===== -->
        <main class="main">
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

            <!-- ===== CONTENT AREA ===== -->
            <div class="content">
                <div class="main-section">
                    <!-- ===== HOME TAB ===== -->
                    <section class="main-content tab-content active" id="home">
                        <div class="dashboard tab-inner active" id="dashboard">
                            <div class="slogan-box">
                                <div class="slogan-text">
                                    <div class="welcome-header">
                                        <i class="fas fa-graduation-cap fa-3x"></i>
                                        <div>
                                            <h2>Welcome <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
                                            <p>See opportunities for better future with BTS.</p>
                                            <p><strong>Enrolled Courses:</strong> <?php echo count($enrolled_courses); ?> | <strong>Pending Requests:</strong> <?php echo count(array_filter($enrollment_requests, function($r) { return $r['status'] == 'pending'; })); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="slideshow-container">
                                    <div class="slider">
                                        <div class="slide active" style="background-image: url('../images/bts3.jpg');"></div>
                                        <div class="slide" style="background-image: url('../images/bts1.jpg');"></div>
                                        <div class="slide" style="background-image: url('../images/bts2.jpg');"></div>
                                        <div class="slide" style="background-image: url('../images/bts4.jpg');"></div>
                                        <div class="slide" style="background-image: url('../images/bts5.jpg');"></div>
                                    </div>
                                    <div class="slideshow-text"></div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ===== OFFERED COURSES TAB ===== -->
                    <section class="main-content tab-content" id="courses">
                        <div class="tab-header">
                            <button id="backToCourses" class="back-btn hidden" aria-label="Back">
                                <i class="fa-solid fa-arrow-left"></i>
                            </button>
                            <h2 id="courseHeaderTitle">Offered Courses</h2>
                        </div>   

                        <!-- Course Grid -->
                        <div id="courseGrid" class="dashboard-cards">
                            <?php if (!empty($offered_courses)): ?>
                                <?php foreach ($offered_courses as $course): ?>
                                <?php 
                                // Check enrollment status
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
                                <div class="dashboard-card course-card" 
                                     data-course="<?php echo htmlspecialchars($course['course_code']); ?>" 
                                     data-title="<?php echo htmlspecialchars($course['course_name']); ?>">
                                    <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/course-placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($course['course_name']); ?>" class="course-img">
                                    <div class="label-text"><?php echo htmlspecialchars($course['course_name']); ?></div>
                                    <div class="sub-text"><?php echo htmlspecialchars($course['course_code']); ?> | <?php echo htmlspecialchars($course['hours']); ?> hours</div>
                                    <div class="course-description">
                                        <?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>...
                                    </div>
                                    <?php if ($is_enrolled): ?>
                                        <button class="enroll-btn enrolled" disabled>Already Enrolled</button>
                                    <?php elseif ($is_pending): ?>
                                        <button class="enroll-btn pending" disabled>Request Pending</button>
                                    <?php else: ?>
                                        <button class="enroll-btn" 
                                                data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>" 
                                                data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>">
                                            Enroll
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-courses">
                                    <p>No courses available at the moment.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- ===== ENROLLED COURSES TAB ===== -->
                    <section class="main-content tab-content" id="enrolled">
                        <button id="backToEnrolledCourses" class="back-btn hidden" aria-label="Back">
                            <i class="fa-solid fa-arrow-left"></i>
                        </button>
                        <h2>Enrolled Courses</h2>
                        <div id="enrolledCoursesContainer" class="dashboard-cards">
                            <?php if (!empty($enrolled_courses)): ?>
                                <?php foreach ($enrolled_courses as $course): ?>
                                <div class="dashboard-card course-card enrolled-course" 
                                     data-course="<?php echo htmlspecialchars($course['course_code']); ?>" 
                                     data-title="<?php echo htmlspecialchars($course['course_name']); ?>">
                                    <img src="<?php echo !empty($course['image']) ? '../uploads/courses/' . htmlspecialchars($course['image']) : '../images/course-placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($course['course_name']); ?>" class="course-img">
                                    <div class="label-text"><?php echo htmlspecialchars($course['course_name']); ?></div>
                                    <div class="sub-text"><?php echo htmlspecialchars($course['course_code']); ?> | <?php echo htmlspecialchars($course['hours']); ?> hours</div>
                                    <button class="view-course-btn">View Course</button>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="no-courses-message">You are not enrolled in any courses.</p>
                            <?php endif; ?>
                        </div>

                       <!-- Course Detail View -->
                        <div id="courseDetail" class="course-detail hidden">
                            <div class="course-content">
                                <!-- Course detail content will be populated by JavaScript -->
                            </div>
                        </div>
                    </section>

                    <!-- ===== ABOUT US TAB ===== -->
                    <section class="main-content tab-content" id="aboutUs">
                        <h2>About Benguet Technical School</h2>
                        <div class="about-content">
                            <p>
                                BENGUET TECHNICAL SCHOOL IS AN LGU-RUN TECHNICAL VOCATIONAL INSTITUTION UNDER THE PROVINCIAL GOVERNMENT OF BENGUET.
                                IT OFFERS VARIOUS PROGRAMS/SHORT COURSES ACCREDITED BY TESDA.
                            </p>
                            <p>
                                The Benguet Technical School traces its roots back to 1997, when then-President Fidel V. Ramos allocated ₱75 million from his Presidential Social Fund to establish Livelihood Training and Productivity Centers in marginalized regions across the country, including the Cordillera Administrative Region.
                            </p>
                            <p>
                                Today, Benguet Technical School stands as a lasting legacy of collaboration between the national government, the provincial government, and private partners. From its beginnings as a livelihood training center to its present role, BTS remains committed to providing opportunities that uplift the socio-economic conditions of communities in Benguet and the greater Cordillera region.
                            </p>
                            
                            <div class="contact-info">
                                <h3>Contact Information</h3>
                                <p><i class="fas fa-map-marker-alt"></i> Motorpool, Wangal, La Trinidad, Benguet</p>
                                <p><i class="fas fa-phone"></i> (074) 424-7060</p>
                                <p><i class="fas fa-envelope"></i> pgobts@bts.gov.ph</p>
                                <p><i class="fab fa-facebook"></i> Benguet Technical School</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <!-- ===== PROFILE MODAL ===== -->
<div class="modal hidden" id="profileModal">
    <div class="modal-content">
        <span class="close-btn" id="closeProfileModal">&times;</span>
        <h2>Edit Profile</h2>
        
        <div class="profile-wrapper">
            <img id="profilePreview" src="<?php echo htmlspecialchars($user['profile_picture'] ?: '../images/school.png'); ?>" alt="Profile">
            <button type="button" id="changeProfileBtn" class="change-profile-btn">Upload Profile</button>
            <input type="file" id="profileUpload" accept="image/*" style="display:none;">
        </div>
        
        <form class="modal-form" id="profileForm">
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
                <div class="validation-error hidden" id="emailError">Please enter a valid email address</div>
            </div>
            <div>
                <label>Contact Number</label>
<input type="text" id="contactNumber" name="contact_number" placeholder="Enter Contact Number" value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>">                <div class="validation-error hidden" id="contactError">Please enter a valid contact number</div>
            </div>
            
            <!-- Password Change Section -->
            <div class="password-change-section">
                <h3>Change Password</h3>
                <div>
                    <label>Old Password</label>
                    <input type="password" id="oldPassword" name="old_password" placeholder="Enter old password">
                </div>
                <div>
                    <label>New Password</label>
                    <input type="password" id="newPassword" name="new_password" placeholder="Enter new password">
                </div>
                <div>
                    <label>Confirm New Password</label>
                    <input type="password" id="confirmPassword" name="confirm_password" placeholder="Confirm new password">
                    <div class="validation-error hidden" id="passwordError">Passwords do not match</div>
                </div>
            </div>
        </form>
        
        <div class="modal-buttons">
            <button type="button" class="delete-account-btn" id="openDeleteModal">Delete Account</button>
            <div>
                <button type="button" class="cancel-btn" id="cancelProfileChanges">Cancel</button>
                <button type="button" class="primary-btn" id="saveProfileChanges">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== DELETE ACCOUNT MODAL ===== -->
<div class="modal hidden" id="deleteAccountModal">
    <div class="modal-content small-modal">
        <span class="close-btn" id="closeDeleteModal">&times;</span>
        <h2>⚠️ Delete Account</h2>
        <p>Are you sure you want to permanently delete your account? <br>This action cannot be undone.</p>
        <div class="modal-buttons">
            <button class="cancel-btn" id="cancelDeleteBtn">Cancel</button>
            <button type="button" class="delete-btn" id="confirmDeleteBtn">Delete</button>
        </div>
    </div>
</div>

<!-- ===== ENROLLMENT MODAL ===== -->
<div id="enrollModal" class="modal hidden">
    <div class="modal-content">
        <span class="close-btn" id="closeEnrollModal">&times;</span>
        <h3>⚠️ Confirmation</h3>
        <p>Are you sure you want to enroll in <strong id="enrollCourseName"></strong>?</p>
        <div class="modal-actions">
            <button id="confirmEnroll" class="confirm-btn">Yes, Enroll</button>
            <button id="cancelEnroll" class="cancel-btn">Cancel</button>
        </div>
    </div>
</div>

<!-- ===== ACTIVITY MODAL ===== -->
<div class="modal hidden" id="activityModal">
    <div class="modal-content activity-modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="activityModalTitle">Activity Title</h2>
            <button class="close-btn" id="closeActivityModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="activity-instructions">
                <div class="instructions-title">
                    <i class="fas fa-info-circle"></i>
                    Instructions
                </div>
                <div class="instructions-content" id="activityInstructions">
                    <!-- Instructions will be populated here -->
                </div>
            </div>
            
            <div class="due-date">
                <i class="fas fa-calendar-alt"></i>
                <span>Due: <strong id="activityDueDate">December 15, 2023</strong></span>
            </div>
            
            <!-- File Upload Section -->
            <div class="upload-section" id="uploadSection">
                <div class="upload-header">
                    <i class="fas fa-upload"></i>
                    <span>Submit Your Work</span>
                </div>
                <div class="upload-area" id="uploadArea">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text">Click to upload your file</div>
                    <div class="upload-hint">Max file size: 10MB | Supported: PDF, DOC, DOCX, JPG, PNG</div>
                    <input type="file" id="activityFileInput" class="file-input" style="display:none;">
                </div>
                <div class="file-preview hidden" id="activityFilePreview"></div>
                <button class="submit-btn" id="activitySubmitBtn" disabled>Submit Assignment</button>
            </div>

            <!-- Quiz Section -->
            <div class="quiz-section hidden" id="quizSection">
                <div class="quiz-instructions">
                    <p>This quiz contains 10 multiple-choice questions. You have 30 minutes to complete it.</p>
                    <button class="primary-btn" id="startQuizBtn">Start Quiz</button>
                </div>
            </div>
            
            <div class="submission-success hidden" id="submissionSuccess">
                <i class="fas fa-check-circle"></i>
                <span>Your assignment has been submitted successfully!</span>
            </div>
        </div>
    </div>
</div>

    <script src="../js/guest.js"></script>
</body>
</html>