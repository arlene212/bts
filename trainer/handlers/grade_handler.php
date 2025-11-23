<?php
require_once __DIR__ . '/../../php/SessionManager.php';
require_once __DIR__ . '/../../php/DatabaseConnection.php';

SessionManager::startSession();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'trainer') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$trainer_id = $_SESSION['user']['user_id'];

try {
    $database = new DatabaseConnection();
    $pdo = $database->getConnection();
    
    switch ($action) {
        case 'get_grades':
            getGrades($pdo, $trainer_id);
            break;
            
        case 'update_grade':
            updateGrade($pdo, $trainer_id);
            break;
            
        case 'export':
            exportGrades($pdo, $trainer_id);
            break;
            
        case 'bulk_import':
            bulkImportGrades($pdo, $trainer_id);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    error_log("Grade handler error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

function getGrades($pdo, $trainer_id) {
    $course_code = $_GET['course_code'] ?? '';
    $grade_type = $_GET['grade_type'] ?? '';
    
    $query = "
        SELECT 
            g.id,
            g.student_id,
            CONCAT(u.first_name, ' ', u.last_name) as student_name,
            u.student_id as student_id_number,
            g.course_code,
            c.course_name,
            g.activity_name,
            g.activity_type,
            g.score,
            g.max_score,
            g.percentage,
            g.grade,
            g.status,
            g.feedback,
            g.submitted_date,
            g.time_taken,
            g.attempts,
            g.submission_details
        FROM grades g
        JOIN users u ON g.student_id = u.user_id
        JOIN courses c ON g.course_code = c.course_code
        JOIN course_assignments ca ON c.course_code = ca.course_code
        WHERE ca.trainer_id = :trainer_id
    ";
    
    $params = [':trainer_id' => $trainer_id];
    
    if ($course_code) {
        $query .= " AND g.course_code = :course_code";
        $params[':course_code'] = $course_code;
    }
    
    if ($grade_type) {
        $query .= " AND g.activity_type = :activity_type";
        $params[':activity_type'] = $grade_type;
    }
    
    $query .= " ORDER BY g.submitted_date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'grades' => $grades
    ]);
}

function updateGrade($pdo, $trainer_id) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }
    
    $grade_id = $_POST['grade_id'] ?? '';
    $score = $_POST['score'] ?? '';
    $feedback = $_POST['feedback'] ?? '';
    
    if (!$grade_id || $score === '') {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    // Verify trainer has access to this grade
    $stmt = $pdo->prepare("
        SELECT g.max_score 
        FROM grades g
        JOIN course_assignments ca ON g.course_code = ca.course_code
        WHERE g.id = :grade_id AND ca.trainer_id = :trainer_id
    ");
    $stmt->execute([':grade_id' => $grade_id, ':trainer_id' => $trainer_id]);
    $grade_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$grade_info) {
        echo json_encode(['success' => false, 'message' => 'Grade not found or access denied']);
        return;
    }
    
    $max_score = $grade_info['max_score'];
    $percentage = ($score / $max_score) * 100;
    $letter_grade = calculateLetterGrade($percentage);
    
    $stmt = $pdo->prepare("
        UPDATE grades 
        SET score = :score, 
            percentage = :percentage, 
            grade = :grade, 
            feedback = :feedback,
            graded_by = :trainer_id,
            graded_date = NOW()
        WHERE id = :grade_id
    ");
    
    $stmt->execute([
        ':score' => $score,
        ':percentage' => $percentage,
        ':grade' => $letter_grade,
        ':feedback' => $feedback,
        ':trainer_id' => $trainer_id,
        ':grade_id' => $grade_id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Grade updated successfully',
        'grade' => $letter_grade,
        'percentage' => $percentage
    ]);
}

function exportGrades($pdo, $trainer_id) {
    $course_code = $_GET['course_code'] ?? '';
    $grade_type = $_GET['grade_type'] ?? '';
    
    $query = "
        SELECT 
            u.student_id as 'Student ID',
            CONCAT(u.first_name, ' ', u.last_name) as 'Student Name',
            c.course_name as 'Course',
            g.activity_name as 'Activity',
            g.activity_type as 'Type',
            g.score as 'Score',
            g.max_score as 'Max Score',
            g.percentage as 'Percentage',
            g.grade as 'Letter Grade',
            g.status as 'Status',
            g.submitted_date as 'Submission Date',
            g.feedback as 'Feedback'
        FROM grades g
        JOIN users u ON g.student_id = u.user_id
        JOIN courses c ON g.course_code = c.course_code
        JOIN course_assignments ca ON c.course_code = ca.course_code
        WHERE ca.trainer_id = :trainer_id
    ";
    
    $params = [':trainer_id' => $trainer_id];
    
    if ($course_code) {
        $query .= " AND g.course_code = :course_code";
        $params[':course_code'] = $course_code;
    }
    
    if ($grade_type) {
        $query .= " AND g.activity_type = :activity_type";
        $params[':activity_type'] = $grade_type;
    }
    
    $query .= " ORDER BY u.last_name, u.first_name, g.submitted_date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="grades_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers
    if (!empty($grades)) {
        fputcsv($output, array_keys($grades[0]));
        
        // Add data
        foreach ($grades as $grade) {
            fputcsv($output, $grade);
        }
    }
    
    fclose($output);
    exit;
}

function bulkImportGrades($pdo, $trainer_id) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }
    
    if (!isset($_FILES['grade_file'])) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        return;
    }
    
    $file = $_FILES['grade_file'];
    $file_type = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    if (!in_array($file_type, ['csv', 'xlsx'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload CSV or XLSX file']);
        return;
    }
    
    // Process file (simplified - would need proper CSV/XLSX parsing)
    $grades_data = [];
    
    if ($file_type === 'csv') {
        if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
            $headers = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                $grades_data[] = array_combine($headers, $data);
            }
            fclose($handle);
        }
    }
    
    $imported = 0;
    $errors = [];
    
    foreach ($grades_data as $row) {
        try {
            // Validate and import each grade
            $student_id = $row['Student ID'] ?? '';
            $activity_name = $row['Activity Name'] ?? '';
            $score = $row['Score'] ?? '';
            $max_score = $row['Max Score'] ?? 100;
            $feedback = $row['Comments'] ?? '';
            
            if (!$student_id || !$activity_name || $score === '') {
                $errors[] = "Missing required fields for student {$student_id}";
                continue;
            }
            
            // Get student user_id
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                $errors[] = "Student not found: {$student_id}";
                continue;
            }
            
            $percentage = ($score / $max_score) * 100;
            $letter_grade = calculateLetterGrade($percentage);
            
            // Update or insert grade
            $stmt = $pdo->prepare("
                INSERT INTO grades (student_id, course_code, activity_name, activity_type, score, max_score, percentage, grade, status, feedback, graded_by, graded_date, submitted_date)
                VALUES (:student_id, :course_code, :activity_name, 'assignment', :score, :max_score, :percentage, :grade, 'completed', :feedback, :trainer_id, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                score = :score, percentage = :percentage, grade = :grade, feedback = :feedback, graded_by = :trainer_id, graded_date = NOW()
            ");
            
            $stmt->execute([
                ':student_id' => $student['user_id'],
                ':course_code' => $row['Course Code'] ?? '',
                ':activity_name' => $activity_name,
                ':score' => $score,
                ':max_score' => $max_score,
                ':percentage' => $percentage,
                ':grade' => $letter_grade,
                ':feedback' => $feedback,
                ':trainer_id' => $trainer_id
            ]);
            
            $imported++;
            
        } catch (Exception $e) {
            $errors[] = "Error importing grade for student {$student_id}: " . $e->getMessage();
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Imported {$imported} grades successfully",
        'imported' => $imported,
        'errors' => $errors
    ]);
}

function calculateLetterGrade($percentage) {
    if ($percentage >= 90) return 'A';
    if ($percentage >= 80) return 'B';
    if ($percentage >= 70) return 'C';
    if ($percentage >= 60) return 'D';
    return 'F';
}
?>