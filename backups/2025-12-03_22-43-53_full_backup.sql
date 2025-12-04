-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: elms_bts
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainer_id` varchar(20) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`trainer_id`),
  CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activities`
--

LOCK TABLES `activities` WRITE;
/*!40000 ALTER TABLE `activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_submissions`
--

DROP TABLE IF EXISTS `activity_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_id` int(11) DEFAULT NULL,
  `guest_id` varchar(50) DEFAULT NULL,
  `submission_text` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `score` decimal(5,2) DEFAULT NULL,
  `graded_by` varchar(50) DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_id` (`activity_id`),
  KEY `trainee_id` (`guest_id`),
  CONSTRAINT `activity_submissions_ibfk_1` FOREIGN KEY (`activity_id`) REFERENCES `topic_activities` (`id`),
  CONSTRAINT `activity_submissions_ibfk_2` FOREIGN KEY (`guest_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_submissions`
--

LOCK TABLES `activity_submissions` WRITE;
/*!40000 ALTER TABLE `activity_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `posted_by` varchar(20) DEFAULT NULL,
  `date_posted` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `posted_by` (`posted_by`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `batch_assignment_status`
--

DROP TABLE IF EXISTS `batch_assignment_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `batch_assignment_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL,
  `batch_name` varchar(100) NOT NULL,
  `trainer_id` varchar(50) DEFAULT NULL,
  `trainee_id` varchar(50) DEFAULT NULL,
  `status` enum('active','archived') NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_trainer` (`trainer_id`),
  KEY `idx_trainee` (`trainee_id`),
  KEY `idx_course_batch` (`course_code`,`batch_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `batch_assignment_status`
--

LOCK TABLES `batch_assignment_status` WRITE;
/*!40000 ALTER TABLE `batch_assignment_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `batch_assignment_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `batch_assignments`
--

DROP TABLE IF EXISTS `batch_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `batch_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainee_id` varchar(20) NOT NULL,
  `trainer_id` varchar(50) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `batch_name` text NOT NULL,
  `date_assigned` datetime DEFAULT current_timestamp(),
  `assigned_by` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `trainee_id` (`trainee_id`),
  KEY `course_code` (`course_code`),
  KEY `assigned_by` (`assigned_by`),
  KEY `batch_id` (`batch_name`(768)),
  KEY `trainer_id` (`trainer_id`),
  CONSTRAINT `batch_assignments_ibfk_1` FOREIGN KEY (`trainee_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `batch_assignments_ibfk_2` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  CONSTRAINT `batch_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `batch_assignments_ibfk_5` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `batch_assignments`
--

LOCK TABLES `batch_assignments` WRITE;
/*!40000 ALTER TABLE `batch_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `batch_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(255) NOT NULL,
  `branch_code` varchar(50) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_code` (`branch_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certificates`
--

DROP TABLE IF EXISTS `certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainee_id` varchar(50) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `certificate_number` varchar(100) NOT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `final_grade` decimal(5,2) DEFAULT NULL,
  `certificate_path` varchar(500) DEFAULT NULL,
  `status` enum('active','expired','revoked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_number` (`certificate_number`),
  KEY `trainee_id` (`trainee_id`),
  KEY `course_code` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificates`
--

LOCK TABLES `certificates` WRITE;
/*!40000 ALTER TABLE `certificates` DISABLE KEYS */;
/*!40000 ALTER TABLE `certificates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `competencies`
--

DROP TABLE IF EXISTS `competencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `competencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `competency_code` varchar(50) NOT NULL,
  `unit_order` int(11) unsigned DEFAULT NULL,
  `competency_name` varchar(255) NOT NULL,
  `module_title` varchar(255) NOT NULL,
  `competency_type` enum('basic','common','core') NOT NULL,
  `nominal_hours` int(11) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `learning_outcomes` mediumtext DEFAULT NULL,
  `hours_per_session` int(11) NOT NULL,
  `status` enum('active','archived') DEFAULT 'active',
  `date_created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `competency_code` (`competency_code`),
  KEY `competencies_ibfk_1` (`course_id`),
  KEY `idx_comp_course_type_order` (`course_id`,`competency_type`,`unit_order`),
  CONSTRAINT `competencies_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `competencies`
--

LOCK TABLES `competencies` WRITE;
/*!40000 ALTER TABLE `competencies` DISABLE KEYS */;
/*!40000 ALTER TABLE `competencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_assignments`
--

DROP TABLE IF EXISTS `course_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainer_id` varchar(20) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `date_assigned` datetime DEFAULT current_timestamp(),
  `assigned_by` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_assignment` (`trainer_id`,`course_code`),
  KEY `course_code` (`course_code`),
  KEY `assigned_by` (`assigned_by`),
  CONSTRAINT `course_assignments_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `course_assignments_ibfk_2` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  CONSTRAINT `course_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_assignments`
--

LOCK TABLES `course_assignments` WRITE;
/*!40000 ALTER TABLE `course_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_batches`
--

DROP TABLE IF EXISTS `course_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL,
  `batch_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `trainer_id` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_batch` (`course_code`,`batch_name`),
  KEY `created_by` (`created_by`),
  KEY `trainer_id` (`trainer_id`),
  CONSTRAINT `course_batches_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  CONSTRAINT `course_batches_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `course_batches_ibfk_3` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_batches`
--

LOCK TABLES `course_batches` WRITE;
/*!40000 ALTER TABLE `course_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_materials`
--

DROP TABLE IF EXISTS `course_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `date_created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_code` (`course_code`),
  KEY `competency_id` (`competency_id`),
  CONSTRAINT `course_materials_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `course_materials_ibfk_2` FOREIGN KEY (`competency_id`) REFERENCES `competencies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_materials`
--

LOCK TABLES `course_materials` WRITE;
/*!40000 ALTER TABLE `course_materials` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_topics`
--

DROP TABLE IF EXISTS `course_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL,
  `competency_id` varchar(255) NOT NULL,
  `topic_name` varchar(255) NOT NULL,
  `topic_description` text DEFAULT NULL,
  `learning_objectives` text DEFAULT NULL,
  `added_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_code` (`course_code`),
  KEY `added_by` (`added_by`),
  CONSTRAINT `course_topics_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  CONSTRAINT `course_topics_ibfk_2` FOREIGN KEY (`added_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_topics`
--

LOCK TABLES `course_topics` WRITE;
/*!40000 ALTER TABLE `course_topics` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_topics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `session_hours` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_code` (`course_code`),
  KEY `idx_courses_verification` (`require_verification`,`verification_type`),
  KEY `idx_courses_status` (`course_status`),
  KEY `idx_courses_branch` (`branch_id`),
  KEY `idx_courses_module_title` (`module_title`),
  KEY `idx_courses_competency_name` (`competency_name`),
  KEY `idx_courses_nominal_hours` (`nominal_hours`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `dropped_by` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trainee_id` (`trainee_id`),
  KEY `course_code` (`course_code`),
  KEY `processed_by` (`processed_by`),
  KEY `idx_enrollments_status` (`status`),
  KEY `idx_enrollments_progress` (`progress_percentage`),
  KEY `idx_enrollments_verification_status` (`verification_status`),
  CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`trainee_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  CONSTRAINT `enrollments_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enrollments`
--

LOCK TABLES `enrollments` WRITE;
/*!40000 ALTER TABLE `enrollments` DISABLE KEYS */;
/*!40000 ALTER TABLE `enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `graded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `trainee_id` (`trainee_id`),
  KEY `course_code` (`course_code`),
  KEY `idx_grades_trainee_course` (`trainee_id`,`course_code`),
  KEY `idx_grade_type` (`grade_type`),
  KEY `idx_graded_date` (`graded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grades`
--

LOCK TABLES `grades` WRITE;
/*!40000 ALTER TABLE `grades` DISABLE KEYS */;
/*!40000 ALTER TABLE `grades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hour_credits`
--

DROP TABLE IF EXISTS `hour_credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hour_credits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainee_id` varchar(20) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `credit_date` date NOT NULL,
  `hours` int(11) NOT NULL,
  `source` enum('schedule','manual') NOT NULL DEFAULT 'schedule',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_credit` (`trainee_id`,`course_code`,`credit_date`),
  KEY `idx_credits_user_course_date` (`trainee_id`,`course_code`,`credit_date`),
  KEY `fk_hour_credits_course` (`course_code`),
  CONSTRAINT `fk_hour_credits_course` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hour_credits_user` FOREIGN KEY (`trainee_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hour_credits`
--

LOCK TABLES `hour_credits` WRITE;
/*!40000 ALTER TABLE `hour_credits` DISABLE KEYS */;
/*!40000 ALTER TABLE `hour_credits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_otps`
--

DROP TABLE IF EXISTS `password_reset_otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_otps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`),
  KEY `is_used` (`is_used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_otps`
--

LOCK TABLES `password_reset_otps` WRITE;
/*!40000 ALTER TABLE `password_reset_otps` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_otps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_analytics`
--

DROP TABLE IF EXISTS `quiz_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `correct_attempts` int(11) DEFAULT 0,
  `total_attempts` int(11) DEFAULT 0,
  `average_time` int(11) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_quiz_question` (`quiz_id`,`question_id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `question_id` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_analytics`
--

LOCK TABLES `quiz_analytics` WRITE;
/*!40000 ALTER TABLE `quiz_analytics` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_analytics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_attempts`
--

DROP TABLE IF EXISTS `quiz_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `trainee_id` varchar(50) NOT NULL,
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answers`)),
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` int(11) DEFAULT NULL,
  `attempt_number` int(11) DEFAULT 1,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_spent` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `trainee_id` (`trainee_id`),
  KEY `idx_attempts_trainee_quiz` (`trainee_id`,`quiz_id`),
  CONSTRAINT `fk_quiz_attempts_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_attempts`
--

LOCK TABLES `quiz_attempts` WRITE;
/*!40000 ALTER TABLE `quiz_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_categories`
--

DROP TABLE IF EXISTS `quiz_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_code` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_categories`
--

LOCK TABLES `quiz_categories` WRITE;
/*!40000 ALTER TABLE `quiz_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_feedback`
--

DROP TABLE IF EXISTS `quiz_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `feedback_text` text DEFAULT NULL,
  `feedback_type` enum('correct','incorrect','partial') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `attempt_id` (`attempt_id`),
  KEY `question_id` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_feedback`
--

LOCK TABLES `quiz_feedback` WRITE;
/*!40000 ALTER TABLE `quiz_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_questions`
--

DROP TABLE IF EXISTS `quiz_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `course_code` (`course_code`),
  KEY `question_type` (`question_type`),
  KEY `difficulty` (`difficulty`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `fk_quiz_questions_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_questions`
--

LOCK TABLES `quiz_questions` WRITE;
/*!40000 ALTER TABLE `quiz_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_settings`
--

DROP TABLE IF EXISTS `quiz_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `setting_key` (`setting_key`),
  CONSTRAINT `fk_quiz_settings_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_settings`
--

LOCK TABLES `quiz_settings` WRITE;
/*!40000 ALTER TABLE `quiz_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quizzes`
--

DROP TABLE IF EXISTS `quizzes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_code` (`course_code`),
  KEY `category_id` (`competency_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_quiz_status` (`status`),
  CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `quizzes_ibfk_2` FOREIGN KEY (`competency_id`) REFERENCES `competencies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quizzes`
--

LOCK TABLES `quizzes` WRITE;
/*!40000 ALTER TABLE `quizzes` DISABLE KEYS */;
/*!40000 ALTER TABLE `quizzes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `submissions`
--

DROP TABLE IF EXISTS `submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainee_id` varchar(20) NOT NULL,
  `material_id` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `teacher_remarks` text DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `graded_at` datetime DEFAULT NULL,
  `graded_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trainee_id` (`trainee_id`),
  KEY `material_id` (`material_id`),
  KEY `graded_by` (`graded_by`),
  CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`trainee_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `course_materials` (`id`),
  CONSTRAINT `submissions_ibfk_3` FOREIGN KEY (`graded_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `submissions`
--

LOCK TABLES `submissions` WRITE;
/*!40000 ALTER TABLE `submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_backups`
--

DROP TABLE IF EXISTS `system_backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_type` enum('full','database','files') NOT NULL,
  `backup_path` varchar(500) NOT NULL,
  `backup_size` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(50) DEFAULT NULL,
  `status` enum('success','failed','in_progress') DEFAULT 'in_progress',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_backups`
--

LOCK TABLES `system_backups` WRITE;
/*!40000 ALTER TABLE `system_backups` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_backups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `topic_activities`
--

DROP TABLE IF EXISTS `topic_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `topic_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `parent_activity_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `topic_activities_ibfk_1` (`topic_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `topic_activities_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `course_topics` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `topic_activities_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `topic_activities`
--

LOCK TABLES `topic_activities` WRITE;
/*!40000 ALTER TABLE `topic_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `topic_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `topic_materials`
--

DROP TABLE IF EXISTS `topic_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `topic_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) DEFAULT NULL,
  `material_title` varchar(255) DEFAULT NULL,
  `material_description` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `uploaded_by` varchar(50) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `topic_materials_ibfk_1` (`topic_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `topic_materials_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `course_topics` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `topic_materials_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `topic_materials`
--

LOCK TABLES `topic_materials` WRITE;
/*!40000 ALTER TABLE `topic_materials` DISABLE KEYS */;
/*!40000 ALTER TABLE `topic_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_verification`
--

DROP TABLE IF EXISTS `user_verification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_verification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `verification_type` enum('email','student_id','phone') NOT NULL,
  `verification_code` varchar(100) DEFAULT NULL,
  `verification_data` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_verification`
--

LOCK TABLES `user_verification` WRITE;
/*!40000 ALTER TABLE `user_verification` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_verification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_verification_history`
--

DROP TABLE IF EXISTS `user_verification_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_verification_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `verification_type` enum('email','student_id','phone') NOT NULL,
  `verification_method` varchar(100) DEFAULT NULL,
  `verified_by` varchar(50) DEFAULT NULL,
  `verified_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('verified','rejected','expired') NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `verification_type` (`verification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_verification_history`
--

LOCK TABLES `user_verification_history` WRITE;
/*!40000 ALTER TABLE `user_verification_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_verification_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `password_changed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_email_verified` (`email_verified`),
  KEY `idx_users_student_id` (`student_id`),
  KEY `idx_users_branch` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `user_id`, `role`, `branch_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `email`, `email_verified`, `student_id`, `phone_verified`, `contact_number`, `password`, `profile_picture`, `force_password_change`, `status`, `date_created`, `last_login`, `password_changed_at`) VALUES (1,'11201123','admin',NULL,'Admin',NULL,'User',NULL,'admin@bts.gov.ph',0,NULL,0,NULL,'$2a$12$vE/WxYtyT7agpUGbM1ToCeQTbyd486bxzi17ga1tZZok/6PbT1w2a',NULL,1,'active','2025-12-01 23:24:00','2025-12-03 20:28:02',NULL),(2,'21202060','trainer',NULL,'Arlene','','Bayani','','bayani.arlene@bts.gov.ph',0,NULL,0,'09123456789','$2y$10$79gZ9DfgkzxYCs/9dg9wPedqyg7rw8tCQsoR32WDb.D4t1Xu5LD6e','profile_21202060_1764730662.jpg',1,'active','2025-12-02 05:11:36','2025-12-03 06:58:45','2025-12-02 05:14:07'),(4,'31202237','trainee',NULL,'Arlene','','Bayani','','bayani.31202237@bts.gov.ph',0,NULL,0,'09639908976','$2y$10$5c3dMW5ryjiWwB5LcKR6DOqaSPCHJN6xWDaxQ2SO87MaQycm8SVJm',NULL,1,'active','2025-12-02 05:51:53','2025-12-02 08:21:12','2025-12-02 05:54:01'),(5,'412025524','guest',NULL,'Arlene',NULL,'Bayani',NULL,'arlenebayani04@gmail.com',0,NULL,0,NULL,'$2y$10$HyPbTSW1KrdhvhfXjjqPBufkaRN0gT/8yNYKR1SkPO4nWot7yNZPG','profile_412025524_1764732842.png',1,'active','2025-12-02 06:52:53','2025-12-03 05:25:49',NULL),(7,'21202115','trainer',NULL,'Arlene','','Bayani','','bayani.arlene1@bts.gov.ph',0,NULL,0,'09876543212','$2y$10$rFrOnmt/W7mazzQA/lg8V.Drpo3kTW4k18f1HFiT9Mt4GD4hvQacq',NULL,1,'archived','2025-12-02 08:22:14',NULL,NULL),(8,'31203312','trainee',NULL,'Juan','de la Cruz','Abad','','abad.31203312@bts.gov.ph',0,NULL,0,'09123456789','$2y$10$Ak1NrLl8rnJ17WNBZOYAiOdVXTWFzvkfBW2WApZkNdwWO2fIRo/My','profile_31203312_1764732329.webp',1,'active','2025-12-03 01:56:38','2025-12-03 07:23:51','2025-12-03 02:04:46');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `verification_tokens`
--

DROP TABLE IF EXISTS `verification_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `verification_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `token_type` enum('email','phone','student_id') NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `token` (`token`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verification_tokens`
--

LOCK TABLES `verification_tokens` WRITE;
/*!40000 ALTER TABLE `verification_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `verification_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'elms_bts'
--

--
-- Dumping routines for database 'elms_bts'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-04  5:43:53
