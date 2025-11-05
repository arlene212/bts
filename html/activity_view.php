<?php
require_once '../php/SessionManager.php';
SessionManager::startSession();
SessionManager::requireRole('trainee');

require_once '../php/DatabaseConnection.php';

$user = SessionManager::getCurrentUser();
$activity_id = $_GET['id'] ?? null;

if (!$activity_id) {
    header("Location: trainee.php");
    exit;
}

$database = new DatabaseConnection();
$pdo = $database->getConnection();

$activity = null;
$submission = null;
$course = null;

try {
    // Fetch activity details and verify enrollment in one go
    $stmt = $pdo->prepare("
        SELECT 
            ta.*, 
            ct.course_code,
            c.course_name
        FROM topic_activities ta
        JOIN course_topics ct ON ta.topic_id = ct.id
        JOIN courses c ON ct.course_code = c.course_code
        JOIN enrollments e ON c.course_code = e.course_code
        WHERE ta.id = ? AND e.trainee_id = ? AND e.status = 'approved'
    ");
    $stmt->execute([$activity_id, $user['user_id']]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$activity) {
        die("Activity not found or you do not have access.");
    }

    // Fetch submission details if they exist
    $subStmt = $pdo->prepare("
        SELECT * FROM activity_submissions 
        WHERE activity_id = ? AND trainee_id = ? 
        ORDER BY submitted_at DESC
    ");
    $subStmt->execute([$activity_id, $user['user_id']]);
    $submission = $subStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching activity details: " . $e->getMessage());
    die("An error occurred while loading the activity.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($activity['activity_title']); ?> - BTS eLMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../images/school.png">
    <link rel="stylesheet" href="../css/trainee.css">
    <style>
        /* Additional styles for the dedicated activity page */
        body {
            background-color: #f4f7fa;
        }
        .activity-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .activity-header {
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .activity-header h1 {
            font-size: 2em;
            margin: 0;
            color: #333;
        }
        .activity-header .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }
        .activity-header .back-link:hover {
            text-decoration: underline;
        }
        .activity-meta {
            display: flex;
            gap: 2rem;
            color: #555;
            margin-top: 0.5rem;
        }
        .activity-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .activity-body h3 {
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #0056b3;
            border-bottom: 2px solid #007bff;
            padding-bottom: 0.5rem;
            display: inline-block;
        }
        .instructions-content {
            line-height: 1.6;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        .attachment-link a {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background-color: #e9ecef;
            color: #333;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
        }
        .attachment-link a:hover {
            background-color: #dee2e6;
        }
    </style>
</head>
<body>
    <div class="activity-container">
        <div class="activity-header">
            <a href="trainee.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <h1><?php echo htmlspecialchars($activity['activity_title']); ?></h1>
            <div class="activity-meta">
                <span>
                    <i class="fas fa-book"></i> 
                    <?php echo htmlspecialchars($activity['course_name']); ?>
                </span>
                <span>
                    <i class="fas fa-calendar-alt"></i> 
                    Due: <?php echo date('F j, Y, g:i a', strtotime($activity['due_date'])); ?>
                </span>
                <span>
                    <i class="fas fa-star"></i> 
                    Max Score: <?php echo htmlspecialchars($activity['max_score']); ?>
                </span>
            </div>
        </div>

        <div class="activity-body">
            <h3><i class="fas fa-info-circle"></i> Instructions</h3>
            <div class="instructions-content">
                <?php echo !empty($activity['activity_description']) ? nl2br(htmlspecialchars($activity['activity_description'])) : '<p>No instructions provided.</p>'; ?>
            </div>

            <?php if (!empty($activity['attachment_path'])): ?>
                <div class="attachment-link">
                    <?php
                        $isUrl = filter_var($activity['attachment_path'], FILTER_VALIDATE_URL);
                        $attachmentUrl = $isUrl ? $activity['attachment_path'] : '../uploads/activities/' . htmlspecialchars($activity['attachment_path']);
                    ?>
                    <a href="<?php echo $attachmentUrl; ?>" target="_blank">
                        <i class="fas fa-paperclip"></i> View Attachment
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($submission): ?>
                <h3><i class="fas fa-check-circle"></i> Your Submission</h3>
                <div class="submission-details">
                    <p>You submitted this activity on <?php echo date('F j, Y, g:i a', strtotime($submission['submitted_at'])); ?>.</p>
                    <?php if ($submission['score'] !== null): ?>
                        <p><strong>Grade:</strong> <?php echo htmlspecialchars($submission['score']); ?> / <?php echo htmlspecialchars($activity['max_score']); ?></p>
                        <?php if (!empty($submission['feedback'])): ?>
                            <p><strong>Feedback:</strong> <?php echo htmlspecialchars($submission['feedback']); ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>Your submission is waiting to be graded.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- The submission form from the modal can be placed here -->
                <h3><i class="fas fa-upload"></i> Submit Your Work</h3>
                <div id="uploadSection">
                    <!-- You can move the upload form HTML from trainee.php here -->
                    <p>Submission form would go here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
