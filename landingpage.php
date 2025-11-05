<?php
require_once 'php/SessionManager.php';
require_once 'php/DatabaseConnection.php';

SessionManager::startSession();

// Check if user is already logged in
if (SessionManager::isLoggedIn()) {
    SessionManager::redirectBasedOnRole();
}

$register_error = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $firstname = trim($_POST['firstname']);
    $middlename = trim($_POST['middlename']);
    $lastname = trim($_POST['lastname']);
    $suffix = trim($_POST['suffix']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirmPassword']);
    
    // SIMPLIFIED VALIDATION FOR TESTING
    if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
        $register_error = "All required fields must be filled!";
    } elseif ($password !== $confirmPassword) {
        $register_error = "Passwords do not match!";
    } else {
        try {
            $db = new DatabaseConnection();
            $conn = $db->getConnection();
            
            // Check if email exists
            $checkStmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            
            if ($checkStmt->fetch()) {
                $register_error = "Email already exists!";
            } else {
                // Generate simple user_id
                $user_id = '4' . date('md') . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert with only required fields
                $sql = "INSERT INTO users (user_id, role, first_name, last_name, email, password) 
                        VALUES (?, 'guest', ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([$user_id, $firstname, $lastname, $email, $hashedPassword]);
                
                if ($result && $stmt->rowCount() > 0) {
                    // Get the new user
                    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $newUser = $stmt->fetch();
                    
                    if ($newUser) {
                        SessionManager::loginUser($newUser);
                        header("Location: html/force_change_password.php");
                        exit();
                    } else {
                        $register_error = "Registration successful but failed to login.";
                    }
                } else {
                    $register_error = "Registration failed!";
                }
            }
        } catch (PDOException $e) {
            $register_error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <title>Benguet Technical School-eLMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/landingpage.css" />
    <link rel="icon" type="image/png" href="images/school.png">
</head>

<body>
  <!-- Header -->
  <header class="site-header">
        <div class="left-header">
            <img src="images/school.png" alt="BTS Logo" class="logo" />
            <h1 class="school-name">Benguet Technical School</h1>
        </div>
        <nav class="nav-links">
            <div id="openLogin" class="login-container">
                <span class="login-text" alt="Profile">Log In</span>
            </div>
        </nav>
    </header>
 
    <!-- Hero Section with Slideshow Background -->
    <section class="hero">
        <div class="slider">
            <div class="slide active" style="background-image: url('images/bts3.jpg');"></div>
            <div class="slide" style="background-image: url('images/bts1.jpg');"></div>
            <div class="slide" style="background-image: url('images/bts2.jpg');"></div>
            <div class="slide" style="background-image: url('images/bts4.jpg');"></div>
            <div class="slide" style="background-image: url('images/bts5.jpg');"></div>
        </div>
        <div class="hero-text">
            <h2>Learn Anytime, Anywhere</h2>
            <p>Empower your future with our easy and accessible eLearning system.</p>
        </div>
    </section>
 
    <!-- Available Courses Section -->
    <section id="courses" class="courses">
        <h2>Available Courses</h2>
        <div id="course-grid" class="course-grid">
            <!-- Course cards -->
            <div class="course-card">
                <img src="images/agriculture.jpg" alt="Agricultural Crops Production" />
                <h3>Agricultural Crops Production NC II</h3>
                <p>Learn crop production, farm tools usage, and sustainable agriculture techniques.</p>
            </div>
            <div class="course-card">
                <img src="images/automotive.jpg" alt="Automotive Servicing" />
                <h3>Automotive Servicing NC II</h3>
                <p>Master vehicle maintenance, diagnostics, and repair techniques for various automotive systems.</p>
            </div>
            <div class="course-card">
                <img src="images/breadandpastry.jpg" alt="Bread and Pastry Production" />
                <h3>Bread and Pastry Production NC II</h3>
                <p>Learn baking techniques, dough preparation, and pastry decoration for various baked goods.</p>
            </div>
            <div class="course-card">
                <img src="images/dressmaking.jpg" alt="Dressmaking" />
                <h3>Dressmaking NC II</h3>
                <p>Acquire skills in garment construction, fabric selection, and sewing techniques for custom clothing</p>
            </div>
            <div class="course-card">
                <img src="images/hairdressing.jpg" alt="Hairdressing"/>
                <h3>Hairdressing NC II</h3>
                <p>Learn haircutting, styling, and hair treatment techniques for various hair types and styles.</p>
            </div>
            <div class="course-card">
                <img src="images/japanese.jpg" alt="Japanese Language and Culture" />
                <h3>Japanese Language and Culture</h3>
                <p>Learn basic Japanese language skills and cultural understanding for effective communication.</p>
            </div>
            <div class="course-card">
                <img src="images/driving.jpg" alt="Driving" />
                <h3>Driving NC II</h3>
                <p>Gain skills in vehicle operation, road safety, and defensive driving techniques.</p>
            </div>
            <div class="course-card">
                <img src="images/tailoring.jpg" alt="Tailoring" />
                <h3>Tailoring NC II</h3>
                <p>Learn garment construction, fabric selection, and sewing techniques for custom clothing.</p>
            </div>
        </div>
    </section>
 
    <!-- Mission & Vision Section -->
    <section id="mission-vision" class="mission-vision">
        <div class="mv-container">
            <div class="mv-box mission">
                <h2>Our Mission</h2>
                <p>To provide inclusive, industry-relevant, and future-ready education through technology-driven learning that empowers individuals anytime, anywhere.</p>
            </div>
            <div class="mv-box vision">
                <h2>Our Vision</h2>
                <p>Benguet Technical School envisions a community where every learner gains equal access to skills, knowledge, and opportunities for lifelong success.</p>
            </div>
        </div>
    </section>
 
<!-- Login Modal -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Login</h2>
        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($_SESSION['login_error']); 
                      unset($_SESSION['login_error']); ?>
            </div>
        <?php endif; ?>
        <form id="loginForm" method="POST" action="php/unified_login.php">
            <input type="email" name="email" placeholder="Enter your email" required>
            <div class="password-wrapper">
                <input type="password" id="loginPassword" name="password" placeholder="Password" required />
                <i class="fa fa-eye toggle-password" data-target="loginPassword"></i>
            </div>
            <button type="submit">Login</button>
            <p class="forgot-password">
                <a href="#" id="forgotPassword">Forgot Password?</a>
            </p>
            <p class="create-account">
                Don't have an account? || <a href="#" id="openRegister">Sign up</a>
            </p>
        </form>
    </div>
</div>

<!-- Registration Modal -->
<div id="registerModal" class="modal">
    <div class="modal-content">
        <span class="close-register">&times;</span>
        <form id="registerForm" method="POST" action="">
            <input type="hidden" name="register" value="1">
            <div class="form-step form-step-active">
                <h3>Create Account</h3>
                
                <?php if (!empty($register_error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($register_error); ?>
                    </div>
                <?php endif; ?>
                
                <input type="text" name="firstname" placeholder="First Name *" required 
                       value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>" />
                <input type="text" name="middlename" placeholder="Middle Name" 
                       value="<?php echo isset($_POST['middlename']) ? htmlspecialchars($_POST['middlename']) : ''; ?>" />
                <input type="text" name="lastname" placeholder="Last Name *" required 
                       value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>" />
                <input type="text" name="suffix" placeholder="Suffix" 
                       value="<?php echo isset($_POST['suffix']) ? htmlspecialchars($_POST['suffix']) : ''; ?>" />
                <input type="email" id="regEmail" name="email" placeholder="Email * (@bts.gov.ph or @gmail.com)" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
                <input type="tel" name="contact" placeholder="Contact Number *" required 
                       value="<?php echo isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : ''; ?>" />
                <div class="password-wrapper">
                    <input type="password" id="regPassword" name="password" placeholder="Password *" required />
                    <i class="fa fa-eye toggle-password" data-target="regPassword"></i>
                </div>
                <div class="password-wrapper">
                    <input type="password" id="regConfirmPassword" name="confirmPassword" placeholder="Confirm Password *" required />
                    <i class="fa fa-eye toggle-password" data-target="regConfirmPassword"></i>
                </div>
                <small>Password must be at least 8 characters, contain one uppercase letter, one number, and one special character (*-_.).</small>
                <small>Email must be from @bts.gov.ph or @gmail.com domains.</small>
                <small>* Required fields</small>
                <button type="submit">Register</button>
                <p class="already-account">
                    Already have an account? || <a href="#" id="openLoginFromRegister">Log In</a>
                </p>
            </div>
        </form>
    </div>
</div>

    <!-- About Us Section -->
    <section id="about" class="about">
        <div class="about-box">
            <h2>About Us</h2>
            <div class="about-content" id="aboutContent">
                <p>
                    BENGUET TECHNICAL SCHOOL IS AN LGU-RUN TECHNICAL VOCATIONAL INSTITUTION UNDER THE PROVINCIAL GOVERNMENT OF BENGUET.
                    IT OFFERS VARIOUS PROGRAMS/SHORT COURSES ACCREDITED BY TESDA.
                </p>
                <p>
                    The Benguet Technical School traces its roots back to 1997, when then-President Fidel V. Ramos allocated ₱75 million from his Presidential Social Fund to establish Livelihood Training and Productivity Centers in marginalized regions across the country, including the Cordillera Administrative Region. Responding to this program, the Provincial Government of Benguet provided a 2,500-square-meter lot in Wangal, La Trinidad, for the establishment of the Benguet Livelihood Training and Productivity Center (BLTPC). This initiative was made possible through a tripartite agreement among the Provincial Government of Benguet, the Presidential Management Staff, and the HELPING Foundation, Inc., spearheaded by First Lady Amelita M. Ramos.
                </p>
                <p>
                    The HELPING Foundation managed the center until 2003, after which the Provincial Government assumed interim management. In 2006, this arrangement was formalized through Provincial Ordinance 06-112, which converted the BLTPC into an economic enterprise under the new name <b>Benguet Technical School (BTS)</b>. A decade later, in 2016, a provincial resolution allocated a new 1,646-square-meter site for BTS in Wangal. Then in 2019, another ordinance dissolved its status as an economic enterprise.
                </p>
                <p>
                    Today, Benguet Technical School stands as a lasting legacy of collaboration between the national government, the provincial government, and private partners. From its beginnings as a livelihood training center to its present role, BTS remains committed to providing opportunities that uplift the socio-economic conditions of communities in Benguet and the greater Cordillera region.
                </p>
            </div>
            <button id="seeMoreBtn" class="see-more-btn">See More...</button>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="footer">
        <div class="contact-icons">
            <div class="contact-item">
                <h2 class="contact-header">Contact Us</h2>
            </div>
            <div class="contact-item">
                <a href="mailto:pgobts@bts.gov.ph" title="Email">
                    <i class="fas fa-envelope"></i>
                </a>
                <span>pgobts@bts.gov.ph</span>
            </div>
            <div class="contact-item">
                <a href="https://www.facebook.com/BenguetTechSchool/" target="_blank" title="Facebook">
                    <i class="fab fa-facebook"></i>
                </a>
                <span>Benguet Technical School</span>
            </div>
            <div class="contact-item">
                <a href="tel:(074)4247060" title="Phone">
                    <i class="fas fa-phone"></i>
                </a>
                <span>(074) 424-7060</span>
            </div>
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <span>Motorpool, Wangal, La Trinidad, Benguet</span>
            </div>
        </div>
    </footer>

<script src="js/landingpage.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if a login error message exists in the DOM
    const loginError = document.querySelector('#loginModal .error-message');
    
    if (loginError) {
        // If an error message is found, open the login modal automatically
        document.getElementById('loginModal').style.display = 'flex';
    }
});
</script>
</body>
</html>