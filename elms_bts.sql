-- Create database
CREATE DATABASE IF NOT EXISTS elms_bts;
USE elms_bts;

-- Users table (unified for all roles)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(20) UNIQUE NOT NULL,
    role ENUM('admin', 'trainer', 'trainee', 'guest') NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    suffix VARCHAR(10),
    email VARCHAR(255) UNIQUE NOT NULL,
    contact_number VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255),
    force_password_change TINYINT(1) DEFAULT 1,
    status ENUM('active', 'archived') DEFAULT 'active',
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME
);

-- Courses table
CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_name VARCHAR(255) NOT NULL,
    course_code VARCHAR(50) UNIQUE NOT NULL,
    hours INT NOT NULL,
    description TEXT,
    image VARCHAR(255),
    competency_types JSON,
    status ENUM('active', 'archived') DEFAULT 'active',
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Announcements table
CREATE TABLE announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    posted_by VARCHAR(20),
    date_posted DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(user_id)
);

-- Enrollments table
CREATE TABLE enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trainee_id VARCHAR(20) NOT NULL,
    course_code VARCHAR(50) NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    batch_name VARCHAR(100),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    remarks TEXT,
    date_requested DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_date DATETIME,
    processed_by VARCHAR(20),
    FOREIGN KEY (trainee_id) REFERENCES users(user_id),
    FOREIGN KEY (course_code) REFERENCES courses(course_code),
    FOREIGN KEY (processed_by) REFERENCES users(user_id)
);

-- Course materials table
CREATE TABLE course_materials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content_type ENUM('pdf', 'video', 'text', 'quiz', 'assignment', 'assessment') NOT NULL,
    file_path VARCHAR(255),
    content TEXT,
    competency_type ENUM('basic', 'common', 'core') NOT NULL,
    activity_data JSON,
    is_locked BOOLEAN DEFAULT FALSE,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_code) REFERENCES courses(course_code)
);

-- Submissions table
CREATE TABLE submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trainee_id VARCHAR(20) NOT NULL,
    material_id INT NOT NULL,
    file_path VARCHAR(255),
    comment TEXT,
    score DECIMAL(5,2),
    teacher_remarks TEXT,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    graded_at DATETIME,
    graded_by VARCHAR(20),
    FOREIGN KEY (trainee_id) REFERENCES users(user_id),
    FOREIGN KEY (material_id) REFERENCES course_materials(id),
    FOREIGN KEY (graded_by) REFERENCES users(user_id)
);

-- Course assignments table
CREATE TABLE course_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trainer_id VARCHAR(20) NOT NULL,
    course_code VARCHAR(50) NOT NULL,
    date_assigned DATETIME DEFAULT CURRENT_TIMESTAMP,
    assigned_by VARCHAR(20) NOT NULL,
    FOREIGN KEY (trainer_id) REFERENCES users(user_id),
    FOREIGN KEY (course_code) REFERENCES courses(course_code),
    FOREIGN KEY (assigned_by) REFERENCES users(user_id),
    UNIQUE KEY unique_assignment (trainer_id, course_code)
);

-- Course batches table
CREATE TABLE course_batches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(50) NOT NULL,
    batch_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_by VARCHAR(20) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_code) REFERENCES courses(course_code),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    UNIQUE KEY unique_batch (course_code, batch_name)
);

-- Batch assignments table
CREATE TABLE batch_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trainee_id VARCHAR(20) NOT NULL,
    course_code VARCHAR(50) NOT NULL,
    batch_name VARCHAR(100) NOT NULL,
    date_assigned DATETIME DEFAULT CURRENT_TIMESTAMP,
    assigned_by VARCHAR(20) NOT NULL,
    FOREIGN KEY (trainee_id) REFERENCES users(user_id),
    FOREIGN KEY (course_code) REFERENCES courses(course_code),
    FOREIGN KEY (assigned_by) REFERENCES users(user_id)
);

-- Topics under competencies
CREATE TABLE course_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(50) NOT NULL,
    competency_id VARCHAR(255) NOT NULL, -- Can be the name of the competency if no ID is available
    topic_name VARCHAR(255) NOT NULL,
    topic_description TEXT,
    learning_objectives TEXT,
    added_by VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_code) REFERENCES courses(course_code),
    FOREIGN KEY (added_by) REFERENCES users(user_id)
);

-- Course materials for topics
CREATE TABLE topic_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT,
    material_title VARCHAR(255),
    material_description TEXT,
    file_path VARCHAR(500),
    uploaded_by VARCHAR(50),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES course_topics(id)
);

-- Activities for topics
CREATE TABLE topic_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT,
    activity_title VARCHAR(255),
    activity_description TEXT,
    activity_type ENUM('assignment', 'quiz', 'project', 'discussion'),
    start_date DATETIME NULL,
    due_date DATETIME,
    max_score INT,
    max_attempts INT DEFAULT 1,
    created_by VARCHAR(50),
    attachment_path VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES course_topics(id)
);

-- Submissions for activities
CREATE TABLE activity_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_id INT,
    trainee_id VARCHAR(50),
    submission_text TEXT,
    file_path VARCHAR(500),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    score DECIMAL(5,2),
    graded_by VARCHAR(50),
    graded_at TIMESTAMP NULL,
    feedback TEXT,
    FOREIGN KEY (activity_id) REFERENCES topic_activities(id),
    FOREIGN KEY (trainee_id) REFERENCES users(user_id)
);

-- Activities table for logging user actions
CREATE TABLE activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(20) NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Add this to your elms_bts.sql file after the courses table creation
CREATE TABLE competencies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    competency_code VARCHAR(50) UNIQUE NOT NULL,
    competency_name VARCHAR(255) NOT NULL,
    competency_type ENUM('basic', 'common', 'core') NOT NULL,
    description TEXT,
    status ENUM('active', 'archived') DEFAULT 'active',
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Add to your existing users table
ALTER TABLE users ADD COLUMN password_changed_at DATETIME NULL AFTER last_login;

-- Insert default admin account (password: password)
INSERT INTO users (user_id, role, first_name, last_name, email, password) 
VALUES ('1000000001', 'admin', 'Admin', 'User', 'admin@bts.gov.ph', 
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');