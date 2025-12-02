<?php
require_once 'php/SessionManager.php';
require_once 'php/DatabaseConnection.php';

SessionManager::startSession();

// Check if user is already logged in and redirect to the refactored module pages
if (SessionManager::isLoggedIn()) {
    $user = SessionManager::getCurrentUser();
    if ($user) {
        $role = $user['role'] ?? '';
        $mustChange = in_array($role, ['trainer', 'trainee']) && isset($user['password_changed_at']) && $user['password_changed_at'] === null;
        if ($mustChange) {
            header('Location: /bts/force_change_password.php');
            exit;
        }
        switch ($role) {
            case 'admin':
                header('Location: /bts/admin/index.php');
                break;
            case 'trainer':
                header('Location: /bts/trainer/index.php');
                break;
            case 'trainee':
                header('Location: /bts/trainee/index.php');
                break;
            case 'guest':
                header('Location: /bts/guest/index.php');
                break;
            default:
                // fall through to show landing page
                break;
        }
        exit;
    }
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

    // Validation
    $phoneRegex = '/^(09\d{9}|\+639\d{9})$/';

    if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
        $register_error = "All required fields must be filled!";
    } elseif ($password !== $confirmPassword) {
        $register_error = "Passwords do not match!";
    } elseif (!preg_match($phoneRegex, $contact)) {
        $register_error = "Please enter a valid Philippine phone number (e.g., 09xxxxxxxxx or +639xxxxxxxxx).";
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

                // CAPTCHA validation
                require_once __DIR__ . '/php/Captcha.php';
                Captcha::start();
                $captchaInput = trim($_POST['captcha_input'] ?? '');
                list($captchaOk, $captchaMsg) = Captcha::validate($captchaInput);
                if (!$captchaOk) {
                    $register_error = $captchaMsg;
                } else {
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
                        $role = $newUser['role'] ?? 'guest';
                        $mustChange = in_array($role, ['trainer', 'trainee']) && isset($newUser['password_changed_at']) && $newUser['password_changed_at'] === null;
                        if ($mustChange) {
                            header('Location: /bts/force_change_password.php');
                            exit();
                        }
                        switch ($role) {
                            case 'admin':
                                header('Location: /bts/admin/index.php');
                                break;
                            case 'trainer':
                                header('Location: /bts/trainer/index.php');
                                break;
                            case 'trainee':
                                header('Location: /bts/trainee/index.php');
                                break;
                            case 'guest':
                            default:
                                header('Location: /bts/guest/index.php');
                                break;
                        }
                        exit();
                    } else {
                        $register_error = "Registration successful but failed to login.";
                    }
                } else {
                    $register_error = "Registration failed!";
                }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <title>Benguet Technical School-eLMS</title>
    <!-- Preload critical resources -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">

    <!-- Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/v4-shims.min.css">

    <!-- Critical CSS -->
    <link rel="stylesheet" href="css/utils.css" />
    <link rel="stylesheet" href="css/landingpage-modern.css" />
    <link rel="icon" type="image/png" href="images/school.png">

    <!-- Performance optimization -->
    <meta name="description" content="Benguet Technical School - Transform your future with technical education. Industry-certified courses in agriculture, automotive, culinary arts, and more.">
    <meta name="keywords" content="technical education, Benguet Technical School, TESDA courses, vocational training, La Trinidad Benguet">
    <meta name="author" content="Benguet Technical School">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="Benguet Technical School - eLearning Management System">
    <meta property="og:description" content="Transform your future with technical education at Benguet Technical School">
    <meta property="og:image" content="images/school.png">
    <meta property="og:url" content="https://bts.edu.ph">
    <meta property="og:type" content="website">
</head>

<body id="top">
    <style>*{ -webkit-user-select: none; -ms-user-select: none; user-select: none; }</style>
    <!-- Skip navigation for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <!-- Modern Header -->
    <header class="site-header" role="banner">
        <div class="left-header">
            <img src="images/school.png" alt="Benguet Technical School Logo" class="logo" />
            <h1 class="school-name">Benguet Technical School</h1>
        </div>
        <nav class="nav-links" role="navigation" aria-label="Main navigation">
            <div id="openLogin" class="login-container btn btn-primary btn-ripple" data-modal-trigger="loginModal" role="button" tabindex="0" aria-label="Open login modal">
                <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                <span>Log In</span>
            </div>
        </nav>
    </header>

    <!-- Modern Hero Section with Enhanced CTAs -->
    <section class="hero" id="main-content">
        <div class="slider">
            <div class="slide active" style="background-image: url('images/bts3.jpg');" loading="lazy"></div>
            <div class="slide" style="background-image: url('images/bts1.jpg');" loading="lazy"></div>
            <div class="slide" style="background-image: url('images/bts2.jpg');" loading="lazy"></div>
            <div class="slide" style="background-image: url('images/bts4.jpg');" loading="lazy"></div>
            <div class="slide" style="background-image: url('images/bts5.jpg');" loading="lazy"></div>
        </div>
        <div class="hero-content">
            <h1 class="hero-title">Transform Your Future with Technical Education</h1>
            <p class="hero-subtitle">Join thousands of learners at Benguet Technical School and master in-demand skills through our comprehensive online learning platform. Learn anytime, anywhere with industry-certified courses.</p>
            <div class="hero-cta">
                <button id="openRegister" class="btn btn-primary btn-lg btn-ripple" data-modal-trigger="registerModal" data-track="hero_cta_register">
                    <i class="fas fa-rocket"></i>
                    Start Learning Today
                </button>
                <button id="browseCourses" class="btn btn-outline btn-lg btn-ripple" onclick="document.getElementById('courses').scrollIntoView({behavior: 'smooth'})" data-track="hero_cta_browse">
                    <i class="fas fa-search"></i>
                    Browse Courses
                </button>
            </div>
        </div>
    </section>

    <!-- Modern Courses Section -->
    <section id="courses" class="courses">
        <div class="section-header">
            <h2 class="section-title">Our Technical Courses</h2>
            <p class="section-subtitle">Choose from our industry-certified programs designed to equip you with in-demand technical skills for today's job market.</p>
        </div>
        <div id="course-grid" class="course-grid">
            <div class="course-card">
                <img src="images/agriculture.jpg" alt="Agricultural Crops Production" loading="lazy" />
                <div class="course-card-content">
                    <h3 class="course-card-title">Agricultural Crops Production NC II</h3>
                    <p class="course-card-description">Master crop production, farm tools usage, and sustainable agriculture techniques for modern farming.</p>
                    <div class="course-card-footer">
                        <span class="course-duration"><i class="fas fa-clock"></i> 300 Hours</span>
                        <span class="course-level">NC II</span>
                    </div>
                </div>
            </div>
            <div class="course-card">
                <img src="images/automotive.jpg" alt="Automotive Servicing" loading="lazy" />
                <div class="course-card-content">
                    <h3 class="course-card-title">Automotive Servicing NC II</h3>
                    <p class="course-card-description">Master vehicle maintenance, diagnostics, and repair techniques for various automotive systems.</p>
                    <div class="course-card-footer">
                        <span class="course-duration"><i class="fas fa-clock"></i> 400 Hours</span>
                        <span class="course-level">NC II</span>
                    </div>
                </div>
            </div>
            <div class="course-card">
                <img src="images/breadandpastry.jpg" alt="Bread and Pastry Production" loading="lazy" />
                <div class="course-card-content">
                    <h3 class="course-card-title">Bread and Pastry Production NC II</h3>
                    <p class="course-card-description">Learn professional baking techniques, dough preparation, and pastry decoration for commercial production.</p>
                    <div class="course-card-footer">
                        <span class="course-duration"><i class="fas fa-clock"></i> 250 Hours</span>
                        <span class="course-level">NC II</span>
                    </div>
                </div>
            </div>
            <div class="course-card">
                <img src="images/dressmaking.jpg" alt="Dressmaking" loading="lazy" />
                <div class="course-card-content">
                    <h3 class="course-card-title">Dressmaking NC II</h3>
                    <p class="course-card-description">Acquire professional skills in garment construction, fabric selection, and sewing techniques for custom clothing.</p>
                    <div class="course-card-footer">
                        <span class="course-duration"><i class="fas fa-clock"></i> 350 Hours</span>
                        <span class="course-level">NC II</span>
                    </div>
                </div>
            </div>
            <div class="course-card">
                <img src="images/hairdressing.jpg" alt="Hairdressing" loading="lazy" />
                <div class="course-card-content">
                    <h3 class="course-card-title">Hairdressing NC II</h3>
                    <p class="course-card-description">Master haircutting, styling, and hair treatment techniques for various hair types and professional salon services.</p>
                    <div class="course-card-footer">
                        <span class="course-duration"><i class="fas fa-clock"></i> 280 Hours</span>
                        <span class="course-level">NC II</span>
                    </div>
                </div>
            </div>
            <div class="course-card">
                <img src="images/japanese.jpg" alt="Japanese Language and Culture" loading="lazy" />
                <div class="course-card-content">
                    <h3 class="course-card-title">Japanese Language and Culture</h3>
                    <p class="course-card-description">Learn Japanese language skills and cultural understanding for effective communication in professional settings.</p>
                    <div class="course-card-footer">
                        <span class="course-duration"><i class="fas fa-clock"></i> 200 Hours</span>
                        <span class="course-level">Language</span>
                    </div>
                </div>
            </div>
            <div class="course-card">
                <img src="images/driving.jpg" alt="Driving" loading="lazy" />
                <div class="course-card-content">
                    <h3 class="course-card-title">Driving NC II</h3>
                    <p class="course-card-description">Gain comprehensive skills in vehicle operation, road safety, and defensive driving techniques for professional driving.</p>
                    <div class="course-card-footer">
                        <span class="course-duration"><i class="fas fa-clock"></i> 150 Hours</span>
                        <span class="course-level">NC II</span>
                    </div>
                </div>
            </div>
            <div class="course-card">
                <img src="images/tailoring.jpg" alt="Tailoring" loading="lazy" />
                <div class="course-card-content">
                    <h3 class="course-card-title">Tailoring NC II</h3>
                    <p class="course-card-description">Master advanced garment construction, fabric selection, and professional sewing techniques for custom tailoring.</p>
                    <div class="course-card-footer">
                        <span class="course-duration"><i class="fas fa-clock"></i> 320 Hours</span>
                        <span class="course-level">NC II</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision Section -->
    <section id="mission-vision" class="mission-vision section-padding">
        <div class="container">
            <div class="section-header text-center">
                <h2 class="section-title">Our Mission & Vision</h2>
                <p class="section-subtitle">Empowering learners through inclusive, technology-driven education</p>
            </div>
            <div class="mv-grid">
                <div class="mv-card mission">
                    <div class="mv-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3 class="mv-title">Our Mission</h3>
                    <p class="mv-description">To provide inclusive, industry-relevant, and future-ready education through technology-driven learning that empowers individuals anytime, anywhere.</p>
                </div>
                <div class="mv-card vision">
                    <div class="mv-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="mv-title">Our Vision</h3>
                    <p class="mv-description">Benguet Technical School envisions a community where every learner gains equal access to skills, knowledge, and opportunities for lifelong success.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content modal-content-modern">
            <div class="modal-header">
                <h2 class="modal-title">Welcome Back</h2>
                <button class="modal-close" data-modal="loginModal">&times;</button>
            </div>
            <div class="modal-body">
                <?php if (isset($_SESSION['login_error'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['login_error']);
                        unset($_SESSION['login_error']); ?>
                    </div>
                <?php endif; ?>
                <form id="loginForm" method="POST" action="php/unified_login.php" class="modern-form">
                    <div class="form-group">
                        <label for="loginEmail" class="form-label">Email Address</label>
                        <input type="email" id="loginEmail" name="email" class="form-input" placeholder="Enter your email" required>
                    </div>
                    <div class="form-group">
                        <label for="loginPassword" class="form-label">Password</label>
                        <div class="password-input-group">
                            <input type="password" id="loginPassword" name="password" class="form-input" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle" data-target="loginPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-full btn-ripple">
                            <i class="fas fa-sign-in-alt"></i>
                            Sign In
                        </button>
                    </div>
                    <div class="form-links">
                        <a href="#" id="forgotPassword" class="form-link">Forgot Password?</a>
                        <span class="form-link-separator">•</span>
                        <a href="#" id="openRegisterLink" class="form-link" data-modal-trigger="registerModal" role="button">Create Account</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Registration Modal -->
    <div id="registerModal" class="modal">
        <div class="modal-content modal-content-modern">
            <div class="modal-header">
                <h2 class="modal-title">Create Your Account</h2>
                <button class="modal-close" data-modal="registerModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="registerForm" method="POST" action="" class="modern-form">
                    <input type="hidden" name="register" value="1">

                    <?php if (!empty($register_error)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($register_error); ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="regFirstname" class="form-label">First Name *</label>
                            <input type="text" id="regFirstname" name="firstname" class="form-input" placeholder="Enter your first name" required
                                value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>"
                                autocomplete="given-name">
                        </div>
                        <div class="form-group">
                            <label for="regMiddlename" class="form-label">Middle Name</label>
                            <input type="text" id="regMiddlename" name="middlename" class="form-input" placeholder="Enter your middle name"
                                value="<?php echo isset($_POST['middlename']) ? htmlspecialchars($_POST['middlename']) : ''; ?>"
                                autocomplete="additional-name">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="regLastname" class="form-label">Last Name *</label>
                            <input type="text" id="regLastname" name="lastname" class="form-input" placeholder="Enter your last name" required
                                value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>"
                                autocomplete="family-name">
                        </div>
                        <div class="form-group">
                            <label for="regSuffix" class="form-label">Suffix</label>
                            <input type="text" id="regSuffix" name="suffix" class="form-input" placeholder="e.g., Jr., Sr., III"
                                value="<?php echo isset($_POST['suffix']) ? htmlspecialchars($_POST['suffix']) : ''; ?>"
                                autocomplete="honorific-suffix">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="regEmail" class="form-label">Email Address *</label>
                        <input type="email" id="regEmail" name="email" class="form-input" placeholder="Enter your email (@bts.gov.ph or @gmail.com)" required
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            pattern="[a-z0-9._%+-]+@(?:bts\.gov\.ph|gmail\.com)$"
                            title="Please enter a valid email address from @bts.gov.ph or @gmail.com"
                            autocomplete="email">
                        <small class="form-help">Email must be from @bts.gov.ph or @gmail.com domains.</small>
                        <div class="form-validation" id="emailValidation"></div>
                    </div>

                    <div class="form-group">
                        <label for="regContact" class="form-label">Contact Number *</label>
                        <input type="tel" id="regContact" name="contact" class="form-input" placeholder="Enter your contact number" required
                            value="<?php echo isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : ''; ?>"
                            pattern="^(09\d{9}|\+639\d{9})$"
                            title="Please enter a valid Philippine phone number (e.g., 09123456789 or +639123456789)"
                            autocomplete="tel">
                        <small class="form-help">Format: 09xxxxxxxxx or +639xxxxxxxxx</small>
                        <div class="form-validation" id="contactValidation"></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="regPassword" class="form-label">Password *</label>
                            <div class="password-input-group">
                                <input type="password" id="regPassword" name="password" class="form-input" placeholder="Create a password" required
                                    minlength="8"
                                    pattern="^(?=.*[A-Z])(?=.*\d)(?=.*[*\-_.])[A-Za-z\d*\-_.]{8,}$"
                                    title="Password must be at least 8 characters with one uppercase letter, one number, and one special character (*-_.)"
                                    autocomplete="new-password">
                                <button type="button" class="password-toggle" data-target="regPassword" aria-label="Toggle password visibility">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="form-help">At least 8 characters, one uppercase, one number, one special character (*-_.).</small>
                            <div class="password-strength" id="passwordStrength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <span class="strength-text" id="strengthText"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="regConfirmPassword" class="form-label">Confirm Password *</label>
                            <div class="password-input-group">
                                <input type="password" id="regConfirmPassword" name="confirmPassword" class="form-input" placeholder="Confirm your password" required>
                                <button type="button" class="password-toggle" data-target="regConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-validation" id="passwordMatch"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">CAPTCHA *</label>
                        <div class="captcha-row" style="display:flex; gap:12px; align-items:center;">
                            <img id="captchaImage" src="php/captcha_image.php" alt="CAPTCHA code image" width="300" height="80" aria-label="CAPTCHA image showing security code"/>
                            <div class="captcha-controls" style="display:flex; flex-direction:column; gap:8px;">
                                <button type="button" id="refreshCaptcha" class="btn btn-outline" aria-label="Refresh CAPTCHA" onclick="(function(){ var img=document.getElementById('captchaImage'); if(img){ img.src='php/captcha_image.php?t=' + Date.now(); } var inp=document.getElementById('captchaInput'); if(inp){ inp.value=''; } var v=document.getElementById('captchaValidation'); if(v){ v.textContent=''; v.className='form-validation'; } })()">Refresh</button>
                                
                            </div>
                        </div>
                        <input type="text" id="captchaInput" name="captcha_input" class="form-input" placeholder="Enter CAPTCHA code" required autocomplete="off" aria-describedby="captchaHelp" pattern="^[A-Za-z0-9]{6,8}$">
                        <small id="captchaHelp" class="form-help">Enter the characters shown (A–Z, a–z, 0–9). Case sensitive.</small>
                        <div class="form-validation" id="captchaValidation"></div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-full btn-ripple" id="registerSubmit"
                            aria-label="Create your account" role="button" tabindex="0">
                            <i class="fas fa-user-plus" aria-hidden="true"></i>
                            Create Account
                        </button>
                    </div>

                    <div class="form-links">
                        <span class="form-text">Already have an account?</span>
                        <a href="#" id="openLoginFromRegister" class="form-link">Sign In</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="modal">
        <div class="modal-content modal-content-modern">
            <div class="modal-header">
                <h2 class="modal-title">Reset Your Password</h2>
                <button class="modal-close" data-modal="forgotPasswordModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="forgotPasswordForm" method="POST" action="php/simple_password_reset.php" class="modern-form">
                    <input type="hidden" name="forgot_password" value="1">

                    <div class="form-description">
                        <p>Enter your email address and new password to reset your account.</p>
                    </div>

                    <div class="form-group">
                        <label for="forgotEmail" class="form-label">Email Address</label>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <input type="email" id="forgotEmail" name="email" class="form-input" required style="flex:1;">
                            <button type="button" id="sendGuestResetOtp" class="btn btn-outline" style="white-space:nowrap;">Send OTP</button>
                        </div>
                        <div class="form-validation" id="forgotEmailValidation"></div>
                        <div class="form-validation" id="forgotOtpStatus"></div>
                    </div>

                    <div class="form-group">
                        <label for="forgotOtpCode" class="form-label">OTP Code</label>
                        <input type="text" id="forgotOtpCode" name="otp_code" class="form-input" placeholder="Enter 6-digit code" inputmode="numeric" pattern="^\d{6}$" required>
                        <div class="form-validation" id="forgotOtpValidation"></div>
                    </div>

                    <div class="form-group">
                        <label for="forgotNewPassword" class="form-label">New Password</label>
                        <input type="password" id="forgotNewPassword" name="new_password" class="form-input" required>
                        <div class="password-strength" id="forgotPasswordStrength">
                            <div class="password-strength-bar"></div>
                            <span class="password-strength-text">Password strength</span>
                        </div>
                        <div class="form-validation" id="forgotNewPasswordValidation"></div>
                    </div>

                    <div class="form-group">
                        <label for="forgotConfirmPassword" class="form-label">Confirm New Password</label>
                        <input type="password" id="forgotConfirmPassword" name="confirm_password" class="form-input" required>
                        <div class="form-validation" id="forgotConfirmPasswordValidation"></div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-full btn-ripple" id="forgotPasswordSubmit">
                            <i class="fas fa-key"></i>
                            Reset Password
                        </button>
                    </div>

                    <div class="form-links">
                        <span class="form-text">Remember your password?</span>
                        <a href="#" id="backToLogin" class="form-link">Back to Sign In</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- About Us Section -->
    <section id="about" class="about section-padding bg-light">
        <div class="container">
            <div class="about-content">
                <div class="section-header text-center">
                    <h2 class="section-title">About Benguet Technical School</h2>
                    <p class="section-subtitle">A legacy of excellence in technical education since 1997</p>
                </div>
                <div class="about-grid">
                    <div class="about-text">
                        <div class="about-highlight">
                            <h3 class="about-subtitle">LGU-Run Technical Vocational Institution</h3>
                            <p class="about-description">
                                Benguet Technical School is an LGU-run technical vocational institution under the Provincial Government of Benguet,
                                offering various programs and short courses accredited by TESDA.
                            </p>
                        </div>
                        <div class="about-timeline">
                            <div class="timeline-item">
                                <div class="timeline-year">1997</div>
                                <div class="timeline-content">
                                    <h4>Establishment</h4>
                                    <p>President Fidel V. Ramos allocated ₱75 million to establish Livelihood Training and Productivity Centers,
                                        including the Benguet Livelihood Training and Productivity Center (BLTPC) in Wangal, La Trinidad.</p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-year">2006</div>
                                <div class="timeline-content">
                                    <h4>Official Recognition</h4>
                                    <p>Provincial Ordinance 06-112 converted BLTPC into Benguet Technical School (BTS),
                                        establishing it as an economic enterprise under the provincial government.</p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-year">Today</div>
                                <div class="timeline-content">
                                    <h4>Continuing Legacy</h4>
                                    <p>BTS stands as a lasting legacy of collaboration between national government,
                                        provincial government, and private partners, committed to uplifting socio-economic conditions
                                        in Benguet and the Cordillera region.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Site Info (moved out of footer) -->
    <section id="site-info" class="prefooter">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-brand">
                        <h3 class="footer-title">Benguet Technical School</h3>
                        <p class="footer-description">Empowering communities through quality technical education and training.</p>
                    </div>
                    <div class="footer-contact">
                        <h4 class="footer-section-title">Contact Information</h4>
                        <div class="contact-list">
                            <div class="contact-item">
                                <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                                <div class="contact-details"><span class="contact-label">Email</span><a href="mailto:pgobts@bts.gov.ph" class="contact-link">pgobts@bts.gov.ph</a></div>
                            </div>
                            <div class="contact-item">
                                <div class="contact-icon"><i class="fas fa-phone"></i></div>
                                <div class="contact-details"><span class="contact-label">Phone</span><a href="tel:(074)4247060" class="contact-link">(074) 424-7060</a></div>
                            </div>
                            <div class="contact-item">
                                <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                                <div class="contact-details"><span class="contact-label">Address</span><span class="contact-text">Motorpool, Wangal, La Trinidad, Benguet</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="footer-section">
                    <div class="footer-social">
                        <h4 class="footer-section-title">Follow Us</h4>
                        <div class="social-links">
                            <a href="https://www.facebook.com/BenguetTechSchool/" target="_blank" class="social-link" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="mailto:pgobts@bts.gov.ph" class="social-link" title="Email"><i class="fas fa-envelope"></i></a>
                            <a href="tel:(074)4247060" class="social-link" title="Phone"><i class="fas fa-phone"></i></a>
                        </div>
                    </div>
                    <div class="footer-links">
                        <h4 class="footer-section-title">Quick Links</h4>
                        <div class="footer-nav" role="navigation" aria-label="Footer navigation">
                            <a href="#top" class="footer-nav-link" aria-label="Navigate to top of page">Home</a>
                            <a href="#courses" class="footer-nav-link" aria-label="Navigate to courses section">Courses</a>
                            <a href="#mission-vision" class="footer-nav-link" aria-label="Navigate to mission and vision section">Mission & Vision</a>
                            <a href="#about" class="footer-nav-link" aria-label="Navigate to about us section">About Us</a>
                            <a href="#contact" class="footer-nav-link" aria-label="Navigate to contact section">Contact</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="footer bg-dark">
        <div class="container">
            
            <div class="footer-bottom">
                <div class="footer-divider"></div>
                <div class="footer-copyright">
                    <p>&copy; 2024 Benguet Technical School. All rights reserved.</p>
                    <p>Under the Provincial Government of Benguet</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modern JavaScript with async loading -->
    <script src="js/landingpage.js" defer></script>

    <!-- Consolidated analytics and modal functionality -->
    <script>
        (function(){
            function d(e){ e.preventDefault(); return false; }
            ['copy','cut','paste','selectstart','dragstart'].forEach(function(ev){ document.addEventListener(ev, d, {capture:true}); });
        })();
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Landing page scripts initializing...');

            // Basic analytics tracking with error handling
            function trackEvent(eventName, properties) {
                try {
                    if (typeof gtag !== 'undefined' && gtag) {
                        gtag('event', eventName, properties);
                    }
                    console.log('Event tracked:', eventName, properties);
                } catch (e) {
                    console.warn('Analytics tracking error:', e);
                }
            }

            // Modern modal functionality with error handling
            try {
                const modalTriggers = document.querySelectorAll('[data-modal-trigger]');
                const modalCloses = document.querySelectorAll('.modal-close');
                const modals = document.querySelectorAll('.modal');

                // Open modal functions
                function openModal(modalId) {
                    try {
                        const modal = document.getElementById(modalId);
                        if (modal) {
                            modal.classList.add('active');
                            document.body.style.overflow = 'hidden';

                            // Focus management for accessibility
                            const firstFocusable = modal.querySelector('input, button, a, [tabindex]:not([tabindex="-1"])');
                            if (firstFocusable) {
                                setTimeout(() => firstFocusable.focus(), 100);
                            }
                        }
                    } catch (error) {
                        console.error('Error opening modal:', error);
                    }
                }

                // Close modal functions
                function closeModal(modalId) {
                    try {
                        const modal = document.getElementById(modalId);
                        if (modal) {
                            modal.classList.remove('active');
                            document.body.style.overflow = '';

                            // Return focus to trigger element
                            const trigger = document.querySelector(`[data-modal-trigger="${modalId}"]`);
                            if (trigger) {
                                trigger.focus();
                            }
                        }
                    } catch (error) {
                        console.error('Error closing modal:', error);
                    }
                }

                // Modal trigger event listeners
                if (modalTriggers && modalTriggers.length > 0) {
                    modalTriggers.forEach(trigger => {
                        if (trigger) {
                            trigger.addEventListener('click', (e) => {
                                e.preventDefault();
                                const modalId = trigger.getAttribute('data-modal-trigger');
                                if (modalId) {
                                    openModal(modalId);
                                }
                            });

                            // Keyboard accessibility
                            trigger.addEventListener('keydown', (e) => {
                                if (e.key === 'Enter' || e.key === ' ') {
                                    e.preventDefault();
                                    const modalId = trigger.getAttribute('data-modal-trigger');
                                    if (modalId) {
                                        openModal(modalId);
                                    }
                                }
                            });
                        }
                    });
                }

                // Modal close event listeners
                if (modalCloses && modalCloses.length > 0) {
                    modalCloses.forEach(closeBtn => {
                        if (closeBtn) {
                            closeBtn.addEventListener('click', (e) => {
                                e.preventDefault();
                                const modalId = closeBtn.getAttribute('data-modal');
                                if (modalId) {
                                    closeModal(modalId);
                                }
                            });
                        }
                    });
                }

                // Close modal when clicking outside
                if (modals && modals.length > 0) {
                    modals.forEach(modal => {
                        if (modal) {
                            modal.addEventListener('click', (e) => {
                                if (e.target === modal) {
                                    const modalId = modal.id;
                                    closeModal(modalId);
                                }
                            });

                            // Keyboard navigation for modals
                            modal.addEventListener('keydown', (e) => {
                                if (e.key === 'Escape') {
                                    e.preventDefault();
                                    const modalId = modal.id;
                                    closeModal(modalId);
                                }
                            });
                        }
                    });
                }

                // Specific modal triggers for landing page
                const openLoginBtn = document.getElementById('openLogin');
                const openRegisterBtn = document.getElementById('openRegister');
                const openLoginFromRegisterBtn = document.getElementById('openLoginFromRegister');

                if (openLoginBtn) {
                    openLoginBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        openModal('loginModal');
                    });
                }

                if (openRegisterBtn) {
                    openRegisterBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        openModal('registerModal');
                    });
                }

                if (openLoginFromRegisterBtn) {
                    openLoginFromRegisterBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        closeModal('registerModal');
                        setTimeout(() => openModal('loginModal'), 150);
                    });
                }
            } catch (error) {
                console.error('Error setting up modal functionality:', error);
            }

            // Track registration attempts with error handling
            try {
                const registerForm = document.getElementById('registerForm');
                if (registerForm) {
                    registerForm.addEventListener('submit', function() {
                        try {
                            trackEvent('registration_attempt', {
                                'page': 'landing_page',
                                'timestamp': new Date().toISOString()
                            });
                        } catch (e) {
                            console.warn('Error tracking registration:', e);
                        }
                    });
                }
            } catch (e) {
                console.warn('Error setting up registration tracking:', e);
            }

            // Track login attempts with error handling
            try {
                const loginForm = document.getElementById('loginForm');
                if (loginForm) {
                    loginForm.addEventListener('submit', function() {
                        try {
                            trackEvent('login_attempt', {
                                'page': 'landing_page',
                                'timestamp': new Date().toISOString()
                            });
                        } catch (e) {
                            console.warn('Error tracking login:', e);
                        }
                    });
                }
            } catch (e) {
                console.warn('Error setting up login tracking:', e);
            }

            // Track CTA clicks with error handling
            try {
                const ctaButtons = document.querySelectorAll('[data-track]');
                if (ctaButtons && ctaButtons.length > 0) {
                    ctaButtons.forEach(button => {
                        if (button) {
                            button.addEventListener('click', function() {
                                try {
                                    const eventName = this.getAttribute('data-track');
                                    if (eventName) {
                                        trackEvent(eventName, {
                                            'element': this.tagName,
                                            'text': this.textContent ? this.textContent.trim() : '',
                                            'timestamp': new Date().toISOString()
                                        });
                                    }
                                } catch (e) {
                                    console.warn('Error tracking CTA click:', e);
                                }
                            });
                        }
                    });
                }
            } catch (e) {
                console.warn('Error setting up CTA tracking:', e);
            }

            console.log('Landing page functionality initialized successfully');
        });

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
                
                // Return focus to trigger element
                const trigger = document.querySelector(`[data-modal-trigger="${modalId}"]`);
                if (trigger) {
                    trigger.focus();
                }
            }
        }

        // Initialize modal event listeners when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            const modalTriggers = document.querySelectorAll('[data-modal-trigger]');
            const modalCloses = document.querySelectorAll('.modal-close');
            const modals = document.querySelectorAll('.modal');

            if (modalTriggers && modalTriggers.length > 0) {
                modalTriggers.forEach(trigger => {
                    if (trigger) {
                        trigger.addEventListener('click', (e) => {
                            e.preventDefault();
                            const modalId = trigger.getAttribute('data-modal-trigger');
                            if (modalId) {
                                openModal(modalId);
                            }
                        });

                        // Keyboard accessibility
                        trigger.addEventListener('keydown', (e) => {
                            if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
                                const modalId = trigger.getAttribute('data-modal-trigger');
                                if (modalId) {
                                    openModal(modalId);
                                }
                            }
                        });
                    }
                });
            }

            if (modalCloses && modalCloses.length > 0) {
                modalCloses.forEach(closeBtn => {
                    if (closeBtn) {
                        closeBtn.addEventListener('click', (e) => {
                            e.preventDefault();
                            const modalId = closeBtn.getAttribute('data-modal');
                            if (modalId) {
                                closeModal(modalId);
                            }
                        });
                    }
                });
            }

            if (modals && modals.length > 0) {
                modals.forEach(modal => {
                    if (modal) {
                        modal.addEventListener('click', (e) => {
                            if (e.target === modal) {
                                const modalId = modal.id;
                                closeModal(modalId);
                            }
                        });

                        // Keyboard navigation for modals
                        modal.addEventListener('keydown', (e) => {
                            if (e.key === 'Escape') {
                                e.preventDefault();
                                const modalId = modal.id;
                                closeModal(modalId);
                            }
                        });
                    }
                });
            }
        });

        // Specific modal triggers for landing page (with proper error handling)
        document.addEventListener('DOMContentLoaded', function() {
            const openLogin = document.getElementById('openLogin');
            const openRegister = document.getElementById('openRegister');
            const openLoginFromRegister = document.getElementById('openLoginFromRegister');
            const openRegisterFromLogin = document.getElementById('openRegisterFromLogin');

            if (openLogin) {
                openLogin.addEventListener('click', (e) => {
                    e.preventDefault();
                    openModal('loginModal');
                });
            }

            if (openRegister) {
                openRegister.addEventListener('click', (e) => {
                    e.preventDefault();
                    openModal('registerModal');
                });
            }

            if (openLoginFromRegister) {
                openLoginFromRegister.addEventListener('click', (e) => {
                    e.preventDefault();
                    closeModal('registerModal');
                    setTimeout(() => openModal('loginModal'), 300);
                });
            }

            if (openRegisterFromLogin) {
                openRegisterFromLogin.addEventListener('click', (e) => {
                    e.preventDefault();
                    closeModal('loginModal');
                    setTimeout(() => openModal('registerModal'), 300);
                });
            }
        });

        // Password toggle functionality and error checking
        document.addEventListener('DOMContentLoaded', function() {
            const passwordToggles = document.querySelectorAll('.password-toggle');
            passwordToggles.forEach(toggle => {
                toggle.addEventListener('click', () => {
                    const targetId = toggle.getAttribute('data-target');
                    const targetInput = document.getElementById(targetId);
                    const icon = toggle.querySelector('i');

                    if (targetInput && targetInput.type === 'password') {
                        targetInput.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else if (targetInput) {
                        targetInput.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });

            // Check if a login error message exists and open the login modal
            const loginError = document.querySelector('#loginModal .alert-error');
            if (loginError && loginError.textContent.trim() !== '') {
                openModal('loginModal');
            }

            // Check if a registration error message exists and open the register modal
            const registerError = document.querySelector('#registerModal .alert-error');
            if (registerError && registerError.textContent.trim() !== '') {
                openModal('registerModal');
            }
        });

        // Smooth scrolling for navigation links
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const href = this.getAttribute('href');
                    if (href && href !== '#' && href.length > 1) {
                        const target = document.querySelector(href);
                        if (target) {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    }
                });
            });

            // Header scroll effect
            window.addEventListener('scroll', () => {
                const header = document.querySelector('.header');
                if (header) {
                    if (window.scrollY > 100) {
                        header.classList.add('scrolled');
                    } else {
                        header.classList.remove('scrolled');
                    }
                }
            });

            // Register form functionality
            const registerForm = document.getElementById('registerForm');
            if (registerForm) {
                const passwordInput = document.getElementById('regPassword');
                const confirmPasswordInput = document.getElementById('regConfirmPassword');
                const emailInput = document.getElementById('regEmail');
                const contactInput = document.getElementById('regContact');
                const submitButton = registerForm.querySelector('button[type="submit"]');

                // Password strength checker
                function checkPasswordStrength(password) {
                    let strength = 0;
                    const patterns = {
                        length: password.length >= 8,
                        uppercase: /[A-Z]/.test(password),
                        lowercase: /[a-z]/.test(password),
                        number: /\d/.test(password),
                        special: /[*\-_.]/.test(password)
                    };

                    Object.values(patterns).forEach(valid => {
                        if (valid) strength++;
                    });

                    return strength;
                }

                function updatePasswordStrength() {
                const password = passwordInput.value;
                const strengthFill = document.getElementById('strengthFill');
                const strengthText = document.getElementById('strengthText');
                const strength = checkPasswordStrength(password);

                if (password.length === 0) {
                    strengthFill.style.width = '0%';
                    strengthText.textContent = '';
                    strengthFill.className = 'strength-fill';
                    strengthText.className = 'strength-text';
                    return;
                }

                const strengthLevels = [{
                        class: 'weak',
                        text: 'Weak'
                    },
                    {
                        class: 'fair',
                        text: 'Fair'
                    },
                    {
                        class: 'good',
                        text: 'Good'
                    },
                    {
                        class: 'strong',
                        text: 'Strong'
                    },
                    {
                        class: 'strong',
                        text: 'Very Strong'
                    }
                ];

                const level = strengthLevels[Math.min(strength - 1, 4)];
                strengthFill.className = `strength-fill ${level.class}`;
                strengthText.className = `strength-text ${level.class}`;
                strengthText.textContent = level.text;
            }

            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                const passwordMatch = document.getElementById('passwordMatch');

                if (confirmPassword.length === 0) {
                    passwordMatch.textContent = '';
                    passwordMatch.className = 'form-validation';
                    return;
                }

                if (password === confirmPassword) {
                    passwordMatch.textContent = '✓ Passwords match';
                    passwordMatch.className = 'form-validation success';
                } else {
                    passwordMatch.textContent = '✗ Passwords do not match';
                    passwordMatch.className = 'form-validation error';
                }
            }

            function validateEmail() {
                const email = emailInput.value;
                const emailValidation = document.getElementById('emailValidation');
                const emailPattern = /^[a-z0-9._%+-]+@(?:bts\.gov\.ph|gmail\.com)$/;

                if (email.length === 0) {
                    emailValidation.textContent = '';
                    emailValidation.className = 'form-validation';
                    return;
                }

                if (emailPattern.test(email)) {
                    emailValidation.textContent = '✓ Valid email format';
                    emailValidation.className = 'form-validation success';
                } else {
                    emailValidation.textContent = '✗ Email must be from @bts.gov.ph or @gmail.com';
                    emailValidation.className = 'form-validation error';
                }
            }

            function validateContact() {
                const contact = contactInput.value;
                const contactValidation = document.getElementById('contactValidation');
                const contactPattern = /^(09\d{9}|\+639\d{9})$/;

                if (contact.length === 0) {
                    contactValidation.textContent = '';
                    contactValidation.className = 'form-validation';
                    return;
                }

                if (contactPattern.test(contact)) {
                    contactValidation.textContent = '✓ Valid contact number';
                    contactValidation.className = 'form-validation success';
                } else {
                    contactValidation.textContent = '✗ Format: 09xxxxxxxxx or +639xxxxxxxxx';
                    contactValidation.className = 'form-validation error';
                }
            }

            // Add event listeners
            passwordInput.addEventListener('input', updatePasswordStrength);
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            emailInput.addEventListener('input', validateEmail);
            contactInput.addEventListener('input', validateContact);

            // Form submission with loading state
            registerForm.addEventListener('submit', function(e) {
                const requiredFields = registerForm.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('error');
                    } else {
                        field.classList.remove('error');
                    }
                });

                // Check password match
                if (passwordInput.value !== confirmPasswordInput.value) {
                    isValid = false;
                    confirmPasswordInput.classList.add('error');
                }

                if (!isValid) {
                    e.preventDefault();
                    return;
                }

                // Show loading state
                submitButton.classList.add('btn-loading');
                submitButton.disabled = true;
                
                // Re-enable button after 3 seconds (fallback)
                setTimeout(() => {
                    submitButton.classList.remove('btn-loading');
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-user-plus" aria-hidden="true"></i> Create Account';
                }, 3000);
            });
        }
        }); // Close DOMContentLoaded
    </script>
</body>

</html>
