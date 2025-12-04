-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 30, 2025 at 05:23 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `elms_bts`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `trainer_id` varchar(20) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_submissions`
--

CREATE TABLE `activity_submissions` (
  `id` int(11) NOT NULL,
  `activity_id` int(11) DEFAULT NULL,
  `guest_id` varchar(50) DEFAULT NULL,
  `submission_text` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `score` decimal(5,2) DEFAULT NULL,
  `graded_by` varchar(50) DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `posted_by` varchar(20) DEFAULT NULL,
  `date_posted` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `batch_assignments`
--

CREATE TABLE `batch_assignments` (
  `id` int(11) NOT NULL,
  `trainee_id` varchar(20) NOT NULL,
  `trainer_id` varchar(50) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `batch_name` text NOT NULL,
  `date_assigned` datetime DEFAULT current_timestamp(),
  `assigned_by` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `batch_assignment_status`
--

CREATE TABLE `batch_assignment_status` (
  `id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `batch_name` varchar(100) NOT NULL,
  `trainer_id` varchar(50) DEFAULT NULL,
  `trainee_id` varchar(50) DEFAULT NULL,
  `status` enum('active','archived') NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `branch_name` varchar(255) NOT NULL,
  `branch_code` varchar(50) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `trainee_id` varchar(50) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `certificate_number` varchar(100) NOT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `final_grade` decimal(5,2) DEFAULT NULL,
  `certificate_path` varchar(500) DEFAULT NULL,
  `status` enum('active','expired','revoked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
--

CREATE TABLE `competencies` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `competency_code` varchar(50) NOT NULL,
  `unit_order` int(11) UNSIGNED DEFAULT NULL,
  `competency_name` varchar(255) NOT NULL,
  `module_title` varchar(255) NOT NULL,
  `competency_type` enum('basic','common','core') NOT NULL,
  `nominal_hours` int(11) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `learning_outcomes` mediumtext DEFAULT NULL,
  `hours_per_session` int(11) NOT NULL,
  `status` enum('active','archived') DEFAULT 'active',
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `competency_name` varchar(255) NOT NULL,
  `module_title` varchar(100) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `hours` int(11) NOT NULL,
  `nominal_hours` int(11) unsigned NOT NULL,
  `description` text DEFAULT NULL,
  `learning_outcomes` mediumtext NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','archived') DEFAULT 'active',
  `course_status` enum('draft','published','archived') DEFAULT 'draft',
  `allow_preview` tinyint(1) DEFAULT 0,
  `preview_content` text DEFAULT NULL,
  `require_verification` tinyint(1) DEFAULT 0,
  `verification_type` enum('email','student_id','phone') DEFAULT 'email',
  `date_created` datetime DEFAULT current_timestamp(),
  `branch_id` int(11) DEFAULT NULL,
  `schedule_days_per_week` int(11) DEFAULT NULL,
  `schedule_days` text DEFAULT NULL,
  `session_hours` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_assignments`
--

CREATE TABLE `course_assignments` (
  `id` int(11) NOT NULL,
  `trainer_id` varchar(20) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `date_assigned` datetime DEFAULT current_timestamp(),
  `assigned_by` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_batches`
--

CREATE TABLE `course_batches` (
  `id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `batch_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `trainer_id` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_materials`
--

CREATE TABLE `course_materials` (
  `id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `competency_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content_type` enum('pdf','video','text','quiz','assignment','assessment') NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `competency_type` enum('basic','common','core') NOT NULL,
  `activity_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`activity_data`)),
  `is_locked` tinyint(1) DEFAULT 0,
  `access_code` varchar(100) DEFAULT NULL,
  `randomize_questions` tinyint(1) DEFAULT 0,
  `prevent_sharing` tinyint(1) DEFAULT 0,
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_topics`
--

CREATE TABLE `course_topics` (
  `id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `competency_id` varchar(255) NOT NULL,
  `topic_name` varchar(255) NOT NULL,
  `topic_description` text DEFAULT NULL,
  `learning_objectives` text DEFAULT NULL,
  `added_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `trainee_id` varchar(20) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `batch_name` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `student_id_verification` varchar(50) DEFAULT NULL,
  `email_verification` varchar(255) DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `date_requested` datetime DEFAULT current_timestamp(),
  `processed_date` datetime DEFAULT NULL,
  `processed_by` varchar(20) DEFAULT NULL,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `completion_date` datetime DEFAULT NULL,
  `final_grade` decimal(5,2) DEFAULT NULL,
  `drop_date` datetime DEFAULT NULL,
  `drop_reason` text DEFAULT NULL,
  `dropped_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `trainee_id` varchar(50) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `activity_id` int(11) DEFAULT NULL,
  `quiz_id` int(11) DEFAULT NULL,
  `grade_type` enum('activity','quiz','assignment','final') NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `max_score` int(11) NOT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `letter_grade` varchar(2) DEFAULT NULL,
  `graded_by` varchar(50) DEFAULT NULL,
  `graded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hour_credits`
--

CREATE TABLE `hour_credits` (
  `id` int(11) NOT NULL,
  `trainee_id` varchar(20) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `credit_date` date NOT NULL,
  `hours` int(11) NOT NULL,
  `source` enum('schedule','manual') NOT NULL DEFAULT 'schedule',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `competency_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `time_limit` int(11) DEFAULT NULL,
  `max_attempts` int(11) DEFAULT 1,
  `passing_score` decimal(5,2) DEFAULT 70.00,
  `is_randomized` tinyint(1) DEFAULT 0,
  `show_correct_answers` tinyint(1) DEFAULT 1,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_analytics`
--

CREATE TABLE `quiz_analytics` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `correct_attempts` int(11) DEFAULT 0,
  `total_attempts` int(11) DEFAULT 0,
  `average_time` int(11) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempts`
--

CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `trainee_id` varchar(50) NOT NULL,
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answers`)),
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` int(11) DEFAULT NULL,
  `attempt_number` int(11) DEFAULT 1,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_spent` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_categories`
--

CREATE TABLE `quiz_categories` (
  `id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_feedback`
--

CREATE TABLE `quiz_feedback` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `feedback_text` text DEFAULT NULL,
  `feedback_type` enum('correct','incorrect','partial') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) DEFAULT NULL,
  `course_code` varchar(50) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer','essay') NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `correct_answer` text DEFAULT NULL,
  `points` int(11) DEFAULT 1,
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `explanation` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `question_order` int(11) DEFAULT 0,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_settings`
--

CREATE TABLE `quiz_settings` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL,
  `trainee_id` varchar(20) NOT NULL,
  `material_id` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `teacher_remarks` text DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `graded_at` datetime DEFAULT NULL,
  `graded_by` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_backups`
--

CREATE TABLE `system_backups` (
  `id` int(11) NOT NULL,
  `backup_type` enum('full','database','files') NOT NULL,
  `backup_path` varchar(500) NOT NULL,
  `backup_size` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(50) DEFAULT NULL,
  `status` enum('success','failed','in_progress') DEFAULT 'in_progress'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `topic_activities`
--

CREATE TABLE `topic_activities` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) DEFAULT NULL,
  `activity_title` varchar(255) DEFAULT NULL,
  `activity_description` text DEFAULT NULL,
  `activity_type` enum('assignment','quiz','project','discussion') DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `max_score` int(11) DEFAULT NULL,
  `max_attempts` int(11) DEFAULT 1,
  `created_by` varchar(50) DEFAULT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `version` int(11) DEFAULT 1,
  `parent_activity_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `topic_materials`
--

CREATE TABLE `topic_materials` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) DEFAULT NULL,
  `material_title` varchar(255) DEFAULT NULL,
  `material_description` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `uploaded_by` varchar(50) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `role` enum('admin','trainer','trainee','guest') NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `student_id` varchar(50) DEFAULT NULL,
  `phone_verified` tinyint(1) DEFAULT 0,
  `contact_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `force_password_change` tinyint(1) DEFAULT 1,
  `status` text DEFAULT 'active',
  `date_created` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_verification`
--

CREATE TABLE `user_verification` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `verification_type` enum('email','student_id','phone') NOT NULL,
  `verification_code` varchar(100) DEFAULT NULL,
  `verification_data` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_verification_history`
--

CREATE TABLE `user_verification_history` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `verification_type` enum('email','student_id','phone') NOT NULL,
  `verification_method` varchar(100) DEFAULT NULL,
  `verified_by` varchar(50) DEFAULT NULL,
  `verified_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('verified','rejected','expired') NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `verification_tokens`
--

CREATE TABLE `verification_tokens` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `token_type` enum('email','phone','student_id') NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`trainer_id`);

--
-- Indexes for table `activity_submissions`
--
ALTER TABLE `activity_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_id` (`activity_id`),
  ADD KEY `trainee_id` (`guest_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `batch_assignments`
--
ALTER TABLE `batch_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trainee_id` (`trainee_id`),
  ADD KEY `course_code` (`course_code`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `batch_id` (`batch_name`(768)),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `batch_assignment_status`
--
ALTER TABLE `batch_assignment_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_trainer` (`trainer_id`),
  ADD KEY `idx_trainee` (`trainee_id`),
  ADD KEY `idx_course_batch` (`course_code`,`batch_name`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `branch_code` (`branch_code`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_number` (`certificate_number`),
  ADD KEY `trainee_id` (`trainee_id`),
  ADD KEY `course_code` (`course_code`);

--
--
ALTER TABLE `competencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `competency_code` (`competency_code`),
  ADD KEY `competencies_ibfk_1` (`course_id`),
  ADD KEY `idx_comp_course_type_order` (`course_id`,`competency_type`,`unit_order`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `idx_courses_verification` (`require_verification`,`verification_type`),
  ADD KEY `idx_courses_status` (`course_status`),
  ADD KEY `idx_courses_branch` (`branch_id`),
  ADD KEY `idx_courses_module_title` (`module_title`),
  ADD KEY `idx_courses_competency_name` (`competency_name`),
  ADD KEY `idx_courses_nominal_hours` (`nominal_hours`);

--
-- Indexes for table `course_assignments`
--
ALTER TABLE `course_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment` (`trainer_id`,`course_code`),
  ADD KEY `course_code` (`course_code`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `course_batches`
--
ALTER TABLE `course_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_batch` (`course_code`,`batch_name`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_code` (`course_code`),
  ADD KEY `competency_id` (`competency_id`);

--
-- Indexes for table `course_topics`
--
ALTER TABLE `course_topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_code` (`course_code`),
  ADD KEY `added_by` (`added_by`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trainee_id` (`trainee_id`),
  ADD KEY `course_code` (`course_code`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_enrollments_status` (`status`),
  ADD KEY `idx_enrollments_progress` (`progress_percentage`),
  ADD KEY `idx_enrollments_verification_status` (`verification_status`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trainee_id` (`trainee_id`),
  ADD KEY `course_code` (`course_code`),
  ADD KEY `idx_grades_trainee_course` (`trainee_id`,`course_code`),
  ADD KEY `idx_grade_type` (`grade_type`),
  ADD KEY `idx_graded_date` (`graded_at`);

--
-- Indexes for table `hour_credits`
--
ALTER TABLE `hour_credits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_credit` (`trainee_id`,`course_code`,`credit_date`),
  ADD KEY `idx_credits_user_course_date` (`trainee_id`,`course_code`,`credit_date`),
  ADD KEY `fk_hour_credits_course` (`course_code`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_code` (`course_code`),
  ADD KEY `category_id` (`competency_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_quiz_status` (`status`);

--
-- Indexes for table `quiz_analytics`
--
ALTER TABLE `quiz_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_quiz_question` (`quiz_id`,`question_id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `trainee_id` (`trainee_id`),
  ADD KEY `idx_attempts_trainee_quiz` (`trainee_id`,`quiz_id`);

--
-- Indexes for table `quiz_categories`
--
ALTER TABLE `quiz_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_code` (`course_code`);

--
-- Indexes for table `quiz_feedback`
--
ALTER TABLE `quiz_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attempt_id` (`attempt_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `course_code` (`course_code`),
  ADD KEY `question_type` (`question_type`),
  ADD KEY `difficulty` (`difficulty`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `quiz_settings`
--
ALTER TABLE `quiz_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `setting_key` (`setting_key`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trainee_id` (`trainee_id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `graded_by` (`graded_by`);

--
-- Indexes for table `system_backups`
--
ALTER TABLE `system_backups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `topic_activities`
--
ALTER TABLE `topic_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_activities_ibfk_1` (`topic_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `topic_materials`
--
ALTER TABLE `topic_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_materials_ibfk_1` (`topic_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email_verified` (`email_verified`),
  ADD KEY `idx_users_student_id` (`student_id`),
  ADD KEY `idx_users_branch` (`branch_id`);

--
-- Indexes for table `user_verification`
--
ALTER TABLE `user_verification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_verification_history`
--
ALTER TABLE `user_verification_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `verification_type` (`verification_type`);

--
-- Indexes for table `verification_tokens`
--
ALTER TABLE `verification_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `token` (`token`),
  ADD KEY `expires_at` (`expires_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_submissions`
--
ALTER TABLE `activity_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batch_assignments`
--
ALTER TABLE `batch_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batch_assignment_status`
--
ALTER TABLE `batch_assignment_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `competencies`
--
ALTER TABLE `competencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_assignments`
--
ALTER TABLE `course_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_batches`
--
ALTER TABLE `course_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_materials`
--
ALTER TABLE `course_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_topics`
--
ALTER TABLE `course_topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hour_credits`
--
ALTER TABLE `hour_credits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_analytics`
--
ALTER TABLE `quiz_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_categories`
--
ALTER TABLE `quiz_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_feedback`
--
ALTER TABLE `quiz_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_settings`
--
ALTER TABLE `quiz_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_backups`
--
ALTER TABLE `system_backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `topic_activities`
--
ALTER TABLE `topic_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `topic_materials`
--
ALTER TABLE `topic_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_verification`
--
ALTER TABLE `user_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_verification_history`
--
ALTER TABLE `user_verification_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `verification_tokens`
--
ALTER TABLE `verification_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `activity_submissions`
--
ALTER TABLE `activity_submissions`
  ADD CONSTRAINT `activity_submissions_ibfk_1` FOREIGN KEY (`activity_id`) REFERENCES `topic_activities` (`id`),
  ADD CONSTRAINT `activity_submissions_ibfk_2` FOREIGN KEY (`guest_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `batch_assignments`
--
ALTER TABLE `batch_assignments`
  ADD CONSTRAINT `batch_assignments_ibfk_1` FOREIGN KEY (`trainee_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `batch_assignments_ibfk_2` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  ADD CONSTRAINT `batch_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `batch_assignments_ibfk_5` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `competencies`
--
ALTER TABLE `competencies`
  ADD CONSTRAINT `competencies_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_assignments`
--
ALTER TABLE `course_assignments`
  ADD CONSTRAINT `course_assignments_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `course_assignments_ibfk_2` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  ADD CONSTRAINT `course_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `course_batches`
--
ALTER TABLE `course_batches`
  ADD CONSTRAINT `course_batches_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  ADD CONSTRAINT `course_batches_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `course_batches_ibfk_3` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD CONSTRAINT `course_materials_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_materials_ibfk_2` FOREIGN KEY (`competency_id`) REFERENCES `competencies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_topics`
--
ALTER TABLE `course_topics`
  ADD CONSTRAINT `course_topics_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  ADD CONSTRAINT `course_topics_ibfk_2` FOREIGN KEY (`added_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`trainee_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  ADD CONSTRAINT `enrollments_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `hour_credits`
--
ALTER TABLE `hour_credits`
  ADD CONSTRAINT `fk_hour_credits_course` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hour_credits_user` FOREIGN KEY (`trainee_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `quizzes_ibfk_2` FOREIGN KEY (`competency_id`) REFERENCES `competencies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD CONSTRAINT `fk_quiz_attempts_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `fk_quiz_questions_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_settings`
--
ALTER TABLE `quiz_settings`
  ADD CONSTRAINT `fk_quiz_settings_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`trainee_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `course_materials` (`id`),
  ADD CONSTRAINT `submissions_ibfk_3` FOREIGN KEY (`graded_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `topic_activities`
--
ALTER TABLE `topic_activities`
  ADD CONSTRAINT `topic_activities_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `course_topics` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `topic_activities_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `topic_materials`
--
ALTER TABLE `topic_materials`
  ADD CONSTRAINT `topic_materials_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `course_topics` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `topic_materials_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
INSERT INTO `courses` (
  `course_name`,
  `competency_name`,
  `module_title`,
  `course_code`,
  `hours`,
  `nominal_hours`,
  `description`,
  `learning_outcomes`,
  `status`,
  `course_status`,
  `allow_preview`,
  `preview_content`,
  `require_verification`,
  `verification_type`,
  `branch_id`,
  `schedule_days_per_week`,
  `schedule_days`,
  `session_hours`
) VALUES (
  'HAIRDRESSING',
  'Hairdressing',
  'National Certificate Level II',
  'HRD-NCII',
  541,
  541,
  'This course provides knowledge, practical skills and attitude to perform pre and post hair care activities, hair and scalp treatment, basic hair coloring, hair bleaching, basic hair perming, hair straightening and basic haircutting, with classroom learning and practical work in actual or simulated sites.',
  'Learners communicate effectively in the workplace, work in teams, solve routine problems, make career and life decisions, contribute to innovation, present relevant information, practice OSH, apply sustainable practices, and demonstrate entrepreneurial skills; They perform pre/post hair care, hair/scalp treatment, basic hair coloring and bleaching, perming, straightening, and basic haircutting to industry standards.',
  'active',
  'published',
  0,
  NULL,
  0,
  'email',
  NULL,
  NULL,
  NULL,
  NULL
);
SET @course_id = LAST_INSERT_ID();

INSERT INTO `competencies` (
  `course_id`, `competency_code`, `unit_order`, `competency_name`, `module_title`, `competency_type`, `nominal_hours`, `description`, `learning_outcomes`, `hours_per_session`, `status`
) VALUES
  (@course_id, 'HRD-B01', 1, 'Participate in workplace communication', 'Participating in workplace communication', 'basic', 6, NULL, 'Obtain and convey workplace information; Perform duties following workplace instructions; Complete relevant work-related documents.', 4, 'active'),
  (@course_id, 'HRD-B02', 2, 'Work in a team environment', 'Working in a team environment', 'basic', 3, NULL, 'Describe team role and scope; Identify one’s role and responsibility within team; Work as a team member.', 4, 'active'),
  (@course_id, 'HRD-B03', 3, 'Solve/address general workplace problems', 'Solving/addressing general workplace problems', 'basic', 3, NULL, 'Identify routine problems; Look for solutions to routine problems; Recommend solutions to problems.', 4, 'active'),
  (@course_id, 'HRD-B04', 4, 'Develop career and life decisions', 'Developing career and life decisions', 'basic', 3, NULL, 'Manage one’s emotion; Develop reflective practice; Boost self-confidence and self-regulation.', 4, 'active'),
  (@course_id, 'HRD-B05', 5, 'Contribute to workplace innovation', 'Contributing to workplace innovation', 'basic', 3, NULL, 'Identify opportunities to do things better; Discuss and develop ideas with others; Integrate ideas for change in the workplace.', 4, 'active'),
  (@course_id, 'HRD-B06', 6, 'Present relevant information', 'Present relevant information', 'basic', 8, NULL, 'Gather data/information; Assess gathered data/information; Record and present information.', 4, 'active'),
  (@course_id, 'HRD-B07', 7, 'Practice occupational health and safety procedures', 'Practicing occupational health and safety procedures', 'basic', 4, NULL, 'Identify OSH compliance requirements; Prepare OSH requirements for compliance; Perform tasks per OSH policies and procedures.', 4, 'active'),
  (@course_id, 'HRD-B08', 8, 'Exercise efficient and effective sustainable practices', 'Exercising efficient and effective sustainable practices in the workplace', 'basic', 3, NULL, 'Identify efficiency and effectiveness of resource utilization; Determine causes of inefficiency/ineffectiveness; Convey inefficient and ineffective environmental practices.', 4, 'active'),
  (@course_id, 'HRD-B09', 9, 'Practice entrepreneurial skills in the workplace', 'Practicing entrepreneurial skills in the workplace', 'basic', 4, NULL, 'Apply entrepreneurial workplace best practices; Communicate entrepreneurial best practices; Implement cost-effective operations.', 4, 'active'),
  (@course_id, 'HRD-C01', 1, 'Maintain an effective relationship with clients/customers', 'Maintaining an effective relationship with clients/customers', 'common', 6, NULL, 'Maintain a professional image; Meet client requirements; Build credibility; Establish professional relationships; Manage interactions; Provide effective responses to enquiries.', 4, 'active'),
  (@course_id, 'HRD-C02', 2, 'Manage own performance', 'Managing own performance', 'common', 6, NULL, 'Plan for completion of own workload; Maintain quality of performance; Build credibility with customers/clients.', 4, 'active'),
  (@course_id, 'HRD-C03', 3, 'Apply quality standards', 'Applying quality standards', 'common', 6, NULL, 'Assess client service needs; Assess own work; Engage in quality improvement.', 4, 'active'),
  (@course_id, 'HRD-C04', 4, 'Maintain a safe clean and efficient work environment', 'Maintaining a safe clean and efficient work environment', 'common', 6, NULL, 'Comply with health regulations; Assess own work; Check and maintain tools, equipment and stocks; Provide a relaxed and caring environment.', 4, 'active'),
  (@course_id, 'HRD-CORE01', 1, 'Perform pre and post hair care activities', 'Performing pre and post hair care activities', 'core', 40, NULL, 'Prepare client and workstation; Apply shampoo/conditioner; Perform blow dry; Perform post-service activities.', 4, 'active'),
  (@course_id, 'HRD-CORE02', 2, 'Perform Hair and Scalp Treatment', 'Performing Hair and Scalp Treatment', 'core', 40, NULL, 'Prepare client and workstation; Treat hair and scalp; Rinse and blow-dry; Perform post-service activities.', 4, 'active'),
  (@course_id, 'HRD-CORE03', 3, 'Perform Basic hair coloring', 'Performing Basic hair coloring', 'core', 80, NULL, 'Prepare client and workstation; Apply basic hair coloring; Perform post-service activities.', 4, 'active'),
  (@course_id, 'HRD-CORE04', 4, 'Perform Basic Hair Bleaching', 'Performing Basic Hair Bleaching', 'core', 40, NULL, 'Prepare client and workstation; Bleach hair; Perform post-service activities.', 4, 'active'),
  (@course_id, 'HRD-CORE05', 5, 'Perform Basic Hair Perming', 'Performing Basic Hair Perming', 'core', 80, NULL, 'Prepare client and workstation; Perm hair; Apply finishing touches; Perform post-service activities.', 4, 'active'),
  (@course_id, 'HRD-CORE06', 6, 'Perform hair straightening', 'Performing hair straightening', 'core', 80, NULL, 'Prepare client and workstation; Apply hair straightening products; Iron hair; Apply fixing solution; Perform post-service activities.', 4, 'active'),
  (@course_id, 'HRD-CORE07', 7, 'Perform basic haircutting', 'Performing basic haircutting', 'core', 120, NULL, 'Prepare client and workstation; Cut hair; Check haircut and apply finishing touches; Perform post-service activities.', 4, 'active');

INSERT INTO `courses` (
  `course_name`,
  `competency_name`,
  `module_title`,
  `course_code`,
  `hours`,
  `nominal_hours`,
  `description`,
  `learning_outcomes`,
  `status`,
  `course_status`,
  `allow_preview`,
  `preview_content`,
  `require_verification`,
  `verification_type`,
  `branch_id`,
  `schedule_days_per_week`,
  `schedule_days`,
  `session_hours`
) VALUES (
  'AUTOMOTIVE SERVICING',
  'Automotive Servicing',
  'National Certificate Level I',
  'AUT-NCI',
  488,
  488,
  'This course enhances knowledge, skills and attitudes in automotive servicing according to industry standards, including basic engine servicing for gas and diesel engines through removal and re-installation of components. It also strengthens the basic and common competencies required in the field.',
  'Learners receive/respond to workplace communication; work with others; solve routine problems; enhance self-management; support innovation; access/maintain information; follow OSH; apply environmental work standards; adopt entrepreneurial mindset. They validate vehicle specifications; move and position vehicles; utilize automotive tools; perform mensuration and calculation; utilize workshop facilities and equipment; prepare servicing parts and consumables; prepare vehicles for servicing and releasing. They perform pre-delivery inspection; periodic maintenance of automotive engine; drive train; brake system; suspension system; and steering system.',
  'active',
  'published',
  0,
  NULL,
  0,
  'email',
  NULL,
  NULL,
  NULL,
  NULL
);
SET @auto_course_id = LAST_INSERT_ID();

INSERT INTO `competencies` (
  `course_id`, `competency_code`, `unit_order`, `competency_name`, `module_title`, `competency_type`, `nominal_hours`, `description`, `learning_outcomes`, `hours_per_session`, `status`
) VALUES
  (@auto_course_id, 'AUT-B01', 1, 'Receive and respond to workplace communication', 'Receiving and responding to workplace communication', 'basic', 8, NULL, 'Follow routine spoken messages; Perform workplace duties following written notices.', 4, 'active'),
  (@auto_course_id, 'AUT-B02', 2, 'Work with others', 'Working with others', 'basic', 3, NULL, 'Develop effective workplace relationships; Contribute to work group activities.', 4, 'active'),
  (@auto_course_id, 'AUT-B03', 3, 'Solve/address routine problems', 'Solving/addressing routine problems', 'basic', 4, NULL, 'Identify the problem; Assess fundamental causes; Determine corrective action; Communicate action plans and recommendations.', 4, 'active'),
  (@auto_course_id, 'AUT-B04', 4, 'Enhance self-management skills', 'Enhancing self-management skills', 'basic', 3, NULL, 'Set personal and career goals; Recognize emotions; Describe oneself as a learner.', 4, 'active'),
  (@auto_course_id, 'AUT-B05', 5, 'Support innovation', 'Supporting innovation', 'basic', 3, NULL, 'Identify need for innovation; Recognize innovative and creative ideas; Support flexible and innovative ways of working.', 4, 'active'),
  (@auto_course_id, 'AUT-B06', 6, 'Access and maintain information', 'Accessing and maintaining information', 'basic', 12, NULL, 'Identify and gather needed information; Search on internet/intranet; Examine, secure, and manage information.', 4, 'active'),
  (@auto_course_id, 'AUT-B07', 7, 'Follow occupational safety and health policies and procedures', 'Following occupational safety and health policies and procedures', 'basic', 8, NULL, 'Identify relevant OSH policies and procedures; Perform relevant OSH procedures; Comply with OSH policies and standards.', 4, 'active'),
  (@auto_course_id, 'AUT-B08', 8, 'Apply environmental work standards', 'Applying Environmental Work Standards', 'basic', 3, NULL, 'Identify environmental work hazards; Follow environmental work procedures; Comply with environmental work requirements.', 4, 'active'),
  (@auto_course_id, 'AUT-B09', 9, 'Adopt entrepreneurial mindset in the workplace', 'Adopting entrepreneurial mindset in the workplace', 'basic', 3, NULL, 'Determine entrepreneurial mindset; Identify entrepreneurial practices.', 4, 'active'),
  (@auto_course_id, 'AUT-C01', 1, 'Validate vehicle specification', 'Validating vehicle specification', 'common', 17, NULL, 'Check body type; Check vehicle engine type; Check vehicle specifications; Complete validation of vehicle specification.', 4, 'active'),
  (@auto_course_id, 'AUT-C02', 2, 'Move and Position Vehicle', 'Moving and Positioning Vehicle', 'common', 40, NULL, 'Prepare vehicle for operation; Position vehicle; Park and stop the vehicle.', 4, 'active'),
  (@auto_course_id, 'AUT-C03', 3, 'Utilize automotive tools', 'Utilizing automotive tools', 'common', 16, NULL, 'Prepare automotive tools; Use automotive tools; Maintain automotive tools.', 4, 'active'),
  (@auto_course_id, 'AUT-C04', 4, 'Perform mensuration and calculation', 'Performing mensuration and calculation', 'common', 43, NULL, 'Select measuring instruments; Carry out measurements and calculation; Maintain measuring instruments.', 4, 'active'),
  (@auto_course_id, 'AUT-C05', 5, 'Utilize workshop facilities and equipment', 'Utilizing workshop facilities and equipment', 'common', 19, NULL, 'Perform pre-operation activities; Use facilities and equipment; Conduct post-operation activities.', 4, 'active'),
  (@auto_course_id, 'AUT-C06', 6, 'Prepare servicing parts and consumables', 'Preparing servicing parts and consumables', 'common', 13, NULL, 'Identify parts and consumables; Retrieve and withdraw parts and consumables; Complete work process.', 4, 'active'),
  (@auto_course_id, 'AUT-C07', 7, 'Prepare vehicle for servicing and releasing', 'Preparing vehicle for servicing and releasing', 'common', 14, NULL, 'Receive vehicle; Prepare vehicle for servicing; Prepare vehicle for releasing.', 4, 'active'),
  (@auto_course_id, 'AUT-CORE01', 1, 'Perform pre-delivery inspection', 'Preforming pre-delivery inspection', 'core', 33, NULL, 'Prepare for pre-delivery inspection; Perform physical and functional inspection; Complete work processes.', 4, 'active'),
  (@auto_course_id, 'AUT-CORE02', 2, 'Perform periodic maintenance of automotive engine', 'Performing periodic maintenance of automotive engine', 'core', 136, NULL, 'Prepare for inspection and service engine; Inspect engine; Service engine; Complete work processes.', 4, 'active'),
  (@auto_course_id, 'AUT-CORE03', 3, 'Perform periodic maintenance of drive train', 'Performing periodic maintenance of drive train', 'core', 28, NULL, 'Perform pre-service preparations; Conduct periodic maintenance of drive trains; Perform post-service activities.', 4, 'active'),
  (@auto_course_id, 'AUT-CORE04', 4, 'Perform periodic maintenance of brake system', 'Performing periodic maintenance of brake system', 'core', 28, NULL, 'Prepare for periodic maintenance of brake system; Carry out periodic maintenance procedures; Complete periodic maintenance procedure.', 4, 'active'),
  (@auto_course_id, 'AUT-CORE05', 5, 'Perform periodic maintenance of suspension system', 'Performing periodic maintenance of suspension system', 'core', 30, NULL, 'Perform pre-periodic maintenance of suspension system; Apply periodic maintenance procedures; Perform work to completion.', 4, 'active'),
  (@auto_course_id, 'AUT-CORE06', 6, 'Perform periodic maintenance of steering system', 'Performing periodic maintenance of steering system', 'core', 24, NULL, 'Perform pre-periodic maintenance of steering system; Apply periodic maintenance procedures; Perform work to completion.', 4, 'active');

INSERT INTO `courses` (
  `course_name`,
  `competency_name`,
  `module_title`,
  `course_code`,
  `hours`,
  `nominal_hours`,
  `description`,
  `learning_outcomes`,
  `status`,
  `course_status`,
  `allow_preview`,
  `preview_content`,
  `require_verification`,
  `verification_type`,
  `branch_id`,
  `schedule_days_per_week`,
  `schedule_days`,
  `session_hours`
) VALUES (
  'JAPANESE LANGUAGE AND CULTURE',
  'Japanese Language and Culture',
  'NTR',
  'JPN-LC',
  350,
  350,
  'This course provides basic knowledge, attitudes and skills in understanding and communicating the Japanese language for Filipinos intending to go to Japan for training, tour or other purposes, including competencies on Japanese culture, writing system, grammar, counting system, expressions, and ordinary conversations.',
  'Learners gain foundations in Japanese culture and work ethics; practice writing systems (Hiragana, Katakana, basic Kanji); master sounds and pronunciation; apply grammar, particles, conjunctions, demonstratives, verbs and adjectives in sentence construction; use counters and honorifics; and participate effectively in ordinary conversations.',
  'active',
  'published',
  0,
  NULL,
  0,
  'email',
  NULL,
  NULL,
  NULL,
  NULL
);
SET @jp_course_id = LAST_INSERT_ID();

INSERT INTO `competencies` (
  `course_id`, `competency_code`, `unit_order`, `competency_name`, `module_title`, `competency_type`, `nominal_hours`, `description`, `learning_outcomes`, `hours_per_session`, `status`
) VALUES
  (@jp_course_id, 'JPN-B01', 1, 'Participate in workplace communication', 'Participating in workplace communication', 'basic', 4, NULL, 'Obtain and convey workplace information; Complete relevant work-related documents; Participate in workplace meetings and discussions.', 4, 'active'),
  (@jp_course_id, 'JPN-B02', 2, 'Work in a team environment', 'Working in a team environment', 'basic', 4, NULL, 'Describe and identify team roles and responsibilities; Work as a team member.', 4, 'active'),
  (@jp_course_id, 'JPN-B03', 3, 'Practice career professionalism', 'Practicing career professionalism', 'basic', 2, NULL, 'Integrate personal objectives with organizational goals; Set and meet work priorities; Maintain professional growth and development.', 4, 'active'),
  (@jp_course_id, 'JPN-B04', 4, 'Practice occupational health and safety', 'Practicing occupational health and safety', 'basic', 2, NULL, 'Evaluate hazards and risks; Control hazards and risks; Maintain occupational health and safety awareness.', 4, 'active'),
  (@jp_course_id, 'JPN-C01', 1, 'Introduce the general features of Japanese Culture', 'Introducing the general features of Japanese culture', 'common', 20, NULL, 'Know Japanese economic industry, arts and political history; Understand religious beliefs and traditions; Know education system; Traditional house manners; Greetings and gratitude; Introductions, parting terms and apologizing; Basic travel inside town; Japanese working ethics and Rajoo Taisoo; Hear testimonies of ex-trainees.', 4, 'active'),
  (@jp_course_id, 'JPN-C02', 2, 'Introduce the Japanese Work Ethics', 'Introducing the Japanese Work Ethics', 'common', 4, NULL, 'Know Japanese work ethics and Rajoo Taisoo; Hear testimonies and personal experiences from ex-Japanese trainees.', 4, 'active'),
  (@jp_course_id, 'JPN-CORE01', 1, 'Practice the basic Japanese Writing System and their Alphabets', 'Practicing the Basic Japanese Writing System and their alphabets', 'core', 54, NULL, 'Read and write Hiragana; Read and write Katakana; Read and write basic Kanji; Differentiate romanization systems HYUJUNSHIKI and HEPBURN.', 4, 'active'),
  (@jp_course_id, 'JPN-CORE02', 2, 'Practice the Japanese Sounds of Alphabets and Pronunciation', 'Practicing the Japanese Sounds of Alphabets and Pronunciation', 'core', 24, NULL, 'Memorize Japanese vowels, sounds and pronunciations; Practice sounds of the alphabets; Omit particles in a sentence without changing meaning.', 4, 'active'),
  (@jp_course_id, 'JPN-CORE03', 3, 'Practice the Japanese Grammar and Application in Sentence Construction', 'Practicing the Japanese Grammar and Application in Sentence Construction', 'core', 88, NULL, 'Memorize common interrogatives and uses; Practice interrogatives; Use particles, conjunctions, demonstratives, adjectives and adverbs; Differentiate "i" and "na" adjectives; Use nouns and pronouns; Apply verb functions; Conjugate verbs; Construct affirmative and negative sentences.', 4, 'active'),
  (@jp_course_id, 'JPN-CORE04', 4, 'Apply the Japanese Counters in a conversation', 'Applying the Japanese counters in a conversation', 'core', 48, NULL, 'Use counters for existence (animate/inanimate); Use counters for location (relative position); Use counters for quantity (Japanese and Chinese origin counters).', 4, 'active'),
  (@jp_course_id, 'JPN-CORE05', 5, 'Perform Japanese Greetings of honorifics in a conversation', 'Performing Japanese Greetings of honorifics in a Conversation', 'core', 40, NULL, 'Know honorifics and humble forms; Distinguish honorific/humble vs polite/plain forms; Manage ambiguous expressions; Apply word repetition rules; Change word order without changing meaning.', 4, 'active'),
  (@jp_course_id, 'JPN-CORE06', 6, 'Participate in Ordinary Conversation', 'Participating in ordinary conversation', 'core', 60, NULL, 'Initiate conversation; Respond to different topics; Respond quickly; Speak while watching listener reaction; Deliver encouragement; Use confirmations; Express assertions; Express appreciation and apology; Complain and reject offers; Ask indirect questions.', 4, 'active');

INSERT INTO `courses` (
  `course_name`,
  `competency_name`,
  `module_title`,
  `course_code`,
  `hours`,
  `nominal_hours`,
  `description`,
  `learning_outcomes`,
  `status`,
  `course_status`,
  `allow_preview`,
  `preview_content`,
  `require_verification`,
  `verification_type`,
  `branch_id`,
  `schedule_days_per_week`,
  `schedule_days`,
  `session_hours`
) VALUES (
  'Dressmaking NC II',
  'Dressmaking',
  'National Certificate Level II',
  'DRS-NCII',
  294,
  294,
  'This course enhances the knowledge, skills and attitudes of trainees in dressmaking according to industry standards, covering basic, common and core competencies including drafting patterns, cutting materials, sewing casual apparel, and applying finishing touches.',
  'Learners participate in workplace communication, work in teams, practice career professionalism, and follow occupational health and safety; They carry out measurements and calculations, apply quality standards, perform basic maintenance, and set up and operate machines; They draft and cut patterns for casual apparel, prepare and cut materials, sew casual apparel, and apply finishing touches to meet industry requirements.',
  'active',
  'published',
  0,
  NULL,
  0,
  'email',
  NULL,
  NULL,
  NULL,
  NULL
);
SET @drs_course_id = LAST_INSERT_ID();

INSERT INTO `competencies` (
  `course_id`, `competency_code`, `unit_order`, `competency_name`, `module_title`, `competency_type`, `nominal_hours`, `description`, `learning_outcomes`, `hours_per_session`, `status`
) VALUES
  (@drs_course_id, 'DRS-B01', 1, 'Participate in workplace communication', 'Participating in workplace communication', 'basic', 4, NULL, 'Obtain and convey workplace information; Complete relevant work-related documents; Participate in workplace meetings and discussions.', 4, 'active'),
  (@drs_course_id, 'DRS-B02', 2, 'Work in a team environment', 'Working in a team environment', 'basic', 4, NULL, 'Describe and identify team roles and responsibilities; Work as a team member.', 4, 'active'),
  (@drs_course_id, 'DRS-B03', 3, 'Practice career professionalism', 'Practicing career professionalism', 'basic', 2, NULL, 'Integrate personal objectives with organizational goals; Set and meet work priorities; Maintain professional growth and development.', 4, 'active'),
  (@drs_course_id, 'DRS-B04', 4, 'Practice occupational health and safety', 'Practicing occupational health and safety', 'basic', 2, NULL, 'Evaluate hazards and risks; Control hazards and risks; Maintain occupational health and safety awareness.', 4, 'active'),
  (@drs_course_id, 'DRS-C01', 1, 'Carry Out Measurements and Calculations', 'Carrying-out Measurements and Calculations', 'common', 4, NULL, 'Obtain measurements; Perform simple calculations; Estimate appropriate quantities.', 4, 'active'),
  (@drs_course_id, 'DRS-C02', 2, 'Apply Quality Standards', 'Applying Quality Standards', 'common', 5, NULL, 'Assess own work based on workplace standards; Assess quality of component parts and take corrective actions; Measure parts using appropriate instruments; Record information per procedures; Study causes of quality deviation and recommend preventive actions.', 4, 'active'),
  (@drs_course_id, 'DRS-C03', 3, 'Perform Basic Maintenance', 'Performing Basic Maintenance', 'common', 3, NULL, 'Operate machine and assess its performance; Clean and lubricate machine; Check machine operation.', 4, 'active'),
  (@drs_course_id, 'DRS-C04', 4, 'Set Up and Operate Machines', 'Setting-up and Operating Machines', 'common', 5, NULL, 'Set machine; Conduct sample run; Test machine output; Re-adjust settings to meet requirements; Maintain records and prepare reports.', 4, 'active'),
  (@drs_course_id, 'DRS-CORE01', 1, 'Draft and Cut Pattern for Casual Apparel', 'Drafting and Cutting Pattern for Casual Apparel', 'core', 80, NULL, 'Plan garment design; Take client body measurements; Draft basic/block pattern; Manipulate pattern; Cut final pattern.', 4, 'active'),
  (@drs_course_id, 'DRS-CORE02', 2, 'Prepare and Cut Materials for Casual Apparel', 'Preparing and Cutting Materials for Casual Apparel', 'core', 40, NULL, 'Prepare materials (fabric); Lay-out and mark pattern on materials; Cut materials.', 4, 'active'),
  (@drs_course_id, 'DRS-CORE03', 3, 'Sew Casual Apparel', 'Sewing casual apparel', 'core', 80, NULL, 'Prepare cut parts; Prepare sewing machine for operation; Sew and assemble garment parts; Alter completed garments.', 4, 'active'),
  (@drs_course_id, 'DRS-CORE04', 4, 'Apply Finishing Touches on Casual Apparel', 'Applying Finishing Touches on Casual Apparel', 'core', 40, NULL, 'Apply finishing touches; Trim excess threads; Press finished garment; Package the finished garment.', 4, 'active');

INSERT INTO `courses` (
  `course_name`,
  `competency_name`,
  `module_title`,
  `course_code`,
  `hours`,
  `nominal_hours`,
  `description`,
  `learning_outcomes`,
  `status`,
  `course_status`,
  `allow_preview`,
  `preview_content`,
  `require_verification`,
  `verification_type`,
  `branch_id`,
  `schedule_days_per_week`,
  `schedule_days`,
  `session_hours`
) VALUES (
  'BREAD AND PASTRY PRODUCTION',
  'Bread and Pastry Production',
  'National Certificate Level II',
  'BPP-NCII',
  160,
  160,
  'This course enhances knowledge, skills and attitudes to clean equipment, tools and utensils and to prepare, portion, plate and serve pastries, breads and other dessert items for guests in hospitality and related operations.',
  'Learners complete basic workplace competencies; observe hygiene; perform computer operations; practice workplace safety; deliver effective customer service; and produce bakery, pastry, cakes, petits fours, and other desserts to industry standards.',
  'active',
  'published',
  0,
  NULL,
  0,
  'email',
  NULL,
  NULL,
  NULL,
  NULL
);
SET @bpp_course_id = LAST_INSERT_ID();

INSERT INTO `competencies` (
  `course_id`, `competency_code`, `unit_order`, `competency_name`, `module_title`, `competency_type`, `nominal_hours`, `description`, `learning_outcomes`, `hours_per_session`, `status`
) VALUES
  (@bpp_course_id, 'BPP-B01', 1, 'Participate in workplace communication', 'Participating in workplace communication', 'basic', 6, NULL, 'Obtain and convey workplace information; Perform duties following workplace instructions; Complete relevant work-related documents.', 4, 'active'),
  (@bpp_course_id, 'BPP-B02', 2, 'Work in a team environment', 'Working in a team environment', 'basic', 3, NULL, 'Describe team role and scope; Identify one’s role and responsibility; Work as a team member.', 4, 'active'),
  (@bpp_course_id, 'BPP-B03', 3, 'Solve/address general workplace problems', 'Solving/addressing general workplace problems', 'basic', 3, NULL, 'Identify routine problems; Look for solutions to routine problems; Recommend solutions to problems.', 4, 'active'),
  (@bpp_course_id, 'BPP-B04', 4, 'Develop career and life decisions', 'Developing career and life decisions', 'basic', 3, NULL, 'Manage one’s emotion; Develop reflective practice; Boost self-confidence and self-regulation.', 4, 'active'),
  (@bpp_course_id, 'BPP-B05', 5, 'Contribute to workplace innovation', 'Contributing to workplace innovation', 'basic', 3, NULL, 'Identify opportunities to do things better; Discuss and develop ideas with others; Integrate ideas for change in the workplace.', 4, 'active'),
  (@bpp_course_id, 'BPP-B06', 6, 'Present relevant information', 'Present relevant information', 'basic', 8, NULL, 'Gather data/information; Assess gathered data/information; Record and present information.', 4, 'active'),
  (@bpp_course_id, 'BPP-B07', 7, 'Practice occupational health and safety procedures', 'Practicing occupational health and safety procedures', 'basic', 4, NULL, 'Identify OSH compliance requirements; Prepare OSH requirements for compliance; Perform tasks per OSH policies and procedures.', 4, 'active'),
  (@bpp_course_id, 'BPP-B08', 8, 'Exercise efficient and effective sustainable practices in the workplace', 'Exercising efficient and effective sustainable practices in the workplace', 'basic', 3, NULL, 'Identify efficiency and effectiveness of resource utilization; Determine causes of inefficiency/ineffectiveness; Convey inefficient and ineffective environmental practices.', 4, 'active'),
  (@bpp_course_id, 'BPP-B09', 9, 'Practice entrepreneurial skills in the workplace', 'Practicing entrepreneurial skills in the workplace', 'basic', 4, NULL, 'Apply entrepreneurial workplace best practices; Communicate entrepreneurial workplace best practices; Implement cost-effective operations.', 4, 'active'),
  (@bpp_course_id, 'BPP-C01', 1, 'Develop and Updated Industry Knowledge', 'Developing and Updating Industry Knowledge', 'common', 2, NULL, 'Seek information on the industry; Update industry knowledge.', 4, 'active'),
  (@bpp_course_id, 'BPP-C02', 2, 'Observe Workplace Hygiene Procedures', 'Observing Workplace Hygiene Procedures', 'common', 4, NULL, 'Follow hygienic procedures; Identify and present hygienic risks.', 4, 'active'),
  (@bpp_course_id, 'BPP-C03', 3, 'Perform Computer Operations', 'Performing Computer Operations', 'common', 4, NULL, 'Plan and prepare for tasks; Input data; Access information; Produce outputs using computer systems.', 4, 'active'),
  (@bpp_course_id, 'BPP-C04', 4, 'Perform Workplace and Safety Practices', 'Performing Workplace and Safety Practices', 'common', 4, NULL, 'Follow workplace and safety practices; Deal with emergency situations; Maintain safe personal preventive standards.', 4, 'active'),
  (@bpp_course_id, 'BPP-C05', 5, 'Provide Effective Customer Service', 'Providing Effective Customer Service', 'common', 4, NULL, 'Greet customers; Deliver service; Identify customer needs; Handle queries via phone, fax, internet; Handle complaints, evaluation and recommendations.', 4, 'active'),
  (@bpp_course_id, 'BPP-CORE01', 1, 'Prepare and produce bakery products', 'Preparing and producing bakery products', 'core', 25, NULL, 'Prepare bakery products; Decorate and present bakery products; Store bakery products.', 4, 'active'),
  (@bpp_course_id, 'BPP-CORE02', 2, 'Prepare and produce pastry products', 'Preparing and producing pastry products', 'core', 25, NULL, 'Prepare pastry products; Decorate and present pastry products; Store pastry products.', 4, 'active'),
  (@bpp_course_id, 'BPP-CORE03', 3, 'Prepare and present gateaux, tortes and cakes', 'Preparing and presenting gateaux, tortes and cakes', 'core', 25, NULL, 'Prepare sponge and cakes; Prepare and use fillings; Decorate cakes; Present cakes; Store cakes.', 4, 'active'),
  (@bpp_course_id, 'BPP-CORE04', 4, 'Prepare and display petits fours', 'Preparing and displaying petits fours', 'core', 20, NULL, 'Prepare iced, fresh, marzipan and caramelized petits fours; Display petits fours; Store petits fours.', 4, 'active'),
  (@bpp_course_id, 'BPP-CORE05', 5, 'Prepare and serve other types of desserts', 'Preparing and serving other types of desserts', 'core', 10, NULL, 'Prepare other types of desserts; Plan, prepare and conduct a dessert trolley presentation; Store and package desserts.', 4, 'active');

INSERT INTO `courses` (
  `course_name`,
  `competency_name`,
  `module_title`,
  `course_code`,
  `hours`,
  `nominal_hours`,
  `description`,
  `learning_outcomes`,
  `status`,
  `course_status`,
  `allow_preview`,
  `preview_content`,
  `require_verification`,
  `verification_type`,
  `branch_id`,
  `schedule_days_per_week`,
  `schedule_days`,
  `session_hours`
) VALUES (
  'Tailoring NC II',
  'Tailoring',
  'National Certificate Level II',
  'TLR-NCII',
  294,
  294,
  'This course enhances knowledge, skills and attitudes in tailoring in accordance with industry standards, covering basic, common and core competencies including drafting and cutting patterns, preparing and cutting materials, sewing casual apparel, and applying finishing touches.',
  'Learners complete basic workplace competencies; carry out measurements and calculations; apply quality standards; perform basic maintenance; set up and operate machines; and perform core tailoring tasks such as drafting and cutting patterns, preparing and cutting materials, sewing casual apparel, and finishing garments.',
  'active',
  'published',
  0,
  NULL,
  0,
  'email',
  NULL,
  NULL,
  NULL,
  NULL
);
SET @tlr_course_id = LAST_INSERT_ID();

INSERT INTO `competencies` (
  `course_id`, `competency_code`, `unit_order`, `competency_name`, `module_title`, `competency_type`, `nominal_hours`, `description`, `learning_outcomes`, `hours_per_session`, `status`
) VALUES
  (@tlr_course_id, 'TLR-B01', 1, 'Participate in workplace communication', 'Participating in workplace communication', 'basic', 6, NULL, 'Obtain and convey workplace information; Perform duties following workplace instructions; Complete relevant work-related documents.', 4, 'active'),
  (@tlr_course_id, 'TLR-B02', 2, 'Work in a team environment', 'Working in a team environment', 'basic', 3, NULL, 'Describe team role and scope; Identify one’s role and responsibility within team; Work as a team member.', 4, 'active'),
  (@tlr_course_id, 'TLR-B03', 3, 'Solve/address general workplace problems', 'Solving/addressing general workplace problems', 'basic', 3, NULL, 'Identify routine problems; Look for solutions to routine problems; Recommend solutions to problems.', 4, 'active'),
  (@tlr_course_id, 'TLR-B04', 4, 'Develop career and life decisions', 'Developing career and life decisions', 'basic', 3, NULL, 'Manage one’s emotion; Develop reflective practice; Boost self-confidence and self-regulation.', 4, 'active'),
  (@tlr_course_id, 'TLR-B05', 5, 'Contribute to workplace innovation', 'Contributing to workplace innovation', 'basic', 3, NULL, 'Identify opportunities to do things better; Discuss and develop ideas with others; Integrate ideas for change in the workplace.', 4, 'active'),
  (@tlr_course_id, 'TLR-B06', 6, 'Present relevant information', 'Present relevant information', 'basic', 8, NULL, 'Gather data/information; Assess gathered data/information; Record and present information.', 4, 'active'),
  (@tlr_course_id, 'TLR-B07', 7, 'Practice occupational health and safety procedures', 'Practicing occupational health and safety procedures', 'basic', 4, NULL, 'Identify OSH compliance requirements; Prepare OSH requirements for compliance; Perform tasks in accordance with OSH policies and procedures.', 4, 'active'),
  (@tlr_course_id, 'TLR-B08', 8, 'Exercise efficient and effective sustainable practices in the workplace', 'Exercising efficient and effective sustainable practices in the workplace', 'basic', 3, NULL, 'Identify efficiency and effectiveness of resource utilization; Determine causes of inefficiency/ineffectiveness; Convey inefficient and ineffective environmental practices.', 4, 'active'),
  (@tlr_course_id, 'TLR-B09', 9, 'Practice entrepreneurial skills in the workplace', 'Practicing entrepreneurial skills in the workplace', 'basic', 4, NULL, 'Apply entrepreneurial workplace best practices; Communicate entrepreneurial workplace best practices; Implement cost-effective operations.', 4, 'active'),
  (@tlr_course_id, 'TLR-C01', 1, 'Carry Out Measurements and Calculations', 'Carrying-out Measurements and Calculations', 'common', 4, NULL, 'Obtain measurements; Perform simple calculations; Estimate appropriate quantities.', 4, 'active'),
  (@tlr_course_id, 'TLR-C02', 2, 'Apply Quality Standards', 'Applying Quality Standards', 'common', 5, NULL, 'Assess own work based on workplace standards; Assess quality of component parts and take corrective actions; Measure parts using appropriate instruments; Record information per procedures; Study causes of quality deviation and recommend preventive actions.', 4, 'active'),
  (@tlr_course_id, 'TLR-C03', 3, 'Perform Basic Maintenance', 'Performing Basic Maintenance', 'common', 3, NULL, 'Operate machine and assess its performance; Clean and lubricate machine; Check machine operation.', 4, 'active'),
  (@tlr_course_id, 'TLR-C04', 4, 'Set Up and Operate Machines', 'Setting-up and Operating Machines', 'common', 5, NULL, 'Set machine; Conduct sample run; Test machine output; Re-adjust machine settings to meet requirements; Maintain records and prepare reports.', 4, 'active'),
  (@tlr_course_id, 'TLR-CORE01', 1, 'Draft and Cut Pattern for Casual Apparel', 'Drafting and Cutting Pattern for Casual Apparel', 'core', 80, NULL, 'Plan garment design; Take client body measurements; Draft basic/block pattern; Manipulate pattern; Cut final pattern.', 4, 'active'),
  (@tlr_course_id, 'TLR-CORE02', 2, 'Prepare and Cut Materials for Casual Apparel', 'Preparing and Cutting Materials for Casual Apparel', 'core', 40, NULL, 'Prepare materials (fabric); Lay-out and mark pattern on materials; Cut materials.', 4, 'active'),
  (@tlr_course_id, 'TLR-CORE03', 3, 'Sew Casual Apparel', 'Sewing casual apparel', 'core', 80, NULL, 'Prepare cut parts; Prepare sewing machine for operation; Sew and assemble garment parts; Alter completed garments.', 4, 'active'),
  (@tlr_course_id, 'TLR-CORE04', 4, 'Apply Finishing Touches on Casual Apparel', 'Applying Finishing Touches on Casual Apparel', 'core', 40, NULL, 'Apply finishing touches; Trim excess threads; Press finished garment; Package the finished garment.', 4, 'active');

INSERT INTO `courses` (
  `course_name`,
  `competency_name`,
  `module_title`,
  `course_code`,
  `hours`,
  `nominal_hours`,
  `description`,
  `learning_outcomes`,
  `status`,
  `course_status`,
  `allow_preview`,
  `preview_content`,
  `require_verification`,
  `verification_type`,
  `branch_id`,
  `schedule_days_per_week`,
  `schedule_days`,
  `session_hours`
) VALUES (
  'AGRICULTURAL CROPS PRODUCTION',
  'Agricultural Crops Production',
  'National Certificate Level II',
  'ACP-NCII',
  355,
  355,
  'This course enhances knowledge, desirable attitudes and skills for nursery operations, planting crops, caring and maintaining crops, and carrying out harvest and postharvest operations, including workplace communication, teamwork, safety in tools and equipment, housekeeping, and processing of farm wastes.',
  'Learners complete basic competencies (communication, teamwork, problem-solving, career and life decisions, innovation, presenting information, OSH, sustainability, and entrepreneurship); common competencies (safety measures, farm tools and equipment, estimation and calculation, processing farm wastes, record keeping); and core competencies (nursery operations, planting crops, caring and maintaining crops, harvest and postharvest operations).',
  'active',
  'published',
  0,
  NULL,
  0,
  'email',
  NULL,
  NULL,
  NULL,
  NULL
);
SET @acp_course_id = LAST_INSERT_ID();

INSERT INTO `competencies` (
  `course_id`, `competency_code`, `unit_order`, `competency_name`, `module_title`, `competency_type`, `nominal_hours`, `description`, `learning_outcomes`, `hours_per_session`, `status`
) VALUES
  (@acp_course_id, 'ACP-B01', 1, 'Participate in workplace communication', 'Participating in workplace communication', 'basic', 4, NULL, 'Obtain and convey workplace information; Participate in workplace meetings and discussions; Complete relevant work-related documents.', 4, 'active'),
  (@acp_course_id, 'ACP-B02', 2, 'Work in a team environment', 'Working in a team environment', 'basic', 4, NULL, 'Acknowledge Gender and Development and other laws; Describe and identify team role and responsibility; Work as a team member.', 4, 'active'),
  (@acp_course_id, 'ACP-B03', 3, 'Solve/address general workplace problems', 'Solving/addressing general workplace problems', 'basic', 4, NULL, 'Identify routine problems; Look for solutions to routine problems; Recommend solutions to problems.', 4, 'active'),
  (@acp_course_id, 'ACP-B04', 4, 'Develop career and life decisions', 'Developing career and life decisions', 'basic', 4, NULL, 'Manage one’s emotion; Develop reflective practice; Boost self-confidence and self-regulation.', 4, 'active'),
  (@acp_course_id, 'ACP-B05', 5, 'Contribute to workplace innovation', 'Contributing to workplace innovation', 'basic', 4, NULL, 'Identify opportunities to do things better; Discuss and develop ideas with others; Integrate ideas for change in the workplace.', 4, 'active'),
  (@acp_course_id, 'ACP-B06', 6, 'Present relevant information', 'Present relevant information', 'basic', 4, NULL, 'Gather data/information; Assess gathered data/information; Record and present information.', 4, 'active'),
  (@acp_course_id, 'ACP-B07', 7, 'Practice occupational health and safety procedures', 'Practicing occupational health and safety procedures', 'basic', 4, NULL, 'Identify hazards and risks; Evaluate hazards and risks; Control hazards and risks; Maintain occupational health and safety awareness.', 4, 'active'),
  (@acp_course_id, 'ACP-B08', 8, 'Exercise efficient and effective sustainable practices in the workplace', 'Exercising efficient and effective sustainable practices in the workplace', 'basic', 4, NULL, 'Identify efficiency and effectiveness of resource utilization; Determine causes of inefficiency/ineffectiveness; Convey inefficient and ineffective environmental practices.', 4, 'active'),
  (@acp_course_id, 'ACP-B09', 9, 'Practice entrepreneurial skills in the workplace', 'Practicing entrepreneurial skills in the workplace', 'basic', 5, NULL, 'Apply entrepreneurial workplace best practices; Communicate entrepreneurial workplace best practices; Implement cost-effective operations.', 4, 'active'),
  (@acp_course_id, 'ACP-C01', 1, 'Apply Safety Measures in Farm Operations', 'Applying Safety Measures in Farm Operations', 'common', 12, NULL, 'Apply appropriate safety measures while working in farm; Safe keep/dispose tools, materials and outfit.', 4, 'active'),
  (@acp_course_id, 'ACP-C02', 2, 'Use farm tools and equipment', 'Using farm tools and equipment', 'common', 12, NULL, 'Prepare and use farm tools; Prepare and operate farm equipment; Perform preventive maintenance procedures/practices.', 4, 'active'),
  (@acp_course_id, 'ACP-C03', 3, 'Perform estimation and basic calculation', 'Performing estimation and basic calculation', 'common', 10, NULL, 'Perform estimation; Perform basic workplace calculation.', 4, 'active'),
  (@acp_course_id, 'ACP-C04', 4, 'Process farm wastes', 'Processing farm wastes', 'common', 12, NULL, 'Collect farm wastes; Identify and segregate wastes; Treat and process farm wastes; Perform housekeeping.', 4, 'active'),
  (@acp_course_id, 'ACP-C05', 5, 'Perform record keeping', 'Performing record keeping', 'common', 12, NULL, 'Carry out inventory activities; Maintain production records; Prepare financial records.', 4, 'active'),
  (@acp_course_id, 'ACP-CORE01', 1, 'Perform nursery operations', 'Performing nursery operations', 'core', 65, NULL, 'Prepare nursery tools, farm implements and simple equipment; Maintain nursery facilities; Handle seeds/planting material; Prepare growing media; Conduct propagation activities.', 4, 'active'),
  (@acp_course_id, 'ACP-CORE02', 2, 'Plant crops', 'Planting crops', 'core', 65, NULL, 'Prepare land for planting; Conduct field lay-out; Dig holes; Perform direct seeding; Transplant seedlings.', 4, 'active'),
  (@acp_course_id, 'ACP-CORE03', 3, 'Care and maintain crops', 'Caring and maintaining crops', 'core', 65, NULL, 'Apply pest control measures; Apply fertilizer; Water crops; Perform pruning; Perform physical growth enhancing practices.', 4, 'active'),
  (@acp_course_id, 'ACP-CORE04', 4, 'Carry-out harvest and postharvest operations', 'Carrying-out harvest and postharvest operations', 'core', 65, NULL, 'Perform pre-harvest operations; Perform harvesting activities; Perform postharvest operations; Monitor storage pests and diseases.', 4, 'active');

INSERT INTO `courses` (
  `course_name`,
  `competency_name`,
  `module_title`,
  `course_code`,
  `hours`,
  `nominal_hours`,
  `description`,
  `learning_outcomes`,
  `status`,
  `course_status`,
  `allow_preview`,
  `preview_content`,
  `require_verification`,
  `verification_type`,
  `branch_id`,
  `schedule_days_per_week`,
  `schedule_days`,
  `session_hours`
) VALUES (
  'DRIVING NC II',
  'Driving',
  'National Certificate Level II',
  'DRV-NCII',
  154,
  154,
  'This course equips individuals with the attitudes, knowledge and skills required to perform tasks of a light vehicle driver per industry standards and regulatory requirements (LTO and other agencies), covering core competencies on vehicle maintenance and servicing, driving light vehicles, observing traffic rules and regulations, and implementing and coordinating accident/emergency procedures, alongside basic and common workplace competencies.',
  'Learners complete basic workplace competencies; apply sealants/adhesives; perform shop maintenance and mensuration/calculation; read, interpret and apply specifications and manuals; use and apply lubricants/coolants; perform tool handling and shop upkeep; and move and position vehicles including theoretical driving. They carry out minor vehicle maintenance and servicing, drive light vehicles, observe traffic rules and regulations, and implement and coordinate accident/emergency procedures.',
  'active',
  'published',
  0,
  NULL,
  0,
  'email',
  NULL,
  NULL,
  NULL,
  NULL
);
SET @drv_course_id = LAST_INSERT_ID();

INSERT INTO `competencies` (
  `course_id`, `competency_code`, `unit_order`, `competency_name`, `module_title`, `competency_type`, `nominal_hours`, `description`, `learning_outcomes`, `hours_per_session`, `status`
) VALUES
  (@drv_course_id, 'DRV-B01', 1, 'Participate in workplace communication', 'Participating in workplace communication', 'basic', 4, NULL, 'Obtain and convey workplace information; Participate in workplace meetings and discussions; Complete relevant work-related documents.', 4, 'active'),
  (@drv_course_id, 'DRV-B02', 2, 'Work in a team environment', 'Working in a team environment', 'basic', 4, NULL, 'Acknowledge Gender and Development and other laws; Describe and identify team role and responsibility; Work as a team member.', 4, 'active'),
  (@drv_course_id, 'DRV-B03', 3, 'Solve/address general workplace problems', 'Solving/addressing general workplace problems', 'basic', 4, NULL, 'Identify routine problems; Look for solutions to routine problems; Recommend solutions to problems.', 4, 'active'),
  (@drv_course_id, 'DRV-B04', 4, 'Develop career and life decisions', 'Developing career and life decisions', 'basic', 4, NULL, 'Manage one’s emotion; Develop reflective practice; Boost self-confidence and self-regulation.', 4, 'active'),
  (@drv_course_id, 'DRV-B05', 5, 'Contribute to workplace innovation', 'Contributing to workplace innovation', 'basic', 4, NULL, 'Identify opportunities to do things better; Discuss and develop ideas with others; Integrate ideas for change in the workplace.', 4, 'active'),
  (@drv_course_id, 'DRV-B06', 6, 'Present relevant information', 'Present relevant information', 'basic', 4, NULL, 'Gather data/information; Assess gathered data/information; Record and present information.', 4, 'active'),
  (@drv_course_id, 'DRV-B07', 7, 'Practice occupational health and safety procedures', 'Practicing occupational health and safety procedures', 'basic', 4, NULL, 'Identify hazards and risks; Evaluate hazards and risks; Control hazards and risks; Maintain occupational health and safety awareness.', 4, 'active'),
  (@drv_course_id, 'DRV-B08', 8, 'Exercise efficient and effective sustainable practices in the workplace', 'Exercising efficient and effective sustainable practices in the workplace', 'basic', 4, NULL, 'Identify efficiency and effectiveness of resource utilization; Determine causes of inefficiency/ineffectiveness; Convey inefficient and ineffective environmental practices.', 4, 'active'),
  (@drv_course_id, 'DRV-B09', 9, 'Practice entrepreneurial skills in the workplace', 'Practicing entrepreneurial skills in the workplace', 'basic', 5, NULL, 'Apply entrepreneurial workplace best practices; Communicate entrepreneurial workplace best practices; Implement cost-effective operations.', 4, 'active'),
  (@drv_course_id, 'DRV-C01', 1, 'Apply appropriate sealant/adhesive', 'Applying appropriate sealant/adhesive', 'common', 3, NULL, 'Identify appropriate sealant/adhesive; Prepare surface for application; Store/dispose used sealant/adhesive.', 4, 'active'),
  (@drv_course_id, 'DRV-C02', 2, 'Perform shop maintenance', 'Performing shop maintenance', 'common', 4, NULL, 'Select measuring instruments; Carry out measurement and calculation; Maintain measuring instruments.', 4, 'active'),
  (@drv_course_id, 'DRV-C03', 3, 'Read, Interpret and Apply Specifications and Manual', 'Reading, Interpreting and Applying Specifications and Manual', 'common', 4, NULL, 'Identify/access manuals and interpret data and specifications; Apply information accessed in manual; Store manual.', 4, 'active'),
  (@drv_course_id, 'DRV-C04', 4, 'Use and apply lubricant/coolant', 'Using and applying lubricant/coolant', 'common', 3, NULL, 'Identify types of lubricant/coolant; Use and apply lubricant/coolant.', 4, 'active'),
  (@drv_course_id, 'DRV-C05', 5, 'Perform Mensuration and Calculation', 'Performing Mensuration and Calculation', 'common', 3, NULL, 'Inspect/clean tools and shop equipment; Store/arrange tools and equipment; Dispose waste/used lubricants; Report damaged tools/equipment.', 4, 'active'),
  (@drv_course_id, 'DRV-C06', 6, 'Move and Position Vehicle', 'Theoretical driving; Moving and positioning vehicle', 'common', 20, NULL, 'Gain required knowledge for introduction to driving and relevant LTO regulations; Prepare vehicle for driving; Move and position vehicle; Check the vehicle.', 4, 'active'),
  (@drv_course_id, 'DRV-CORE01', 1, 'Carry out minor vehicle maintenance and servicing', 'Carrying out minor vehicle maintenance and servicing', 'core', 30, NULL, 'Clean vehicle unit; Maintain and service the vehicle system.', 4, 'active'),
  (@drv_course_id, 'DRV-CORE02', 2, 'Drive light vehicles', 'Driving light vehicles', 'core', 30, NULL, 'Perform light vehicle pre-starting and warm-up; Drive light vehicles; Monitor and maintain vehicle performance.', 4, 'active'),
  (@drv_course_id, 'DRV-CORE03', 3, 'Observe traffic rules and regulations', 'Observing traffic rules and regulations', 'core', 10, NULL, 'Observe traffic signs and road markers; Obey traffic rules and regulations; Practice courtesy; Respect traffic enforcers and other traffic management units.', 4, 'active'),
  (@drv_course_id, 'DRV-CORE04', 4, 'Implement and coordinate accident/emergency procedures', 'Implementing and coordinating accident/emergency procedures', 'core', 10, NULL, 'Respond to emergencies; Arrange follow-up support and assistance.', 4, 'active');
INSERT INTO `users` (
  `user_id`, `role`, `branch_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `email`, `email_verified`, `student_id`, `phone_verified`, `contact_number`, `password`, `profile_picture`, `force_password_change`, `status`
) VALUES (
  '11204345', 'admin', NULL, 'Admin', NULL, 'User', NULL, 'admin@bts.gov.ph', 1, NULL, 0, NULL, '$2y$10$XKt00Jt3bRURLBArm27/XenG2oolNo8PnYolS87515FuOJgcP6hLm', NULL, 1, 'active'
);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
